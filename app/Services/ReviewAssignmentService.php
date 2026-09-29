<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\AbstractReviewerExclusion;
use App\Models\ReviewHistory;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\EmailNotificationService;

class ReviewAssignmentService
{
    /**
     * Automatically assign a reviewer to an abstract based on subtheme matching and load balancing.
     */
    public function autoAssign(AbstractSubmission $abstract, int $reviewerNumber = 1, array $excludeReviewerIds = [], string $assignmentType = 'assigned'): ?User
    {
        $assignedReviewerIds = array_filter([$abstract->reviewer_id, $abstract->reviewer_2_id]);
        $persistentExclusions = $this->getExcludedReviewerIdsForAbstract($abstract);
        $excludeReviewerIds = array_unique(array_merge($excludeReviewerIds, $assignedReviewerIds, $persistentExclusions));

        $eligibleReviewers = $this->getEligibleReviewersForSubthemes($abstract, $excludeReviewerIds);

        if ($eligibleReviewers->isEmpty()) {
            Log::warning("No preferred reviewers found for abstract ID {$abstract->id} (subtheme: '{$abstract->subtheme}').");
            return null;
        }

        $availableReviewers = $eligibleReviewers->filter(function ($reviewer) {
            $currentLoad = $this->getCurrentDraftLoad($reviewer);
            $maxLoad = max(0, (int) ($reviewer->reviewer_max_load ?? 0));

            return $currentLoad < $maxLoad;
        });

        $overflowing = false;

        if ($availableReviewers->isEmpty()) {
            Log::warning("No eligible reviewers under max load for abstract ID {$abstract->id}; using balanced overflow assignment within matching subthemes.");
            $availableReviewers = $eligibleReviewers;
            $overflowing = true;
        }

        $selectedReviewer = $availableReviewers
            ->sortBy([
                fn ($reviewer) => $this->getCurrentDraftLoad($reviewer),
                fn ($reviewer) => strtolower($reviewer->name),
            ])
            ->first();

        if (!$selectedReviewer) {
            return null;
        }

        $matchType = $this->getMatchTypeForReviewer($selectedReviewer, $abstract->subtheme);

        if ($overflowing && $matchType) {
            $matchType .= '_overflow';
        }

        $this->assign($abstract, $selectedReviewer, $reviewerNumber, $assignmentType, $matchType);

        return $selectedReviewer;
    }

    /**
     * Remove an abstract from a reviewer's queue because it falls outside their expertise,
     * and prevent future automatic assignment back to the same reviewer.
     */
    public function declineForExpertiseMismatch(AbstractSubmission $abstract, User $reviewer, ?string $reason = null): array
    {
        $reviewerNumber = $this->getReviewerSlotForAbstract($abstract, $reviewer->id);

        if (!$reviewerNumber) {
            throw new \RuntimeException('This reviewer is no longer assigned to the abstract.');
        }

        if ($this->reviewerHasSubmittedCurrentRound($abstract, $reviewer->id)) {
            throw new \RuntimeException('You already submitted the current review, so this assignment cannot be declined.');
        }

        $reasonText = trim((string) $reason);
        if ($reasonText === '') {
            $reasonText = 'Reviewer indicated the abstract falls outside their area of expertise.';
        }

        DB::transaction(function () use ($abstract, $reviewer, $reviewerNumber, $reasonText) {
            AbstractReviewerExclusion::updateOrCreate(
                [
                    'abstract_submission_id' => $abstract->id,
                    'reviewer_id' => $reviewer->id,
                ],
                [
                    'created_by' => $reviewer->id,
                    'reason' => $reasonText,
                ]
            );

            ReviewHistory::create([
                'abstract_submission_id' => $abstract->id,
                'reviewer_id' => $reviewer->id,
                'reviewer_position' => $reviewerNumber,
                'action' => 'declined_expertise_mismatch',
                'reason' => $reasonText,
                'admin_user_id' => $reviewer->id,
                'metadata' => [
                    'review_round' => $abstract->revision_round ?? 0,
                    'declined_via' => 'reviewer_portal',
                    'initiated_by' => 'reviewer',
                ],
                'action_date' => now(),
            ]);

            $this->releaseUnfinishedReviewerSlot($abstract, $reviewerNumber);
        });

        $freshAbstract = $abstract->fresh();
        $replacement = $this->autoAssign($freshAbstract, $reviewerNumber, [$reviewer->id], 'reassigned');

        $this->sendRevocationNotification($abstract, $reviewer, 'Removed after reviewer flagged expertise mismatch');

        return [
            'reviewer_number' => $reviewerNumber,
            'replacement' => $replacement,
            'abstract' => $freshAbstract->fresh(),
        ];
    }

    /**
     * Manually assign a reviewer to an abstract.
     */
    public function assign(AbstractSubmission $abstract, User $reviewer, int $reviewerNumber = 1, string $assignmentType = 'assigned', string $matchType = 'exact')
    {
        DB::transaction(function() use ($abstract, $reviewer, $reviewerNumber) {
            // Update abstract table
            if ($reviewerNumber === 1) {
                $abstract->reviewer_id = $reviewer->id;
            } else {
                $abstract->reviewer_2_id = $reviewer->id;
            }
            
            $abstract->assigned_at = now();

            if (!$abstract->hasFinalDecisionStatus()) {
                $hasAnyReviewer = !is_null($abstract->reviewer_id) || !is_null($abstract->reviewer_2_id);
                $isRevisionFlow = ($abstract->revision_round ?? 0) > 0 && !is_null($abstract->revision_submitted_at);

                if ($isRevisionFlow) {
                    $abstract->status = $hasAnyReviewer ? 'revision_under_review' : 'revision_submitted';
                } else {
                    $hasBothReviewers = !is_null($abstract->reviewer_id) && !is_null($abstract->reviewer_2_id);
                    $abstract->status = $hasBothReviewers ? 'under_review' : 'reviewer_assigned';
                }
            }

            $abstract->save();

            // Create or update review record
            $this->ensureDraftReview($abstract, $reviewer, $reviewerNumber);

            // Send notification
            $this->sendAssignmentNotification($abstract, $reviewer);
        });

        // Send email notification (outside transaction)
        try {
            app(EmailNotificationService::class)->sendReviewerAssignment(
                $abstract,
                $reviewer,
                'reviewer',
                [
                    'assignment_type' => $assignmentType,
                    'match_type' => $matchType,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send reviewer assignment email: ' . $e->getMessage());
        }
    }

    /**
     * Re-assign an abstract to a new reviewer.
     */
    public function reassign(AbstractSubmission $abstract, User $oldReviewer, int $reviewerNumber): ?User
    {
        Log::info("Re-assigning abstract ID {$abstract->id} from reviewer ID {$oldReviewer->id}");

        if ($this->reviewerHasSubmittedCurrentRound($abstract, $oldReviewer->id)) {
            Log::info("Skipping reassignment for abstract ID {$abstract->id}; reviewer {$oldReviewer->id} already submitted current round.");
            return null;
        }

        $releasedReviewer = $this->releaseUnfinishedReviewerSlot($abstract, $reviewerNumber);
        if (!$releasedReviewer) {
            Log::info("Skipping reassignment for abstract ID {$abstract->id}; reviewer slot {$reviewerNumber} is no longer pending.");
            return null;
        }

        // 1. Find a new reviewer for the now-empty unfinished slot
        $newReviewer = $this->autoAssign($abstract, $reviewerNumber, [$oldReviewer->id], 'reassigned');

        if ($newReviewer) {
            // Notify the old reviewer about the revocation
            $this->sendRevocationNotification($abstract, $oldReviewer);
        }

        return $newReviewer;
    }

    /**
     * Ensure a draft review exists for the given reviewer and round.
     * Does not overwrite submitted reviews.
     */
    public function ensureDraftReview(AbstractSubmission $abstract, User $reviewer, int $reviewerNumber = 1): void
    {
        $reviewRound = $abstract->revision_round ?? 0;

        $existing = AbstractReview::where('abstract_submission_id', $abstract->id)
            ->where('reviewer_id', $reviewer->id)
            ->where('review_round', $reviewRound)
            ->first();

        if ($existing && $existing->status === 'submitted') {
            return;
        }

        AbstractReview::updateOrCreate(
            [
                'abstract_submission_id' => $abstract->id,
                'reviewer_id' => $reviewer->id,
                'review_round' => $reviewRound,
            ],
            [
                'reviewer_number' => $reviewerNumber,
                'status' => 'draft',
                'assigned_at' => $existing?->assigned_at ?? $abstract->assigned_at ?? now(),
                'last_reminded_at' => null,
            ]
        );
    }

    /**
     * Return an unfinished reviewer slot to the pool without touching completed reviews.
     */
    public function releaseUnfinishedReviewerSlot(AbstractSubmission $abstract, int $reviewerNumber): ?User
    {
        $field = $reviewerNumber === 1 ? 'reviewer_id' : 'reviewer_2_id';
        $currentReviewerId = $abstract->$field;

        if (!$currentReviewerId) {
            return null;
        }

        if ($this->reviewerHasSubmittedCurrentRound($abstract, $currentReviewerId)) {
            return null;
        }

        $reviewRound = $abstract->revision_round ?? 0;
        $reviewer = User::find($currentReviewerId);

        AbstractReview::where('abstract_submission_id', $abstract->id)
            ->where('reviewer_id', $currentReviewerId)
            ->where('review_round', $reviewRound)
            ->where('status', '!=', 'submitted')
            ->delete();

        $abstract->$field = null;
        $abstract->save();

        $this->syncAssignmentStatus($abstract);

        return $reviewer;
    }

    /**
     * Re-pool all unfinished reviewer seats, preserving submitted reviews,
     * then reassign them using reviewer-selected preferences.
     */
    public function repoolUnfinishedAssignmentsAndReassign(): array
    {
        $abstracts = AbstractSubmission::whereNotIn('status', ['draft', 'accepted', 'rejected', 'revision_required', 'revision_requested'])
            ->where(function ($query) {
                $query->whereNotNull('reviewer_id')->orWhereNotNull('reviewer_2_id');
            })
            ->get();

        $released = 0;
        $reassigned = 0;
        $unmatched = 0;

        foreach ($abstracts as $abstract) {
            $releasedReviewers = [];

            foreach ([1, 2] as $slot) {
                $field = $slot === 1 ? 'reviewer_id' : 'reviewer_2_id';
                $currentReviewerId = $abstract->$field;

                if (!$currentReviewerId || $this->reviewerHasSubmittedCurrentRound($abstract, $currentReviewerId)) {
                    continue;
                }

                $releasedReviewer = $this->releaseUnfinishedReviewerSlot($abstract, $slot);
                if ($releasedReviewer) {
                    $released++;
                    $releasedReviewers[$slot] = $releasedReviewer->id;
                }
            }

            foreach ([1, 2] as $slot) {
                $field = $slot === 1 ? 'reviewer_id' : 'reviewer_2_id';
                if ($abstract->$field) {
                    continue;
                }

                $newReviewer = $this->autoAssign(
                    $abstract,
                    $slot,
                    isset($releasedReviewers[$slot]) ? [$releasedReviewers[$slot]] : [],
                    'reassigned'
                );

                if ($newReviewer) {
                    $reassigned++;
                } else {
                    $unmatched++;
                }
            }
        }

        return [
            'released' => $released,
            'reassigned' => $reassigned,
            'unmatched' => $unmatched,
        ];
    }

    /**
     * Remove assignments that no longer match the reviewer preferences.
     * Only removes DRAFT reviews (unfinished work).
     */
    public function cleanupMismatchedAssignments(User $reviewer): int
    {
        $draftReviews = AbstractReview::where('reviewer_id', $reviewer->id)
            ->where('status', 'draft')
            ->with('abstractSubmission')
            ->get();

        $preferredSubthemes = $reviewer->interests()->pluck('subtheme_name')->filter()->values()->all();
        $unassignedCount = 0;

        foreach ($draftReviews as $review) {
            $abstract = $review->abstractSubmission;
            if (!$abstract) continue;


            // Check if current abstract subtheme is still allowed by reviewer's chosen interests (fuzzy)
            $isStillPreferred = $this->reviewerHasSubthemesMatch($preferredSubthemes, $abstract->subtheme);

            if (!$isStillPreferred) {
                // Return to pool
                $this->releaseUnfinishedReviewerSlot($abstract, (int)$review->reviewer_number);
                $unassignedCount++;
            }
        }

        return $unassignedCount;
    }

    /**
     * Immediately refill a reviewer's queue when they complete a review and have free capacity.
     */
    public function assignNextPreferredAbstract(User $reviewer): ?AbstractSubmission
    {
        if (!$reviewer->reviewer_preferences_set) {
            return null;
        }

        $currentLoad = $this->getCurrentDraftLoad($reviewer);
        if ($currentLoad >= $reviewer->reviewer_max_load) {
            return null;
        }

        $preferredSubthemes = $reviewer->interests()->pluck('subtheme_name')->filter()->values()->all();
        if (empty($preferredSubthemes)) {
            return null;
        }

        // Fetch candidates missing at least one reviewer - then check subtheme in PHP for robust mapping
        $candidates = AbstractSubmission::whereNotIn('status', ['draft', 'accepted', 'rejected', 'revision_required', 'revision_requested'])
            ->where(function ($query) {
                $query->whereNull('reviewer_id')->orWhereNull('reviewer_2_id');
            })
            ->orderBy('created_at')
            ->get();

        foreach ($candidates as $abstract) {
            if (in_array($reviewer->id, $this->getExcludedReviewerIdsForAbstract($abstract), true)) {
                continue;
            }

            // 1. Check if the subtheme matches the reviewer's interests (Fuzzy Match)
            if (!$this->reviewerHasSubthemesMatch($preferredSubthemes, $abstract->subtheme)) {
                continue;
            }

            // 2. Conflict of interest (Author matches reviewer)
            if ($abstract->user_id == $reviewer->id) {
                continue;
            }

            // 3. System-checked conflicts
            if (!empty($abstract->checkAllConflicts($reviewer->id))) {
                continue;
            }

            // 4. Identify slot
            $slot = is_null($abstract->reviewer_id) ? 1 : (is_null($abstract->reviewer_2_id) ? 2 : null);
            if (!$slot) {
                continue;
            }

            // 5. Already assigned to the other slot
            if (in_array($reviewer->id, array_filter([$abstract->reviewer_id, $abstract->reviewer_2_id]), true)) {
                continue;
            }

            $matchType = $this->getMatchTypeForReviewer($reviewer, $abstract->subtheme);
            $this->assign($abstract, $reviewer, $slot, 'assigned', $matchType);
            return $abstract->fresh();
        }

        return null;
    }

    /**
     * Fill a reviewer's queue immediately up to their allowed active load.
     */
    public function fillReviewerQueue(User $reviewer): int
    {
        if (!$reviewer->reviewer_preferences_set) {
            return 0;
        }

        // Phase 1: Cleanup any work that no longer matches current preferences
        $this->cleanupMismatchedAssignments($reviewer);

        // Phase 2: Refill the queue up to max capacity
        $assignedCount = 0;
        $maxAssignments = max(0, (int) $reviewer->reviewer_max_load);

        while ($this->getCurrentDraftLoad($reviewer) < $maxAssignments) {
            $assignedAbstract = $this->assignNextPreferredAbstract($reviewer);

            if (!$assignedAbstract) {
                break;
            }

            $assignedCount++;
        }

        return $assignedCount;
    }

    /**
     * Decide whether the reviewer has already completed the current round for this abstract.
     */
    public function reviewerHasSubmittedCurrentRound(AbstractSubmission $abstract, int $reviewerId): bool
    {
        $reviewRound = $abstract->revision_round ?? 0;

        return AbstractReview::where('abstract_submission_id', $abstract->id)
            ->where('reviewer_id', $reviewerId)
            ->where('review_round', $reviewRound)
            ->where('status', 'submitted')
            ->exists();
    }

    public function getReviewerSlotForAbstract(AbstractSubmission $abstract, int $reviewerId): ?int
    {
        if ((int) $abstract->reviewer_id === $reviewerId) {
            return 1;
        }

        if ((int) $abstract->reviewer_2_id === $reviewerId) {
            return 2;
        }

        return null;
    }

    private function getCurrentDraftLoad(User $reviewer): int
    {
        return AbstractReview::where('reviewer_id', $reviewer->id)
            ->where('status', 'draft')
            ->count();
    }

    private function getExcludedReviewerIdsForAbstract(AbstractSubmission $abstract): array
    {
        if ($abstract->relationLoaded('reviewerExclusions')) {
            return $abstract->reviewerExclusions
                ->pluck('reviewer_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return AbstractReviewerExclusion::where('abstract_submission_id', $abstract->id)
            ->pluck('reviewer_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function syncAssignmentStatus(AbstractSubmission $abstract): void
    {
        $abstract->refresh();

        if ($abstract->hasFinalDecisionStatus()) {
            return;
        }

        $hasBothReviewers = !is_null($abstract->reviewer_id) && !is_null($abstract->reviewer_2_id);
        $hasAnyReviewer = !is_null($abstract->reviewer_id) || !is_null($abstract->reviewer_2_id);
        $isRevisionFlow = ($abstract->revision_round ?? 0) > 0 && !is_null($abstract->revision_submitted_at);

        if ($isRevisionFlow) {
            if ($hasAnyReviewer) {
                $abstract->update([
                    'status' => 'revision_under_review',
                    'assigned_at' => $abstract->assigned_at ?? $abstract->revision_submitted_at ?? now(),
                ]);
                return;
            }

            $abstract->update([
                'status' => 'revision_submitted',
                'assigned_at' => null,
            ]);
            return;
        }

        if ($hasBothReviewers) {
            $abstract->update([
                'status' => 'under_review',
                'assigned_at' => $abstract->assigned_at ?? now(),
            ]);
            return;
        }

        if ($hasAnyReviewer) {
            $abstract->update(['status' => 'reviewer_assigned']);
            return;
        }

        $abstract->update([
            'status' => 'submitted',
            'assigned_at' => null,
        ]);
    }

    /**
     * Send assignment notification/email.
     */
    private function sendAssignmentNotification(AbstractSubmission $abstract, User $reviewer)
    {
        // Internal Notification
        Notification::create([
            'user_id' => $reviewer->id,
            'title' => 'New Abstract Assignment',
            'message' => "You have been assigned a new abstract to review: '{$abstract->title}'. Please complete the review within 72 hours.",
            'type' => 'review_assignment',
            'link' => route('reviewer.review', $abstract->id),
        ]);
    }

    /**
     * Send revocation notification (deadline missed).
     */
    private function sendRevocationNotification(AbstractSubmission $abstract, User $reviewer, string $reason = 'Deadline passed without submission')
    {
        $message = str_contains(strtolower($reason), 'expertise mismatch')
            ? "The review assignment for '{$abstract->title}' has been removed because you indicated it falls outside your expertise. It will not be assigned back to you automatically."
            : "The review assignment for '{$abstract->title}' has been revoked as the 96-hour deadline passed without a submission. It has been re-assigned to another expert.";

        Notification::create([
            'user_id' => $reviewer->id,
            'title' => 'Review Assignment Revoked',
            'message' => $message,
            'type' => 'review_revoked',
        ]);

        try {
            app(EmailNotificationService::class)->sendReviewerRevocation($abstract, $reviewer, $reason);
        } catch (\Exception $e) {
            Log::warning('Failed to send reviewer revocation email: ' . $e->getMessage());
        }
    }

    /**
     * Internal helper to determine if an abstract subtheme matches a list of preferred subthemes.
     * Includes fuzzy logic to handle short vs long theme names (e.g. "Mental Health" -> "Mental Health and NCDs").
     */
    private function reviewerHasSubthemesMatch(array $preferredNames, ?string $abstractSubtheme): bool
    {
        if (!$abstractSubtheme) return false;

        foreach ($preferredNames as $pref) {
            if ($this->getSubthemeMatchType($pref, $abstractSubtheme) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get eligible reviewers for the abstract, including match metadata.
     */
    private function getEligibleReviewersForSubthemes(AbstractSubmission $abstract, array $excludeReviewerIds = [])
    {
        $potentialReviewers = User::whereHas('roles', function($query) {
                $query->where('name', 'reviewer');
            })
            ->where('reviewer_preferences_set', true)
            ->where('id', '!=', $abstract->user_id)
            ->when(!empty($excludeReviewerIds), function($q) use ($excludeReviewerIds) {
                $q->whereNotIn('id', $excludeReviewerIds);
            })
            ->with('interests')
            ->get();

        return $potentialReviewers
            ->map(function ($reviewer) use ($abstract) {
                $matchType = $this->getMatchTypeForReviewer($reviewer, $abstract->subtheme);

                if ($matchType === null) {
                    return null;
                }

                if (!empty($abstract->checkAllConflicts($reviewer->id))) {
                    return null;
                }

                $reviewer->assignment_match_type = $matchType;
                return $reviewer;
            })
            ->filter()
            ->values();
    }

    private function getMatchTypeForReviewer(User $reviewer, ?string $abstractSubtheme): ?string
    {
        if (!$abstractSubtheme) {
            return null;
        }

        foreach ($reviewer->interests->pluck('subtheme_name')->filter() as $preferredSubtheme) {
            $matchType = $this->getSubthemeMatchType($preferredSubtheme, $abstractSubtheme);
            if ($matchType !== null) {
                return $matchType;
            }
        }

        return null;
    }

    private function getSubthemeMatchType(?string $preferredSubtheme, ?string $abstractSubtheme): ?string
    {
        if (!$preferredSubtheme || !$abstractSubtheme) {
            return null;
        }

        $preferred = strtolower(trim($preferredSubtheme));
        $abstract = strtolower(trim($abstractSubtheme));

        if ($preferred === $abstract) {
            return 'exact';
        }

        if (str_contains($preferred, $abstract) || str_contains($abstract, $preferred)) {
            return 'fuzzy';
        }

        return null;
    }
}
