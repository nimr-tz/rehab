<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard with comprehensive statistics
     */
    public function index(Request $request)
    {
        $stats = $this->getDashboardStats();

        return view('admin.dashboard', array_merge($stats, ['stats' => $stats]));
    }

    /**
     * Get comprehensive dashboard statistics
     */
    private function getDashboardStats(string $statsSort = 'default')
    {
        $registeredUsers = User::with([
                'groupMember.groupRegistration',
                'groupRegistrations',
                'abstractSubmissions',
            ])
            ->whereNotNull('registration_category')
            ->get();

        $groupMembers = GroupMember::with('groupRegistration')->get();

        $allAbstracts = AbstractSubmission::with([
                'user.groupMember.groupRegistration',
                'user.groupRegistrations',
            ])
            ->where('status', '!=', 'draft')
            ->orderBy('conference_code')
            ->orderBy('title')
            ->get();

        $acceptedAbstracts = $allAbstracts->where('status', 'accepted')->values();
        $acceptedCount = $acceptedAbstracts->count();
        $presenterUsers = $allAbstracts
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        $paidPresenterUsers = $presenterUsers
            ->filter(fn(User $user) => $this->isConferencePaid($user))
            ->values();
        $unpaidPresenterUsers = $presenterUsers
            ->reject(fn(User $user) => $this->isConferencePaid($user))
            ->values();

        $paidPresenterIds = $paidPresenterUsers->pluck('id')->all();
        $paidAcceptedAbstracts = $acceptedAbstracts->filter(
            fn($abstract) => $abstract->user && in_array($abstract->user->id, $paidPresenterIds, true)
        );
        $unpaidAcceptedAbstracts = $acceptedAbstracts->filter(
            fn($abstract) => $abstract->user && ! in_array($abstract->user->id, $paidPresenterIds, true)
        );

        $totalPresentationsUploaded = $this->countUploadedPresentations($acceptedAbstracts);

        $paidWithPresentation = $this->countUploadedPresentations($paidAcceptedAbstracts);
        $paidMissingPresentation = $paidAcceptedAbstracts->count() - $paidWithPresentation;
        $unpaidWithPresentation = $this->countUploadedPresentations($unpaidAcceptedAbstracts);
        $unpaidMissingPresentation = $unpaidAcceptedAbstracts->count() - $unpaidWithPresentation;

        $assignedCount = $acceptedAbstracts->whereNotNull('conference_code')->count();
        $pendingCodeCount = $acceptedCount - $assignedCount;
        $scheduledCount = $acceptedAbstracts->whereNotNull('session_id')->count();
        $unscheduledCount = $acceptedCount - $scheduledCount;
        $orphanedAcceptedCount = $acceptedAbstracts->whereNull('user')->count();
        $oralCount = $this->countPresentationMode($acceptedAbstracts, 'oral');
        $posterCount = $this->countPresentationMode($acceptedAbstracts, 'poster');

        $registeredParticipantRecords = collect()
            ->merge($registeredUsers->map(fn(User $user) => $this->participantRecordFromUser($user)))
            ->merge($groupMembers->map(fn(GroupMember $member) => $this->participantRecordFromGroupMember($member)))
            ->unique('key')
            ->values();

        $abstractParticipantRecords = $allAbstracts
            ->map(fn($abstract) => $this->participantRecordFromAbstract($abstract))
            ->unique('key')
            ->values();

        $scopeSections = [
            [
                'title' => 'All Participants',
                'scope' => 'all',
                'note' => 'Individual registrations plus group members',
                'tone' => 'text-sky-500',
                'accent' => 'bg-sky-400',
                'stats' => $this->buildScopeStats($registeredParticipantRecords, $allAbstracts),
            ],
            [
                'title' => 'Participants With Abstracts',
                'scope' => 'abstracts',
                'note' => 'Distinct authors with submitted abstracts',
                'tone' => 'text-indigo-500',
                'accent' => 'bg-indigo-400',
                'stats' => $this->buildScopeStats($abstractParticipantRecords, $allAbstracts),
            ],
        ];

        return [
            'scopeSections' => $scopeSections,
            'allParticipantCount' => $registeredParticipantRecords->count(),
            'abstractParticipantCount' => $abstractParticipantRecords->count(),
            'acceptedCount' => $acceptedCount,
            'acceptedPresenterCount' => $presenterUsers->count(),
            'paidPresenterCount' => $paidPresenterUsers->count(),
            'unpaidPresenterCount' => $unpaidPresenterUsers->count(),
            'paidAcceptedAbstractCount' => $paidAcceptedAbstracts->count(),
            'unpaidAcceptedAbstractCount' => $unpaidAcceptedAbstracts->count(),
            'orphanedAcceptedCount' => $orphanedAcceptedCount,
            'assignedCount' => $assignedCount,
            'pendingCodeCount' => $pendingCodeCount,
            'scheduledCount' => $scheduledCount,
            'unscheduledCount' => $unscheduledCount,
            'oralCount' => $oralCount,
            'posterCount' => $posterCount,
            'totalPresentationsUploaded' => $totalPresentationsUploaded,
            'presentationsMissingCount' => $acceptedCount - $totalPresentationsUploaded,
            'paidWithPresentation' => $paidWithPresentation,
            'paidMissingPresentation' => $paidMissingPresentation,
            'unpaidWithPresentation' => $unpaidWithPresentation,
            'unpaidMissingPresentation' => $unpaidMissingPresentation,
            'todayAttendance' => \App\Models\Attendance::where('day', 1)->count(),
        ];
    }

    private function buildScopeStats($participants, $abstracts): array
    {
        $participants = collect($participants)->unique('key')->values();
        $participantKeys = $participants->pluck('key')->all();
        $abstracts = collect($abstracts)
            ->filter(fn($abstract) => in_array($this->participantRecordFromAbstract($abstract)['key'], $participantKeys, true))
            ->values();
        $accepted = $abstracts->where('status', 'accepted')->values();
        $notAccepted = $abstracts->reject(fn($abstract) => $abstract->status === 'accepted')->values();
        $uploaded = $this->countUploadedPresentations($accepted);
        $scheduled = $accepted->whereNotNull('session_id')->count();

        return [
            'participants' => $participants->count(),
            'paid' => $participants->where('paid', true)->count(),
            'unpaid' => $participants->where('paid', false)->count(),
            'with_abstracts' => $participants->where('has_abstracts', true)->count(),
            'paid_with_abstracts' => $participants
                ->filter(fn(array $participant) => $participant['paid'] && $participant['has_abstracts'])
                ->count(),
            'paid_without_abstracts' => $participants
                ->filter(fn(array $participant) => $participant['paid'] && ! $participant['has_abstracts'])
                ->count(),
            'unpaid_with_abstracts' => $participants
                ->filter(fn(array $participant) => ! $participant['paid'] && $participant['has_abstracts'])
                ->count(),
            'unpaid_without_abstracts' => $participants
                ->filter(fn(array $participant) => ! $participant['paid'] && ! $participant['has_abstracts'])
                ->count(),
            'without_abstracts' => $participants->where('has_abstracts', false)->count(),
            'abstracts' => $abstracts->count(),
            'accepted' => $accepted->count(),
            'not_accepted' => $notAccepted->count(),
            'uploaded' => $uploaded,
            'not_uploaded' => $accepted->count() - $uploaded,
            'scheduled' => $scheduled,
            'unscheduled' => $accepted->count() - $scheduled,
            'oral' => $this->countPresentationMode($accepted, 'oral'),
            'poster' => $this->countPresentationMode($accepted, 'poster'),
        ];
    }

    private function participantRecordFromUser(User $user): array
    {
        return [
            'key' => 'user:' . $user->id,
            'name' => $user->name ?? '',
            'email' => strtolower(trim((string) $user->email)),
            'registration_category' => $user->registration_category ?? '',
            'paid' => $this->isConferencePaid($user),
            'has_abstracts' => $user->abstractSubmissions->where('status', '!=', 'draft')->isNotEmpty(),
        ];
    }

    private function participantRecordFromGroupMember(GroupMember $member): array
    {
        $email = strtolower(trim((string) $member->email));

        return [
            'key' => $email !== '' ? 'email:' . $email : 'group-member:' . $member->id,
            'name' => $member->full_name ?? '',
            'email' => $email,
            'registration_category' => $member->registration_category ?? '',
            'paid' => in_array($member->groupRegistration?->payment_status, ['verified', 'waived'], true),
            'has_abstracts' => false,
        ];
    }

    private function participantRecordFromAbstract($abstract): array
    {
        if ($abstract->user) {
            return [
                'key' => 'user:' . $abstract->user->id,
                'name' => $abstract->user->name ?? '',
                'email' => strtolower(trim((string) $abstract->user->email)),
                'registration_category' => $abstract->user->registration_category ?? '',
                'paid' => $this->isConferencePaid($abstract->user),
                'has_abstracts' => true,
                ];
        }

        $email = strtolower(trim((string) $abstract->author_email));

        return [
            'key' => $this->presenterKey($abstract),
            'name' => $abstract->author_name ?? '',
            'email' => $email,
            'registration_category' => '',
            'paid' => false,
            'has_abstracts' => true,
        ];
    }

    private function isConferencePaid(User $user): bool
    {
        if (in_array($user->payment_status, ['verified', 'waived'], true)) {
            return true;
        }

        return $user->isPaid();
    }

    private function countUploadedPresentations($abstracts): int
    {
        return collect($abstracts)
            ->filter(fn($abstract) => $abstract->hasActualPresentationFiles())
            ->count();
    }

    private function countPresentationMode($abstracts, string $mode): int
    {
        return $abstracts
            ->filter(fn($abstract) => strtolower(trim((string) $abstract->presentation_mode)) === $mode)
            ->count();
    }

    private function presenterKey($abstract): string
    {
        if ($abstract->user?->id) {
            return 'user:' . $abstract->user->id;
        }

        $email = strtolower(trim((string) $abstract->author_email));
        if ($email !== '') {
            return 'email:' . $email;
        }

        return 'name:' . strtolower(trim((string) $abstract->author_name));
    }

    /**
     * Return a filtered list of participants for a given scope+filter combination.
     */
    public function participants(Request $request)
    {
        $scope = $request->query('scope', 'all');
        $filter = $request->query('filter', 'participants');

        [$participants, $abstracts] = $this->getScopeData($scope);

        $rows = $this->applyFilter($participants, $abstracts, $filter);

        return response()->json(['rows' => $rows->values()]);
    }

    /**
     * Export a filtered list as CSV download.
     */
    public function exportParticipants(Request $request)
    {
        $scope = $request->query('scope', 'all');
        $filter = $request->query('filter', 'participants');

        [$participants, $abstracts] = $this->getScopeData($scope);

        $rows = $this->applyFilter($participants, $abstracts, $filter)->values();

        $isAbstractFilter = in_array($filter, ['abstracts', 'accepted', 'not_accepted', 'uploaded', 'not_uploaded', 'scheduled', 'unscheduled', 'oral', 'poster'], true);

        $filename = 'participants_' . $scope . '_' . $filter . '_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rows, $isAbstractFilter) {
            $handle = fopen('php://output', 'w');

            if ($isAbstractFilter) {
                fputcsv($handle, ['#', 'Title', 'Author', 'Email', 'Institute', 'Mode', 'Status', 'Conference Code', 'Scheduled']);
                foreach ($rows as $i => $row) {
                    fputcsv($handle, [
                        $i + 1,
                        $row['title'] ?? '',
                        $row['author'] ?? '',
                        $row['email'] ?? '',
                        $row['institute'] ?? '',
                        $row['mode'] ?? '',
                        $row['status'] ?? '',
                        $row['conference_code'] ?? '',
                        $row['scheduled'] ? 'Yes' : 'No',
                    ]);
                }
            } else {
                fputcsv($handle, ['#', 'Name', 'Email', 'Registration Category', 'Paid', 'Has Abstracts']);
                foreach ($rows as $i => $row) {
                    fputcsv($handle, [
                        $i + 1,
                        $row['name'] ?? '',
                        $row['email'] ?? '',
                        $row['registration_category'] ?? '',
                        $row['paid'] ? 'Yes' : 'No',
                        $row['has_abstracts'] ? 'Yes' : 'No',
                    ]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getScopeData(string $scope): array
    {
        $registeredUsers = User::with([
                'groupMember.groupRegistration',
                'groupRegistrations',
                'abstractSubmissions',
            ])
            ->whereNotNull('registration_category')
            ->get();

        $groupMembers = GroupMember::with('groupRegistration')->get();

        $allAbstracts = AbstractSubmission::with(['user'])
            ->where('status', '!=', 'draft')
            ->orderBy('conference_code')
            ->orderBy('title')
            ->get();

        $registeredParticipantRecords = collect()
            ->merge($registeredUsers->map(fn(User $user) => $this->participantRecordFromUser($user)))
            ->merge($groupMembers->map(fn(GroupMember $member) => $this->participantRecordFromGroupMember($member)))
            ->unique('key')
            ->values();

        $abstractParticipantRecords = $allAbstracts
            ->map(fn($abstract) => $this->participantRecordFromAbstract($abstract))
            ->unique('key')
            ->values();

        return match ($scope) {
            'abstracts' => [$abstractParticipantRecords, $allAbstracts],
            default => [$registeredParticipantRecords, $allAbstracts],
        };
    }

    private function applyFilter($participants, $abstracts, string $filter): \Illuminate\Support\Collection
    {
        $isAbstractFilter = in_array($filter, ['abstracts', 'accepted', 'not_accepted', 'uploaded', 'not_uploaded', 'scheduled', 'unscheduled', 'oral', 'poster'], true);

        if ($isAbstractFilter) {
            $filtered = match ($filter) {
                'accepted'     => collect($abstracts)->where('status', 'accepted'),
                'not_accepted' => collect($abstracts)->reject(fn($a) => $a->status === 'accepted'),
                'uploaded'     => collect($abstracts)->where('status', 'accepted')->filter(fn($a) => $a->hasActualPresentationFiles()),
                'not_uploaded' => collect($abstracts)->where('status', 'accepted')->reject(fn($a) => $a->hasActualPresentationFiles()),
                'scheduled'    => collect($abstracts)->where('status', 'accepted')->whereNotNull('session_id'),
                'unscheduled'  => collect($abstracts)->where('status', 'accepted')->whereNull('session_id'),
                'oral'         => collect($abstracts)->where('status', 'accepted')->filter(fn($a) => strtolower(trim((string) $a->presentation_mode)) === 'oral'),
                'poster'       => collect($abstracts)->where('status', 'accepted')->filter(fn($a) => strtolower(trim((string) $a->presentation_mode)) === 'poster'),
                default        => collect($abstracts),
            };

            return $filtered->map(fn($a) => [
                'title'           => $a->title,
                'author'          => $a->author_name,
                'email'           => $a->author_email,
                'institute'       => $a->author_institute,
                'mode'            => $a->presentation_mode,
                'status'          => $a->status,
                'conference_code' => $a->conference_code,
                'scheduled'       => ! is_null($a->session_id),
            ])->values();
        }

        $filtered = match ($filter) {
            'paid'                   => collect($participants)->where('paid', true),
            'unpaid'                 => collect($participants)->where('paid', false),
            'with_abstracts'         => collect($participants)->where('has_abstracts', true),
            'without_abstracts'      => collect($participants)->where('has_abstracts', false),
            'paid_with_abstracts'    => collect($participants)->filter(fn($p) => $p['paid'] && $p['has_abstracts']),
            'paid_without_abstracts' => collect($participants)->filter(fn($p) => $p['paid'] && ! $p['has_abstracts']),
            'unpaid_with_abstracts'  => collect($participants)->filter(fn($p) => ! $p['paid'] && $p['has_abstracts']),
            'unpaid_without_abstracts' => collect($participants)->filter(fn($p) => ! $p['paid'] && ! $p['has_abstracts']),
            default                  => collect($participants),
        };

        return $filtered->values();
    }

    /**
     * Get real-time dashboard statistics via AJAX
     */
    public function getRealtimeStats()
    {
        $stats = $this->getDashboardStats();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'timestamp' => now()->toISOString()
        ]);
    }
}
