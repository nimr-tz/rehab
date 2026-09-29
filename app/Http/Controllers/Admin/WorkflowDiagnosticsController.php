<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractReview;
use App\Models\AbstractSubmission;
use App\Models\EmailLog;
use App\Models\RevisionHistory;
use App\Models\User;
use App\Services\ReviewAssignmentService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use App\Services\AbstractStatusService;

class WorkflowDiagnosticsController extends Controller
{
    public function index()
    {
        $acceptRecommendations = ['accept', 'accept_oral', 'accept_poster'];

        $abstracts = AbstractSubmission::with([
            'user:id,first_name,last_name,email',
            'reviews' => function ($query) {
                $query->where('status', 'submitted')
                    ->orderByDesc('review_round')
                    ->orderByDesc('submitted_at')
                    ->orderByDesc('id');
            },
        ])
            ->where('status', '!=', 'draft')
            ->where(function ($query) {
                $query->whereNotNull('reviewer_id')
                    ->orWhereNotNull('reviewer_2_id');
            })
            ->get();

        $emailLogsByAbstract = EmailLog::whereIn('abstract_submission_id', $abstracts->pluck('id'))
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('abstract_submission_id');

        $summary = [
            'scanned_abstracts' => $abstracts->count(),
            'under_review_round0' => 0,
            'fully_reviewed_under_review' => 0,
            'unanimous_current_round_accepts' => 0,
            'stuck_unanimous_accepts' => 0,
            'revision_workflow_drift' => 0,
            'assignment_status_drift' => 0,
            'email_status_drift' => 0,
            'accepted_without_email' => 0,
        ];

        $fullyReviewedUnderReview = collect();
        $stuckUnanimousAccepts = collect();
        $revisionWorkflowDrift = collect();
        $assignmentStatusDrift = collect();
        $emailStatusDrift = collect();
        $acceptedWithoutEmail = collect();
        $staleBulkAcceptEligible = collect();
        $staleQuickDecision = collect();

        foreach ($abstracts as $abstract) {
            $assignedReviewerIds = collect($abstract->assigned_reviewer_ids);

            if ($abstract->status === 'under_review' && (($abstract->revision_round ?? 0) === 0)) {
                $summary['under_review_round0']++;
            }

            if ($assignedReviewerIds->isEmpty()) {
                continue;
            }

            $latestReviews = $this->getLatestReviewsForAssignedReviewers($abstract->reviews, $assignedReviewerIds);
            $effectiveCurrentReviews = $latestReviews->filter(fn (AbstractReview $review) => $abstract->isSubmittedReviewInEffectiveCurrentRound($review))->values();

            $hasAllAssignedLatestReviews = $latestReviews->count() === $assignedReviewerIds->count();
            $allCurrentRound = $hasAllAssignedLatestReviews
                && $effectiveCurrentReviews->count() === $assignedReviewerIds->count();
            $allAccepted = $hasAllAssignedLatestReviews
                && $effectiveCurrentReviews->every(fn (AbstractReview $review) => in_array(strtolower((string) $review->recommendation), $acceptRecommendations, true));

            $acceptanceLogs = $this->getAcceptanceLogs($emailLogsByAbstract->get($abstract->id, collect()));
            $hasAcceptanceEmail = $acceptanceLogs->isNotEmpty();

            if ($allCurrentRound && $allAccepted) {
                $summary['unanimous_current_round_accepts']++;
            }

            $row = $this->buildDiagnosticRow($abstract, $latestReviews, $effectiveCurrentReviews, $acceptanceLogs);
            $staleSnapshot = $this->buildStaleDecisionSnapshot($abstract, $latestReviews, $effectiveCurrentReviews);

            if ($allCurrentRound && $abstract->status === 'under_review') {
                $fullyReviewedUnderReview->push($row);
            }

            if ($allCurrentRound && $allAccepted && $abstract->status !== 'accepted') {
                $stuckUnanimousAccepts->push($row);
            }

            if ($this->isRevisionWorkflowDrift($abstract)) {
                $revisionWorkflowDrift->push($row);
            }

            if ($this->isAssignmentStatusDrift($abstract)) {
                $assignmentStatusDrift->push($row);
            }

            if ($hasAcceptanceEmail && $abstract->status !== 'accepted') {
                $emailStatusDrift->push($row);
            }

            if ($abstract->status === 'accepted' && !$hasAcceptanceEmail) {
                $acceptedWithoutEmail->push($row);
            }

            if ($staleSnapshot) {
                if ($staleSnapshot['eligible_for_bulk_accept']) {
                    $staleBulkAcceptEligible->push($staleSnapshot);
                } else {
                    $staleQuickDecision->push($staleSnapshot);
                }
            }
        }

        $summary['fully_reviewed_under_review'] = $fullyReviewedUnderReview->count();
        $summary['stuck_unanimous_accepts'] = $stuckUnanimousAccepts->count();
        $summary['revision_workflow_drift'] = $revisionWorkflowDrift->count();
        $summary['assignment_status_drift'] = $assignmentStatusDrift->count();
        $summary['email_status_drift'] = $emailStatusDrift->count();
        $summary['accepted_without_email'] = $acceptedWithoutEmail->count();
        $summary['stale_bulk_accept_eligible'] = $staleBulkAcceptEligible->count();
        $summary['stale_quick_decision'] = $staleQuickDecision->count();

        return view('admin.diagnostics.workflow', [
            'summary' => $summary,
            'fullyReviewedUnderReview' => $fullyReviewedUnderReview->sortBy('id')->values(),
            'stuckUnanimousAccepts' => $stuckUnanimousAccepts->sortBy('id')->values(),
            'revisionWorkflowDrift' => $revisionWorkflowDrift->sortBy('id')->values(),
            'assignmentStatusDrift' => $assignmentStatusDrift->sortBy('id')->values(),
            'emailStatusDrift' => $emailStatusDrift->sortBy('id')->values(),
            'acceptedWithoutEmail' => $acceptedWithoutEmail->sortBy('id')->values(),
            'staleBulkAcceptEligible' => $staleBulkAcceptEligible->sortBy('id')->values(),
            'staleQuickDecision' => $staleQuickDecision->sortBy('id')->values(),
        ]);
    }

    public function repair(AbstractSubmission $abstract, Request $request)
    {
        $progress = $abstract->getAssignedReviewProgressSummary();

        if ($abstract->status !== 'under_review' || !$progress['is_ready']) {
            return redirect()
                ->route('admin.diagnostics.workflow')
                ->with('error', "Abstract #{$abstract->id} is not currently flagged as a stale under-review record.");
        }

        $oldStatus = $abstract->status;
        $abstract->syncReviewStatus();
        $abstract->refresh();

        $message = $abstract->status !== $oldStatus
            ? "Abstract #{$abstract->id} was re-synced from {$oldStatus} to {$abstract->status}."
            : "Abstract #{$abstract->id} was checked, but its status did not change.";

        return redirect()
            ->route('admin.diagnostics.workflow')
            ->with($abstract->status !== $oldStatus ? 'success' : 'info', $message);
    }

    public function bulkRepair(Request $request, AbstractStatusService $statusService)
    {
        $acceptRecommendations = ['accept', 'accept_oral', 'accept_poster'];

        $abstracts = AbstractSubmission::with([
            'reviews' => function ($query) {
                $query->where('status', 'submitted')
                    ->orderByDesc('review_round')
                    ->orderByDesc('submitted_at')
                    ->orderByDesc('id');
            },
        ])
            ->where('status', 'under_review')
            ->where(function ($query) {
                $query->whereNotNull('reviewer_id')
                    ->whereNotNull('reviewer_2_id');
            })
            ->get();

        $checked = 0;
        $accepted = 0;
        $decisionRequired = 0;
        $unchanged = 0;

        foreach ($abstracts as $abstract) {
            $progress = $abstract->getAssignedReviewProgressSummary();
            if (!$progress['is_ready']) {
                continue;
            }

            $checked++;
            $effectiveCurrentReviews = collect($progress['effective_current_reviews'] ?? []);
            $allAccepted = $effectiveCurrentReviews->isNotEmpty()
                && $effectiveCurrentReviews->every(function (AbstractReview $review) use ($acceptRecommendations) {
                    return in_array(strtolower((string) $review->recommendation), $acceptRecommendations, true);
                });

            $oldStatus = $abstract->status;
            if ($allAccepted) {
                $result = $statusService->changeStatus($abstract, 'accepted', 'Bulk repaired from workflow diagnostics after unanimous current-round accept reviews', [
                    'review_completed_at' => $abstract->review_completed_at ?? now(),
                ]);
                if (($result['success'] ?? false) && $abstract->fresh()->status === 'accepted') {
                    $accepted++;
                } else {
                    $unchanged++;
                }
                continue;
            }

            $result = $statusService->changeStatus($abstract, 'ready_for_decision', 'Bulk repaired from workflow diagnostics after full current-round review completion', [
                'review_completed_at' => $abstract->review_completed_at ?? now(),
            ]);
            if (($result['success'] ?? false) && $abstract->fresh()->status === 'ready_for_decision') {
                $decisionRequired++;
            } else {
                $unchanged++;
            }
        }

        return redirect()
            ->route('admin.diagnostics.workflow')
            ->with(
                'success',
                "Bulk repair checked {$checked} stale records: {$accepted} moved to accepted, {$decisionRequired} moved to ready_for_decision, {$unchanged} unchanged."
            );
    }

    public function acceptFromStaleQueue(AbstractSubmission $abstract, AbstractStatusService $statusService)
    {
        $abstract->loadMissing([
            'user:id,first_name,last_name,email',
            'reviews' => function ($query) {
                $query->where('status', 'submitted')
                    ->orderByDesc('review_round')
                    ->orderByDesc('submitted_at')
                    ->orderByDesc('id');
            },
        ]);

        $assignedReviewerIds = collect($abstract->assigned_reviewer_ids);
        $latestReviews = $this->getLatestReviewsForAssignedReviewers($abstract->reviews, $assignedReviewerIds);
        $effectiveCurrentReviews = $latestReviews->filter(fn (AbstractReview $review) => $abstract->isSubmittedReviewInEffectiveCurrentRound($review))->values();
        $snapshot = $this->buildStaleDecisionSnapshot($abstract, $latestReviews, $effectiveCurrentReviews);

        if (!$snapshot) {
            return redirect()
                ->route('admin.diagnostics.workflow')
                ->with('error', "Abstract #{$abstract->id} is not currently in the stale decision queue.");
        }

        if (!$snapshot['eligible_for_bulk_accept']) {
            return redirect()
                ->route('admin.diagnostics.workflow')
                ->with('error', "Abstract #{$abstract->id} is below the 70-point stale-accept threshold.");
        }

        $result = $statusService->changeStatus(
            $abstract,
            'accepted',
            'Accepted from stale decision queue using best available review evidence',
            [
                'review_completed_at' => $abstract->review_completed_at ?? now(),
                'admin_comment' => trim(($abstract->admin_comment ? $abstract->admin_comment . "\n\n" : '') . 'Accepted from stale decision queue after review recycling was detected.'),
            ]
        );

        return redirect()
            ->route('admin.diagnostics.workflow')
            ->with(($result['success'] ?? false) ? 'success' : 'error', ($result['success'] ?? false)
                ? "Abstract #{$abstract->id} accepted from stale decision queue."
                : ($result['message'] ?? "Could not accept abstract #{$abstract->id}." ));
    }

    public function bulkAcceptStaleEligible(Request $request, AbstractStatusService $statusService)
    {
        $abstracts = AbstractSubmission::with([
            'user:id,first_name,last_name,email',
            'reviews' => function ($query) {
                $query->where('status', 'submitted')
                    ->orderByDesc('review_round')
                    ->orderByDesc('submitted_at')
                    ->orderByDesc('id');
            },
        ])
            ->whereNotIn('status', ['draft', 'accepted', 'rejected'])
            ->get();

        $checked = 0;
        $accepted = 0;
        $unchanged = 0;

        foreach ($abstracts as $abstract) {
            $assignedReviewerIds = collect($abstract->assigned_reviewer_ids);
            $latestReviews = $this->getLatestReviewsForAssignedReviewers($abstract->reviews, $assignedReviewerIds);
            $effectiveCurrentReviews = $latestReviews->filter(fn (AbstractReview $review) => $abstract->isSubmittedReviewInEffectiveCurrentRound($review))->values();
            $snapshot = $this->buildStaleDecisionSnapshot($abstract, $latestReviews, $effectiveCurrentReviews);

            if (!$snapshot || !$snapshot['eligible_for_bulk_accept']) {
                continue;
            }

            $checked++;

            $result = $statusService->changeStatus(
                $abstract,
                'accepted',
                'Bulk accepted from stale decision queue using best available review evidence',
                [
                    'review_completed_at' => $abstract->review_completed_at ?? now(),
                    'admin_comment' => trim(($abstract->admin_comment ? $abstract->admin_comment . "\n\n" : '') . 'Bulk accepted from stale decision queue after review recycling was detected.'),
                ]
            );

            if (($result['success'] ?? false) && $abstract->fresh()->status === 'accepted') {
                $accepted++;
            } else {
                $unchanged++;
            }
        }

        return redirect()
            ->route('admin.diagnostics.workflow')
            ->with('success', "Bulk stale accept checked {$checked} records: {$accepted} accepted, {$unchanged} unchanged.");
    }

    public function acceptFromEmailDrift(AbstractSubmission $abstract, AbstractStatusService $statusService)
    {
        $acceptanceLogs = $this->getAcceptanceLogs(
            EmailLog::where('abstract_submission_id', $abstract->id)
                ->orderByDesc('created_at')
                ->get()
        );

        if ($acceptanceLogs->isEmpty() || $abstract->status === 'accepted') {
            return redirect()
                ->route('admin.diagnostics.workflow')
                ->with('error', "Abstract #{$abstract->id} is not currently flagged as acceptance-email/status drift.");
        }

        $result = $statusService->changeStatus(
            $abstract,
            'accepted',
            'Accepted from acceptance-email drift queue to match prior acceptance notice already sent to the author',
            [
                'review_completed_at' => $abstract->review_completed_at ?? now(),
                'admin_comment' => trim(($abstract->admin_comment ? $abstract->admin_comment . "\n\n" : '') . 'Accepted from diagnostics after confirming an acceptance email had already been sent.'),
            ],
            [
                'suppress_notifications' => true,
            ]
        );

        return redirect()
            ->route('admin.diagnostics.workflow')
            ->with(($result['success'] ?? false) ? 'success' : 'error', ($result['success'] ?? false)
                ? "Abstract #{$abstract->id} accepted without resending the acceptance email."
                : ($result['message'] ?? "Could not accept abstract #{$abstract->id}." ));
    }

    public function bulkAcceptEmailDrift(Request $request, AbstractStatusService $statusService)
    {
        $abstracts = AbstractSubmission::where('status', '!=', 'accepted')->get();
        $emailLogsByAbstract = EmailLog::whereIn('abstract_submission_id', $abstracts->pluck('id'))
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('abstract_submission_id');

        $checked = 0;
        $accepted = 0;
        $unchanged = 0;

        foreach ($abstracts as $abstract) {
            $acceptanceLogs = $this->getAcceptanceLogs($emailLogsByAbstract->get($abstract->id, collect()));
            if ($acceptanceLogs->isEmpty()) {
                continue;
            }

            $checked++;

            $result = $statusService->changeStatus(
                $abstract,
                'accepted',
                'Bulk accepted from acceptance-email drift queue to match prior author notification',
                [
                    'review_completed_at' => $abstract->review_completed_at ?? now(),
                    'admin_comment' => trim(($abstract->admin_comment ? $abstract->admin_comment . "\n\n" : '') . 'Bulk accepted from diagnostics after confirming an acceptance email had already been sent.'),
                ],
                [
                    'suppress_notifications' => true,
                ]
            );

            if (($result['success'] ?? false) && $abstract->fresh()->status === 'accepted') {
                $accepted++;
            } else {
                $unchanged++;
            }
        }

        return redirect()
            ->route('admin.diagnostics.workflow')
            ->with('success', "Bulk acceptance-email drift check processed {$checked} records: {$accepted} accepted, {$unchanged} unchanged.");
    }

    public function repairRevisionPath(AbstractSubmission $abstract, ReviewAssignmentService $assignmentService)
    {
        if (!$this->isRevisionWorkflowDrift($abstract)) {
            return redirect()
                ->route('admin.diagnostics.workflow')
                ->with('error', "Abstract #{$abstract->id} is not flagged as revision workflow drift.");
        }

        $result = $this->repairLegacyRevisionDrift($abstract, $assignmentService);

        return redirect()
            ->route('admin.diagnostics.workflow')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function bulkRepairRevisionPath(Request $request, ReviewAssignmentService $assignmentService)
    {
        $abstracts = AbstractSubmission::with('reviews')
            ->whereNotIn('status', ['accepted', 'rejected'])
            ->get()
            ->filter(fn (AbstractSubmission $abstract) => $this->isRevisionWorkflowDrift($abstract))
            ->values();

        $checked = 0;
        $repaired = 0;
        $unchanged = 0;

        foreach ($abstracts as $abstract) {
            $checked++;
            $result = $this->repairLegacyRevisionDrift($abstract, $assignmentService);
            if ($result['success']) {
                $repaired++;
            } else {
                $unchanged++;
            }
        }

        return redirect()
            ->route('admin.diagnostics.workflow')
            ->with('success', "Bulk revision-path repair checked {$checked} records: {$repaired} repaired, {$unchanged} unchanged.");
    }

    public function repairAssignmentStatus(AbstractSubmission $abstract)
    {
        if (!$this->isAssignmentStatusDrift($abstract)) {
            return redirect()
                ->route('admin.diagnostics.workflow')
                ->with('error', "Abstract #{$abstract->id} is not flagged as assignment/status drift.");
        }

        $result = $this->cleanAssignmentStatusDrift($abstract);

        return redirect()
            ->route('admin.diagnostics.workflow')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function bulkRepairAssignmentStatus(Request $request)
    {
        $abstracts = AbstractSubmission::with('reviews')
            ->whereNotIn('status', ['accepted', 'rejected'])
            ->get()
            ->filter(fn (AbstractSubmission $abstract) => $this->isAssignmentStatusDrift($abstract))
            ->values();

        $checked = 0;
        $repaired = 0;
        $unchanged = 0;

        foreach ($abstracts as $abstract) {
            $checked++;
            $result = $this->cleanAssignmentStatusDrift($abstract);
            if ($result['success']) {
                $repaired++;
            } else {
                $unchanged++;
            }
        }

        return redirect()
            ->route('admin.diagnostics.workflow')
            ->with('success', "Bulk assignment/status cleanup checked {$checked} records: {$repaired} repaired, {$unchanged} unchanged.");
    }

    private function getLatestReviewsForAssignedReviewers(Collection $reviews, Collection $assignedReviewerIds): Collection
    {
        return $assignedReviewerIds->map(function ($reviewerId) use ($reviews) {
            return $reviews->firstWhere('reviewer_id', $reviewerId);
        })->filter();
    }

    private function getAcceptanceLogs(Collection $logs): Collection
    {
        return $logs->filter(function (EmailLog $log) {
            $type = strtolower((string) $log->email_type);
            $subject = strtolower((string) $log->subject);
            $metadata = is_array($log->metadata) ? $log->metadata : [];
            $metaStatus = strtolower((string) ($metadata['status'] ?? ''));
            $metaDecision = strtolower((string) ($metadata['decision'] ?? ''));

            return $type === 'abstract_accepted'
                || ($type === 'status_notification' && $metaStatus === 'accepted')
                || ($type === 'committee_decision' && $metaDecision === 'accepted')
                || str_contains($subject, 'accepted');
        })->values();
    }

    private function buildDiagnosticRow(AbstractSubmission $abstract, Collection $latestReviews, Collection $effectiveCurrentReviews, Collection $acceptanceLogs): array
    {
        return [
            'id' => $abstract->id,
            'title' => $abstract->title,
            'author' => $abstract->author_name,
            'author_email' => $abstract->user?->email,
            'status' => $abstract->status,
            'revision_round' => (int) ($abstract->revision_round ?? 0),
            'conference_code' => $abstract->conference_code,
            'status_changed_at' => optional($abstract->status_changed_at)->toDateTimeString(),
            'assigned_count' => count($abstract->assigned_reviewer_ids),
            'completed_count' => $effectiveCurrentReviews->count(),
            'avg_score' => $effectiveCurrentReviews->count() > 0 ? round((float) $effectiveCurrentReviews->avg('score'), 1) : null,
            'reviews' => $latestReviews->map(function (AbstractReview $review) use ($abstract) {
                return [
                    'reviewer_id' => $review->reviewer_id,
                    'review_round' => (int) $review->review_round,
                    'recommendation' => $review->recommendation,
                    'submitted_at' => optional($review->submitted_at)->toDateTimeString(),
                    'score' => $review->score,
                    'is_effective_current' => $abstract->isSubmittedReviewInEffectiveCurrentRound($review),
                ];
            })->values()->all(),
            'acceptance_email_count' => $acceptanceLogs->count(),
            'latest_acceptance_email' => $acceptanceLogs->first()
                ? [
                    'type' => $acceptanceLogs->first()->email_type,
                    'status' => $acceptanceLogs->first()->status,
                    'subject' => $acceptanceLogs->first()->subject,
                    'created_at' => optional($acceptanceLogs->first()->created_at)->toDateTimeString(),
                ]
                : null,
        ];
    }

    private function buildStaleDecisionSnapshot(AbstractSubmission $abstract, Collection $latestReviews, Collection $effectiveCurrentReviews): ?array
    {
        if (in_array($abstract->status, ['draft', 'accepted', 'rejected', 'withdrawn'], true)) {
            return null;
        }

        $submittedReviews = $abstract->reviews
            ->where('status', 'submitted')
            ->sortBy([
                ['review_round', 'desc'],
                ['submitted_at', 'desc'],
                ['id', 'desc'],
            ])
            ->values();

        if ($submittedReviews->isEmpty()) {
            return null;
        }

        $assignedReviewerIds = collect($abstract->assigned_reviewer_ids)->filter()->values();
        $assignedCount = $assignedReviewerIds->count();
        $currentCompletedCount = $effectiveCurrentReviews->count();
        $latestSubmittedRound = (int) ($submittedReviews->max('review_round') ?? 0);

        $latestCompletedRound = null;
        $latestCompletedRoundReviews = collect();
        foreach ($submittedReviews->groupBy(fn (AbstractReview $review) => (int) ($review->review_round ?? 0))->sortKeysDesc() as $round => $roundReviews) {
            if ($roundReviews->pluck('reviewer_id')->unique()->count() >= 2) {
                $latestCompletedRound = (int) $round;
                $latestCompletedRoundReviews = $roundReviews->sortBy('reviewer_number')->values();
                break;
            }
        }

        if ($latestCompletedRound === null) {
            return null;
        }

        $currentReviewerIds = $assignedReviewerIds->map(fn ($id) => (int) $id)->sort()->values()->all();
        $historicalReviewerIds = $latestCompletedRoundReviews->pluck('reviewer_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $reviewerChanged = !empty($currentReviewerIds) && !empty($historicalReviewerIds) && ($currentReviewerIds !== $historicalReviewerIds);

        $hasPartialCurrentRound = $assignedCount > 0 && $currentCompletedCount > 0 && $currentCompletedCount < 2;
        $hasOrphanDecisionStatus = $abstract->status === 'ready_for_decision' && $assignedCount < 2;
        $hasHistoricalCompletedRound = $latestCompletedRoundReviews->count() >= 2;
        $newerPartialRoundExists = $latestSubmittedRound > $latestCompletedRound;
        $currentRound = (int) ($abstract->revision_round ?? 0);

        $isStaleCandidate = $hasHistoricalCompletedRound && (
            $hasPartialCurrentRound
            || $hasOrphanDecisionStatus
            || ($reviewerChanged && $newerPartialRoundExists)
            || (in_array($abstract->status, ['revision_under_review', 'revision_review', 'revision_submitted'], true) && $newerPartialRoundExists)
        );

        if (!$isStaleCandidate) {
            return null;
        }

        $bestEvidenceReviews = $currentCompletedCount >= 2 ? $effectiveCurrentReviews : $latestCompletedRoundReviews;
        $bestAvgScore = $bestEvidenceReviews->count() > 0 ? round((float) $bestEvidenceReviews->avg('score'), 1) : 0.0;
        $bestRound = $currentCompletedCount >= 2
            ? ($currentRound > 0 ? $currentRound : $latestSubmittedRound)
            : $latestCompletedRound;

        $plainExplanation = match (true) {
            $hasPartialCurrentRound && $reviewerChanged => 'This abstract already had a fully reviewed earlier round, then the reviewer seats changed and only part of the newer round came back.',
            $hasPartialCurrentRound && $newerPartialRoundExists => 'This abstract already had a completed earlier round, but it was recycled into a newer partial round and is now stuck.',
            $hasOrphanDecisionStatus => 'This abstract already reached decision stage, but its current reviewer seats no longer match the review history.',
            in_array($abstract->status, ['revision_under_review', 'revision_review', 'revision_submitted'], true) => 'This revised abstract already has enough earlier review evidence, but the current revision round is only partially completed.',
            default => 'This abstract has enough previous review evidence, but the current reviewer state is no longer clean.',
        };

        $recommendationSummary = $bestEvidenceReviews
            ->map(function (AbstractReview $review) {
                return trim(($review->reviewer?->first_name ?? 'Reviewer') . ' ' . ($review->reviewer?->last_name ?? '')) . ': ' . ($review->recommendation_label ?? ucwords(str_replace('_', ' ', (string) $review->recommendation)));
            })
            ->values()
            ->all();

        return [
            'id' => $abstract->id,
            'title' => $abstract->title,
            'author' => $abstract->author_name,
            'author_email' => $abstract->user?->email,
            'status' => $abstract->status,
            'status_label' => str_replace('_', ' ', $abstract->status),
            'conference_code' => $abstract->conference_code,
            'current_round' => $currentRound,
            'latest_submitted_round' => $latestSubmittedRound,
            'best_round' => $bestRound,
            'best_round_label' => $bestRound === 0 ? 'Initial round' : 'Round ' . $bestRound,
            'best_avg_score' => $bestAvgScore,
            'eligible_for_bulk_accept' => $bestAvgScore >= 70,
            'current_completed_count' => $currentCompletedCount,
            'current_assigned_count' => $assignedCount,
            'reviewer_changed' => $reviewerChanged,
            'newer_partial_round_exists' => $newerPartialRoundExists,
            'plain_explanation' => $plainExplanation,
            'recommendation_summary' => $recommendationSummary,
            'best_reviews' => $bestEvidenceReviews->map(function (AbstractReview $review) {
                return [
                    'reviewer' => trim(($review->reviewer?->first_name ?? '') . ' ' . ($review->reviewer?->last_name ?? '')) ?: 'Unknown reviewer',
                    'recommendation' => $review->recommendation_label ?? ucwords(str_replace('_', ' ', (string) $review->recommendation)),
                    'score' => $review->score,
                    'submitted_at' => optional($review->submitted_at)->toDateTimeString(),
                    'review_round' => (int) ($review->review_round ?? 0),
                ];
            })->values()->all(),
        ];
    }

    private function isRevisionWorkflowDrift(AbstractSubmission $abstract): bool
    {
        return ($abstract->revision_round ?? 0) > 0
            && !is_null($abstract->revision_submitted_at)
            && in_array($abstract->status, ['submitted', 'reviewer_assigned', 'under_review'], true)
            && !in_array($abstract->status, ['accepted', 'rejected'], true);
    }

    private function isAssignmentStatusDrift(AbstractSubmission $abstract): bool
    {
        $assignedCount = count($abstract->assigned_reviewer_ids);

        return in_array($abstract->status, ['ready_for_decision', 'under_review'], true)
            && $assignedCount < 2;
    }

    private function repairLegacyRevisionDrift(AbstractSubmission $abstract, ReviewAssignmentService $assignmentService): array
    {
        $abstract->loadMissing('reviews');

        $currentRound = (int) ($abstract->revision_round ?? 0);
        if ($currentRound <= 0 || !$abstract->revision_submitted_at) {
            return [
                'success' => false,
                'message' => "Abstract #{$abstract->id} is missing revision markers needed for repair.",
            ];
        }

        $previousRound = max(0, $currentRound - 1);
        $acceptRecommendations = ['accept', 'accept_oral', 'accept_poster'];

        $previousRoundReviews = $abstract->reviews
            ->where('review_round', $previousRound)
            ->where('status', 'submitted')
            ->sortBy('reviewer_number')
            ->values();

        $currentRoundSubmittedReviews = $abstract->reviews
            ->where('review_round', $currentRound)
            ->where('status', 'submitted')
            ->sortBy('reviewer_number')
            ->values();

        if ($previousRoundReviews->isEmpty()) {
            return [
                'success' => false,
                'message' => "Abstract #{$abstract->id} has no previous-round submitted reviews to rebuild revision routing from.",
            ];
        }

        if ($currentRoundSubmittedReviews->isNotEmpty()) {
            $previousReviewerIds = $previousRoundReviews->pluck('reviewer_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $currentReviewerIds = $currentRoundSubmittedReviews->pluck('reviewer_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $hasReviewerChange = $previousReviewerIds !== $currentReviewerIds;

            return [
                'success' => false,
                'message' => $hasReviewerChange
                    ? "Abstract #{$abstract->id} already has submitted re-review work from a changed reviewer set. It should stay in admin decision handling, not be auto-restored."
                    : "Abstract #{$abstract->id} already has submitted work in the current revision round. It should be handled through decision/status sync, not auto-restored.",
            ];
        }

        $reviewersToAssign = $previousRoundReviews
            ->filter(function (AbstractReview $review) use ($acceptRecommendations) {
                return !in_array(strtolower((string) $review->recommendation), $acceptRecommendations, true);
            })
            ->map(function (AbstractReview $review) {
                return [
                    'id' => (int) $review->reviewer_id,
                    'number' => (int) $review->reviewer_number,
                ];
            })
            ->unique(fn (array $reviewer) => $reviewer['number'])
            ->values();

        AbstractReview::where('abstract_submission_id', $abstract->id)
            ->where('review_round', $currentRound)
            ->where('status', '!=', 'submitted')
            ->delete();

        $abstract->reviewer_id = null;
        $abstract->reviewer_2_id = null;
        $abstract->reviewer_1_score = null;
        $abstract->reviewer_2_score = null;
        $abstract->average_score = null;
        $abstract->review_completed_at = null;
        $abstract->assigned_at = $reviewersToAssign->isNotEmpty()
            ? ($abstract->revision_submitted_at ?? now())
            : null;

        foreach ($reviewersToAssign as $reviewerData) {
            $slotField = $reviewerData['number'] === 1 ? 'reviewer_id' : 'reviewer_2_id';
            $abstract->$slotField = $reviewerData['id'];
        }

        $abstract->status = $reviewersToAssign->isNotEmpty() ? 'revision_under_review' : 'revision_submitted';
        $abstract->status_changed_at = now();
        $abstract->status_changed_by = auth()->id();
        $abstract->save();

        foreach ($reviewersToAssign as $reviewerData) {
            $reviewer = User::find($reviewerData['id']);
            if ($reviewer) {
                $assignmentService->ensureDraftReview($abstract, $reviewer, $reviewerData['number']);
            }
        }

        RevisionHistory::create([
            'abstract_submission_id' => $abstract->id,
            'action' => 'repair_revision_path',
            'user_id' => auth()->id(),
            'revision_round' => $currentRound,
            'admin_notes' => 'Legacy direct-to-review revision path repaired from workflow diagnostics.',
            'metadata' => [
                'repaired_from_status' => $abstract->getOriginal('status'),
                're_reviewers_restored' => $reviewersToAssign->values()->all(),
            ],
        ]);

        return [
            'success' => true,
            'message' => "Abstract #{$abstract->id} repaired into {$abstract->status}. Restored {$reviewersToAssign->count()} revision reviewer seat(s).",
        ];
    }

    private function cleanAssignmentStatusDrift(AbstractSubmission $abstract): array
    {
        $assignedCount = count($abstract->assigned_reviewer_ids);
        $hasRevisionMarkers = ($abstract->revision_round ?? 0) > 0
            || !is_null($abstract->revision_requested_at)
            || !is_null($abstract->revision_submitted_at)
            || $abstract->reviews()
                ->where('status', 'submitted')
                ->where(function ($query) {
                    $query->where('review_round', '>', 0)
                        ->orWhere('is_revision_review', true);
                })
                ->exists();

        if ($assignedCount >= 2) {
            return [
                'success' => false,
                'message' => "Abstract #{$abstract->id} still has two assigned reviewers, so no assignment cleanup was needed.",
            ];
        }

        $oldStatus = $abstract->status;

        if ($assignedCount === 0) {
            $abstract->status = $hasRevisionMarkers ? 'revision_submitted' : 'submitted';
            $abstract->assigned_at = null;
        } elseif ($assignedCount === 1) {
            $abstract->status = $hasRevisionMarkers ? 'revision_under_review' : 'reviewer_assigned';
        }

        $abstract->review_completed_at = null;
        $abstract->status_changed_at = now();
        $abstract->status_changed_by = auth()->id();
        $abstract->save();

        return [
            'success' => true,
            'message' => "Abstract #{$abstract->id} cleaned from {$oldStatus} to {$abstract->status} based on its current reviewer assignment state.",
        ];
    }
}
