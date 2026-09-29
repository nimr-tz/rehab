<?php

namespace App\Http\Controllers;

use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\User;
use App\Models\Attendance;
use App\Models\ConferenceSession;
use App\Models\InvitationLetter;
use App\Models\GroupRegistration;
use App\Models\GroupMember;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Executive Command Center Dashboard
 *
 * Provides strategic overview for conference leadership:
 * - Management & Conference Director
 * - Scientific Committee Chair
 * - Logistics & Registration Officers
 * - Finance Team
 *
 * Covers both pre-conference and during-conference phases.
 */
class ExecutiveDashboardController extends Controller
{
    /**
     * Main Executive Dashboard - Command Center View
     */
    public function index()
    {
        // Determine conference phase
        $conferenceStartDate = Carbon::parse(config('conference.start_date', '2026-06-09'));
        $conferenceEndDate = Carbon::parse(config('conference.end_date', '2026-06-11'));
        $submissionDeadline = Carbon::parse(config('conference.submission_deadline', '2026-04-15'));
        $today = now();

        $phase = $this->determineConferencePhase($today, $conferenceStartDate, $conferenceEndDate);

        // Get all metrics (executive-focused: finance, country/institute, reviewers, conference headcount)
        $metrics = [
            'management' => $this->getManagementMetrics(),
            'finance' => $this->getFinanceMetrics(),
            'registrations' => $this->getRegistrationMetrics(),
            'submissions_by_country' => $this->getSubmissionsByCountry(),
            'registrations_by_country' => $this->getRegistrationsByCountry(),
            'submissions_by_institute' => $this->getSubmissionsByInstitute(),
            'registrations_by_institute' => $this->getRegistrationsByInstitute(),
            'reviewers' => $this->getReviewerMetrics(),
            'conference' => $this->getConferenceHeadcount(),
            'sessions' => $this->getSessionMetrics(),
            'attendance' => $this->getAttendanceMetrics(),
            'alerts' => $this->getAlerts(),
            'timeline' => $this->getTimelineMetrics($submissionDeadline, $conferenceStartDate),
        ];
        $metrics['reviewers']['assigned_to_conference'] = AbstractSubmission::whereNotIn('status', ['draft'])
            ->get()
            ->flatMap(fn ($a) => array_filter([$a->reviewer_id, $a->reviewer_2_id]))
            ->unique()
            ->count();

        return view('executive.dashboard', [
            'phase' => $phase,
            'metrics' => $metrics,
            'conferenceStartDate' => $conferenceStartDate,
            'conferenceEndDate' => $conferenceEndDate,
            'submissionDeadline' => $submissionDeadline,
            'conferenceName' => config('conference.name', config('conference.short_name') . ' ' . config('conference.year')),
            'conferenceEdition' => config('conference.edition', '33rd'),
        ]);
    }

    /**
     * Executive summary metrics focused on registrations, abstracts, and student mix.
     */
    private function getManagementMetrics(): array
    {
        $individualRegistrants = User::whereNotNull('registration_category')->count();
        $groupRegistrants = GroupMember::count();
        $allRegistered = $individualRegistrants + $groupRegistrants;

        $individualPaid = User::whereNotNull('registration_category')
            ->whereIn('payment_status', ['verified', 'waived'])
            ->count();
        $groupPaid = GroupMember::whereHas('groupRegistration', function ($query) {
            $query->whereIn('payment_status', ['verified', 'waived']);
        })->count();
        $allRegisteredPaid = $individualPaid + $groupPaid;

        $registeredWithAbstracts = User::whereNotNull('registration_category')
            ->whereHas('abstractSubmissions', function ($query) {
                $query->where('status', '!=', 'draft');
            })
            ->count();

        $totalAbstracts = AbstractSubmission::where('status', '!=', 'draft')->count();

        $registrationStudents = User::where('registration_category', 'like', 'student_%')->count()
            + GroupMember::where('registration_category', 'like', 'student_%')->count();
        $registrationNormal = $allRegistered - $registrationStudents;

        $abstractStudents = AbstractSubmission::where('status', '!=', 'draft')
            ->whereHas('user', function ($query) {
                $query->where('registration_category', 'like', 'student_%');
            })
            ->count();
        $abstractNormal = $totalAbstracts - $abstractStudents;

        return [
            'all_registered' => $allRegistered,
            'all_registered_paid' => $allRegisteredPaid,
            'registered_with_abstracts' => $registeredWithAbstracts,
            'total_abstracts' => $totalAbstracts,
            'registration_students' => $registrationStudents,
            'registration_normal' => $registrationNormal,
            'abstract_students' => $abstractStudents,
            'abstract_normal' => $abstractNormal,
            'registration_student_percent' => $allRegistered > 0 ? round(($registrationStudents / $allRegistered) * 100, 1) : 0,
            'abstract_student_percent' => $totalAbstracts > 0 ? round(($abstractStudents / $totalAbstracts) * 100, 1) : 0,
            'paid_conversion_percent' => $allRegistered > 0 ? round(($allRegisteredPaid / $allRegistered) * 100, 1) : 0,
            'abstract_conversion_percent' => $allRegistered > 0 ? round(($registeredWithAbstracts / $allRegistered) * 100, 1) : 0,
        ];
    }

    /**
     * Determine the current conference phase
     */
    private function determineConferencePhase($today, $conferenceStart, $conferenceEnd): string
    {
        if ($today->lt($conferenceStart->copy()->subDays(7))) {
            return 'pre_conference'; // More than 7 days before
        } elseif ($today->lt($conferenceStart)) {
            return 'final_preparations'; // Within 7 days before
        } elseif ($today->between($conferenceStart, $conferenceEnd)) {
            return 'during_conference';
        } else {
            return 'post_conference';
        }
    }

    /**
     * High-level overview metrics
     */
    private function getOverviewMetrics(): array
    {
        $totalParticipants = User::count();

        $totalAbstracts = AbstractSubmission::count();
        $totalReviews = AbstractReview::where('status', 'submitted')->count();

        return [
            'total_users' => $totalParticipants,
            'total_abstracts' => $totalAbstracts,
            'total_reviews' => $totalReviews,
            'total_sessions' => ConferenceSession::where('is_active', true)->count(),
        ];
    }

    /**
     * Submission pipeline metrics
     */
    private function getSubmissionMetrics(): array
    {
        $total = 0;
        $abstracts = [];
        $submissionsLast30Days = 0;
        $submissionTrend = 0;

        try {
            $abstracts = AbstractSubmission::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $total = array_sum($abstracts);

            // Submission trends (last 30 days)
            $submissionsLast30Days = AbstractSubmission::where('created_at', '>=', now()->subDays(30))->count();
            $submissionsPrev30Days = AbstractSubmission::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count();

            $submissionTrend = $submissionsPrev30Days > 0
                ? round((($submissionsLast30Days - $submissionsPrev30Days) / $submissionsPrev30Days) * 100, 1)
                : 0;
        } catch (\Exception $e) {
            Log::warning('Executive dashboard submission metrics failed: ' . $e->getMessage());
        }

        return [
            'total' => $total,
            'by_status' => [
                'draft' => $abstracts['draft'] ?? 0,
                'submitted' => $abstracts['submitted'] ?? 0,
                'under_review' => $abstracts['under_review'] ?? 0,
                'reviewed' => $abstracts['reviewed'] ?? 0,
                'revision_required' => ($abstracts['revision'] ?? 0) + ($abstracts['revision_required'] ?? 0) + ($abstracts['revision_requested'] ?? 0),
                'accepted' => $abstracts['accepted'] ?? 0,
                'rejected' => $abstracts['rejected'] ?? 0,
            ],
            'by_type' => [],
            'by_subtheme' => [],
            'submissions_last_30_days' => $submissionsLast30Days,
            'submission_trend' => $submissionTrend,
            'acceptance_rate' => $total > 0
                ? round((($abstracts['accepted'] ?? 0) / max(1, ($abstracts['accepted'] ?? 0) + ($abstracts['rejected'] ?? 0))) * 100, 1)
                : 0,
        ];
    }

    /**
     * Review process metrics
     */
    private function getReviewMetrics(): array
    {
        $totalReviewsNeeded = 0;
        $completedReviews = 0;
        $awaitingBothReviewers = 0;
        $awaitingOneReviewer = 0;
        $overdueReviews = 0;
        $avgReviewTime = 0;
        $avgScore = 0;
        $scoreDistribution = [];

        try {
            $totalReviewsNeeded = AbstractSubmission::whereIn('status', ['submitted', 'under_review'])->count() * 2;
            $completedReviews = AbstractReview::where('status', 'submitted')->count();

            // Overdue reviews (under review for more than 14 days)
            $overdueReviews = AbstractSubmission::where('status', 'under_review')
                ->where('updated_at', '<', now()->subDays(14))
                ->count();

            // Average review time
            $avgReviewTime = AbstractReview::where('status', 'submitted')
                ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
                ->value('avg_days') ?? 0;

            // Average score
            $avgScore = AbstractReview::where('status', 'submitted')
                ->whereNotNull('overall_score')
                ->avg('overall_score') ?? 0;
        } catch (\Exception $e) {
            Log::warning('Executive dashboard review metrics failed: ' . $e->getMessage());
        }

        return [
            'total_needed' => $totalReviewsNeeded,
            'completed' => $completedReviews,
            'progress_percent' => $totalReviewsNeeded > 0 ? round(($completedReviews / $totalReviewsNeeded) * 100, 1) : 0,
            'awaiting_assignment' => 0,
            'awaiting_both' => 0,
            'awaiting_one' => 0,
            'overdue' => $overdueReviews,
            'avg_review_time_days' => round($avgReviewTime, 1),
            'avg_score' => round($avgScore, 1),
            'score_distribution' => $scoreDistribution,
        ];
    }

    /**
     * Reviewer workload and performance metrics
     */
    private function getReviewerMetrics(): array
    {
        // Get all reviewers
        $reviewers = User::whereHas('roles', function($q) {
            $q->where('name', 'reviewer');
        })->get();

        $totalReviewers = $reviewers->count();

        // Active reviewers (have completed at least one review)
        $activeReviewers = User::whereHas('roles', function($q) {
            $q->where('name', 'reviewer');
        })->whereHas('reviews', function($q) {
            $q->where('status', 'submitted');
        })->count();

        // Reviewers with pending work (assigned abstracts under review)
        $reviewersWithPending = User::whereHas('roles', function($q) {
            $q->where('name', 'reviewer');
        })->whereHas('reviews', function($q) {
            $q->where('status', '!=', 'submitted');
        })->count();

        // Inactive reviewers (have pending reviews, not submitted)
        // We'll use a simpler approach since last_login_at might not exist
        $inactiveReviewers = User::whereHas('roles', function($q) {
            $q->where('name', 'reviewer');
        })->whereHas('reviews', function($q) {
            $q->where('status', 'draft')
              ->where('created_at', '<', now()->subDays(7));
        })->count();

        // Top performers (most completed reviews)
        $topPerformers = DB::table('abstract_reviews')
            ->select('reviewer_id', DB::raw('COUNT(*) as completed_count'))
            ->where('status', 'submitted')
            ->groupBy('reviewer_id')
            ->orderByDesc('completed_count')
            ->limit(5)
            ->get()
            ->map(function($item) {
                $user = User::find($item->reviewer_id);
                return [
                    'name' => $user ? $user->full_name : 'Unknown',
                    'count' => $item->completed_count,
                ];
            });

        // Workload distribution
        $workloadDistribution = [
            'overloaded' => 0, // 5+ pending
            'normal' => 0,    // 2-4 pending
            'light' => 0,     // 0-1 pending
        ];

        foreach ($reviewers as $reviewer) {
            $pending = AbstractSubmission::where('status', 'under_review')
                ->where(function($q) use ($reviewer) {
                    $q->where('reviewer_id', $reviewer->id)
                      ->orWhere('reviewer_2_id', $reviewer->id);
                })
                ->count();

            if ($pending >= 5) {
                $workloadDistribution['overloaded']++;
            } elseif ($pending >= 2) {
                $workloadDistribution['normal']++;
            } else {
                $workloadDistribution['light']++;
            }
        }

        return [
            'total' => $totalReviewers,
            'active' => $activeReviewers,
            'with_pending' => $reviewersWithPending,
            'inactive_with_pending' => $inactiveReviewers,
            'top_performers' => $topPerformers,
            'workload_distribution' => $workloadDistribution,
        ];
    }

    /**
     * Registration and payment metrics
     */
    private function getRegistrationMetrics(): array
    {
        // Safe counts that don't depend on payment columns
        $totalProfessional = User::count();

        $registrationsLast7Days = User::where('created_at', '>=', now()->subDays(7))->count();
        $registrationsPrev7Days = User::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();

        // Try to get payment-related metrics if the columns exist
        $paidUsers = 0;
        $pendingPayment = 0;
        try {
            $paidUsers = User::where('payment_status', 'verified')->count();
            $pendingPayment = User::where('payment_status', 'submitted')->count();
        } catch (\Exception $e) {
            // Payment columns may not exist yet
        }

        // Group registrations (if table exists)
        $groupRegistrations = 0;
        $paidGroups = 0;
        try {
            $groupRegistrations = GroupRegistration::count();
            $paidGroups = GroupRegistration::where('payment_status', 'verified')->count();
        } catch (\Exception $e) {
            // Group registration table may not exist
        }

        // Invitation letters (if table exists)
        $invitationsPending = 0;
        $invitationsApproved = 0;
        try {
            $invitationsPending = InvitationLetter::where('status', 'pending')->count();
            $invitationsApproved = InvitationLetter::where('status', 'approved')->count();
        } catch (\Exception $e) {
            // Invitation letters table may not exist
        }

        return [
            'total_users' => $totalProfessional,
            'paid_users' => $paidUsers,
            'pending_payment_verification' => $pendingPayment,
            'group_registrations' => $groupRegistrations,
            'paid_groups' => $paidGroups,
            'registrations_last_7_days' => $registrationsLast7Days,
            'registration_trend' => $registrationsPrev7Days > 0
                ? round((($registrationsLast7Days - $registrationsPrev7Days) / $registrationsPrev7Days) * 100, 1)
                : 0,
            'by_category' => [],
            'invitations_pending' => $invitationsPending,
            'invitations_approved' => $invitationsApproved,
        ];
    }

    /**
     * Finance: paid count, unpaid, expected when paid, received (from verified)
     */
    private function getFinanceMetrics(): array
    {
        // 1. Headcounts
        $verifiedIndividuals = User::where('payment_status', 'verified')->count();
        $waivedIndividuals = User::where('payment_status', 'waived')->count();
        
        $unpaidIndividuals = User::whereNotIn('payment_status', ['verified', 'waived'])
            ->whereNotNull('registration_category')
            ->count();

        $groupMembersVerified = GroupMember::whereHas('groupRegistration', fn ($q) => $q->where('payment_status', 'verified'))->count();
        $groupMembersWaived = GroupMember::whereHas('groupRegistration', fn ($q) => $q->where('payment_status', 'waived'))->count();
        $groupMembersUnpaid = GroupMember::whereHas('groupRegistration', fn ($q) => $q->whereNotIn('payment_status', ['verified', 'waived']))->count();

        // 2. Revenue (Only verified - exclude waived from cash totals)
        $receivedTzs = 0;
        $receivedUsd = 0;
        
        // Individuals (Verified ONLY)
        foreach (User::where('payment_status', 'verified')->get() as $u) {
            $fee = $u->getRegistrationFee();
            if (($fee['currency'] ?? '') === 'TZS') {
                $receivedTzs += (float) ($fee['amount'] ?? 0);
            } else {
                $receivedUsd += (float) ($fee['amount'] ?? 0);
            }
        }

        // Groups (Verified ONLY)
        $verifiedGroups = GroupRegistration::where('payment_status', 'verified')->get();
        foreach ($verifiedGroups as $gr) {
            foreach ($gr->members as $m) {
                if (($m->fee_currency ?? '') === 'TZS') {
                    $receivedTzs += (float) ($m->fee_amount ?? 0);
                } else {
                    $receivedUsd += (float) ($m->fee_amount ?? 0);
                }
            }
        }

        // 3. Expected (Total Potential)
        $expectedIfAllPaidTzs = 0;
        $expectedIfAllPaidUsd = 0;
        foreach (User::whereNotNull('registration_category')->get() as $u) {
            $fee = $u->getRegistrationFee();
            if (($fee['currency'] ?? '') === 'TZS') {
                $expectedIfAllPaidTzs += (float) ($fee['amount'] ?? 0);
            } else {
                $expectedIfAllPaidUsd += (float) ($fee['amount'] ?? 0);
            }
        }
        foreach (GroupMember::all() as $m) {
            if (($m->fee_currency ?? '') === 'TZS') {
                $expectedIfAllPaidTzs += (float) ($m->fee_amount ?? 0);
            } else {
                $expectedIfAllPaidUsd += (float) ($m->fee_amount ?? 0);
            }
        }

        return [
            'paid_count' => $verifiedIndividuals + $groupMembersVerified,
            'waived_count' => $waivedIndividuals + $groupMembersWaived,
            'unpaid_count' => $unpaidIndividuals + $groupMembersUnpaid,
            'received_tzs' => round($receivedTzs, 0),
            'received_usd' => round($receivedUsd, 2),
            'expected_if_all_paid_tzs' => round($expectedIfAllPaidTzs, 0),
            'expected_if_all_paid_usd' => round($expectedIfAllPaidUsd, 2),
        ];
    }

    /**
     * Submissions (non-draft) by country (author country from user)
     */
    private function getSubmissionsByCountry(): \Illuminate\Support\Collection
    {
        $rows = collect(DB::select(
            "SELECT COALESCE(NULLIF(TRIM(COALESCE(u.country, '')), ''), 'Not specified') as name, COUNT(*) as count
             FROM abstract_submissions a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.status != 'draft'
             GROUP BY 1 ORDER BY count DESC"
        ));

        if ($rows->count() <= 20) {
            return $rows;
        }

        $top = $rows->take(19);
        $othersCount = $rows->slice(19)->sum('count');

        return $top->push((object)[
            'name' => 'Other Countries (' . ($rows->count() - 19) . ')',
            'count' => $othersCount
        ]);
    }

    /**
     * Registrations (paid) by country: individual users + group members
     */
    private function getRegistrationsByCountry(): \Illuminate\Support\Collection
    {
        $fromUsers = collect(DB::select(
            "SELECT COALESCE(NULLIF(TRIM(country), ''), 'Not specified') as name, COUNT(*) as count
             FROM users WHERE registration_category IS NOT NULL GROUP BY 1"
        ))->keyBy('name');

        $fromGroups = collect(DB::select(
            "SELECT COALESCE(NULLIF(TRIM(gm.country), ''), 'Not specified') as name, COUNT(*) as count
             FROM group_members gm
             GROUP BY 1"
        ))->keyBy('name');

        $merged = $fromUsers->keys()->merge($fromGroups->keys())->unique();
        $rows = $merged->map(fn ($name) => (object)[
            'name' => $name,
            'count' => (int) (isset($fromUsers[$name]) ? $fromUsers[$name]->count : 0) + (int) (isset($fromGroups[$name]) ? $fromGroups[$name]->count : 0),
        ])->sortByDesc('count')->values();

        if ($rows->count() <= 20) {
            return $rows;
        }

        $top = $rows->take(19);
        $othersCount = $rows->slice(19)->sum('count');

        return $top->push((object)[
            'name' => 'Other Countries (' . ($rows->count() - 19) . ')',
            'count' => $othersCount
        ]);
    }

    /**
     * Submissions by institute (free text – abbreviations/spelling may vary)
     */
    private function getSubmissionsByInstitute(): \Illuminate\Support\Collection
    {
        $rows = collect(DB::select(
            "SELECT COALESCE(NULLIF(TRIM(author_institute), ''), 'Not specified') as name, COUNT(*) as count
             FROM abstract_submissions WHERE status != 'draft' GROUP BY 1 ORDER BY count DESC"
        ));

        if ($rows->count() <= 15) {
            return $rows;
        }

        $top = $rows->take(14);
        $othersCount = $rows->slice(14)->sum('count');

        return $top->push((object)[
            'name' => 'Other Institutes (' . ($rows->count() - 14) . ')',
            'count' => $othersCount
        ]);
    }

    /**
     * Registrations by institute (affiliation/institution – free text)
     */
    private function getRegistrationsByInstitute(): \Illuminate\Support\Collection
    {
        $fromUsers = collect(DB::select(
            "SELECT COALESCE(NULLIF(TRIM(affiliation), ''), 'Not specified') as name, COUNT(*) as count
             FROM users WHERE registration_category IS NOT NULL GROUP BY 1"
        ))->keyBy('name');

        $fromGroups = collect(DB::select(
            "SELECT COALESCE(NULLIF(TRIM(gm.institution), ''), 'Not specified') as name, COUNT(*) as count
             FROM group_members gm
             GROUP BY 1"
        ))->keyBy('name');

        $merged = $fromUsers->keys()->merge($fromGroups->keys())->unique();
        $rows = $merged->map(fn ($name) => (object)[
            'name' => $name,
            'count' => (int) (isset($fromUsers[$name]) ? $fromUsers[$name]->count : 0) + (int) (isset($fromGroups[$name]) ? $fromGroups[$name]->count : 0),
        ])->sortByDesc('count')->values();

        if ($rows->count() <= 15) {
            return $rows;
        }

        $top = $rows->take(14);
        $othersCount = $rows->slice(14)->sum('count');

        return $top->push((object)[
            'name' => 'Other Institutes (' . ($rows->count() - 14) . ')',
            'count' => $othersCount
        ]);
    }

    /**
     * Conference headcount: total registered (paid), accepted abstracts, sessions
     */
    private function getConferenceHeadcount(): array
    {
        $registeredPaid = User::whereIn('payment_status', ['verified', 'waived'])->count()
            + GroupMember::whereHas('groupRegistration', fn ($q) => $q->whereIn('payment_status', ['verified', 'waived']))->count();
        $acceptedAbstracts = AbstractSubmission::where('status', 'accepted')->count();
        $sessionsPlanned = ConferenceSession::where('is_active', true)->count();

        return [
            'registered_paid' => $registeredPaid,
            'accepted_abstracts' => $acceptedAbstracts,
            'sessions_planned' => $sessionsPlanned,
        ];
    }

    /**
     * Session and program metrics
     */
    private function getSessionMetrics(): array
    {
        $sessions = ConferenceSession::where('is_active', true)->get();

        $totalSessions = $sessions->count();
        $scheduledSessions = $sessions->where('status', 'scheduled')->count();

        // Session capacity
        $totalCapacity = $sessions->sum('max_abstracts');
        $usedCapacity = $sessions->sum('current_abstracts');

        // Sessions by type
        $byType = $sessions->groupBy('session_type')
            ->map(fn($group) => $group->count())
            ->toArray();

        // Unconfirmed chairs/rapporteurs
        $unconfirmedChairs = $sessions->whereNull('chair_confirmed_at')
            ->whereNotNull('session_chair_id')
            ->count();
        $unconfirmedRapporteurs = $sessions->whereNull('rapporteur_confirmed_at')
            ->whereNotNull('session_rapporteur_id')
            ->count();

        return [
            'total' => $totalSessions,
            'scheduled' => $scheduledSessions,
            'capacity_total' => $totalCapacity,
            'capacity_used' => $usedCapacity,
            'capacity_percent' => $totalCapacity > 0 ? round(($usedCapacity / $totalCapacity) * 100, 1) : 0,
            'by_type' => $byType,
            'unconfirmed_chairs' => $unconfirmedChairs,
            'unconfirmed_rapporteurs' => $unconfirmedRapporteurs,
        ];
    }

    /**
     * Attendance metrics (during conference)
     */
    private function getAttendanceMetrics(): array
    {
        $totalDays = config('conference.total_days', 3);
        $byDay = [];

        try {
            for ($day = 1; $day <= $totalDays; $day++) {
                $byDay[$day] = Attendance::where('day', $day)->count();
            }
        } catch (\Exception $e) {
            for ($day = 1; $day <= $totalDays; $day++) {
                $byDay[$day] = 0;
            }
        }

        // Today's attendance (calculate current day)
        $conferenceStart = Carbon::parse(config('conference.start_date', '2026-06-09'));
        $currentDay = max(1, min($totalDays, (int)now()->diffInDays($conferenceStart) + 1));

        $todayAttendance = 0;
        $uniqueAttendees = 0;
        $hourlyCheckins = [];

        try {
            $todayAttendance = Attendance::where('day', $currentDay)->count();
            $uniqueAttendees = Attendance::distinct('user_id')->count('user_id');

            // Check-in rate by hour (if during conference)
            $hourlyCheckins = Attendance::whereDate('checked_in_at', today())
                ->selectRaw('HOUR(checked_in_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->pluck('count', 'hour')
                ->toArray();
        } catch (\Exception $e) {
            // Attendance table may not have expected columns
        }

        // Safe count for expected attendees
        $expectedAttendees = 0;
        try {
            $expectedAttendees = User::where('payment_status', 'verified')->count();
        } catch (\Exception $e) {
            $expectedAttendees = User::count(); // Fallback to total users
        }

        return [
            'by_day' => $byDay,
            'current_day' => $currentDay,
            'today' => $todayAttendance,
            'hourly_checkins' => $hourlyCheckins,
            'unique_attendees' => $uniqueAttendees,
            'expected_attendees' => $expectedAttendees,
        ];
    }

    /**
     * Generate alerts for issues needing attention
     */
    private function getAlerts(): array
    {
        $alerts = [];

        try {
            // Overdue reviews (abstracts under review for more than 14 days)
            $overdueReviews = AbstractSubmission::where('status', 'under_review')
                ->where('updated_at', '<', now()->subDays(14))
                ->count();
            if ($overdueReviews > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'reviews',
                    'title' => 'Overdue Reviews',
                    'message' => "{$overdueReviews} abstract(s) have been under review for more than 14 days",
                    'action_url' => route('admin.abstracts.index', ['status' => 'under_review']),
                    'action_label' => 'View Abstracts',
                ];
            }

            // Abstracts awaiting decision
            $awaitingDecision = AbstractSubmission::where('status', 'reviewed')->count();
            if ($awaitingDecision > 5) {
                $alerts[] = [
                    'type' => 'info',
                    'category' => 'decisions',
                    'title' => 'Decision Queue',
                    'message' => "{$awaitingDecision} abstracts are reviewed and waiting for final decision",
                    'action_url' => route('admin.decisions.index'),
                    'action_label' => 'Make Decisions',
                ];
            }

            // Reviewers with draft reviews older than 7 days
            $pendingReviewers = User::whereHas('roles', function($q) {
                $q->where('name', 'reviewer');
            })->whereHas('reviews', function($q) {
                $q->where('status', 'draft')
                  ->where('created_at', '<', now()->subDays(7));
            })->count();
            if ($pendingReviewers > 0) {
                $alerts[] = [
                    'type' => 'error',
                    'category' => 'reviewers',
                    'title' => 'Pending Reviews',
                    'message' => "{$pendingReviewers} reviewer(s) have draft reviews pending for more than 7 days",
                    'action_url' => route('admin.users'),
                    'action_label' => 'Manage Reviewers',
                ];
            }

            // Unassigned accepted abstracts (no session)
            $unassignedAccepted = AbstractSubmission::where('status', 'accepted')
                ->whereNull('session_id')
                ->count();
            if ($unassignedAccepted > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'program',
                    'title' => 'Unscheduled Abstracts',
                    'message' => "{$unassignedAccepted} accepted abstract(s) not yet assigned to sessions",
                    'action_url' => route('admin.conference-program.index'),
                    'action_label' => 'Assign to Sessions',
                ];
            }
        } catch (\Exception $e) {
            // If any query fails, just return empty alerts
            Log::warning('Executive dashboard alerts failed: ' . $e->getMessage());
        }

        return $alerts;
    }

    /**
     * Get trend data for charts
     */
    private function getTrends(): array
    {
        // Daily submissions for the last 30 days
        $submissionTrends = AbstractSubmission::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Daily registrations for the last 30 days
        $registrationTrends = User::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Reviews completed per day
        $reviewTrends = AbstractReview::where('status', 'submitted')
            ->where('submitted_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(submitted_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'submissions' => $submissionTrends,
            'registrations' => $registrationTrends,
            'reviews' => $reviewTrends,
        ];
    }

    /**
     * Timeline and deadline metrics
     */
    private function getTimelineMetrics($submissionDeadline, $conferenceStartDate): array
    {
        $today = now();

        return [
            'days_to_submission_deadline' => (int) max(0, floor($today->diffInDays($submissionDeadline, false))),
            'days_to_conference' => (int) max(0, floor($today->diffInDays($conferenceStartDate, false))),
            'submission_deadline_passed' => $today->gt($submissionDeadline),
            'conference_started' => $today->gte($conferenceStartDate),
        ];
    }

    /**
     * API endpoint for real-time data refresh
     */
    public function refreshMetrics()
    {
        return response()->json([
            'management' => $this->getManagementMetrics(),
            'finance' => $this->getFinanceMetrics(),
            'overview' => $this->getOverviewMetrics(),
            'submissions' => $this->getSubmissionMetrics(),
            'reviews' => $this->getReviewMetrics(),
            'attendance' => $this->getAttendanceMetrics(),
            'alerts' => $this->getAlerts(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
