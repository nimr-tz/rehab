<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewHistoryService
{
    /**
     * Get complete review history for a specific reviewer on an abstract
     * Returns all rounds in chronological order
     */
    public function getReviewHistory(int $abstractId, int $reviewerId): Collection
    {
        return AbstractReview::where('abstract_submission_id', $abstractId)
            ->where('reviewer_id', $reviewerId)
            ->orderBy('review_round', 'asc')
            ->with(['reviewer', 'abstractSubmission'])
            ->get();
    }

    /**
     * Get all reviews for a specific round across all reviewers
     */
    public function getReviewsByRound(int $abstractId, int $round): Collection
    {
        return AbstractReview::where('abstract_submission_id', $abstractId)
            ->where('review_round', $round)
            ->with(['reviewer'])
            ->orderBy('reviewer_number', 'asc')
            ->get();
    }

    /**
     * Get the latest review round for an abstract
     */
    public function getLatestRound(int $abstractId): ?int
    {
        return AbstractReview::where('abstract_submission_id', $abstractId)
            ->max('review_round');
    }

    /**
     * Get the previous review for a specific reviewer (one round back)
     */
    public function getPreviousReview(AbstractReview $currentReview): ?AbstractReview
    {
        if ($currentReview->review_round <= 1) {
            return null;
        }

        return AbstractReview::where('abstract_submission_id', $currentReview->abstract_submission_id)
            ->where('reviewer_id', $currentReview->reviewer_id)
            ->where('review_round', $currentReview->review_round - 1)
            ->first();
    }

    /**
     * Compare two review rounds for the same reviewer
     * Returns detailed comparison data
     */
    public function compareReviewRounds(int $abstractId, int $reviewerId, int $round1, int $round2): array
    {
        $review1 = AbstractReview::where('abstract_submission_id', $abstractId)
            ->where('reviewer_id', $reviewerId)
            ->where('review_round', $round1)
            ->first();

        $review2 = AbstractReview::where('abstract_submission_id', $abstractId)
            ->where('reviewer_id', $reviewerId)
            ->where('review_round', $round2)
            ->first();

        if (!$review1 || !$review2) {
            return [
                'error' => 'One or both reviews not found',
                'review1' => $review1,
                'review2' => $review2
            ];
        }

        return [
            'round1' => $round1,
            'round2' => $round2,
            'review1' => $review1,
            'review2' => $review2,
            'score_change' => $this->calculateScoreChange($review1, $review2),
            'recommendation_change' => $this->calculateRecommendationChange($review1, $review2),
            'improved' => $this->determineIfImproved($review1, $review2),
            'criteria_changes' => $this->compareCriteria($review1, $review2),
        ];
    }

    /**
     * Calculate score change between two reviews
     */
    private function calculateScoreChange(?AbstractReview $review1, ?AbstractReview $review2): array
    {
        if (!$review1 || !$review2 || !$review1->score || !$review2->score) {
            return [
                'change' => null,
                'percentage_change' => null,
                'direction' => 'unknown'
            ];
        }

        $change = $review2->score - $review1->score;
        $percentageChange = ($review1->score > 0) ? ($change / $review1->score) * 100 : 0;

        return [
            'old_score' => $review1->score,
            'new_score' => $review2->score,
            'change' => round($change, 2),
            'percentage_change' => round($percentageChange, 2),
            'direction' => $change > 0 ? 'improved' : ($change < 0 ? 'declined' : 'unchanged')
        ];
    }

    /**
     * Calculate recommendation change between two reviews
     */
    private function calculateRecommendationChange(?AbstractReview $review1, ?AbstractReview $review2): array
    {
        if (!$review1 || !$review2) {
            return ['changed' => false, 'old' => null, 'new' => null];
        }

        $recommendationWeights = [
            'reject' => 1,
            'major_revisions' => 2,
            'minor_revisions' => 3,
            'accept_poster' => 4,
            'accept_oral' => 5,
        ];

        $oldWeight = $recommendationWeights[$review1->recommendation] ?? 0;
        $newWeight = $recommendationWeights[$review2->recommendation] ?? 0;

        return [
            'changed' => $review1->recommendation !== $review2->recommendation,
            'old' => $review1->recommendation,
            'new' => $review2->recommendation,
            'direction' => $newWeight > $oldWeight ? 'improved' : ($newWeight < $oldWeight ? 'declined' : 'unchanged')
        ];
    }

    /**
     * Determine if the review improved overall
     */
    private function determineIfImproved(?AbstractReview $review1, ?AbstractReview $review2): string
    {
        if (!$review1 || !$review2) {
            return 'n/a';
        }

        $scoreChange = $this->calculateScoreChange($review1, $review2);
        $recommendationChange = $this->calculateRecommendationChange($review1, $review2);

        // If both improved
        if ($scoreChange['direction'] === 'improved' && $recommendationChange['direction'] === 'improved') {
            return 'improved';
        }

        // If both declined
        if ($scoreChange['direction'] === 'declined' && $recommendationChange['direction'] === 'declined') {
            return 'declined';
        }

        // If score improved significantly (>10%)
        if ($scoreChange['change'] > 10) {
            return 'improved';
        }

        // If score declined significantly (<-10%)
        if ($scoreChange['change'] < -10) {
            return 'declined';
        }

        // Otherwise unchanged
        return 'same';
    }

    /**
     * Compare individual criteria between two reviews
     */
    private function compareCriteria(?AbstractReview $review1, ?AbstractReview $review2): array
    {
        if (!$review1 || !$review2) {
            return [];
        }

        $criteria = ['technical_quality', 'novelty', 'relevance', 'clarity'];
        $changes = [];

        foreach ($criteria as $criterion) {
            $old = $review1->$criterion;
            $new = $review2->$criterion;

            if ($old && $new) {
                $change = $new - $old;
                $changes[$criterion] = [
                    'old' => $old,
                    'new' => $new,
                    'change' => round($change, 2),
                    'direction' => $change > 0 ? 'improved' : ($change < 0 ? 'declined' : 'unchanged')
                ];
            }
        }

        return $changes;
    }

    /**
     * Get review history timeline for display
     * Returns formatted data ready for frontend consumption
     */
    public function getReviewTimeline(int $abstractId, int $reviewerId): array
    {
        $reviews = $this->getReviewHistory($abstractId, $reviewerId);
        $timeline = [];

        foreach ($reviews as $index => $review) {
            $previousReview = $index > 0 ? $reviews[$index - 1] : null;
            
            $timelineItem = [
                'round' => $review->review_round,
                'status' => $review->status,
                'score' => $review->score,
                'recommendation' => $review->recommendation,
                'comments' => $review->comments,
                'submitted_at' => $review->submitted_at,
                'is_revision_review' => $review->is_revision_review,
                'revision_notes' => $review->revision_notes ?? null,
            ];

            // Add comparison if there's a previous review
            if ($previousReview) {
                $timelineItem['comparison'] = $this->compareReviewRounds(
                    $abstractId,
                    $reviewerId,
                    $previousReview->review_round,
                    $review->review_round
                );
            }

            $timeline[] = $timelineItem;
        }

        return $timeline;
    }

    /**
     * Get statistics across all review rounds for an abstract
     */
    public function getReviewStatistics(int $abstractId): array
    {
        $reviews = AbstractReview::where('abstract_submission_id', $abstractId)
            ->with(['reviewer'])
            ->get();

        $rounds = $reviews->pluck('review_round')->unique()->sort()->values()->toArray();
        $reviewerCount = $reviews->pluck('reviewer_id')->unique()->count();

        $roundStats = [];
        foreach ($rounds as $round) {
            $roundReviews = $reviews->where('review_round', $round);
            $scores = $roundReviews->pluck('score')->filter();

            $roundStats[$round] = [
                'review_count' => $roundReviews->count(),
                'completed_count' => $roundReviews->where('status', 'submitted')->count(),
                'average_score' => $scores->isNotEmpty() ? round($scores->average(), 2) : null,
                'min_score' => $scores->isNotEmpty() ? $scores->min() : null,
                'max_score' => $scores->isNotEmpty() ? $scores->max() : null,
                'recommendations' => $roundReviews->pluck('recommendation')->filter()->toArray(),
            ];
        }

        return [
            'total_rounds' => count($rounds),
            'rounds' => $rounds,
            'total_reviewers' => $reviewerCount,
            'total_reviews' => $reviews->count(),
            'round_statistics' => $roundStats,
        ];
    }

    /**
     * Preserve a review snapshot before creating a new round
     * This ensures data integrity and creates an audit trail
     */
    public function preserveReviewSnapshot(AbstractReview $review): bool
    {
        try {
            // Link the new round to the previous round
            if ($review->review_round > 1) {
                $previousReview = $this->getPreviousReview($review);
                if ($previousReview) {
                    $review->update([
                        'previous_round_id' => $previousReview->id
                    ]);
                }
            }

            Log::info("Preserved review snapshot for review ID {$review->id}, round {$review->review_round}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to preserve review snapshot: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a reviewer has completed their review for a specific round
     */
    public function hasCompletedRound(int $abstractId, int $reviewerId, int $round): bool
    {
        return AbstractReview::where('abstract_submission_id', $abstractId)
            ->where('reviewer_id', $reviewerId)
            ->where('review_round', $round)
            ->where('status', 'submitted')
            ->exists();
    }

    /**
     * Get pending re-reviews for a reviewer (drafts from revision rounds)
     */
    public function getPendingReReviews(int $reviewerId): Collection
    {
        return AbstractReview::where('reviewer_id', $reviewerId)
            ->where('is_revision_review', true)
            ->where('status', '!=', 'submitted')
            ->with(['abstractSubmission'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get completed review count per round for an abstract
     */
    public function getCompletedReviewsPerRound(int $abstractId): array
    {
        $reviews = AbstractReview::where('abstract_submission_id', $abstractId)
            ->where('status', 'submitted')
            ->get()
            ->groupBy('review_round');

        $counts = [];
        foreach ($reviews as $round => $roundReviews) {
            $counts[$round] = $roundReviews->count();
        }

        return $counts;
    }
}

