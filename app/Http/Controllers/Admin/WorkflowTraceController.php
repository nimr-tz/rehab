<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\EmailLog;
use App\Models\ReviewHistory;
use App\Models\RevisionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WorkflowTraceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->string('search', ''));
        $statusFilter = (string) $request->string('status', 'non_final');

        $query = AbstractSubmission::with([
            'user:id,first_name,last_name,email',
            'reviewer1:id,first_name,last_name,email',
            'reviewer2:id,first_name,last_name,email',
        ])->where('status', '!=', 'draft');

        if ($statusFilter === 'non_final') {
            $query->whereNotIn('status', ['accepted', 'rejected']);
        } elseif ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('id', $search)
                    ->orWhere('title', 'like', '%' . $search . '%')
                    ->orWhere('author_name', 'like', '%' . $search . '%')
                    ->orWhere('conference_code', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', '%' . $search . '%')
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ['%' . $search . '%']);
                    });
            });
        }

        $abstracts = $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $abstractIds = $abstracts->getCollection()->pluck('id');

        $submittedReviews = \App\Models\AbstractReview::with('reviewer:id,first_name,last_name,email')
            ->whereIn('abstract_submission_id', $abstractIds)
            ->where('status', 'submitted')
            ->orderByDesc('review_round')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('abstract_submission_id');

        $revisionHistory = RevisionHistory::with('user:id,first_name,last_name,email')
            ->whereIn('abstract_submission_id', $abstractIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('abstract_submission_id');

        $reviewHistory = ReviewHistory::with(['reviewer:id,first_name,last_name,email', 'admin:id,first_name,last_name,email'])
            ->whereIn('abstract_submission_id', $abstractIds)
            ->orderByDesc('action_date')
            ->get()
            ->groupBy('abstract_submission_id');

        $emailLogs = EmailLog::whereIn('abstract_submission_id', $abstractIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('abstract_submission_id');

        $rows = $abstracts->getCollection()->map(function (AbstractSubmission $abstract) use ($submittedReviews, $revisionHistory, $reviewHistory, $emailLogs) {
            $reviews = $submittedReviews->get($abstract->id, collect());
            $revisionEntries = $revisionHistory->get($abstract->id, collect());
            $reviewEntries = $reviewHistory->get($abstract->id, collect());
            $logEntries = $emailLogs->get($abstract->id, collect());
            $progress = $abstract->getAssignedReviewProgressSummary();

            return [
                'abstract' => $abstract,
                'progress' => $progress,
                'warnings' => $this->inferWarnings($abstract, $reviews, $revisionEntries, $logEntries, $progress),
                'path' => $this->inferPath($abstract, $revisionEntries, $reviews),
                'latest_activity' => $this->resolveLatestActivity($abstract, $revisionEntries, $reviewEntries, $logEntries, $reviews),
                'review_count' => $reviews->count(),
                'revision_count' => $revisionEntries->count(),
                'email_count' => $logEntries->count(),
                'status_label' => $this->humanizeStatus($abstract->status),
            ];
        });

        $abstracts->setCollection($rows);

        $statusOptions = [
            'non_final' => 'Non-final only',
            'all' => 'All statuses',
            'submitted' => 'Submitted',
            'reviewer_assigned' => 'Reviewer Assigned',
            'under_review' => 'Under Review',
            'ready_for_decision' => 'Ready for Decision',
            'revision_required' => 'Revision Required',
            'revision_requested' => 'Revision Requested',
            'revision_submitted' => 'Revision Submitted',
            'revision_under_review' => 'Revision Under Review',
            'revision_review' => 'Revision Review',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
        ];

        return view('admin.diagnostics.workflow-trace.index', [
            'abstracts' => $abstracts,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function show(AbstractSubmission $abstract)
    {
        $abstract->load([
            'user:id,first_name,last_name,email',
            'reviewer1:id,first_name,last_name,email',
            'reviewer2:id,first_name,last_name,email',
            'reviews.reviewer:id,first_name,last_name,email',
        ]);

        $reviews = $abstract->reviews()
            ->with('reviewer:id,first_name,last_name,email')
            ->orderBy('review_round')
            ->orderBy('reviewer_number')
            ->orderBy('submitted_at')
            ->get();

        $revisionEntries = RevisionHistory::with('user:id,first_name,last_name,email')
            ->where('abstract_submission_id', $abstract->id)
            ->orderBy('created_at')
            ->get();

        $reviewEntries = ReviewHistory::with(['reviewer:id,first_name,last_name,email', 'admin:id,first_name,last_name,email'])
            ->where('abstract_submission_id', $abstract->id)
            ->orderBy('action_date')
            ->get();

        $emailLogs = EmailLog::where('abstract_submission_id', $abstract->id)
            ->orderBy('created_at')
            ->get();

        $progress = $abstract->getAssignedReviewProgressSummary();
        $warnings = $this->inferWarnings($abstract, $reviews->where('status', 'submitted')->values(), $revisionEntries, $emailLogs, $progress);
        $path = $this->inferPath($abstract, $revisionEntries, $reviews);
        $acceptanceEmailExists = $emailLogs->contains(function (EmailLog $log) {
            $type = strtolower((string) $log->email_type);
            $subject = strtolower((string) $log->subject);

            return $type === 'abstract_accepted' || str_contains($subject, 'accepted');
        });

        $quickFixes = [
            'repair_revision_path' => ($abstract->revision_round ?? 0) > 0
                && !is_null($abstract->revision_submitted_at)
                && in_array($abstract->status, ['submitted', 'reviewer_assigned', 'under_review'], true)
                && !in_array($abstract->status, ['accepted', 'rejected'], true),
            'resync_status' => $abstract->status === 'under_review' && ($progress['is_ready'] ?? false),
            'clean_assignment_drift' => in_array($abstract->status, ['ready_for_decision', 'under_review'], true)
                && count($abstract->assigned_reviewer_ids) < 2,
            'accept_without_reemail' => $acceptanceEmailExists && $abstract->status !== 'accepted',
        ];

        $rounds = $reviews->groupBy(function ($review) {
            return (int) ($review->review_round ?? 0);
        })->map(function (Collection $roundReviews, int $round) use ($abstract) {
            return [
                'round' => $round,
                'label' => $round === 0 ? 'Round 0 (Initial)' : 'Round ' . $round,
                'reviews' => $roundReviews->values(),
                'submitted_count' => $roundReviews->where('status', 'submitted')->count(),
                'draft_count' => $roundReviews->where('status', 'draft')->count(),
                'is_current_effective' => $roundReviews->contains(fn ($review) => $abstract->isSubmittedReviewInEffectiveCurrentRound($review)),
            ];
        })->sortKeys()->values();

        $timeline = $this->buildTimeline($abstract, $reviews, $revisionEntries, $reviewEntries);
        $communicationLog = $this->buildCommunicationLog($emailLogs);

        return view('admin.diagnostics.workflow-trace.show', [
            'abstract' => $abstract,
            'progress' => $progress,
            'warnings' => $warnings,
            'path' => $path,
            'rounds' => $rounds,
            'emailLogs' => $emailLogs,
            'timeline' => $timeline,
            'communicationLog' => $communicationLog,
            'statusLabel' => $this->humanizeStatus($abstract->status),
            'quickFixes' => $quickFixes,
        ]);
    }

    private function inferWarnings(
        AbstractSubmission $abstract,
        Collection $submittedReviews,
        Collection $revisionEntries,
        Collection $emailLogs,
        array $progress
    ): array {
        $warnings = [];

        if (($abstract->revision_round ?? 0) > 0 && in_array($abstract->status, ['submitted', 'reviewer_assigned', 'under_review'], true)) {
            $warnings[] = 'This abstract has revision history, but it is still sitting on the normal review path.';
        }

        if ($abstract->revision_submitted_at && !in_array($abstract->status, ['revision_submitted', 'revision_under_review', 'revision_review', 'accepted', 'rejected'], true)) {
            $warnings[] = 'The author already submitted a revision, but the status is not using the revision workflow.';
        }

        if (($progress['completed_count'] ?? 0) >= 2 && $abstract->status === 'under_review') {
            $warnings[] = 'Both reviews are already in, but the status still says Under Review.';
        }

        $assignedCount = count($abstract->assigned_reviewer_ids);
        if ($abstract->status === 'ready_for_decision' && $assignedCount < 2) {
            $warnings[] = 'This abstract says Decision Required even though fewer than two reviewer seats are assigned right now.';
        }

        $acceptanceEmailExists = $emailLogs->contains(function (EmailLog $log) {
            $type = strtolower((string) $log->email_type);
            $subject = strtolower((string) $log->subject);
            return $type === 'abstract_accepted' || str_contains($subject, 'accepted');
        });

        if ($acceptanceEmailExists && $abstract->status !== 'accepted') {
            $warnings[] = 'An acceptance email was sent, but the current status is not Accepted.';
        }

        if ($revisionEntries->isNotEmpty() && $submittedReviews->isEmpty() && in_array($abstract->status, ['submitted', 'reviewer_assigned'], true)) {
            $warnings[] = 'This has revision history, but it now looks like a fresh submission waiting in the queue.';
        }

        return $warnings;
    }

    private function inferPath(AbstractSubmission $abstract, Collection $revisionEntries, Collection $reviews): array
    {
        if (($abstract->revision_round ?? 0) > 0) {
            if (in_array($abstract->status, ['revision_submitted', 'revision_under_review', 'revision_review'], true)) {
                return [
                    'label' => 'Revision flow is set up correctly',
                    'tone' => 'emerald',
                    'detail' => 'This abstract is already using the proper revision statuses.',
                ];
            }

            if (in_array($abstract->status, ['under_review', 'reviewer_assigned', 'submitted'], true) || $revisionEntries->contains('action', 'revision_submitted')) {
                return [
                    'label' => 'Revision happened, but it stayed on the old path',
                    'tone' => 'amber',
                    'detail' => 'Revision activity exists, but the abstract is still moving through the normal review statuses.',
                ];
            }
        }

        if ($reviews->isNotEmpty()) {
            return [
                'label' => 'Still in the first review round',
                'tone' => 'sky',
                'detail' => 'No revision activity yet. This still looks like a normal first-round review.',
            ];
        }

        return [
            'label' => 'Still waiting to move into review',
            'tone' => 'slate',
            'detail' => 'There is not enough workflow history yet to show a fuller review path.',
        ];
    }

    private function resolveLatestActivity(
        AbstractSubmission $abstract,
        Collection $revisionEntries,
        Collection $reviewEntries,
        Collection $emailLogs,
        Collection $reviews
    ): ?array {
        $events = collect();

        if ($revisionEntries->isNotEmpty()) {
            $latest = $revisionEntries->sortByDesc('created_at')->first();
            $events->push([
                'at' => $latest->created_at,
                'label' => $this->friendlyRevisionAction($latest->action_description),
            ]);
        }

        if ($reviewEntries->isNotEmpty()) {
            $latest = $reviewEntries->sortByDesc('action_date')->first();
            $events->push([
                'at' => $latest->action_date,
                'label' => $this->friendlyReviewHistoryAction((string) $latest->action),
            ]);
        }

        if ($events->isEmpty() && $emailLogs->isNotEmpty()) {
            $latest = $emailLogs->sortByDesc('created_at')->first();
            $events->push([
                'at' => $latest->created_at,
                'label' => $this->friendlyEmailType($latest->email_type),
            ]);
        }

        if ($reviews->isNotEmpty()) {
            $latest = $reviews->sortByDesc('submitted_at')->first();
            $events->push([
                'at' => $latest->submitted_at ?? $latest->updated_at,
                'label' => 'Review submitted by ' . trim(($latest->reviewer?->first_name ?? 'Reviewer') . ' ' . ($latest->reviewer?->last_name ?? '')),
            ]);
        }

        if ($events->isEmpty() && $abstract->submitted_at) {
            $events->push([
                'at' => $abstract->submitted_at,
                'label' => 'Abstract submitted',
            ]);
        }

        $latest = $events->filter(fn ($event) => !empty($event['at']))->sortByDesc('at')->first();

        if (!$latest) {
            return null;
        }

        return [
            'label' => $latest['label'],
            'at' => $latest['at']->toDateTimeString(),
        ];
    }

    private function buildTimeline(
        AbstractSubmission $abstract,
        Collection $reviews,
        Collection $revisionEntries,
        Collection $reviewEntries
    ): Collection {
        $events = collect();

        $push = function (?object $at, string $type, string $label, ?string $detail = null, array $meta = []) use (&$events) {
            if (!$at) {
                return;
            }

            $events->push([
                'at' => $at,
                'type' => $type,
                'label' => $label,
                'detail' => $detail,
                'meta' => $meta,
            ]);
        };

        $push($abstract->created_at, 'abstract', 'Abstract created', 'Current status: ' . $this->humanizeStatus($abstract->status));
        $push($abstract->submitted_at, 'abstract', 'Abstract submitted');
        $push($abstract->assigned_at, 'assignment', 'Reviewers assigned', $abstract->reviewer1?->email . ($abstract->reviewer2 ? ' and ' . $abstract->reviewer2?->email : ''));
        $push($abstract->revision_requested_at, 'revision', 'Revision requested', $abstract->revision_feedback ?: $abstract->admin_comment);
        $push($abstract->revision_submitted_at, 'revision', 'Revision submitted by author');
        $push($abstract->status_changed_at, 'status', 'Status changed to ' . $this->humanizeStatus($abstract->status));

        foreach ($reviews as $review) {
            $push(
                $review->submitted_at ?? $review->updated_at,
                'review',
                'Reviewer ' . ($review->reviewer_number ?? '?') . ' submitted a review',
                $review->recommendation ? 'Recommendation: ' . $this->humanizeRecommendation($review->recommendation) : null,
                [
                    'round' => $review->review_round,
                    'reviewer' => $review->reviewer?->email,
                ]
            );
        }

        foreach ($revisionEntries as $entry) {
            $push($entry->created_at, 'revision', $this->friendlyRevisionAction($entry->action_description), $entry->feedback ?: $entry->author_response, [
                'round' => $entry->revision_round,
                'user' => $entry->user?->email,
            ]);
        }

        foreach ($reviewEntries as $entry) {
            $push($entry->action_date, 'review', $this->friendlyReviewHistoryAction((string) $entry->action), $entry->reason ?: $entry->comments, [
                'round' => $entry->metadata['review_round'] ?? null,
                'reviewer' => $entry->reviewer?->email,
                'admin' => $entry->admin?->email,
            ]);
        }

        return $events
            ->sortBy('at')
            ->unique(fn ($event) => $event['type'] . '|' . $event['label'] . '|' . optional($event['at'])->toDateTimeString())
            ->values();
    }

    private function buildCommunicationLog(Collection $emailLogs): Collection
    {
        return $emailLogs->map(function (EmailLog $log) {
            return [
                'at' => $log->created_at,
                'label' => $this->friendlyEmailType($log->email_type),
                'detail' => $log->subject,
                'meta' => [
                    'status' => $log->status,
                    'recipient' => $log->recipient_email,
                ],
            ];
        })->values();
    }

    private function humanizeStatus(?string $status): string
    {
        return match ($status) {
            'submitted' => 'Submitted',
            'reviewer_assigned' => 'Reviewer Assigned',
            'under_review' => 'Under Review',
            'ready_for_decision' => 'Decision Required',
            'revision_required', 'revision_requested' => 'Revision With Author',
            'revision_submitted' => 'Revision Submitted',
            'revision_under_review', 'revision_review' => 'Revision With Reviewers',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            default => ucwords(str_replace('_', ' ', (string) $status)),
        };
    }

    private function humanizeRecommendation(?string $recommendation): string
    {
        return ucwords(str_replace('_', ' ', (string) $recommendation));
    }

    private function friendlyRevisionAction(?string $action): string
    {
        $normalized = strtolower(trim((string) $action));

        return match ($normalized) {
            'revision requested' => 'Revision was requested',
            'revision submitted' => 'Author submitted a revision',
            'revision approved' => 'Revision was approved',
            default => ucfirst((string) $action),
        };
    }

    private function friendlyReviewHistoryAction(string $action): string
    {
        return match ($action) {
            'review_declined' => 'A reviewer declined the assignment',
            'review_reassigned' => 'The review was reassigned',
            'review_submitted' => 'A review was submitted',
            'review_completed' => 'The review round was marked complete',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    private function friendlyEmailType(?string $emailType): string
    {
        return match (strtolower((string) $emailType)) {
            'abstract_accepted' => 'Acceptance email sent',
            'abstract_rejected' => 'Rejection email sent',
            'reviewer_assignment' => 'Reviewer assignment email sent',
            'revision_rereview', 'revision_re-review' => 'Revision re-review email sent',
            'submission_confirmation' => 'Submission confirmation email sent',
            default => 'Email sent: ' . ucwords(str_replace('_', ' ', (string) $emailType)),
        };
    }
}
