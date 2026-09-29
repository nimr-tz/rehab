<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ReviewWorkflowService
{
    // Enhanced configuration constants - should be configurable by admin
    const ACCEPT_THRESHOLD = 75;        // Average score >= 75% = Accept
    const REVISION_THRESHOLD = 50;      // 50-74% = Revision required  
    const REJECT_THRESHOLD = 49;        // < 50% = Reject
    
    // Disagreement thresholds
    const DISAGREEMENT_THRESHOLD = 20;  // Major disagreement
    const MINOR_DISAGREEMENT_THRESHOLD = 15;  // Minor disagreement
    
    // Quality score thresholds
    const EXCELLENT_THRESHOLD = 90;     // Excellent quality
    const GOOD_THRESHOLD = 80;         // Good quality
    const SATISFACTORY_THRESHOLD = 70; // Satisfactory quality
    
    // Decision confidence levels
    const HIGH_CONFIDENCE_THRESHOLD = 85;   // High confidence decisions
    const LOW_CONFIDENCE_THRESHOLD = 60;    // Low confidence decisions
    
    // Status constants for consistency
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_MINOR_REVISION = 'minor_revision';
    const STATUS_MAJOR_REVISION = 'major_revision';
    const STATUS_READY_FOR_DECISION = 'ready_for_decision';

    /**
     * Enhanced decision processing with comprehensive logic
     */
    public function processFinalDecision(AbstractSubmission $abstract)
    {
        // Ensure both reviews are complete
        if (!$this->hasBothReviewsComplete($abstract)) {
            return false;
        }

        $review1 = $this->getReview($abstract, 1);
        $review2 = $this->getReview($abstract, 2);
        
        // Calculate comprehensive decision metrics
        $metrics = $this->calculateDecisionMetrics($review1, $review2);
        
        // Update scores first
        $abstract->update([
            'reviewer_1_score' => $review1['score'],
            'reviewer_2_score' => $review2['score'],
            'average_score' => $metrics['average_score'],
            'review_completed_at' => now(),
        ]);

        // Calculate the final decision using specific human-driven logic
        $decision = $this->calculateEnhancedDecision($review1, $review2);
        
        // Update status and add comprehensive decision notes
        $abstract->update([
            'status' => $decision['status'],
            'admin_comment' => $decision['reason'],
            'status_changed_at' => now(),
            'status_changed_by' => null // System decision
        ]);

        // Log the decision for audit trail
        Log::info("Auto-decision for abstract {$abstract->id}", [
            'decision' => $decision['status'],
            'scores' => [$review1['score'], $review2['score']],
            'average' => $metrics['average_score'],
            'confidence' => $decision['confidence'] ?? 'medium',
            'reason' => $decision['reason']
        ]);

        return $decision;
    }

    /**
     * Calculate comprehensive decision metrics
     */
    private function calculateDecisionMetrics($review1, $review2)
    {
        $averageScore = ($review1['score'] + $review2['score']) / 2;
        
        return [
            'average_score' => $averageScore,
        ];
    }

    /**
     * Enhanced decision calculation with human-driven logic
     */
    private function calculateEnhancedDecision($review1, $review2)
    {
        $recommendation1 = $review1['recommendation'];
        $recommendation2 = $review2['recommendation'];
        
        if ($recommendation1 === 'accept' && $recommendation2 === 'accept') {
            return [
                'status' => self::STATUS_ACCEPTED,
                'reason' => 'Unanimous acceptance by both reviewers.',
                'confidence' => 'high'
            ];
        }
        
        return [
            'status' => self::STATUS_READY_FOR_DECISION,
            'reason' => sprintf(
                'Manual decision needed. (Reviewer 1: %s, Reviewer 2: %s)',
                $this->formatRecommendation($recommendation1),
                $this->formatRecommendation($recommendation2)
            ),
            'confidence' => 'medium'
        ];
    }

    /**
     * Format recommendation for display
     */
    private function formatRecommendation($rec)
    {
        return match($rec) {
            'accept' => 'Accept',
            'accept_with_revisions' => 'Accept with Revisions',
            'reject' => 'Reject',
            default => str_replace('_', ' ', ucfirst($rec))
        };
    }

    /**
     * Get detailed decision analysis for admin review
     */
    public function getDecisionAnalysis(AbstractSubmission $abstract)
    {
        if (!$this->hasBothReviewsComplete($abstract)) {
            return null;
        }

        $review1 = $this->getReview($abstract, 1);
        $review2 = $this->getReview($abstract, 2);
        $metrics = $this->calculateDecisionMetrics($review1, $review2);
        
        return [
            'reviews' => [
                'reviewer_1' => $review1,
                'reviewer_2' => $review2
            ],
            'metrics' => $metrics,
            'suggested_decision' => $this->calculateEnhancedDecision($review1, $review2),
            'decision_history' => $this->getDecisionHistory($abstract)
        ];
    }
    
    /**
     * Get decision history for an abstract
     */
    private function getDecisionHistory(AbstractSubmission $abstract)
    {
        return [
            'current_status' => $abstract->status,
            'status_changed_at' => $abstract->status_changed_at,
            'status_changed_by' => $abstract->status_changed_by,
            'admin_comment' => $abstract->admin_comment
        ];
    }

    /**
     * Check if both reviews are complete
     */
    private function hasBothReviewsComplete(AbstractSubmission $abstract)
    {
        return !is_null($abstract->reviewer_1_score) && 
               !is_null($abstract->reviewer_2_score) &&
               $abstract->reviewer_id && 
               $abstract->reviewer_2_id;
    }

    /**
     * Get review data for a specific reviewer position
     */
    private function getReview(AbstractSubmission $abstract, $reviewerPosition)
    {
        if ($reviewerPosition === 1) {
            return [
                'score' => $abstract->reviewer_1_score,
                'comments' => $abstract->reviewer_1_comments,
                'recommendation' => $this->getReviewerRecommendation($abstract->id, $abstract->reviewer_id)
            ];
        } else {
            return [
                'score' => $abstract->reviewer_2_score,
                'comments' => $abstract->reviewer_2_comments,
                'recommendation' => $this->getReviewerRecommendation($abstract->id, $abstract->reviewer_2_id)
            ];
        }
    }

    /**
     * Get reviewer's recommendation from the abstract_reviews table
     */
    private function getReviewerRecommendation($abstractId, $reviewerId)
    {
        $review = DB::table('abstract_reviews')
            ->where('abstract_submission_id', $abstractId)
            ->where('reviewer_id', $reviewerId)
            ->where('status', 'submitted')
            ->orderBy('review_round', 'desc')
            ->orderBy('id', 'desc')
            ->first();
            
        return $review ? $review->recommendation : 'accept'; // Default fallback
    }

    /**
     * Get enhanced decision summary for admin dashboard
     */
    public function getDecisionStatistics()
    {
        $total = AbstractSubmission::whereNotNull('average_score')->count();
        
        return [
            'total_decided' => $total,
            'automatic_accepted' => AbstractSubmission::where('status', self::STATUS_ACCEPTED)
                ->where('admin_comment', 'LIKE', '%Unanimous acceptance%')->count(),
            'automatic_rejected' => AbstractSubmission::where('status', self::STATUS_REJECTED)->count(),
            'ready_for_decision' => AbstractSubmission::where('status', self::STATUS_READY_FOR_DECISION)->count(),
            'minor_revisions' => AbstractSubmission::where('status', self::STATUS_MINOR_REVISION)->count(),
            'major_revisions' => AbstractSubmission::where('status', self::STATUS_MAJOR_REVISION)->count(),
        ];
    }

    /**
     * Get decision breakdown by quality levels
     */
    public function getQualityStatistics()
    {
        return [
            'excellent' => AbstractSubmission::where('average_score', '>=', self::EXCELLENT_THRESHOLD)->count(),
            'good' => AbstractSubmission::whereBetween('average_score', [self::GOOD_THRESHOLD, self::EXCELLENT_THRESHOLD - 0.1])->count(),
            'satisfactory' => AbstractSubmission::whereBetween('average_score', [self::SATISFACTORY_THRESHOLD, self::GOOD_THRESHOLD - 0.1])->count(),
            'needs_improvement' => AbstractSubmission::whereBetween('average_score', [self::REVISION_THRESHOLD, self::SATISFACTORY_THRESHOLD - 0.1])->count(),
            'poor' => AbstractSubmission::where('average_score', '<', self::REVISION_THRESHOLD)->count(),
        ];
    }

    /**
     * Override automatic decision (for admin use)
     */
    public function overrideDecision(AbstractSubmission $abstract, $newStatus, $reason, $adminUserId)
    {
        $oldStatus = $abstract->status;
        
        $abstract->update([
            'status' => $newStatus,
            'admin_comment' => "Manual override: {$reason} (Previous: {$oldStatus})",
            'status_changed_at' => now(),
            'status_changed_by' => $adminUserId
        ]);

        Log::info("Admin override decision for abstract {$abstract->id}", [
            'admin_id' => $adminUserId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason
        ]);

        return true;
    }

    /**
     * Get configuration for admin settings
     */
    public function getConfiguration()
    {
        return [
            'thresholds' => [
                'accept' => self::ACCEPT_THRESHOLD,
                'revision' => self::REVISION_THRESHOLD,
                'reject' => self::REJECT_THRESHOLD,
                'excellent' => self::EXCELLENT_THRESHOLD,
                'good' => self::GOOD_THRESHOLD,
                'satisfactory' => self::SATISFACTORY_THRESHOLD,
            ],
            'disagreement_levels' => [
                'major' => self::DISAGREEMENT_THRESHOLD,
                'minor' => self::MINOR_DISAGREEMENT_THRESHOLD,
            ],
            'confidence_levels' => [
                'high' => self::HIGH_CONFIDENCE_THRESHOLD,
                'low' => self::LOW_CONFIDENCE_THRESHOLD,
            ]
        ];
    }

    /**
     * Validate configuration values
     */
    public function validateConfiguration($config)
    {
        $errors = [];
        
        if ($config['accept_threshold'] <= $config['revision_threshold']) {
            $errors[] = 'Accept threshold must be higher than revision threshold';
        }
        
        if ($config['revision_threshold'] <= $config['reject_threshold']) {
            $errors[] = 'Revision threshold must be higher than reject threshold';
        }
        
        if ($config['disagreement_threshold'] < 5 || $config['disagreement_threshold'] > 50) {
            $errors[] = 'Disagreement threshold must be between 5% and 50%';
        }
        
        return $errors;
    }
}
