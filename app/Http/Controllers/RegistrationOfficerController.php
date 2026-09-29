<?php

namespace App\Http\Controllers;

use App\Models\AbstractSubmission;
use App\Models\Attendance;
use App\Models\BadgeNameOverride;
use App\Models\GroupMember;
use App\Models\OnsiteVisitor;
use App\Models\User;
use App\Services\BadgeDataQualityService;
use App\Services\EmailNotificationService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class RegistrationOfficerController extends Controller
{
    protected $notificationService;

    protected $emailService;

    public function __construct(NotificationService $notificationService, EmailNotificationService $emailService)
    {
        $this->notificationService = $notificationService;
        $this->emailService = $emailService;
    }

    public function publicBadge(string $token)
    {
        $token = $this->normalizeQrToken($token);

        if ($user = User::findByQrToken($token)) {
            if ($this->viewerCanOpenStaffBadge()) {
                return redirect()->route('registration.attendee-details', $user);
            }

            $acceptedAbstracts = $user->abstractSubmissions()
                ->where('status', 'accepted')
                ->get(['id', 'title', 'conference_code', 'presentation_mode']);

            return view('registration.public-badge', [
                'attendee' => [
                    'type' => 'Delegate',
                    'name' => trim(($user->title ? $user->title.' ' : '').$user->full_name),
                    'initials' => $user->initials,
                    'affiliation' => $user->affiliation ?: $user->institute,
                    'country' => $user->country,
                    'category' => str_replace('_', ' ', $user->registration_category ?? 'Delegate'),
                    'is_paid' => $user->isPaid(),
                    'is_presenter' => $acceptedAbstracts->isNotEmpty(),
                    'presentations' => $acceptedAbstracts,
                    'qr_token' => $user->qr_code_token,
                ],
            ]);
        }

        $groupMember = GroupMember::where('qr_token', $token)->with('groupRegistration')->first();
        if ($groupMember) {
            if ($this->viewerCanOpenStaffBadge()) {
                return redirect()->route('registration.group-member-details', $groupMember);
            }

            return view('registration.public-badge', [
                'attendee' => [
                    'type' => 'Group Member',
                    'name' => $groupMember->full_name,
                    'initials' => $groupMember->initials,
                    'affiliation' => $groupMember->institution,
                    'country' => $groupMember->country,
                    'category' => str_replace('_', ' ', $groupMember->registration_category ?? 'Group Member'),
                    'is_paid' => $groupMember->isPaymentVerified(),
                    'is_presenter' => false,
                    'presentations' => collect(),
                    'qr_token' => $groupMember->qr_token,
                    'group_name' => $groupMember->groupRegistration?->group_name,
                ],
            ]);
        }

        $onsiteVisitor = OnsiteVisitor::where('qr_token', $token)->first();
        if ($onsiteVisitor) {
            return view('registration.public-badge', [
                'attendee' => [
                    'type' => $onsiteVisitor->category_label,
                    'name' => $onsiteVisitor->effective_name,
                    'initials' => $onsiteVisitor->initials,
                    'affiliation' => $onsiteVisitor->institution,
                    'country' => null,
                    'category' => $onsiteVisitor->category_label,
                    'is_paid' => true,
                    'is_presenter' => false,
                    'presentations' => collect(),
                    'qr_token' => $onsiteVisitor->qr_token,
                ],
            ]);
        }

        abort(404);
    }

    protected function viewerCanOpenStaffBadge(): bool
    {
        return Auth::check() && Auth::user()->hasAnyRole(['admin', 'registration_officer']);
    }

    protected function normalizeQrToken(string $value): string
    {
        $value = trim($value);

        if (preg_match('~/((?:badge|connect))/([^/?#]+)~', $value, $matches)) {
            return rawurldecode($matches[2]);
        }

        return $value;
    }

    protected function qrPayloadForToken(string $token): string
    {
        return route('badge.public', $token);
    }

    /**
     * Display the Registration Officer dashboard.
     */
    public function dashboard(Request $request)
    {
        $activeTab = $request->query('tab', 'desk'); // desk | registry | prints

        // ── shared ──────────────────────────────────────────────────────────
        $currentDay = $this->getCurrentConferenceDay();
        $todayAttendanceIds = Attendance::where('day', $currentDay)->pluck('user_id')->toArray();

        $paidUsers = User::whereIn('payment_status', ['verified', 'waived']);
        $paidCount = (clone $paidUsers)->count();
        $presentToday = (clone $paidUsers)->whereIn('id', $todayAttendanceIds)->count();
        $paidAwaiting = (clone $paidUsers)->whereNotIn('id', $todayAttendanceIds)->count();

        $printLogsTableExists = \Illuminate\Support\Facades\Schema::hasTable('badge_print_logs');
        $badgesToday = $printLogsTableExists ? \App\Models\BadgePrintLog::whereDate('printed_at', today())->count() : 0;
        $badgesTotal = $printLogsTableExists ? \App\Models\BadgePrintLog::count() : 0;

        $headerStats = [
            'total_registered' => User::whereNotNull('payment_status')->count(),
            'checked_in_today' => $presentToday,
            'pending_checkin' => $paidAwaiting,
            'paid_count' => $paidCount,
            'onsite_count' => OnsiteVisitor::count(),
            'current_day' => $currentDay,
            'badges_today' => $badgesToday,
            'badges_total' => $badgesTotal,
        ];

        $quality = app(BadgeDataQualityService::class);
        $flaggedCount = $quality->flaggedCount();

        // ── Desk tab data ────────────────────────────────────────────────────
        $recentCheckins = Attendance::with(['user', 'groupMember', 'onsiteVisitor'])
            ->where('day', $currentDay)
            ->orderBy('checked_in_at', 'desc')
            ->limit(12)
            ->get();

        $recentOnsiteVisitors = OnsiteVisitor::latest()->limit(5)->get();

        // ── Registry tab data ────────────────────────────────────────────────
        $regStatus = $request->query('status', 'all');
        $regSearch = $request->query('search', '');
        $regCategory = $request->query('category', 'all');

        $regUsers = User::whereNotNull('payment_status')
            ->with(['attendances' => fn ($q) => $q->orderBy('day')])
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'type' => 'user',
                'name' => $u->full_name,
                'email' => $u->email,
                'affiliation' => $u->affiliation,
                'country' => $u->country,
                'category' => $u->registration_category,
                'payment_status' => in_array($u->payment_status, ['verified', 'waived'], true) ? $u->payment_status : ($u->isPaid() ? 'verified' : 'pending'),
                'attended_today' => $u->attendances->where('day', $currentDay)->isNotEmpty(),
                'checked_in_at' => optional($u->attendances->where('day', $currentDay)->first())->checked_in_at,
                'quality_flagged' => (bool) $u->quality_flagged,
                'quality_flags' => is_string($u->quality_flags) ? json_decode($u->quality_flags, true) : ($u->quality_flags ?? []),
            ]);

        $regGroupMembers = GroupMember::with(['groupRegistration', 'attendances' => fn ($q) => $q->orderBy('day')])
            ->get()
            ->map(function ($m) use ($currentDay) {
                $st = $m->groupRegistration?->payment_status ?? 'pending';

                return [
                    'id' => $m->id,
                    'type' => 'group_member',
                    'name' => $m->full_name,
                    'email' => $m->email,
                    'affiliation' => $m->institution,
                    'country' => $m->country,
                    'category' => $m->registration_category,
                    'payment_status' => in_array($st, ['verified', 'waived'], true) ? $st : 'pending',
                    'attended_today' => $m->attendances->where('day', $currentDay)->isNotEmpty(),
                    'checked_in_at' => optional($m->attendances->where('day', $currentDay)->first())->checked_in_at,
                    'quality_flagged' => (bool) $m->quality_flagged,
                    'quality_flags' => is_string($m->quality_flags) ? json_decode($m->quality_flags, true) : ($m->quality_flags ?? []),
                ];
            });

        // Inject print-name overrides (badge-only, original name untouched for admin)
        $overridePairs = $regUsers->map(fn ($r) => ['user', $r['id']])
            ->concat($regGroupMembers->map(fn ($r) => ['group_member', $r['id']]))
            ->toArray();
        $nameOverrides = BadgeNameOverride::bulkLoad($overridePairs);

        $applyOverride = fn ($row) => array_merge($row, [
            'print_name' => $nameOverrides["{$row['type']}:{$row['id']}"]?->print_name ?? null,
            'print_institute' => $nameOverrides["{$row['type']}:{$row['id']}"]?->print_institute ?? null,
        ]);

        $regUsers = $regUsers->map($applyOverride);
        $regGroupMembers = $regGroupMembers->map($applyOverride);

        $allRegRows = $regUsers->concat($regGroupMembers)
            ->filter(function ($row) use ($regStatus, $regSearch, $regCategory) {
                if ($regCategory !== 'all') {
                    if (! str_contains(strtolower((string) ($row['category'] ?? '')), strtolower($regCategory))) {
                        return false;
                    }
                }
                if ($regStatus === 'paid' && ! in_array($row['payment_status'], ['verified', 'waived'])) {
                    return false;
                }
                if ($regStatus === 'unpaid' && in_array($row['payment_status'], ['verified', 'waived'])) {
                    return false;
                }
                if ($regStatus === 'flagged' && empty($row['quality_flagged'])) {
                    return false;
                }
                if ($regStatus === 'checkedin' && (! in_array($row['payment_status'], ['verified', 'waived']) || empty($row['attended_today']))) {
                    return false;
                }
                if ($regStatus === 'pending_checkin' && (! in_array($row['payment_status'], ['verified', 'waived']) || ! empty($row['attended_today']))) {
                    return false;
                }
                if ($regSearch !== '') {
                    $hay = strtolower(implode(' ', [$row['name'] ?? '', $row['email'] ?? '', $row['affiliation'] ?? '']));
                    if (! str_contains($hay, strtolower($regSearch))) {
                        return false;
                    }
                }

                return true;
            })
            ->sortBy('name')
            ->values();

        $perPage = 30;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $attendees = new LengthAwarePaginator(
            $allRegRows->slice(($page - 1) * $perPage, $perPage)->values(),
            $allRegRows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $allCombined = $regUsers->concat($regGroupMembers);
        $regStats = [
            'total' => $allCombined->count(),
            'paid' => $allCombined->filter(fn ($r) => in_array($r['payment_status'], ['verified', 'waived']))->count(),
            'unpaid' => $allCombined->filter(fn ($r) => ! in_array($r['payment_status'], ['verified', 'waived']))->count(),
            'flagged' => $allCombined->filter(fn ($r) => ! empty($r['quality_flagged']))->count(),
            'checkedin' => $allCombined->filter(fn ($r) => in_array($r['payment_status'], ['verified', 'waived']) && ! empty($r['attended_today']))->count(),
            'pending_checkin' => $allCombined->filter(fn ($r) => in_array($r['payment_status'], ['verified', 'waived']) && empty($r['attended_today']))->count(),
        ];

        // ── Print Log tab data ───────────────────────────────────────────────
        $printLogFilter = $request->query('print_type', 'all');
        $printLogSearch = $request->query('print_search', '');
        $printLogs = collect();
        $printLogStats = ['today' => $badgesToday, 'total' => $badgesTotal, 'reprints' => 0, 'walkins' => 0];
        if ($printLogsTableExists) {
            $plQuery = \App\Models\BadgePrintLog::query()->orderBy('printed_at', 'desc');
            if ($printLogFilter !== 'all') {
                $plQuery->where('entity_type', $printLogFilter);
            }
            if ($printLogSearch !== '') {
                $plQuery->where(fn ($q) => $q->where('entity_name', 'like', "%{$printLogSearch}%")
                    ->orWhere('entity_institution', 'like', "%{$printLogSearch}%"));
            }
            $printLogs = $plQuery->paginate(40, ['*'], 'pl_page')->withQueryString();
            $printLogStats['reprints'] = \App\Models\BadgePrintLog::where('print_number', '>', 1)->count();
            $printLogStats['walkins'] = \App\Models\BadgePrintLog::where('entity_type', 'onsite_visitor')->count();
        }

        return view('registration.dashboard', compact(
            'activeTab',
            'headerStats',
            'flaggedCount',
            'recentCheckins',
            'recentOnsiteVisitors',
            'attendees',
            'regStats',
            'regStatus',
            'regSearch',
            'regCategory',
            'printLogs',
            'printLogStats',
            'printLogFilter',
            'printLogSearch',
            'printLogsTableExists'
        ));
    }

    /**
     * Search for attendees to check in.
     */
    public function search(Request $request)
    {
        $search = trim($request->query('q', ''));
        $currentDay = $this->getCurrentConferenceDay();

        if (strlen($search) < 2) {
            return response()->json(['attendees' => []]);
        }

        $query = User::query();

        // Split search terms to ensure multi-word search works (e.g., "John Doe")
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        // 1. Search regular Users
        $userQuery = User::query();
        foreach ($terms as $term) {
            $userQuery->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('affiliation', 'like', "%{$term}%")
                    ->orWhere('qr_code_token', 'like', "%{$term}%");
            });
        }

        $users = $userQuery->with([
            'abstractSubmissions' => function ($q) {
                $q->where('status', 'accepted');
            },
            'attendances' => function ($q) use ($currentDay) {
                $q->where('day', $currentDay);
            },
        ])
            ->limit(10)
            ->get()
            ->map(function ($user) {
                $todayAttendance = $user->attendances->first();

                return [
                    'id' => $user->id,
                    'type' => 'user',
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'affiliation' => $user->affiliation,
                    'category' => $user->registration_category,
                    'payment_status' => $user->payment_status,
                    'checked_in' => $todayAttendance !== null,
                    'checked_in_at' => $todayAttendance?->checked_in_at?->format('H:i'),
                    'has_presentation' => $user->abstractSubmissions->count() > 0,
                    'qr_token' => $user->qr_code_token,
                    'initials' => $user->initials,
                    'quality_flagged' => (bool) $user->quality_flagged,
                ];
            });

        // 2. Search Group Members
        $gmQuery = \App\Models\GroupMember::query();
        foreach ($terms as $term) {
            $gmQuery->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('institution', 'like', "%{$term}%")
                    ->orWhere('qr_token', 'like', "%{$term}%");
            });
        }

        $groupMembers = $gmQuery->with([
            'groupRegistration',
            'attendances' => function ($q) use ($currentDay) {
                $q->where('day', $currentDay);
            },
        ])
            ->limit(10)
            ->get()
            ->map(function ($gm) {
                $todayAttendance = $gm->attendances->first();

                return [
                    'id' => $gm->id,
                    'type' => 'group_member',
                    'name' => $gm->full_name,
                    'email' => $gm->email,
                    'phone' => $gm->phone,
                    'affiliation' => $gm->institution,
                    'category' => $gm->registration_category,
                    'payment_status' => $gm->groupRegistration->payment_status,
                    'checked_in' => $todayAttendance !== null,
                    'checked_in_at' => $todayAttendance?->checked_in_at?->format('H:i'),
                    'has_presentation' => false,
                    'qr_token' => $gm->qr_token,
                    'initials' => $gm->initials,
                    'quality_flagged' => (bool) $gm->quality_flagged,
                ];
            });

        $attendees = $users->concat($groupMembers)->sortBy('name')->values();

        return response()->json(['attendees' => $attendees]);
    }

    /**
     * Check in an attendee.
     */
    public function checkIn(Request $request, User $user)
    {
        $day = (int) $request->input('day', $this->getCurrentConferenceDay());
        $dayGuard = $this->validateAttendanceDay($day);

        if (! $dayGuard['valid']) {
            return response()->json([
                'success' => false,
                'message' => $dayGuard['message'],
                'current_day' => $dayGuard['current_day'],
                'current_day_label' => $this->attendanceDayLabel($dayGuard['current_day']),
            ], 422);
        }

        if (! $user->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'This attendee has not completed payment verification or waiver.',
            ], 400);
        }

        if (Attendance::hasAttendance($user->id, $day)) {
            $attendance = Attendance::where('user_id', $user->id)
                ->where('day', $day)
                ->first();

            return response()->json([
                'success' => false,
                'message' => "This attendee has already been checked in for Day {$day} at ".$attendance->checked_in_at->format('H:i'),
            ], 409);
        }

        $attendance = Attendance::recordAttendance($user->id, $day, Auth::id());
        $this->syncUserCheckInSnapshot($user);

        return response()->json([
            'success' => true,
            'message' => "{$user->full_name} has been checked in successfully for Day {$day}!",
            'checked_in_at' => $attendance->checked_in_at->format('H:i'),
        ]);
    }

    /**
     * Check in a group member.
     */
    public function groupMemberCheckIn(Request $request, \App\Models\GroupMember $member)
    {
        $day = (int) $request->input('day', $this->getCurrentConferenceDay());
        $dayGuard = $this->validateAttendanceDay($day);

        if (! $dayGuard['valid']) {
            return response()->json([
                'success' => false,
                'message' => $dayGuard['message'],
                'current_day' => $dayGuard['current_day'],
                'current_day_label' => $this->attendanceDayLabel($dayGuard['current_day']),
            ], 422);
        }

        if (! $member->groupRegistration->isVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'The group payment for this member has not been verified.',
            ], 400);
        }

        if (Attendance::hasGroupMemberAttendance($member->id, $day)) {
            $attendance = Attendance::where('group_member_id', $member->id)
                ->where('day', $day)
                ->first();

            return response()->json([
                'success' => false,
                'message' => "This member has already been checked in for Day {$day} at ".$attendance->checked_in_at->format('H:i'),
            ], 409);
        }

        $attendance = Attendance::recordGroupMemberAttendance($member->id, $day, Auth::id());
        $this->syncGroupMemberCheckInSnapshot($member);

        return response()->json([
            'success' => true,
            'message' => "{$member->full_name} has been checked in successfully for Day {$day}!",
            'checked_in_at' => $attendance->checked_in_at->format('H:i'),
        ]);
    }

    /**
     * Check in a walk-in (onsite) visitor.
     */
    public function onsiteCheckIn(Request $request, OnsiteVisitor $visitor)
    {
        $day = (int) $request->input('day', $this->getCurrentConferenceDay());
        $dayGuard = $this->validateAttendanceDay($day);

        if (! $dayGuard['valid']) {
            return response()->json([
                'success' => false,
                'message' => $dayGuard['message'],
                'current_day' => $dayGuard['current_day'],
                'current_day_label' => $this->attendanceDayLabel($dayGuard['current_day']),
            ], 422);
        }

        if (Attendance::hasOnsiteVisitorAttendance($visitor->id, $day)) {
            $attendance = Attendance::where('onsite_visitor_id', $visitor->id)
                ->where('day', $day)
                ->first();

            return response()->json([
                'success' => false,
                'message' => "This visitor has already been checked in for Day {$day} at ".$attendance->checked_in_at->format('H:i'),
            ], 409);
        }

        $attendance = Attendance::recordOnsiteVisitorAttendance($visitor->id, $day, Auth::id());

        return response()->json([
            'success' => true,
            'message' => "{$visitor->effective_name} has been checked in successfully for Day {$day}!",
            'checked_in_at' => $attendance->checked_in_at->format('H:i'),
        ]);
    }

    /**
     * View group member details for registration desk.
     */
    public function groupMemberDetails(\App\Models\GroupMember $member)
    {
        $member->load(['groupRegistration', 'attendances']);

        $currentDay = $this->getCurrentConferenceDay();
        $attendanceByDay = [];

        for ($day = 1; $day <= 3; $day++) {
            $attendance = $member->attendances->where('day', $day)->first();
            $attendanceByDay[$day] = [
                'attended' => $attendance !== null,
                'checked_in_at' => $attendance?->checked_in_at?->format('g:i A'),
            ];
        }

        $data = [
            'id' => $member->id,
            'type' => 'group_member',
            'name' => $member->full_name,
            'email' => $member->email,
            'phone' => $member->phone,
            'affiliation' => $member->institution,
            'country' => $member->country,
            'category' => $member->registration_category,
            'student_id_path' => $member->student_id_path,
            'payment_status' => $member->groupRegistration->payment_status,
            'is_paid' => in_array($member->groupRegistration->payment_status, ['verified', 'waived'], true),
            'qr_token' => $member->qr_token,
            'initials' => $member->initials,
            'is_presenter' => false,
            'checked_in' => $member->checked_in,
            'checked_in_at' => $member->checked_in_at ? $member->checked_in_at->format('H:i') : null,
            'badge_printed' => $member->badge_printed,
            'group_name' => $member->groupRegistration->group_name,
            'attendance' => $attendanceByDay,
            'current_day' => $currentDay,
        ];

        if (request()->expectsJson()) {
            return response()->json($data);
        }

        return view('registration.group-member-show', ['attendee' => $data, 'member' => $member]);
    }

    public function attendeeDetails(User $user)
    {
        $user->load(['abstractSubmissions' => function ($q) {
            $q->where('status', 'accepted');
        }, 'attendances']);

        // Check presentation upload status for each accepted abstract
        $presentations = $user->abstractSubmissions->map(function ($abstract) {
            $isOral = strtolower($abstract->presentation_mode) !== 'poster';
            $hasUpload = $isOral
                ? ! empty($abstract->oral_presentation_file)
                : ! empty($abstract->poster_presentation_file);

            return [
                'id' => $abstract->id,
                'title' => $abstract->title,
                'conference_code' => $abstract->conference_code,
                'presentation_type' => $abstract->presentation_mode,
                'session_id' => $abstract->session_id,
                'has_upload' => $hasUpload,
                'upload_file' => $isOral ? $abstract->oral_presentation_file : $abstract->poster_presentation_file,
            ];
        });

        // Get current conference day
        $currentDay = $this->getCurrentConferenceDay();

        // Daily attendance status
        $attendanceByDay = [];
        if ($currentDay === 0) {
            $attendance = $user->attendances->where('day', 0)->first();
            $attendanceByDay[0] = [
                'attended' => $attendance !== null,
                'checked_in_at' => $attendance?->checked_in_at?->format('g:i A'),
            ];
        }

        for ($day = 1; $day <= 3; $day++) {
            $attendance = $user->attendances->where('day', $day)->first();
            $attendanceByDay[$day] = [
                'attended' => $attendance !== null,
                'checked_in_at' => $attendance?->checked_in_at?->format('g:i A'),
            ];
        }

        $data = [
            'id' => $user->id,
            'title' => $user->title,
            'name' => $user->full_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'affiliation' => $user->affiliation,
            'institute' => $user->institute ?: $user->affiliation,
            'profile_image' => $user->profile_image,
            'bio' => $user->bio,
            'country' => $user->country,
            'category' => $user->registration_category,
            'payment_status' => $user->payment_status,
            'is_paid' => $user->isPaid(),
            'qr_token' => $user->getOrCreateQrToken(),
            'initials' => $user->initials,
            'is_presenter' => $presentations->isNotEmpty(),
            'presentations' => $presentations,
            'all_uploads_complete' => $presentations->isEmpty() || $presentations->every(fn ($p) => $p['has_upload']),
            'attendance' => $attendanceByDay,
            'current_day' => $currentDay,
            'attended_today' => isset($attendanceByDay[$currentDay]) && $attendanceByDay[$currentDay]['attended'],
            'badge_printed' => $user->badge_printed,
            'badge_printed_at' => $user->badge_printed_at ? $user->badge_printed_at->format('Y-m-d H:i') : null,
        ];

        if (request()->expectsJson()) {
            return response()->json($data);
        }

        return view('registration.show', ['attendee' => $data, 'user' => $user]);
    }

    /**
     * Update an attendee's profile details from the registration desk.
     */
    public function updateAttendee(Request $request, User $user)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:50',
            'affiliation' => 'nullable|string|max:255',
            'institute' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:2000',
            'registration_category' => 'nullable|in:professional_local,professional_international,student_local,student_international',
            'payment_status' => 'nullable|in:pending,submitted,verified,waived,rejected',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        $user->fill($validated);

        // Stamp verification metadata when an officer flips payment to a "paid" state.
        if ($user->isDirty('payment_status') && in_array($user->payment_status, ['verified', 'waived'], true)) {
            $user->payment_verified_at = now();
            $user->payment_verified_by = Auth::id();
        }

        $user->save();

        $message = $user->full_name."'s details were updated successfully.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()
            ->route('registration.attendee-details', $user)
            ->with('success', $message);
    }

    /**
     * Get current conference day (1, 2, or 3)
     */
    protected function getCurrentConferenceDay(): int
    {
        $timezone = config('conference.timezone', config('app.timezone', 'Africa/Dar_es_Salaam'));
        $conferenceStart = \Carbon\Carbon::parse(config('conference.start_date'), $timezone)->startOfDay();
        $totalDays = config('conference.total_days', 3);
        $today = \Carbon\Carbon::now($timezone)->startOfDay();

        if ($today->lt($conferenceStart)) {
            return 0;
        }

        $day = (int) $conferenceStart->diffInDays($today) + 1;

        return $day > $totalDays ? 0 : $day;
    }

    protected function attendanceDayLabel(int $day): string
    {
        return $day === 0 ? 'Demo Day' : "Day {$day}";
    }

    protected function validateAttendanceDay(int $day): array
    {
        $currentDay = $this->getCurrentConferenceDay();

        if ($day === $currentDay) {
            return ['valid' => true, 'current_day' => $currentDay, 'message' => null];
        }

        if ($currentDay === 0) {
            return [
                'valid' => false,
                'current_day' => $currentDay,
                'message' => 'Real attendance is locked outside the conference dates. Use Demo Day for testing.',
            ];
        }

        return [
            'valid' => false,
            'current_day' => $currentDay,
            'message' => "Attendance can only be marked for {$this->attendanceDayLabel($currentDay)} today.",
        ];
    }

    /**
     * Record daily attendance for an attendee.
     */
    public function recordAttendance(Request $request, User $user)
    {
        $day = (int) $request->input('day', $this->getCurrentConferenceDay());
        $dayGuard = $this->validateAttendanceDay($day);

        if (! $dayGuard['valid']) {
            return response()->json([
                'success' => false,
                'message' => $dayGuard['message'],
                'current_day' => $dayGuard['current_day'],
                'current_day_label' => $this->attendanceDayLabel($dayGuard['current_day']),
            ], 422);
        }

        // Check payment status
        if (! $user->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not verified. Please verify payment before recording attendance.',
            ], 400);
        }

        // Check if already attended today
        if (Attendance::hasAttendance($user->id, $day)) {
            $attendance = Attendance::where('user_id', $user->id)->where('day', $day)->first();

            return response()->json([
                'success' => false,
                'message' => "Already checked in for {$this->attendanceDayLabel($day)} at ".$attendance->checked_in_at->format('g:i A'),
                'already_attended' => true,
                'checked_in_at' => $attendance->checked_in_at->format('g:i A'),
            ], 409);
        }

        // Record attendance
        $attendance = Attendance::recordAttendance($user->id, $day, Auth::id());
        $this->syncUserCheckInSnapshot($user);

        return response()->json([
            'success' => true,
            'message' => "{$user->full_name} marked present for {$this->attendanceDayLabel($day)}!",
            'attendance' => [
                'day' => $day,
                'day_label' => $this->attendanceDayLabel($day),
                'checked_in_at' => $attendance->checked_in_at->format('g:i A'),
            ],
        ]);
    }

    /**
     * Legacy attendees route — redirects to the unified Reception page.
     */
    public function attendees(Request $request)
    {
        return redirect()->route('registration.dashboard', array_merge(
            $request->only(['status', 'search', 'category', 'page']),
            ['tab' => 'registry']
        ));
    }

    /**
     * Get real-time statistics for dashboard refresh.
     */
    public function getStats()
    {
        $currentDay = $this->getCurrentConferenceDay();
        $todayAttendanceIds = Attendance::where('day', $currentDay)->pluck('user_id');
        return response()->json([
            'total_registered' => User::whereIn('payment_status', ['verified', 'waived'])->count(),
            'checked_in_today' => User::whereIn('payment_status', ['verified', 'waived'])
                ->whereIn('id', $todayAttendanceIds)
                ->count(),
            'pending_checkin' => User::whereIn('payment_status', ['verified', 'waived'])
                ->whereNotIn('id', $todayAttendanceIds)
                ->count(),
            'current_day' => $currentDay,
        ]);
    }

    /**
     * Export the attendance report as a CSV.
     *
     * By default this produces a full roster: one row per attendee with a
     * present/time column for EVERY conference day plus a "Days Attended" total.
     * Pass ?day=N to export a single day instead. Pass ?status= to filter
     * (full mode: all|attended|absent — day mode: all|checked_in|pending).
     * Delegates, group members and onsite visitors are all
     * included.
     */
    public function exportReport(Request $request)
    {
        $status = $request->query('status', 'all');
        $totalDays = (int) config('conference.total_days', 3);
        $days = range(1, max(1, $totalDays));

        // Single-day mode only when a valid day is requested.
        $dayParam = $request->query('day');
        $singleDay = ($dayParam !== null && in_array((int) $dayParam, $days, true))
            ? (int) $dayParam
            : null;

        // Helper: turn a loaded attendances relation into [day => Carbon|null].
        $byDay = function ($attendances) use ($days) {
            $map = [];
            foreach ($days as $d) {
                $map[$d] = optional(optional($attendances)->firstWhere('day', $d))->checked_in_at;
            }

            return $map;
        };

        // Delegates (regular paid users).
        $users = User::whereIn('payment_status', ['verified', 'waived'])
            ->with(['attendances' => fn ($q) => $q->whereIn('day', $days)])
            ->get()
            ->map(fn ($user) => [
                'name' => $user->full_name,
                'email' => $user->email ?? '-',
                'phone' => $user->phone ?? '-',
                'affiliation' => $user->affiliation ?? '-',
                'category' => $user->registration_category ?? 'Not set',
                'attendee_type' => 'Delegate',
                'days' => $byDay($user->attendances),
            ]);

        // Group members (paid via their group registration).
        $groupMembers = GroupMember::with([
            'groupRegistration',
            'attendances' => fn ($q) => $q->whereIn('day', $days),
        ])
            ->whereHas('groupRegistration', fn ($q) => $q->whereIn('payment_status', ['verified', 'waived']))
            ->get()
            ->map(fn ($member) => [
                'name' => $member->full_name,
                'email' => $member->email ?? '-',
                'phone' => $member->phone ?? '-',
                'affiliation' => $member->institution ?? '-',
                'category' => $member->category_label,
                'attendee_type' => 'Group Delegate',
                'days' => $byDay($member->attendances),
            ]);

        // Onsite / walk-in visitors. OnsiteVisitor has no attendances relation,
        // so resolve their check-ins from the attendances table directly.
        $onsiteAttendance = Attendance::whereNotNull('onsite_visitor_id')
            ->whereIn('day', $days)
            ->get()
            ->groupBy('onsite_visitor_id');

        $onsiteVisitors = OnsiteVisitor::get()
            ->map(fn ($visitor) => [
                'name' => $visitor->effective_name,
                'email' => '-',
                'phone' => '-',
                'affiliation' => $visitor->institution ?? '-',
                'category' => $visitor->category_label,
                'attendee_type' => 'Onsite Visitor',
                'days' => $byDay($onsiteAttendance->get($visitor->id)),
            ]);

        $rows = $users
            ->concat($groupMembers)
            ->concat($onsiteVisitors)
            ->filter(function ($row) use ($status, $singleDay, $days) {
                $presentLike = in_array($status, ['present', 'checked_in', 'attended'], true);
                $absentLike = in_array($status, ['absent', 'pending'], true);

                if ($singleDay !== null) {
                    $present = $row['days'][$singleDay] !== null;
                } else {
                    $present = collect($days)->contains(fn ($d) => $row['days'][$d] !== null);
                }

                if ($presentLike) {
                    return $present;
                }
                if ($absentLike) {
                    return ! $present;
                }

                return true;
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $filename = $singleDay !== null
            ? 'attendance_day'.$singleDay.'_'.date('Y-m-d_H-i').'.csv'
            : 'attendance_full_'.date('Y-m-d_H-i').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($rows, $days, $singleDay) {
            $file = fopen('php://output', 'w');

            if ($singleDay !== null) {
                fputcsv($file, ['Name', 'Email', 'Phone', 'Affiliation', 'Category', 'Attendee Type', 'Day', 'Present', 'Check-in Time']);
                foreach ($rows as $row) {
                    $time = $row['days'][$singleDay];
                    fputcsv($file, [
                        $row['name'], $row['email'], $row['phone'], $row['affiliation'],
                        $row['category'], $row['attendee_type'],
                        'Day '.$singleDay,
                        $time ? 'Yes' : 'No',
                        $time ? $time->format('Y-m-d H:i') : '-',
                    ]);
                }
            } else {
                $header = ['Name', 'Email', 'Phone', 'Affiliation', 'Category', 'Attendee Type'];
                foreach ($days as $d) {
                    $header[] = 'Day '.$d;
                    $header[] = 'Day '.$d.' Time';
                }
                $header[] = 'Days Attended';
                fputcsv($file, $header);

                foreach ($rows as $row) {
                    $line = [
                        $row['name'], $row['email'], $row['phone'], $row['affiliation'],
                        $row['category'], $row['attendee_type'],
                    ];
                    $count = 0;
                    foreach ($days as $d) {
                        $time = $row['days'][$d];
                        $line[] = $time ? 'Yes' : 'No';
                        $line[] = $time ? $time->format('Y-m-d H:i') : '-';
                        if ($time) {
                            $count++;
                        }
                    }
                    $line[] = $count;
                    fputcsv($file, $line);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Send presentation upload reminder to author.
     */
    public function sendPresentationReminder(AbstractSubmission $abstract)
    {
        $user = $abstract->user;

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Author not found for this abstract.',
            ], 404);
        }

        // Check if presentation is already uploaded
        $hasUpload = strtolower($abstract->presentation_mode) === 'poster'
            ? ! empty($abstract->poster_presentation_file)
            : ! empty($abstract->oral_presentation_file);

        if ($hasUpload) {
            return response()->json([
                'success' => false,
                'message' => 'Presentation materials have already been uploaded.',
            ]);
        }

        try {
            // Send email notification
            $this->emailService->sendPresentationReminder($abstract);

            // Send in-app notification
            $this->notificationService->createPresentationReminderNotification(
                $user,
                $abstract->title,
                $abstract->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Presentation reminder sent successfully (Email & In-App).',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reminder: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate and print badge for attendee.
     */
    public function printBadge(User $user)
    {
        // 1. Verify Payment (using centralized isPaid logic which handles groups)
        if (! $user->isPaid()) {
            return response()->json(['error' => 'Payment not verified. You must complete payment before printing your badge.'], 403);
        }

        // 2. Mark as printed
        $user->markBadgePrinted();

        // 3. Generate QR Code (defensive check)
        $token = $user->getOrCreateQrToken();
        if (! $token) {
            $token = $user->generateQrToken();
        }
        $qrImage = $this->generateQrCodeBase64($this->qrPayloadForToken($token));

        // 4. Generate PDF with conference config
        $printName = BadgeNameOverride::getFor('user', $user->id)
            ?? trim(($user->title ? $user->title.' ' : '').$user->first_name.' '.$user->last_name);
        $printInstitute = BadgeNameOverride::getInstituteFor('user', $user->id);
        \App\Models\BadgePrintLog::record('user', $user->id, $printName, $printInstitute ?? $user->affiliation ?? $user->institute ?? null, $user->registration_category);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('registration.badge.print', [
            'user' => $user,
            'printName' => $printName,
            'printInstitute' => $printInstitute,
            'qrImage' => $qrImage,
            'conference' => [
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
            ],
        ]);

        $pdf->setPaper($this->badgePaperSize());

        return $pdf->stream('badge-'.$user->id.'.pdf');
    }

    /**
     * Generate QR code as base64 data URI (local generation, no external API)
     * Uses High error correction (H) to allow logo overlay in center
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

        // Return as base64 data URI
        return $qrcode->render($data);
    }

    public function printMyBadge()
    {
        $user = Auth::user();

        // Users can only print their own badge
        return $this->printBadge($user);
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

    /**
     * Bulk generate and print badges for a mixed selection of attendees.
     */
    public function bulkPrintBadges(Request $request)
    {
        $userIds = $request->input('user_ids', []);
        $groupMemberIds = $request->input('group_member_ids', []);

        if (empty($userIds) && empty($groupMemberIds)) {
            return response()->json(['error' => 'No attendees selected'], 400);
        }

        $attendeeData = [];

        // Preload name overrides for all selected entities
        $overridePairs = array_merge(
            array_map(fn ($id) => ['user', $id], $userIds),
            array_map(fn ($id) => ['group_member', $id], $groupMemberIds),
        );
        $nameOverrides = BadgeNameOverride::bulkLoad($overridePairs);

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            if ($user->isPaid()) {
                $token = $user->getOrCreateQrToken();
                $user->markBadgePrinted();
                $bulkName = $nameOverrides["user:{$user->id}"]?->print_name
                    ?? trim(($user->title ? $user->title.' ' : '').$user->first_name.' '.$user->last_name);
                $bulkInst = $nameOverrides["user:{$user->id}"]?->print_institute ?? ($user->institute ?: $user->affiliation);
                \App\Models\BadgePrintLog::record('user', $user->id, $bulkName, $bulkInst, $user->registration_category);
                $attendeeData[] = [
                    'subject' => $user,
                    'name' => $bulkName,
                    'institution' => $bulkInst,
                    'qrImage' => $this->generateQrCodeBase64($this->qrPayloadForToken($token)),
                ];
            }
        }

        foreach (\App\Models\GroupMember::with('groupRegistration')->whereIn('id', $groupMemberIds)->get() as $member) {
            if (in_array($member->groupRegistration?->payment_status ?? '', ['verified', 'waived'])) {
                $member->markBadgePrinted();
                $bulkName = $nameOverrides["group_member:{$member->id}"]?->print_name ?? $member->full_name;
                $bulkInst = $nameOverrides["group_member:{$member->id}"]?->print_institute ?? $member->institution;
                \App\Models\BadgePrintLog::record('group_member', $member->id, $bulkName, $bulkInst, $member->registration_category ?? null);
                $attendeeData[] = [
                    'subject' => $member,
                    'name' => $bulkName,
                    'institution' => $bulkInst,
                    'qrImage' => $this->generateQrCodeBase64($this->qrPayloadForToken($member->qr_token)),
                ];
            }
        }

        if (empty($attendeeData)) {
            return response()->json(['error' => 'No eligible paid attendees found in selection'], 400);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('registration.badge.all-print', [
            'attendees' => $attendeeData,
            'conference' => [
                'name' => config('conference.name'),
                'short_name' => config('conference.short_name'),
                'edition' => config('conference.edition'),
                'year' => config('conference.year'),
                'display_dates' => config('conference.display_dates'),
                'venue' => config('conference.venue'),
                'city' => config('conference.city'),
                'country' => config('conference.country'),
                'host' => config('conference.host'),
            ],
        ]);

        $pdf->getDomPDF()->setPaper($this->badgePaperSize());

        return $pdf->download('bulk-badges-'.now()->format('YmdHi').'.pdf');
    }

    /**
     * Generate and download badges for all paid attendees in one shot.
     */
    public function printAllPaidBadges()
    {
        set_time_limit(300);

        $conference = [
            'name' => config('conference.name'),
            'short_name' => config('conference.short_name'),
            'edition' => config('conference.edition'),
            'year' => config('conference.year'),
            'display_dates' => config('conference.display_dates'),
            'venue' => config('conference.venue'),
            'city' => config('conference.city'),
            'country' => config('conference.country'),
            'host' => config('conference.host'),
        ];

        $attendeeData = [];

        $users = User::whereNotNull('payment_status')
            ->get()
            ->filter(fn ($u) => $u->isPaid());

        foreach ($users as $user) {
            $token = $user->getOrCreateQrToken();
            $user->markBadgePrinted();
            $allName = trim(($user->title ? $user->title.' ' : '').$user->first_name.' '.$user->last_name);
            \App\Models\BadgePrintLog::record('user', $user->id, $allName, $user->institute ?: $user->affiliation, $user->registration_category);
            $attendeeData[] = [
                'subject' => $user,
                'name' => $allName,
                'institution' => $user->institute ?: $user->affiliation,
                'qrImage' => $this->generateQrCodeBase64($this->qrPayloadForToken($token)),
            ];
        }

        $groupMembers = \App\Models\GroupMember::with('groupRegistration')->get()
            ->filter(fn ($m) => in_array($m->groupRegistration?->payment_status ?? '', ['verified', 'waived']));

        foreach ($groupMembers as $member) {
            $member->markBadgePrinted();
            \App\Models\BadgePrintLog::record('group_member', $member->id, $member->full_name, $member->institution, $member->registration_category ?? null);
            $attendeeData[] = [
                'subject' => $member,
                'name' => $member->full_name,
                'institution' => $member->institution,
                'qrImage' => $this->generateQrCodeBase64($this->qrPayloadForToken($member->qr_token)),
            ];
        }

        if (empty($attendeeData)) {
            return redirect()->route('registration.attendees')->with('error', 'No paid attendees found to print.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('registration.badge.all-print', [
            'attendees' => $attendeeData,
            'conference' => $conference,
        ]);

        $pdf->getDomPDF()->setPaper($this->badgePaperSize());

        return $pdf->download('all-badges-'.now()->format('YmdHi').'.pdf');
    }

    /**
     * Record daily attendance for a group member.
     */
    public function recordGroupMemberAttendance(Request $request, \App\Models\GroupMember $member)
    {
        $day = (int) $request->input('day', $this->getCurrentConferenceDay());
        $dayGuard = $this->validateAttendanceDay($day);

        if (! $dayGuard['valid']) {
            return response()->json([
                'success' => false,
                'message' => $dayGuard['message'],
                'current_day' => $dayGuard['current_day'],
                'current_day_label' => $this->attendanceDayLabel($dayGuard['current_day']),
            ], 422);
        }

        // Check payment status
        if (! $member->groupRegistration->isVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Group payment not verified. Please verify payment before recording attendance.',
            ], 400);
        }

        // Check if already attended today
        if (Attendance::hasGroupMemberAttendance($member->id, $day)) {
            $attendance = Attendance::where('group_member_id', $member->id)->where('day', $day)->first();

            return response()->json([
                'success' => false,
                'message' => "Already checked in for Day {$day} at ".$attendance->checked_in_at->format('g:i A'),
                'already_attended' => true,
                'checked_in_at' => $attendance->checked_in_at->format('g:i A'),
            ], 409);
        }

        // Record attendance
        $attendance = Attendance::recordGroupMemberAttendance($member->id, $day, Auth::id());
        $this->syncGroupMemberCheckInSnapshot($member);

        return response()->json([
            'success' => true,
            'message' => "{$member->full_name} marked present for Day {$day}!",
            'attendance' => [
                'day' => $day,
                'checked_in_at' => $attendance->checked_in_at->format('g:i A'),
            ],
        ]);
    }

    protected function syncUserCheckInSnapshot(User $user): void
    {
        $latestAttendance = Attendance::where('user_id', $user->id)
            ->latest('checked_in_at')
            ->first();

        $user->update([
            'checked_in_at' => $latestAttendance?->checked_in_at,
            'checked_in_by' => $latestAttendance?->checked_in_by,
        ]);
    }

    protected function syncGroupMemberCheckInSnapshot(GroupMember $member): void
    {
        $latestAttendance = Attendance::where('group_member_id', $member->id)
            ->latest('checked_in_at')
            ->first();

        $member->update([
            'checked_in' => $latestAttendance !== null,
            'checked_in_at' => $latestAttendance?->checked_in_at,
        ]);
    }

    /**
     * Generate and print badge for group member.
     */
    public function printGroupMemberBadge(\App\Models\GroupMember $member)
    {
        // 1. Verify Payment
        if (! $member->groupRegistration->isVerified()) {
            return response()->json(['error' => 'Group payment not verified'], 403);
        }

        // 2. Mark as printed
        $member->markBadgePrinted();
        \App\Models\BadgePrintLog::record('group_member', $member->id, $member->full_name, $member->institution, $member->registration_category ?? null);

        // 3. Generate QR Code
        $qrImage = $this->generateQrCodeBase64($this->qrPayloadForToken($member->qr_token));
        $printInstitute = BadgeNameOverride::getInstituteFor('group_member', $member->id);

        // 4. Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('registration.badge.member-print', [
            'member' => $member,
            'printInstitute' => $printInstitute,
            'qrImage' => $qrImage,
            'conference' => [
                'name' => config('conference.name'),
                'short_name' => config('conference.short_name'),
                'edition' => config('conference.edition'),
                'year' => config('conference.year'),
                'display_dates' => config('conference.display_dates'),
                'venue' => config('conference.venue'),
                'city' => config('conference.city'),
                'country' => config('conference.country'),
                'host' => config('conference.host'),
            ],
        ]);

        $pdf->getDomPDF()->setPaper($this->badgePaperSize());

        return $pdf->download('badge-gm-'.$member->id.'.pdf');
    }

    // =========================================================================
    // ONSITE VISITOR — walk-in / invitee fast print
    // =========================================================================

    public function onsiteVisitors(Request $request)
    {
        $search = $request->input('search');

        $visitors = OnsiteVisitor::with('createdBy')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('institution', 'like', "%{$search}%"))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        // Every listed walk-in needs a token so staff can share their
        // personal certificate claim link.
        $visitors->getCollection()->each(fn (OnsiteVisitor $v) => $v->getOrCreateQrToken());

        $quality = app(BadgeDataQualityService::class);

        return view('registration.onsite.index', [
            'visitors' => $visitors,
            'search' => $search,
            'categories' => OnsiteVisitor::$categories,
            'flaggedCount' => $quality->flaggedCount(),
        ]);
    }

    public function onsiteCreate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'institution' => 'nullable|string|max:255',
            'badge_category' => 'required|in:invitee,vip,guest,media,staff,speaker',
            'notes' => 'nullable|string|max:500',
        ]);

        // Quick quality check — warn but don't block
        $quality = app(BadgeDataQualityService::class);
        $flags = $quality->quickCheck($validated['name'], $validated['institution'] ?? null);

        $visitor = OnsiteVisitor::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'visitor_id' => $visitor->id,
                'flags' => $flags,
                'print_url' => route('registration.onsite.print-badge', $visitor),
            ]);
        }

        return redirect()->route('registration.onsite.print-badge', $visitor);
    }

    public function registryNameOverride(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|in:user,group_member',
            'entity_id' => 'required|integer|min:1',
            'print_name' => 'nullable|string|max:255',
            'print_institute' => 'nullable|string|max:255',
        ]);

        $type = $request->entity_type;
        $id = (int) $request->entity_id;
        $hasName = $request->has('print_name');
        $hasInstitute = $request->has('print_institute');
        $name = $hasName ? trim((string) $request->print_name) : null;
        $institute = $hasInstitute ? trim((string) $request->print_institute) : null;

        $record = BadgeNameOverride::where('entity_type', $type)->where('entity_id', $id)->first();

        // Resolve the final effective value for each field:
        //  - if the field was sent, use the new value (empty string → null = clear override)
        //  - if the field was NOT sent, keep whatever is already stored
        $effectiveName = $hasName ? ($name !== '' ? $name : null) : ($record?->print_name);
        $effectiveInst = $hasInstitute ? ($institute !== '' ? $institute : null) : ($record?->print_institute);

        if ($effectiveName === null && $effectiveInst === null) {
            $record?->delete();
        } else {
            $values = ['updated_by' => \Illuminate\Support\Facades\Auth::id()];
            if ($effectiveName !== null) {
                $values['print_name'] = $effectiveName;
            }
            if ($effectiveInst !== null) {
                $values['print_institute'] = $effectiveInst;
            }
            // Explicitly set to NULL when clearing a field (column is nullable)
            if ($hasName && $effectiveName === null) {
                $values['print_name'] = null;
            }
            if ($hasInstitute && $effectiveInst === null) {
                $values['print_institute'] = null;
            }

            BadgeNameOverride::updateOrCreate(
                ['entity_type' => $type, 'entity_id' => $id],
                $values
            );
        }

        return response()->json([
            'success' => true,
            'print_name' => $effectiveName,
            'print_institute' => $effectiveInst,
        ]);
    }

    public function onsiteUpdate(Request $request, OnsiteVisitor $visitor)
    {
        $validated = $request->validate([
            'print_name' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
        ]);

        $visitor->update($validated);

        return response()->json([
            'success' => true,
            'effective_name' => $visitor->effective_name,
            'initials' => $visitor->initials,
            'print_url' => route('registration.onsite.print-badge', $visitor),
        ]);
    }

    public function onsitePrint(OnsiteVisitor $visitor)
    {
        $token = $visitor->getOrCreateQrToken();
        $visitor->markBadgePrinted();
        \App\Models\BadgePrintLog::record('onsite_visitor', $visitor->id, $visitor->effective_name ?? $visitor->name, $visitor->institution, $visitor->badge_category ?? null);

        $qrImage = $this->generateQrCodeBase64($this->qrPayloadForToken($token));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('registration.badge.onsite-print', [
            'visitor' => $visitor,
            'qrImage' => $qrImage,
            'conference' => [
                'name' => config('conference.name'),
                'short_name' => config('conference.short_name'),
                'edition' => config('conference.edition'),
                'year' => config('conference.year'),
                'display_dates' => config('conference.display_dates'),
                'venue' => config('conference.venue'),
                'city' => config('conference.city'),
                'country' => config('conference.country'),
                'host' => config('conference.host'),
            ],
        ]);

        $pdf->getDomPDF()->setPaper($this->badgePaperSize());

        return $pdf->stream('badge-onsite-'.$visitor->id.'.pdf');
    }

    // =========================================================================
    // QUALITY MANAGEMENT
    // =========================================================================

    public function qualityScan(Request $request)
    {
        $quality = app(BadgeDataQualityService::class);

        // Pre-print gate: check a set of selected attendees in-memory
        $userIds = $request->input('user_ids', []);
        $groupMemberIds = $request->input('group_member_ids', []);

        $flagged = [];

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            $name = trim(($user->title ? $user->title.' ' : '').$user->first_name.' '.$user->last_name);
            $institution = $user->institute ?: $user->affiliation;
            $flags = $quality->quickCheck($name, $institution);
            if (! empty($flags)) {
                $flagged[] = [
                    'type' => 'user',
                    'id' => $user->id,
                    'name' => $name,
                    'institution' => $institution,
                    'email' => $user->email,
                    'flags' => $flags,
                    'worst' => $quality->worstSeverity($flags),
                ];
            }
        }

        foreach (GroupMember::whereIn('id', $groupMemberIds)->get() as $member) {
            $flags = $quality->quickCheck($member->full_name, $member->institution);
            if (! empty($flags)) {
                $flagged[] = [
                    'type' => 'group_member',
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'institution' => $member->institution,
                    'email' => $member->email,
                    'flags' => $flags,
                    'worst' => $quality->worstSeverity($flags),
                ];
            }
        }

        return response()->json([
            'flagged_count' => count($flagged),
            'records' => $flagged,
        ]);
    }

}
