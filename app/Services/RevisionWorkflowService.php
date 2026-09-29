<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\User;
use App\Models\RevisionHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RevisionWorkflowService
{
    /**
     * Revision workflow status definitions
     */
    const STATUS_REVISION_REQUESTED = 'revision_requested';
    const STATUS_REVISION_IN_PROGRESS = 'revision_in_progress';
    const STATUS_REVISION_SUBMITTED = 'revision_submitted';
    const STATUS_REVISION_UNDER_REVIEW = 'revision_under_review';
    const STATUS_REVISION_APPROVED = 'revision_approved';
    const STATUS_REVISION_FINAL_REVIEW = 'revision_final_review';
    const STATUS_REVISION_REJECTED = 'revision_rejected';

    /**
     * Valid status transitions for revision workflow
     */
    const VALID_TRANSITIONS = [
        'under_review' => [self::STATUS_REVISION_REQUESTED],
        'ready_for_decision' => [self::STATUS_REVISION_REQUESTED],
        self::STATUS_REVISION_REQUESTED => [self::STATUS_REVISION_IN_PROGRESS],
        self::STATUS_REVISION_IN_PROGRESS => [self::STATUS_REVISION_SUBMITTED],
        self::STATUS_REVISION_SUBMITTED => [
            self::STATUS_REVISION_UNDER_REVIEW,
            self::STATUS_REVISION_APPROVED,
            self::STATUS_REVISION_REJECTED
        ],
        self::STATUS_REVISION_UNDER_REVIEW => [
            self::STATUS_REVISION_APPROVED,
            self::STATUS_REVISION_REJECTED,
            self::STATUS_REVISION_REQUESTED // Request more changes
        ],
        self::STATUS_REVISION_APPROVED => [self::STATUS_REVISION_FINAL_REVIEW],
        self::STATUS_REVISION_FINAL_REVIEW => ['accepted', 'rejected'],
    ];

    /**
     * Request revision from reviewers or admin
     */
    public function requestRevision(
        AbstractSubmission $abstract,
        string $feedback,
        User $requestedBy,
        string $revisionType = 'major'
    ): array {
        try {
            DB::beginTransaction();

            // Determine current revision round
            $currentRound = $this->getCurrentRevisionRound($abstract);
            $newRound = $currentRound + 1;

            // Update abstract with revision request
            $abstract->update([
                'status' => self::STATUS_REVISION_REQUESTED,
                'revision_feedback' => $feedback,
                'revision_requested_at' => now(),
                'revision_round' => $newRound,
                'status_changed_at' => now(),
                'status_changed_by' => $requestedBy->id,
                'admin_comment' => $feedback,
            ]);

            // Create revision history record
            $this->createRevisionHistoryRecord($abstract, [
                'action' => 'revision_requested',
                'user_id' => $requestedBy->id,
                'round' => $newRound,
                'feedback' => $feedback,
                'revision_type' => $revisionType,
            ]);

            // Send notification to author
            $this->sendRevisionRequestNotification($abstract, $feedback);

            DB::commit();

            Log::info("Revision requested for abstract {$abstract->id} by user {$requestedBy->id}");

            return [
                'success' => true,
                'message' => 'Revision request sent to author successfully.',
                'revision_round' => $newRound
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to request revision for abstract {$abstract->id}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to send revision request. Please try again.'
            ];
        }
    }

    /**
     * Author submits revised abstract
     */
    public function submitRevision(
        AbstractSubmission $abstract,
        array $revisionData,
        ?string $authorResponse = null
    ): array {
        try {
            DB::beginTransaction();

            // Validate current status
            if ($abstract->status !== self::STATUS_REVISION_REQUESTED) {
                throw new \Exception('Abstract is not in revision requested status');
            }

            // Update abstract with revised content
            $updateData = array_merge($revisionData, [
                'status' => self::STATUS_REVISION_SUBMITTED,
                'revision_submitted_at' => now(),
                'revision_feedback' => $authorResponse,
                'updated_at' => now(),
            ]);

            $abstract->update($updateData);

            // Create revision history record
            $this->createRevisionHistoryRecord($abstract, [
                'action' => 'revision_submitted',
                'user_id' => $abstract->user_id,
                'round' => $abstract->revision_round,
                'author_response' => $authorResponse,
            ]);

            // Notify admin of submitted revision
            $this->sendRevisionSubmittedNotification($abstract);

            DB::commit();

            Log::info("Revision submitted for abstract {$abstract->id} in round {$abstract->revision_round}");

            return [
                'success' => true,
                'message' => 'Revision submitted successfully. It will now be reviewed by the admin.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to submit revision for abstract {$abstract->id}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to submit revision. Please try again.'
            ];
        }
    }

    /**
     * Admin reviews submitted revision
     */
    public function adminReviewRevision(
        AbstractSubmission $abstract,
        string $decision,
        User $reviewer,
        ?string $adminNotes = null
    ): array {
        try {
            DB::beginTransaction();

            // Validate current status
            if (!in_array($abstract->status, [self::STATUS_REVISION_SUBMITTED, self::STATUS_REVISION_UNDER_REVIEW])) {
                throw new \Exception('Abstract is not available for admin review');
            }

            // Only 3 decisions allowed - 'approve' automatically routes to reviewers
            $validDecisions = ['approve', 'reject', 'request_more_changes'];
            if (!in_array($decision, $validDecisions)) {
                throw new \Exception('Invalid decision. Valid options: approve, reject, request_more_changes');
            }

            // Determine new status based on decision
            $newStatus = match($decision) {
                'approve' => self::STATUS_REVISION_UNDER_REVIEW, // Will be sent to reviewers automatically
                'reject' => self::STATUS_REVISION_REJECTED,
                'request_more_changes' => self::STATUS_REVISION_REQUESTED,
            };

            // Update abstract
            $abstract->update([
                'status' => $newStatus,
                'admin_comment' => $adminNotes,
                'status_changed_at' => now(),
                'status_changed_by' => $reviewer->id,
            ]);

            // Create revision history record
            $this->createRevisionHistoryRecord($abstract, [
                'action' => "admin_{$decision}",
                'user_id' => $reviewer->id,
                'round' => $abstract->revision_round,
                'admin_notes' => $adminNotes,
            ]);

            // Handle post-decision actions
            if ($decision === 'approve') {
                // AUTOMATIC ROUTING: Approved revisions are automatically sent to reviewers
                Log::info("Auto-routing approved revision for abstract {$abstract->id} to reviewers");
                $this->sendRevisionToReviewers($abstract, $adminNotes);
            } elseif ($decision === 'reject') {
                $this->sendRevisionRejectedNotification($abstract, $adminNotes);
            } elseif ($decision === 'request_more_changes') {
                $this->sendAdditionalChangesRequestNotification($abstract, $adminNotes);
            }

            DB::commit();

            Log::info("Admin {$reviewer->id} reviewed revision for abstract {$abstract->id} with decision: {$decision}");

            return [
                'success' => true,
                'message' => $this->getAdminDecisionMessage($decision),
                'next_status' => $newStatus
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to review revision for abstract {$abstract->id}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to process revision review. Please try again.'
            ];
        }
    }

    /**
     * Send approved revision back to reviewers
     */
    public function sendRevisionToReviewers(AbstractSubmission $abstract, ?string $adminNotes = null): array
    {
        try {
            DB::beginTransaction();

            // Ensure proper revision round
            $currentRound = $this->getCurrentRevisionRound($abstract);
            if ($abstract->revision_round <= 0) {
                $abstract->update(['revision_round' => $currentRound]);
            }

            // Update status to under review for revision
            $abstract->update([
                'status' => self::STATUS_REVISION_UNDER_REVIEW,
                'review_completed_at' => null, // Reset completion timestamp
            ]);

            // Create new review records for the same reviewers for this revision round
            $this->createReviewRecordsForRevision($abstract);

            // Send notifications to reviewers
            $this->notifyReviewersOfRevision($abstract);

            // Create revision history record
            $this->createRevisionHistoryRecord($abstract, [
                'action' => 'sent_to_reviewers',
                'user_id' => null,
                'round' => $abstract->revision_round,
                'admin_notes' => $adminNotes,
            ]);

            DB::commit();

            Log::info("Revision for abstract {$abstract->id} sent back to reviewers for final evaluation");

            return [
                'success' => true,
                'message' => 'Revision sent to reviewers for final evaluation.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to send revision to reviewers for abstract {$abstract->id}: " . $e->getMessage());

            // Temporarily throw the exception to see what's wrong
            throw $e;
            
            return [
                'success' => false,
                'message' => 'Failed to send revision to reviewers.'
            ];
        }
    }

    /**
     * Get current revision round for an abstract
     */
    public function getCurrentRevisionRound(AbstractSubmission $abstract): int
    {
        return max(1, $abstract->revision_round ?? 1);
    }

    /**
     * Get revision workflow status for display
     */
    public function getRevisionStatus(AbstractSubmission $abstract): array
    {
        $status = $abstract->status;
        $round = $this->getCurrentRevisionRound($abstract);

        $statusInfo = [
            self::STATUS_REVISION_REQUESTED => [
                'label' => 'Revision Requested',
                'description' => 'Reviewers have requested changes to your abstract.',
                'next_action' => 'Submit revised abstract',
                'color' => 'warning'
            ],
            self::STATUS_REVISION_IN_PROGRESS => [
                'label' => 'Revision in Progress',
                'description' => 'Author is working on requested changes.',
                'next_action' => 'Complete and submit revision',
                'color' => 'info'
            ],
            self::STATUS_REVISION_SUBMITTED => [
                'label' => 'Revision Submitted',
                'description' => 'Revised abstract submitted and awaiting admin review.',
                'next_action' => 'Wait for admin review',
                'color' => 'info'
            ],
            self::STATUS_REVISION_UNDER_REVIEW => [
                'label' => 'Under Admin Review',
                'description' => 'Admin is reviewing the submitted revision.',
                'next_action' => 'Wait for decision',
                'color' => 'info'
            ],
            self::STATUS_REVISION_APPROVED => [
                'label' => 'Revision Approved',
                'description' => 'Admin approved the revision. Sending to reviewers.',
                'next_action' => 'Wait for final review',
                'color' => 'success'
            ],
            self::STATUS_REVISION_FINAL_REVIEW => [
                'label' => 'Final Review',
                'description' => 'Reviewers are conducting final evaluation of the revision.',
                'next_action' => 'Wait for final decision',
                'color' => 'info'
            ],
            self::STATUS_REVISION_REJECTED => [
                'label' => 'Revision Rejected',
                'description' => 'The revision did not adequately address the concerns.',
                'next_action' => 'Abstract has been declined',
                'color' => 'danger'
            ],
        ];

        return array_merge($statusInfo[$status] ?? [
            'label' => ucwords(str_replace('_', ' ', $status)),
            'description' => 'Status unknown',
            'next_action' => 'Contact admin',
            'color' => 'secondary'
        ], [
            'round' => $round,
            'raw_status' => $status
        ]);
    }

    /**
     * Create revision history record for audit trail
     */
    private function createRevisionHistoryRecord(AbstractSubmission $abstract, array $data): void
    {
        RevisionHistory::createEntry([
            'abstract_id' => $abstract->id,
            'action' => $data['action'],
            'user_id' => $data['user_id'] ?? null,
            'round' => $data['round'] ?? $abstract->revision_round,
            'feedback' => $data['feedback'] ?? null,
            'author_response' => $data['author_response'] ?? null,
            'admin_notes' => $data['admin_notes'] ?? null,
            'revision_type' => $data['revision_type'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Create new review records for revision evaluation
     * CRITICAL: This creates NEW records for each round to preserve history
     */
    private function createReviewRecordsForRevision(AbstractSubmission $abstract): void
    {
        $reviewers = [
            ['id' => $abstract->reviewer_id, 'number' => 1],
            ['id' => $abstract->reviewer_2_id, 'number' => 2]
        ];

        foreach ($reviewers as $reviewer) {
            if ($reviewer['id']) {
                // Check if review already exists for this round
                $existingReviewForRound = AbstractReview::where('abstract_submission_id', $abstract->id)
                    ->where('reviewer_id', $reviewer['id'])
                    ->where('review_round', $abstract->revision_round)
                    ->first();

                if ($existingReviewForRound) {
                    // Review already exists for this round - skip to prevent duplicates
                    Log::info("Review already exists for abstract {$abstract->id}, reviewer {$reviewer['id']}, round {$abstract->revision_round}");
                    continue;
                }

                // Get previous round's review for reference (optional - for tracking improvement)
                $previousReview = AbstractReview::where('abstract_submission_id', $abstract->id)
                    ->where('reviewer_id', $reviewer['id'])
                    ->where('review_round', '<', $abstract->revision_round)
                    ->orderBy('review_round', 'desc')
                    ->first();

                // CREATE NEW RECORD for this revision round (preserves history)
                AbstractReview::create([
                    'abstract_submission_id' => $abstract->id,
                    'reviewer_id' => $reviewer['id'],
                    'reviewer_number' => $reviewer['number'],
                    'review_round' => $abstract->revision_round,
                    'is_revision_review' => true,
                    'status' => 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("Created new review record for abstract {$abstract->id}, reviewer {$reviewer['id']}, round {$abstract->revision_round}");
            }
        }
    }

    /**
     * Send various notifications (would integrate with EmailNotificationService)
     */
    private function sendRevisionRequestNotification(AbstractSubmission $abstract, string $feedback): void
    {
        // Implementation would use EmailNotificationService
        Log::info("Sending revision request notification for abstract {$abstract->id}");
    }

    private function sendRevisionSubmittedNotification(AbstractSubmission $abstract): void
    {
        Log::info("Sending revision submitted notification for abstract {$abstract->id}");
    }

    private function sendRevisionRejectedNotification(AbstractSubmission $abstract, ?string $notes): void
    {
        Log::info("Sending revision rejected notification for abstract {$abstract->id}");
    }

    private function sendAdditionalChangesRequestNotification(AbstractSubmission $abstract, ?string $notes): void
    {
        Log::info("Sending additional changes request notification for abstract {$abstract->id}");
    }

    private function notifyReviewersOfRevision(AbstractSubmission $abstract): void
    {
        Log::info("Notifying reviewers of revision for abstract {$abstract->id}");
    }

    /**
     * Get user-friendly message for admin decisions
     */
    private function getAdminDecisionMessage(string $decision): string
    {
        return match($decision) {
            'approve' => 'Revision approved and automatically sent to reviewers for re-evaluation.',
            'reject' => 'Revision rejected. Author has been notified.',
            'request_more_changes' => 'Additional changes requested from author.',
            default => 'Revision review completed.'
        };
    }

    /**
     * Check if revision can be transitioned to new status
     */
    public function canTransitionTo(AbstractSubmission $abstract, string $newStatus): bool
    {
        $currentStatus = $abstract->status;
        $validTransitions = self::VALID_TRANSITIONS[$currentStatus] ?? [];
        
        return in_array($newStatus, $validTransitions);
    }

    /**
     * Get all abstracts in revision workflow
     */
    public function getAbstractsInRevisionWorkflow(): \Illuminate\Database\Eloquent\Collection
    {
        $revisionStatuses = [
            self::STATUS_REVISION_REQUESTED,
            self::STATUS_REVISION_IN_PROGRESS,
            self::STATUS_REVISION_SUBMITTED,
            self::STATUS_REVISION_UNDER_REVIEW,
            self::STATUS_REVISION_APPROVED,
            self::STATUS_REVISION_FINAL_REVIEW,
        ];

        return AbstractSubmission::whereIn('status', $revisionStatuses)
            ->with(['user', 'reviewer1', 'reviewer2', 'reviews'])
            ->orderBy('revision_submitted_at', 'desc')
            ->orderBy('revision_requested_at', 'desc')
            ->get();
    }
}
