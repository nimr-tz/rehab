<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AbstractReview;
use App\Models\AbstractSubmission;
use App\Services\ReviewAssignmentService;
use App\Services\EmailNotificationService;
use App\Models\Notification;
use Carbon\Carbon;

class ReviewWatchdogCommand extends Command
{
    private const ACTIVE_REVIEW_STATUSES = [
        'reviewer_assigned',
        'under_review',
        'revision_submitted',
        'revision_under_review',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conference:review-watchdog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitors review deadlines and automates reminders and re-assignments';

    /**
     * Execute the console command.
     */
    public function handle(ReviewAssignmentService $assignmentService, EmailNotificationService $emailService)
    {
        $this->info('Starting review watchdog cycle...');

        // Recover missing current-round draft review rows so automation
        // can act on all active reviewer seats, even if older data drifted.
        $this->synchronizeMissingDraftReviews($assignmentService);

        // 1. Send Reminders (3 days = 72 hours)
        $this->processReminders($emailService);

        // 2. Process Re-assignments (4 days = 96 hours)
        $this->processReassignments($assignmentService);

        $this->info('Review watchdog cycle completed.');
    }

    private function synchronizeMissingDraftReviews(ReviewAssignmentService $assignmentService): void
    {
        $activeAbstracts = AbstractSubmission::with('reviews')
            ->where('is_auto_managed', true)
            ->whereIn('status', self::ACTIVE_REVIEW_STATUSES)
            ->where(function ($query) {
                $query->whereNotNull('reviewer_id')->orWhereNotNull('reviewer_2_id');
            })
            ->get();

        foreach ($activeAbstracts as $abstract) {
            $currentRound = $abstract->revision_round ?? 0;

            foreach ([1, 2] as $slot) {
                $reviewerId = $slot === 1 ? $abstract->reviewer_id : $abstract->reviewer_2_id;
                if (!$reviewerId) {
                    continue;
                }

                $currentReview = $abstract->reviews->first(function ($review) use ($reviewerId, $currentRound) {
                    return (int) $review->reviewer_id === (int) $reviewerId
                        && (int) ($review->review_round ?? 0) === (int) $currentRound;
                });

                if ($currentReview) {
                    continue;
                }

                $reviewer = $slot === 1 ? $abstract->reviewer1 : $abstract->reviewer2;
                if (!$reviewer) {
                    continue;
                }

                $assignmentService->ensureDraftReview($abstract, $reviewer, $slot);
                $this->warn("Recovered missing draft review for abstract ID {$abstract->id}, reviewer slot {$slot}.");
            }
        }
    }

    /**
     * Send reminders for reviews that haven't been completed in 3 days.
     */
    private function processReminders(EmailNotificationService $emailService)
    {
        $staleReviews = AbstractReview::where('status', 'draft')
            ->whereNull('last_reminded_at')
            ->get()
            ->filter(function ($review) {
                $startAt = $review->assigned_at
                    ?? $review->abstractSubmission?->assigned_at
                    ?? $review->created_at;
                return $startAt && $startAt->diffInDays(now()) >= 3;
            });

        foreach ($staleReviews as $review) {
            $reviewer = $review->reviewer;
            $abstract = $review->abstractSubmission;

            if (!$reviewer || !$abstract) continue;
            if (!$this->isCurrentPendingAssignment($review, $abstract, $reviewer->id)) continue;

            $startAt = $review->assigned_at
                ?? $review->abstractSubmission?->assigned_at
                ?? $review->created_at;

            // Send Notification
            Notification::create([
                'user_id' => $reviewer->id,
                'title' => 'URGENT: Review Deadline Pending',
                'message' => "The review for '{$abstract->title}' is pending after 72 hours. Please submit your review within the next 24 hours to avoid automatic re-assignment.",
                'type' => 'review_reminder',
                'link' => route('reviewer.review', $abstract->id),
            ]);

            // Send email reminder
            $deadline = $startAt ? $startAt->copy()->addDays(4) : null;
            $emailService->sendReviewReminder($reviewer, collect([$abstract]), $deadline);

            // Track reminder
            $review->update(['last_reminded_at' => now()]);
            
            $this->warn("Reminder sent to {$reviewer->name} for abstract ID {$abstract->id}");
        }
    }

    /**
     * Re-assign abstracts if they remain unreviewed after 4 days.
     */
    private function processReassignments(ReviewAssignmentService $assignmentService)
    {
        $abandonedReviews = AbstractReview::where('status', 'draft')
            ->get()
            ->filter(function ($review) {
                $startAt = $review->assigned_at
                    ?? $review->abstractSubmission?->assigned_at
                    ?? $review->created_at;
                return $startAt && $startAt->diffInDays(now()) >= 4;
            });

        foreach ($abandonedReviews as $review) {
            $abstract = $review->abstractSubmission;
            $oldReviewer = $review->reviewer;

            if (!$abstract || !$oldReviewer) continue;
            if (!$this->isCurrentPendingAssignment($review, $abstract, $oldReviewer->id)) continue;

            // Skip if manually overridden
            if (!$abstract->is_auto_managed) {
                $this->info("Skipping manual-only abstract ID {$abstract->id}");
                continue;
            }

            $this->error("Re-assigning abandoned abstract ID {$abstract->id} from {$oldReviewer->name}");

            // Perform re-assignment
            $assignmentService->reassign($abstract, $oldReviewer, $review->reviewer_number);
        }
    }

    private function isCurrentPendingAssignment(AbstractReview $review, AbstractSubmission $abstract, int $reviewerId): bool
    {
        $currentAssignedReviewerId = $review->reviewer_number === 1
            ? $abstract->reviewer_id
            : $abstract->reviewer_2_id;

        if ((int) $currentAssignedReviewerId !== (int) $reviewerId) {
            return false;
        }

        $currentRound = $abstract->revision_round ?? 0;
        if ((int) ($review->review_round ?? 0) !== (int) $currentRound) {
            return false;
        }

        return $review->status === 'draft';
    }
}
