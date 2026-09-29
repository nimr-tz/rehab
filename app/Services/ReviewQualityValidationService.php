<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewQualityValidationService
{
    // Quality thresholds
    const MIN_COMMENT_LENGTH = 50;
    const MAX_SCORE_DIFFERENCE = 30; // Flag reviews with >30% difference
    const MIN_REVIEW_TIME = 300; // 5 minutes minimum review time
    const MAX_REVIEW_TIME = 7200; // 2 hours maximum review time

    /**
     * Validate review quality for a specific abstract
     */
    public function validateReviewQuality($abstractId)
    {
        $abstract = AbstractSubmission::find($abstractId);
        if (!$abstract) {
            return ['valid' => false, 'error' => 'Abstract not found'];
        }

        $issues = [];
        $warnings = [];

        // Get both reviews
        $review1 = $abstract->review1;
        $review2 = $abstract->review2;

        if (!$review1 || !$review2) {
            return ['valid' => false, 'error' => 'Both reviews not found'];
        }

        // Check individual review quality
        $review1Quality = $this->validateIndividualReview($review1);
        $review2Quality = $this->validateIndividualReview($review2);

        if (!$review1Quality['valid']) {
            foreach ($review1Quality['issues'] as $issue) {
                $issues[] = "Reviewer 1: " . $issue;
            }
        }

        if (!$review2Quality['valid']) {
            foreach ($review2Quality['issues'] as $issue) {
                $issues[] = "Reviewer 2: " . $issue;
            }
        }

        // Check for review consistency
        $consistencyCheck = $this->checkReviewConsistency($review1, $review2);
        $issues = array_merge($issues, $consistencyCheck['issues']);
        $warnings = array_merge($warnings, $consistencyCheck['warnings']);

        // Check for potential bias or conflicts
        $biasCheck = $this->checkForBias($abstract);
        $issues = array_merge($issues, $biasCheck['issues']);
        $warnings = array_merge($warnings, $biasCheck['warnings']);

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
            'review1_quality' => $review1Quality,
            'review2_quality' => $review2Quality,
            'consistency' => $consistencyCheck,
            'bias_check' => $biasCheck
        ];
    }

    /**
     * Validate individual review quality
     */
    private function validateIndividualReview($review)
    {
        $issues = [];
        $warnings = [];

        // Check comment length
        if (strlen($review->comments ?? '') < self::MIN_COMMENT_LENGTH) {
            $issues[] = "Comments too short (minimum " . self::MIN_COMMENT_LENGTH . " characters)";
        }

        // Check if all criteria are scored
        $newCriteria = [
            'title_score', 'word_count_score', 'writing_quality_score', 'structure_score',
            'background_score', 'rationale_score', 'objective_score', 'methodology_design_score',
            'methodology_analysis_score', 'results_logic_score', 'results_findings_score',
            'results_data_score', 'conclusion_interpretation_score', 'conclusion_impact_score',
            'relevance_theme_score'
        ];

        $oldCriteria = ['technical_quality', 'novelty', 'relevance', 'clarity'];

        $usingNew = !is_null($review->title_score);
        $checkList = $usingNew ? $newCriteria : $oldCriteria;

        foreach ($checkList as $criterion) {
            if (is_null($review->$criterion)) {
                $issues[] = "Missing " . str_replace('_', ' ', $criterion) . " score";
            }
        }

        // Check for extreme scores without justification
        if ($review->score <= 20 && strlen($review->comments ?? '') < 100) {
            $warnings[] = "Very low score ({$review->score}) with minimal justification";
        }

        if ($review->score >= 90 && strlen($review->comments ?? '') < 100) {
            $warnings[] = "Very high score ({$review->score}) with minimal justification";
        }

        // Check review time (if available)
        if ($review->created_at && $review->completed_at) {
            $reviewTime = $review->completed_at->diffInSeconds($review->created_at);

            if ($reviewTime < self::MIN_REVIEW_TIME) {
                $warnings[] = "Review completed very quickly ({$reviewTime} seconds)";
            }

            if ($reviewTime > self::MAX_REVIEW_TIME) {
                $warnings[] = "Review took unusually long ({$reviewTime} seconds)";
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
            'score' => $review->score,
            'comment_length' => strlen($review->comments ?? ''),
            'review_time' => $review->created_at && $review->completed_at ?
                $review->completed_at->diffInSeconds($review->created_at) : null
        ];
    }

    /**
     * Check consistency between two reviews
     */
    private function checkReviewConsistency($review1, $review2)
    {
        $issues = [];
        $warnings = [];

        $scoreDifference = abs($review1->score - $review2->score);

        // Flag large score differences
        if ($scoreDifference > self::MAX_SCORE_DIFFERENCE) {
            $issues[] = "Large score difference ({$scoreDifference}%) between reviewers";
        } elseif ($scoreDifference > 20) {
            $warnings[] = "Moderate score difference ({$scoreDifference}%) between reviewers";
        }

        // Check for conflicting recommendations
        $recommendations = [$review1->recommendation, $review2->recommendation];

        if (in_array('reject', $recommendations) && in_array('accept_oral', $recommendations)) {
            $issues[] = "Conflicting recommendations: one reviewer recommends reject, another recommends accept";
        }

        if (in_array('major_revisions', $recommendations) && in_array('accept_oral', $recommendations)) {
            $warnings[] = "Conflicting recommendations: major revisions vs accept";
        }

        // Check for similar comment patterns (potential collusion)
        $similarity = $this->calculateCommentSimilarity($review1->comments, $review2->comments);
        if ($similarity > 0.8) {
            $warnings[] = "Very similar comments between reviewers (potential collusion)";
        }

        return [
            'score_difference' => $scoreDifference,
            'recommendation_conflict' => $this->hasRecommendationConflict($recommendations),
            'comment_similarity' => $similarity,
            'issues' => $issues,
            'warnings' => $warnings
        ];
    }

    /**
     * Check for potential bias or conflicts
     */
    private function checkForBias($abstract)
    {
        $issues = [];
        $warnings = [];

        // Check if reviewers are from the same institution as authors
        $authorInstitution = $abstract->institution;
        $reviewer1 = $abstract->reviewer1;
        $reviewer2 = $abstract->reviewer2;

        if ($reviewer1 && $reviewer1->institution === $authorInstitution) {
            $warnings[] = "Reviewer 1 is from the same institution as the author";
        }

        if ($reviewer2 && $reviewer2->institution === $authorInstitution) {
            $warnings[] = "Reviewer 2 is from the same institution as the author";
        }

        // Check for co-author conflicts
        $coauthors = $abstract->coauthors ?? [];
        foreach ($coauthors as $coauthor) {
            if ($reviewer1 && $this->namesAreSimilar($coauthor['name'], $reviewer1->full_name)) {
                $issues[] = "Reviewer 1 appears to be a co-author";
            }
            if ($reviewer2 && $this->namesAreSimilar($coauthor['name'], $reviewer2->full_name)) {
                $issues[] = "Reviewer 2 appears to be a co-author";
            }
        }

        return [
            'institution_conflicts' => $warnings,
            'coauthor_conflicts' => $issues,
            'issues' => $issues,
            'warnings' => $warnings
        ];
    }

    /**
     * Calculate similarity between two comment strings
     */
    private function calculateCommentSimilarity($comment1, $comment2)
    {
        if (!$comment1 || !$comment2) {
            return 0;
        }

        // Simple similarity calculation using similar_text
        similar_text(strtolower($comment1), strtolower($comment2), $percent);
        return $percent / 100;
    }

    /**
     * Check if recommendations conflict
     */
    private function hasRecommendationConflict($recommendations)
    {
        $acceptRecommendations = ['accept_oral', 'accept_poster'];
        $rejectRecommendations = ['reject'];
        $revisionRecommendations = ['minor_revisions', 'major_revisions'];

        $hasAccept = in_array($recommendations[0], $acceptRecommendations) ||
                    in_array($recommendations[1], $acceptRecommendations);
        $hasReject = in_array($recommendations[0], $rejectRecommendations) ||
                    in_array($recommendations[1], $rejectRecommendations);
        $hasRevision = in_array($recommendations[0], $revisionRecommendations) ||
                      in_array($recommendations[1], $revisionRecommendations);

        return ($hasAccept && $hasReject) || ($hasAccept && in_array('major_revisions', $recommendations));
    }

    /**
     * Check if names are similar (for conflict detection)
     */
    private function namesAreSimilar($name1, $name2)
    {
        if (!$name1 || !$name2) {
            return false;
        }

        // Remove titles and normalize
        $cleanName1 = preg_replace('/\b(dr|prof|professor|mr|ms|mrs)\b\.?\s*/i', '', $name1);
        $cleanName2 = preg_replace('/\b(dr|prof|professor|mr|ms|mrs)\b\.?\s*/i', '', $name2);

        similar_text(strtolower($cleanName1), strtolower($cleanName2), $percent);
        return $percent > 85;
    }

    /**
     * Get quality statistics for all reviews
     */
    public function getQualityStatistics()
    {
        $reviews = AbstractReview::where('status', 'submitted')
            ->with(['abstractSubmission', 'reviewer'])
            ->get();

        $stats = [
            'total_reviews' => $reviews->count(),
            'short_comments' => $reviews->filter(fn($r) => strlen($r->comments ?? '') < self::MIN_COMMENT_LENGTH)->count(),
            'missing_criteria' => $reviews->filter(fn($r) =>
                (is_null($r->title_score) && (is_null($r->technical_quality) || is_null($r->novelty) || is_null($r->relevance) || is_null($r->clarity))) ||
                (!is_null($r->title_score) && (
                    is_null($r->word_count_score) || is_null($r->writing_quality_score) || is_null($r->structure_score) ||
                    is_null($r->background_score) || is_null($r->rationale_score) || is_null($r->objective_score) ||
                    is_null($r->methodology_design_score) || is_null($r->methodology_analysis_score) ||
                    is_null($r->results_logic_score) || is_null($r->results_findings_score) || is_null($r->results_data_score) ||
                    is_null($r->conclusion_interpretation_score) || is_null($r->conclusion_impact_score) || is_null($r->relevance_theme_score)
                ))
            )->count(),
            'extreme_scores' => $reviews->filter(fn($r) => $r->score <= 20 || $r->score >= 90)->count(),
            'average_comment_length' => $reviews->avg(fn($r) => strlen($r->comments ?? '')),
            'average_score' => $reviews->avg('score'),
            'score_distribution' => [
                '0-20' => $reviews->filter(fn($r) => $r->score <= 20)->count(),
                '21-40' => $reviews->filter(fn($r) => $r->score > 20 && $r->score <= 40)->count(),
                '41-60' => $reviews->filter(fn($r) => $r->score > 40 && $r->score <= 60)->count(),
                '61-80' => $reviews->filter(fn($r) => $r->score > 60 && $r->score <= 80)->count(),
                '81-100' => $reviews->filter(fn($r) => $r->score > 80)->count(),
            ]
        ];

        return $stats;
    }

    /**
     * Flag abstracts that need quality review
     */
    public function getAbstractsNeedingQualityReview()
    {
        $abstracts = AbstractSubmission::where('status', 'ready_for_decision')
            ->with(['review1', 'review2'])
            ->get();

        $flaggedAbstracts = [];

        foreach ($abstracts as $abstract) {
            $qualityCheck = $this->validateReviewQuality($abstract->id);

            if (!$qualityCheck['valid'] || !empty($qualityCheck['warnings'])) {
                $flaggedAbstracts[] = [
                    'abstract' => $abstract,
                    'quality_check' => $qualityCheck
                ];
            }
        }

        return $flaggedAbstracts;
    }
}
