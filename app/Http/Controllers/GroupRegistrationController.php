<?php

namespace App\Http\Controllers;

use App\Models\GroupRegistration;
use App\Models\GroupMember;
use App\Models\InvitationLetter;
use App\Services\EmailNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GroupRegistrationController extends Controller
{
    protected $emailService;
    protected $notificationService;

    public function __construct(
        EmailNotificationService $emailService,
        NotificationService $notificationService
    ) {
        $this->emailService = $emailService;
        $this->notificationService = $notificationService;
    }

    /**
     * Show list of user's group registrations
     */
    public function index()
    {
        $user = Auth::user();
        $groupRegistrations = GroupRegistration::where('leader_user_id', $user->id)
            ->with('members')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('group-registration.index', compact('groupRegistrations'));
    }

    /**
     * Show form to create a new group registration
     */
    public function create()
    {
        $user = Auth::user();
        $fees = GroupRegistration::getFeeStructure();
        return view('group-registration.create', compact('fees', 'user'));
    }

    /**
     * Store a new group registration with members
     */
    public function store(Request $request)
    {
        $request->validate([
            'group_name' => 'nullable|string|max:255',
            'organization' => 'required|string|max:255',
            'members' => 'required|array|min:3',
            'members.*.full_name' => 'required|string|max:255',
            'members.*.email' => 'required|email|max:255',
            'members.*.phone' => 'nullable|string|max:50',
            'members.*.institution' => 'required|string|max:255',
            'members.*.country' => 'required|string|max:100',
            'members.*.registration_category' => 'required|in:professional_local,professional_international,student_local,student_international',
            'members.*.student_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'members.min' => 'Group registration requires at least 3 members.',
            'members.*.full_name.required' => 'Each member must have a full name.',
            'members.*.registration_category.required' => 'Each member must have a registration category.',
        ]);

        $user = Auth::user();
        $fees = GroupRegistration::getFeeStructure();
        $members = $request->all()['members'] ?? [];

        if (count($members) < 3) {
            return back()
                ->withInput()
                ->withErrors([
                    'members' => 'Group registration requires at least 3 members.',
                ]);
        }

        foreach ($members as $index => $memberData) {
            $isStudent = in_array($memberData['registration_category'], ['student_local', 'student_international'], true);

            if ($isStudent && !isset($memberData['student_document'])) {
                return back()
                    ->withInput()
                    ->withErrors(["members.{$index}.student_document" => 'Student group members must upload a Student ID.']);
            }
        }

        DB::beginTransaction();
        try {
            // Create group registration
            $group = GroupRegistration::create([
                'leader_user_id' => $user->id,
                'group_name' => $request->group_name,
                'organization' => $request->organization,
                'payment_status' => 'pending',
            ]);

            // Add members
            foreach ($members as $memberData) {
                $fee = $fees[$memberData['registration_category']];

                GroupMember::create([
                    'group_registration_id' => $group->id,
                    'full_name' => $memberData['full_name'],
                    'email' => $memberData['email'] ?? null,
                    'phone' => $memberData['phone'] ?? null,
                    'institution' => $memberData['institution'] ?? null,
                    'country' => $memberData['country'] ?? null,
                    'registration_category' => $memberData['registration_category'],
                    'student_id_path' => isset($memberData['student_document']) ? $memberData['student_document']->store('student_documents', 'public') : null,
                    'fee_amount' => $fee['amount'],
                    'fee_currency' => $fee['currency'],
                ]);
            }

            // Calculate and update total
            $group->recalculateTotal();

            DB::commit();

            return redirect()
                ->route('group-registration.show', $group)
                ->with('success', 'Group registration created successfully! Please proceed with payment.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create group registration. Please try again.');
        }
    }

    /**
     * Show a specific group registration
     */
    public function show(GroupRegistration $groupRegistration)
    {
        // Ensure user owns this group
        if ($groupRegistration->leader_user_id !== Auth::id()) {
            abort(403, 'You do not have access to this group registration.');
        }

        $groupRegistration->load('members');

        return view('group-registration.show', compact('groupRegistration'));
    }

    /**
     * Show form to edit group registration (only if pending)
     */
    public function edit(GroupRegistration $groupRegistration)
    {
        if ($groupRegistration->leader_user_id !== Auth::id()) {
            abort(403);
        }

        if ($groupRegistration->payment_status !== 'pending') {
            return redirect()
                ->route('group-registration.show', $groupRegistration)
                ->with('error', 'Cannot edit group after payment has been submitted.');
        }

        $groupRegistration->load('members');
        $fees = GroupRegistration::getFeeStructure();

        return view('group-registration.edit', compact('groupRegistration', 'fees'));
    }

    /**
     * Update group registration
     */
    public function update(Request $request, GroupRegistration $groupRegistration)
    {
        if ($groupRegistration->leader_user_id !== Auth::id()) {
            abort(403);
        }

        if ($groupRegistration->payment_status !== 'pending') {
            return redirect()
                ->route('group-registration.show', $groupRegistration)
                ->with('error', 'Cannot edit group after payment has been submitted.');
        }

        $request->validate([
            'group_name' => 'nullable|string|max:255',
            'organization' => 'required|string|max:255',
            'members' => 'required|array|min:3',
            'members.*.full_name' => 'required|string|max:255',
            'members.*.email' => 'required|email|max:255',
            'members.*.phone' => 'nullable|string|max:50',
            'members.*.institution' => 'required|string|max:255',
            'members.*.country' => 'required|string|max:100',
            'members.*.registration_category' => 'required|in:professional_local,professional_international,student_local,student_international',
            'members.*.student_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = Auth::user();
        $fees = GroupRegistration::getFeeStructure();
        $members = $request->all()['members'] ?? [];

        if (count($members) < 3) {
            return back()
                ->withInput()
                ->withErrors([
                    'members' => 'Group registration requires at least 3 members.',
                ]);
        }

        foreach ($members as $index => $memberData) {
            $isStudent = in_array($memberData['registration_category'], ['student_local', 'student_international'], true);
            $hasNewDocument = isset($memberData['student_document']);
            $hasExistingDocument = !empty($memberData['existing_student_document'] ?? null);

            if ($isStudent && !$hasNewDocument && !$hasExistingDocument) {
                return back()
                    ->withInput()
                    ->withErrors(["members.{$index}.student_document" => 'Student group members must upload a Student ID.']);
            }
        }

        DB::beginTransaction();
        try {
            $groupRegistration->update([
                'group_name' => $request->group_name,
                'organization' => $request->organization,
            ]);

            // Delete existing members and recreate
            $groupRegistration->members()->delete();

            foreach ($members as $memberData) {
                $fee = $fees[$memberData['registration_category']];

                GroupMember::create([
                    'group_registration_id' => $groupRegistration->id,
                    'full_name' => $memberData['full_name'],
                    'email' => $memberData['email'] ?? null,
                    'phone' => $memberData['phone'] ?? null,
                    'institution' => $memberData['institution'] ?? null,
                    'country' => $memberData['country'] ?? null,
                    'registration_category' => $memberData['registration_category'],
                    'student_id_path' => isset($memberData['student_document']) ? $memberData['student_document']->store('student_documents', 'public') : (isset($memberData['existing_student_document']) ? $memberData['existing_student_document'] : null),
                    'fee_amount' => $fee['amount'],
                    'fee_currency' => $fee['currency'],
                ]);
            }

            $groupRegistration->recalculateTotal();

            DB::commit();

            return redirect()
                ->route('group-registration.show', $groupRegistration)
                ->with('success', 'Group registration updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update group registration.');
        }
    }

    /**
     * Add a member to existing group (if pending)
     */
    public function addMember(Request $request, GroupRegistration $groupRegistration)
    {
        if ($groupRegistration->leader_user_id !== Auth::id()) {
            abort(403);
        }

        if ($groupRegistration->payment_status !== 'pending') {
            return response()->json(['error' => 'Cannot modify group after payment submitted'], 403);
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'institution' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'registration_category' => 'required|in:professional_local,professional_international,student_local,student_international',
            'student_document' => 'nullable|required_if:registration_category,student_local,student_international|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'student_document.required_if' => 'Student group members must upload a Student ID.',
        ]);

        $fees = GroupRegistration::getFeeStructure();
        $fee = $fees[$request->registration_category];

        $member = GroupMember::create([
            'group_registration_id' => $groupRegistration->id,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'institution' => $request->institution,
            'country' => $request->country,
            'registration_category' => $request->registration_category,
            'student_id_path' => $request->hasFile('student_document') ? $request->file('student_document')->store('student_documents', 'public') : null,
            'fee_amount' => $fee['amount'],
            'fee_currency' => $fee['currency'],
        ]);

        $groupRegistration->recalculateTotal();

        return response()->json([
            'success' => true,
            'member' => $member,
            'new_total' => $groupRegistration->fresh()->formatted_total,
        ]);
    }

    /**
     * Remove a member from group (if pending and more than 3 members)
     */
    /**
     * Remove a member from group (if pending and more than 3 members)
     */
    public function removeMember(GroupRegistration $groupRegistration, GroupMember $member)
    {
        if ($groupRegistration->leader_user_id !== Auth::id()) {
            abort(403);
        }

        if ($groupRegistration->payment_status !== 'pending') {
            return response()->json(['error' => 'Cannot modify group after payment submitted'], 403);
        }

        if ($member->group_registration_id !== $groupRegistration->id) {
            abort(404);
        }

        if ($groupRegistration->members()->count() <= 3) {
            return response()->json(['error' => 'Group must have at least 3 members'], 400);
        }

        $member->delete();
        $groupRegistration->recalculateTotal();

        return response()->json([
            'success' => true,
            'new_total' => $groupRegistration->fresh()->formatted_total,
            'member_count' => $groupRegistration->members()->count(),
        ]);
    }

    /**
     * Update member info (Post-payment allowable edits)
     */
    public function updateMember(Request $request, GroupRegistration $groupRegistration, GroupMember $member)
    {
        if ($groupRegistration->leader_user_id !== Auth::id()) {
            abort(403);
        }

        if ($member->group_registration_id !== $groupRegistration->id) {
            abort(404);
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'institution' => 'required|string|max:255',
        ]);

        $member->update([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'institution' => $request->institution,
        ]);

        // If this member is already linked to a user account, try to update it lightly?
        // Or just leave it as their 'ticket' name. Let's stick to the ticket name.

        return response()->json([
            'success' => true,
            'message' => 'Member details updated successfully.',
            'member' => $member
        ]);
    }

    /**
     * Print a badge for a group member (Leader view)
     */
    public function printMemberBadge(GroupRegistration $groupRegistration, GroupMember $member)
    {
        if ($groupRegistration->leader_user_id !== Auth::id()) {
            abort(403);
        }

        if ($member->group_registration_id !== $groupRegistration->id) {
            abort(404);
        }

        if (!$groupRegistration->isVerified()) {
            return back()->with('error', 'Badges are only available for verified registrations.');
        }

        // Mark as printed
        $member->markBadgePrinted();

        // Generate QR Image
        $qrImage = $this->generateQrCodeBase64($this->qrPayloadForToken($member->qr_token));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('registration.badge.member-print', [
            'member' => $member,
            'qrImage' => $qrImage,
            'conference' => [
                'name' => config('conference.name'),
                'short_name' => config('conference.short_name'),
                'edition' => config('conference.edition'),
                'year' => config('conference.year'),
                'display_dates' => config('conference.display_dates'),
            ],
        ]);

        $pdf->getDomPDF()->setPaper($this->badgePaperSize());

        return $pdf->download('badge-' . $member->id . '.pdf');
    }

    /**
     * Bulk print badges for all group members
     */
    public function bulkPrintBadges(GroupRegistration $groupRegistration)
    {
        if ($groupRegistration->leader_user_id !== Auth::id()) {
            abort(403);
        }

        if (!$groupRegistration->isVerified()) {
            return back()->with('error', 'Badges are only available for verified registrations.');
        }

        $attendeeData = [];
        foreach ($groupRegistration->members as $member) {
            $member->markBadgePrinted();
            $qrImage = $this->generateQrCodeBase64($this->qrPayloadForToken($member->qr_token));

            $attendeeData[] = [
                'member' => $member,
                'qrImage' => $qrImage
            ];
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('registration.badge.bulk-member-print', [
            'attendees' => $attendeeData,
            'conference' => [
                'name' => config('conference.name'),
                'short_name' => config('conference.short_name'),
                'edition' => config('conference.edition'),
                'year' => config('conference.year'),
                'display_dates' => config('conference.display_dates'),
            ],
        ]);

        $pdf->getDomPDF()->setPaper($this->badgePaperSize());

        return $pdf->download('group-badges-' . $groupRegistration->id . '.pdf');
    }

    /**
     * Generate QR code as base64 data URI (Helper)
     */
    protected function generateQrCodeBase64(string $data): string
    {
        $options = new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::H,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'scale' => 10,
        ]);

        $qrcode = new \chillerlan\QRCode\QRCode($options);

        $path = public_path('qrcodes');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $filename = 'qr_' . md5($data) . '.png';
        $fullPath = $path . DIRECTORY_SEPARATOR . $filename;
        $qrcode->render($data, $fullPath);

        return $fullPath;
    }

    protected function badgePaperSize(): array
    {
        $template = config('print_design.badge.template', []);
        $widthMm = (float) ($template['print_width_mm'] ?? 80.88);
        $heightMm = (float) ($template['print_height_mm'] ?? 137.4);

        return [
            0,
            0,
            round($widthMm * 72 / 25.4, 2),
            round($heightMm * 72 / 25.4, 2),
        ];
    }

    protected function qrPayloadForToken(string $token): string
    {
        return route('badge.public', $token);
    }

    /**
     * Download an invitation letter PDF for a group member.
     */
    public function downloadMemberInvitation(GroupRegistration $groupRegistration, GroupMember $member)
    {
        if ($groupRegistration->leader_user_id !== Auth::id()) {
            abort(403);
        }

        if ((int) $member->group_registration_id !== (int) $groupRegistration->id) {
            abort(404);
        }

        $letter = new InvitationLetter([
            'passport_name' => $member->full_name,
            'institute' => $member->institution,
            'title' => null,
            'status' => 'approved',
        ]);

        $user = (object) [
            'full_name' => $member->full_name,
            'institute' => $member->institution,
            'affiliation' => $member->institution,
        ];

        $filename = implode('-', array_filter([
            config('conference.file_prefix'),
            'invitation-letter',
            (string) str($member->full_name)->slug(),
        ])) . '.pdf';

        return Pdf::loadView('pdf.invitation-letter', [
            'letter' => $letter,
            'abstract' => null,
            'user' => $user,
        ])->setPaper('a4')->download($filename);
    }

    /**
     * Delete group registration (only if pending)
     */
    public function destroy(GroupRegistration $groupRegistration)
    {
        if ($groupRegistration->leader_user_id !== Auth::id()) {
            abort(403);
        }

        if ($groupRegistration->payment_status !== 'pending') {
            return redirect()
                ->route('group-registration.index')
                ->with('error', 'Cannot delete group after payment has been submitted.');
        }

        $groupRegistration->delete();

        return redirect()
            ->route('group-registration.index')
            ->with('success', 'Group registration deleted.');
    }

}
