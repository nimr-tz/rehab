<?php

namespace App\Http\Controllers;

use App\Models\AbstractSubmission;
use App\Models\Certificate;
use App\Models\GroupMember;
use App\Models\OnsiteVisitor;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Certificate Controller
 *
 * Handles certificate generation, download, and verification for:
 * - Full Attendance Certificates (all 3 days)
 * - Participation Certificates (1-2 days)
 * - Oral Presentation Certificates
 * - Poster Presentation Certificates
 */
class CertificateController extends Controller
{
    private const NO_ATTENDANCE_MESSAGE = 'No conference attendance is recorded for this badge. Please contact the organizers.';

    private function conferenceDays(): int
    {
        return max(1, (int) config('conference.total_days', 3));
    }

    /**
     * Moment certificate downloads unlock (config: conference.certificates_release_at).
     */
    private function certificatesReleaseAt(): Carbon
    {
        return Carbon::parse(
            config('conference.certificates_release_at'),
            config('conference.timezone')
        );
    }

    private function downloadsReleased(): bool
    {
        return now()->gte($this->certificatesReleaseAt());
    }

    /**
     * Certificates are held back until the participant has shared conference feedback.
     */
    private function hasSubmittedFeedback(User $user): bool
    {
        return \App\Models\ConferenceFeedback::where('user_id', $user->id)->exists();
    }

    /**
     * Show certificate page for authenticated user
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's attendance days
        $attendanceDays = $this->attendanceDays($user);

        // Determine what certificates user is eligible for
        $eligibility = $this->getEligibility($user, $attendanceDays);

        // Get already issued certificates for this user
        $issuedCertificates = Certificate::where('user_id', $user->id)
            ->valid()
            ->with('abstract')
            ->get();

        return view('certificates.index', [
            'user' => $user,
            'attendanceDays' => $attendanceDays,
            'eligibility' => $eligibility,
            'issuedCertificates' => $issuedCertificates,
            'conferenceEnded' => $this->hasConferenceEnded(),
            'conference' => $this->getConferenceData(),
        ]);
    }

    /**
     * Download attendance certificate (full or partial)
     */
    public function downloadAttendance()
    {
        $user = Auth::user();

        if (! $this->downloadsReleased()) {
            return back()->with('error', 'Certificates unlock on '.$this->certificatesReleaseAt()->format('F j, Y \a\t H:i').'. Please check back then.');
        }

        if (! $this->hasSubmittedFeedback($user)) {
            return redirect()->route('feedback.create')->with('error', 'Please share your conference feedback first — your certificate unlocks right after.');
        }

        $attendanceDays = $this->attendanceDays($user);
        $type = $this->attendanceTypeFor($attendanceDays);

        // Check eligibility
        if (! $this->canGetAttendanceCertificate($user, $attendanceDays)) {
            return back()->with('error', 'You are not eligible for an attendance certificate. Please ensure you have attended the conference.');
        }

        // Find or create certificate
        $certificate = $this->getOrCreateCertificate($user, $type, null, $attendanceDays);

        if ($certificate->isRevoked()) {
            return back()->with('error', 'This certificate has been revoked. Please contact the conference organizers.');
        }

        // Generate PDF
        $data = $this->getCertificateData($user, $certificate);
        $template = $type === Certificate::TYPE_ATTENDANCE_FULL ? 'certificates.attendance-full' : 'certificates.attendance-partial';

        $pdf = Pdf::loadView($template, $data);
        $pdf->setPaper($this->getTemplatePaperSize());

        // Record download
        $certificate->recordDownload([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $filename = $this->generateFilename($user, $certificate);

        return $pdf->download($filename);
    }

    /**
     * Download presentation certificate
     */
    public function downloadPresentation(AbstractSubmission $abstract)
    {
        $user = Auth::user();

        if (! $this->downloadsReleased()) {
            return back()->with('error', 'Certificates unlock on '.$this->certificatesReleaseAt()->format('F j, Y \a\t H:i').'. Please check back then.');
        }

        if (! $this->hasSubmittedFeedback($user)) {
            return redirect()->route('feedback.create')->with('error', 'Please share your conference feedback first — your certificate unlocks right after.');
        }

        // Verify ownership
        if ($abstract->user_id !== $user->id) {
            abort(403, 'You cannot download a certificate for someone else\'s presentation.');
        }

        // Check eligibility
        $attendanceDays = $this->attendanceDays($user);
        if (! $this->canGetPresenterCertificate($user, $abstract, $attendanceDays)) {
            return back()->with('error', 'You are not eligible for a presenter certificate. Please ensure you have attended the conference and your abstract was accepted.');
        }

        // Determine type based on presentation type
        $type = strtolower($abstract->presentation_mode) === 'poster'
            ? Certificate::TYPE_POSTER_PRESENTATION
            : Certificate::TYPE_ORAL_PRESENTATION;

        // Find or create certificate
        $certificate = $this->getOrCreateCertificate($user, $type, $abstract, $attendanceDays);

        if ($certificate->isRevoked()) {
            return back()->with('error', 'This certificate has been revoked. Please contact the conference organizers.');
        }

        // Generate PDF
        $data = $this->getCertificateData($user, $certificate);
        $template = $type === Certificate::TYPE_POSTER_PRESENTATION ? 'certificates.poster' : 'certificates.oral';

        $pdf = Pdf::loadView($template, $data);
        $pdf->setPaper($this->getTemplatePaperSize());

        // Record download
        $certificate->recordDownload([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $filename = $this->generateFilename($user, $certificate);

        return $pdf->download($filename);
    }

    /**
     * Public self-service claim page for badge-only attendees
     * (group members, walk-in visitors).
     */
    public function claim()
    {
        return view('certificates.claim', [
            'conference' => $this->getConferenceData(),
            'released' => $this->downloadsReleased(),
            'releaseAt' => $this->certificatesReleaseAt(),
        ]);
    }

    /**
     * Look up a badge QR token and stream the participation certificate.
     */
    public function claimDownload(Request $request)
    {
        $request->validate(['code' => 'required|string|max:255']);

        // Accept a raw badge token or a scanned badge URL (https://.../badge/{token})
        $code = trim($request->input('code'));
        if (preg_match('~/(?:badge|connect)/([^/?#]+)~', $code, $matches)) {
            $code = rawurldecode($matches[1]);
        }
        $code = strtoupper($code);

        if (! $this->downloadsReleased()) {
            return back()->with('error', 'Certificates unlock on '.$this->certificatesReleaseAt()->format('F j, Y \a\t H:i').'. Please check back then.');
        }

        $member = GroupMember::where('qr_token', $code)->first()
            ?? OnsiteVisitor::where('qr_token', $code)->first();

        // Registered delegates can claim with their badge too.
        if (! $member) {
            if ($user = User::where('qr_code_token', $code)->first()) {
                return $this->claimForUser($user, $code);
            }

            return back()->with('error', 'We could not find a badge with that code. Please check the code and try again, or contact the organizers.');
        }

        return $this->deliverMemberCertificate($member, $code);
    }

    /**
     * Feedback-gate and stream the certificate for a badge-only attendee.
     */
    private function deliverMemberCertificate(GroupMember|OnsiteVisitor $member, string $code)
    {
        // Feedback first, for everyone — it shapes the next conference.
        if (! \App\Models\ConferenceFeedback::where('badge_code', $code)->exists()) {
            return redirect()->route('feedback.create', ['badge' => $code])
                ->with('error', 'Please share your conference feedback first — your certificate downloads right after.');
        }

        $attendanceDays = $this->attendanceDays($member);
        if (empty($attendanceDays)) {
            return back()->with('error', self::NO_ATTENDANCE_MESSAGE);
        }

        $holderName = $member->full_name ?? $member->effective_name;
        $type = $this->attendanceTypeFor($attendanceDays);

        $memberColumn = match (true) {
            $member instanceof GroupMember => 'group_member_id',
            default => 'onsite_visitor_id',
        };

        $certificate = Certificate::where($memberColumn, $member->id)
            ->where('type', $type)
            ->first();

        if (! $certificate) {
            $certificate = Certificate::create([
                $memberColumn => $member->id,
                'holder_name' => $holderName,
                'type' => $type,
                'certificate_number' => Certificate::generateCertificateNumber($type),
                'attendance_days' => $attendanceDays,
                'issued_at' => Certificate::officialIssueDate(),
            ]);
        }

        if ($certificate->isRevoked()) {
            return back()->with('error', 'This certificate has been revoked. Please contact the conference organizers.');
        }

        $holder = (object) ['title' => null, 'full_name' => $holderName];
        $data = $this->getCertificateData($holder, $certificate);
        $template = $type === Certificate::TYPE_ATTENDANCE_FULL ? 'certificates.attendance-full' : 'certificates.attendance-partial';

        $pdf = Pdf::loadView($template, $data);
        $pdf->setPaper($this->getTemplatePaperSize());

        $certificate->recordDownload([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $pdf->download($this->generateFilename($holder, $certificate));
    }

    /**
     * Badge claim for a registered delegate: reuses their account
     * certificate record so the badge and login paths stay in sync.
     */
    private function claimForUser(User $user, string $code)
    {
        // Feedback is required on the badge path too — accept feedback tied
        // to either their account or their badge code.
        $hasFeedback = \App\Models\ConferenceFeedback::where('user_id', $user->id)
            ->orWhere('badge_code', $code)
            ->exists();

        if (! $hasFeedback) {
            return redirect()->route('feedback.create', ['badge' => $code])
                ->with('error', 'Please share your conference feedback first — your certificate downloads right after.');
        }

        $attendanceDays = $this->attendanceDays($user);
        if (empty($attendanceDays)) {
            return back()->with('error', self::NO_ATTENDANCE_MESSAGE);
        }

        $type = $this->attendanceTypeFor($attendanceDays);

        $certificate = $this->getOrCreateCertificate($user, $type, null, $attendanceDays);

        if ($certificate->isRevoked()) {
            return back()->with('error', 'This certificate has been revoked. Please contact the conference organizers.');
        }

        $data = $this->getCertificateData($user, $certificate);
        $template = $type === Certificate::TYPE_ATTENDANCE_FULL ? 'certificates.attendance-full' : 'certificates.attendance-partial';

        $pdf = Pdf::loadView($template, $data);
        $pdf->setPaper($this->getTemplatePaperSize());

        $certificate->recordDownload([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $pdf->download($this->generateFilename($user, $certificate));
    }

    /**
     * PDF paper size in points, matching the certificate template artwork
     */
    private function getTemplatePaperSize(): array
    {
        $template = config('print_design.certificate.template');
        $mmToPt = 72 / 25.4;

        return [
            0,
            0,
            ($template['width_mm'] ?? 297) * $mmToPt,
            ($template['height_mm'] ?? 210) * $mmToPt,
        ];
    }

    /**
     * Preview certificate (for admins or certificate owners)
     */
    public function preview(Request $request, ?Certificate $certificate = null)
    {
        $user = Auth::user();

        // Non-admins may only preview once downloads are released and they have submitted feedback
        if (! $user->hasRole('admin') && (! $this->downloadsReleased() || ! $this->hasSubmittedFeedback($user))) {
            abort(403, 'Certificate previews are not yet available.');
        }

        $abstractId = $request->query('abstract_id');

        // Case 1: Preview a specific issued certificate
        if ($certificate) {
            if (! $user->hasRole('admin') && $certificate->user_id !== $user->id) {
                abort(403);
            }
            $user = $certificate->user;
        }
        // Case 2: Preview a presentation certificate for a specific abstract
        elseif ($abstractId) {
            $abstract = AbstractSubmission::findOrFail($abstractId);
            if ($abstract->user_id !== $user->id && ! $user->hasRole('admin')) {
                abort(403);
            }

            $attendanceDays = $this->attendanceDays($user);
            $type = strtolower($abstract->presentation_mode) === 'poster'
                ? Certificate::TYPE_POSTER_PRESENTATION
                : Certificate::TYPE_ORAL_PRESENTATION;

            $certificate = new Certificate([
                'user_id' => $user->id,
                'type' => $type,
                'certificate_number' => Certificate::generateCertificateNumber($type),
                'abstract_submission_id' => $abstract->id,
                'attendance_days' => $attendanceDays,
                'issued_at' => Certificate::officialIssueDate(),
            ]);
            $certificate->setRelation('abstract', $abstract);
        }
        // Case 3: Preview the general attendance certificate
        else {
            $attendanceDays = $this->attendanceDays($user);
            $type = count($attendanceDays) >= $this->conferenceDays()
                ? Certificate::TYPE_ATTENDANCE_FULL
                : Certificate::TYPE_ATTENDANCE_PARTIAL;

            $certificate = new Certificate([
                'user_id' => $user->id,
                'type' => $type,
                'certificate_number' => Certificate::generateCertificateNumber($type),
                'attendance_days' => $attendanceDays,
                'issued_at' => Certificate::officialIssueDate(),
            ]);
        }

        $data = $this->getCertificateData($user, $certificate);

        // Determine template
        $template = match ($certificate->type) {
            Certificate::TYPE_ATTENDANCE_FULL => 'certificates.attendance-full',
            Certificate::TYPE_ATTENDANCE_PARTIAL => 'certificates.attendance-partial',
            Certificate::TYPE_ORAL_PRESENTATION => 'certificates.oral',
            Certificate::TYPE_POSTER_PRESENTATION => 'certificates.poster',
            default => 'certificates.attendance-full',
        };

        return view($template, $data);
    }

    /**
     * Public verification page (from QR code scan)
     */
    public function verifyPage(string $code)
    {
        $certificate = Certificate::findByCode($code);

        if (! $certificate) {
            return view('certificates.verify', [
                'valid' => false,
                'certificate' => null,
                'message' => 'Certificate not found. The verification code may be invalid.',
            ]);
        }

        // Record verification
        $certificate->recordVerification([
            'ip' => request()->ip(),
        ]);

        return view('certificates.verify', [
            'valid' => ! $certificate->isRevoked(),
            'certificate' => $certificate,
            'user' => $certificate->holder,
            'conference' => $this->getConferenceData(),
            'isRevoked' => $certificate->isRevoked(),
            'revokedReason' => $certificate->revoked_reason,
        ]);
    }

    /**
     * API verification endpoint
     */
    public function verify(string $code)
    {
        $certificate = Certificate::findByCode($code);

        if (! $certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found.',
            ], 404);
        }

        // Record verification
        $certificate->recordVerification(['ip' => request()->ip()]);

        if ($certificate->isRevoked()) {
            return response()->json([
                'success' => false,
                'message' => 'This certificate has been revoked.',
                'revoked_at' => $certificate->revoked_at->toIso8601String(),
                'revoked_reason' => $certificate->revoked_reason,
            ], 410);
        }

        $holder = $certificate->holder;

        return response()->json([
            'success' => true,
            'certificate' => [
                'valid' => true,
                'type' => $certificate->type_label,
                'certificate_number' => $certificate->certificate_number,
                'holder_name' => $holder->full_name,
                'holder_title' => $holder->title,
                'affiliation' => $holder->affiliation,
                'issued_at' => $certificate->issued_at->format('F j, Y'),
                'conference' => config('conference.name'),
                'edition' => config('conference.edition'),
                'year' => config('conference.year'),
                'dates' => $certificate->isFullAttendance()
                    ? config('conference.display_dates')
                    : $certificate->attendance_dates,
            ],
            'message' => 'Certificate verified successfully.',
        ]);
    }

    /**
     * Conference days (1..N) on which the holder's badge was scanned in.
     *
     * @return list<int>
     */
    private function attendanceDays(User|GroupMember|OnsiteVisitor $holder): array
    {
        $days = $holder->attendances()
            ->whereBetween('day', [1, $this->conferenceDays()])
            ->distinct()
            ->orderBy('day')
            ->pluck('day')
            ->map(fn ($day) => (int) $day)
            ->all();

        return array_values($days);
    }

    private function attendanceTypeFor(array $attendanceDays): string
    {
        return count($attendanceDays) >= $this->conferenceDays()
            ? Certificate::TYPE_ATTENDANCE_FULL
            : Certificate::TYPE_ATTENDANCE_PARTIAL;
    }

    /**
     * Get eligibility status for all certificate types
     */
    private function getEligibility(User $user, array $attendanceDays): array
    {
        $conferenceEnded = $this->hasConferenceEnded();
        $hasAttendance = ! empty($attendanceDays);
        $isFullAttendance = count($attendanceDays) >= $this->conferenceDays();

        // Only abstracts that are accepted AND placed in the programme (session_id set)
        // qualify for a presenter certificate.
        $acceptedAbstracts = AbstractSubmission::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->whereNotNull('session_id')
            ->get();

        $oralAbstracts = $acceptedAbstracts->filter(fn ($a) => strtolower($a->presentation_mode) === 'oral');
        $posterAbstracts = $acceptedAbstracts->filter(fn ($a) => strtolower($a->presentation_mode) === 'poster');

        $released = $this->downloadsReleased();
        $feedbackSubmitted = $this->hasSubmittedFeedback($user);

        // Logic refinement:
        // 1. MUST have attendance records to download anything (strictly enforced tiers)
        // 2. Downloads unlock at the release time AND after feedback is submitted
        // 3. The conference must have ended
        $canDownload = $released && $feedbackSubmitted && $hasAttendance && $conferenceEnded;

        return [
            'can_download' => $canDownload,
            'downloads_enabled' => $released,
            'released' => $released,
            'release_at' => $this->certificatesReleaseAt()->toIso8601String(),
            'feedback_submitted' => $feedbackSubmitted,
            'conference_ended' => $conferenceEnded,
            'has_attendance' => $hasAttendance,
            'attendance' => [
                'eligible' => $canDownload,
                'type' => $isFullAttendance ? 'full' : 'partial',
                'days_attended' => count($attendanceDays),
                'days_required' => $this->conferenceDays(),
            ],
            'oral_presentations' => $oralAbstracts->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'session' => $a->session?->name ?? 'TBA',
                'eligible' => $released && $feedbackSubmitted && $hasAttendance && $conferenceEnded,
            ])->values()->all(),
            'poster_presentations' => $posterAbstracts->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'poster_id' => $a->poster_id ?? 'P-'.str_pad($a->id, 3, '0', STR_PAD_LEFT),
                'eligible' => $released && $feedbackSubmitted && $hasAttendance && $conferenceEnded,
            ])->values()->all(),
        ];
    }

    /**
     * Check if user can get attendance certificate
     */
    private function canGetAttendanceCertificate(User $user, array $attendanceDays): bool
    {
        return $this->hasConferenceEnded() && ! empty($attendanceDays);
    }

    /**
     * Check if user can get presenter certificate
     */
    private function canGetPresenterCertificate(User $user, AbstractSubmission $abstract, array $attendanceDays): bool
    {
        return $this->hasConferenceEnded()
            && ! empty($attendanceDays)
            && $abstract->status === 'accepted'
            && $abstract->session_id !== null;
    }

    /**
     * Get or create a certificate record
     */
    private function getOrCreateCertificate(User $user, string $type, ?AbstractSubmission $abstract, array $attendanceDays): Certificate
    {
        // Check if certificate already exists
        $query = Certificate::where('user_id', $user->id)
            ->where('type', $type);

        if ($abstract) {
            $query->where('abstract_submission_id', $abstract->id);
        } else {
            $query->whereNull('abstract_submission_id');
        }

        $certificate = $query->first();

        if ($certificate) {
            return $certificate;
        }

        // Create new certificate
        return Certificate::create([
            'user_id' => $user->id,
            'type' => $type,
            'certificate_number' => Certificate::generateCertificateNumber($type),
            'abstract_submission_id' => $abstract?->id,
            'attendance_days' => $attendanceDays,
            'issued_at' => Certificate::officialIssueDate(),
        ]);
    }

    /**
     * Get certificate data for PDF rendering.
     * $user is any object exposing title/full_name (a User or a holder shim).
     */
    private function getCertificateData(object $user, Certificate $certificate): array
    {
        $conference = $this->getConferenceData();

        // Generate QR code for verification
        $verifyUrl = route('certificate.verify', $certificate->certificate_number);
        $qrImage = $this->generateQrCodeBase64($verifyUrl);

        $data = [
            'user' => $user,
            'certificate' => $certificate,
            'conference' => $conference,
            'certificateNumber' => $certificate->certificate_number,
            'issueDate' => ($certificate->issued_at ?? Certificate::officialIssueDate())->format('F j, Y'),
            'qrImage' => $qrImage,
            'verifyUrl' => $verifyUrl,
        ];

        // Add abstract data for presenter certificates
        if ($certificate->isPresenterCertificate() && $certificate->abstract) {
            $data['abstract'] = $certificate->abstract;
        }

        return $data;
    }

    /**
     * Get conference configuration data
     */
    private function getConferenceData(): array
    {
        return [
            'name' => config('conference.name'),
            'short_name' => config('conference.short_name'),
            'edition' => config('conference.edition'),
            'year' => config('conference.year'),
            'display_dates' => config('conference.display_dates'),
            'venue' => config('conference.venue'),
            'city' => config('conference.city'),
            'country' => config('conference.country'),
            'host' => config('conference.host'),
            'host_short' => config('conference.host_short'),
        ];
    }

    /**
     * Generate QR code as base64 data URI
     */
    protected function generateQrCodeBase64(string $data): string
    {
        $options = new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::H,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
            'scale' => 8,
        ]);

        $qrcode = new \chillerlan\QRCode\QRCode($options);

        return $qrcode->render($data);
    }

    /**
     * Check if conference has ended
     */
    private function hasConferenceEnded(): bool
    {
        $endDate = Carbon::parse(config('conference.end_date'), config('conference.timezone'));

        return now()->isAfter($endDate->endOfDay());
    }

    /**
     * Generate filename for certificate download
     */
    private function generateFilename(object $user, Certificate $certificate): string
    {
        $name = str_replace(' ', '_', $user->full_name);
        $typeShort = match ($certificate->type) {
            Certificate::TYPE_ATTENDANCE_FULL => 'Attendance',
            Certificate::TYPE_ATTENDANCE_PARTIAL => 'Participation',
            Certificate::TYPE_ORAL_PRESENTATION => 'Oral_Presentation',
            Certificate::TYPE_POSTER_PRESENTATION => 'Poster_Presentation',
            default => 'Certificate',
        };
        return "Certificate_{$typeShort}_{$name}_" . config('conference.file_prefix') . '.pdf';
    }
}
