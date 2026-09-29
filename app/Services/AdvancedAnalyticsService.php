<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\User;
use App\Models\ConferenceSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdvancedAnalyticsService
{
    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $totalAbstracts = AbstractSubmission::count();
        $pendingAbstracts = AbstractSubmission::where('status', 'pending')->count();
        $underReview = AbstractSubmission::where('status', 'under_review')->count();
        $acceptedAbstracts = AbstractSubmission::where('status', 'accepted')->count();
        $rejectedAbstracts = AbstractSubmission::where('status', 'rejected')->count();

        $totalReviewers = User::whereHas('roles', function($query) {
            $query->where('name', 'reviewer');
        })->count();

        $activeReviewers = AbstractReview::distinct('reviewer_id')->count();

        $avgReviewTime = AbstractReview::whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at) / 3600) as avg_hours')
            ->first()->avg_hours ?? 0;

        $completionRate = $totalAbstracts > 0 ?
            (($acceptedAbstracts + $rejectedAbstracts) / $totalAbstracts) * 100 : 0;

        return [
            'total_abstracts' => $totalAbstracts,
            'pending_abstracts' => $pendingAbstracts,
            'under_review' => $underReview,
            'accepted_abstracts' => $acceptedAbstracts,
            'rejected_abstracts' => $rejectedAbstracts,
            'total_reviewers' => $totalReviewers,
            'active_reviewers' => $activeReviewers,
            'avg_review_time_hours' => round($avgReviewTime, 1),
            'completion_rate' => round($completionRate, 1),
            'acceptance_rate' => $totalAbstracts > 0 ? round(($acceptedAbstracts / $totalAbstracts) * 100, 1) : 0,
        ];
    }

    /**
     * Get submission trends over time
     */
    public function getSubmissionTrends(int $days = 30): array
    {
        $trends = AbstractSubmission::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as submissions,
            COUNT(CASE WHEN status = \'accepted\' THEN 1 END) as accepted,
            COUNT(CASE WHEN status = \'rejected\' THEN 1 END) as rejected
        ')
        ->where('created_at', '>=', now()->subDays($days))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return [
            'labels' => $trends->pluck('date')->toArray(),
            'submissions' => $trends->pluck('submissions')->toArray(),
            'accepted' => $trends->pluck('accepted')->toArray(),
            'rejected' => $trends->pluck('rejected')->toArray()
        ];
    }

    /**
     * Get reviewer performance analytics
     */
    public function getReviewerAnalytics(): array
    {
        $reviewers = User::whereHas('roles', function($query) {
            $query->where('name', 'reviewer');
        })->with(['reviews' => function($query) {
            $query->whereNotNull('completed_at');
        }])->get();

        $analytics = [];

        foreach ($reviewers as $reviewer) {
            $reviews = $reviewer->reviews;
            $totalReviews = $reviews->count();

            if ($totalReviews === 0) continue;

            $avgScore = $reviews->avg('score');
            // Calculate average time manually to avoid DB::raw issues
            $totalHours = 0;
            $validReviews = 0;
            foreach ($reviews as $review) {
                if ($review->completed_at && $review->created_at) {
                    $hours = $review->created_at->diffInHours($review->completed_at);
                    $totalHours += $hours;
                    $validReviews++;
                }
            }
            $avgTime = $validReviews > 0 ? $totalHours / $validReviews : 0;
            $qualityScore = $this->calculateReviewerQualityScore($reviewer);

            $analytics[] = [
                'reviewer_id' => $reviewer->id,
                'reviewer_name' => $reviewer->full_name ?? $reviewer->name,
                'total_reviews' => $totalReviews,
                'avg_score' => round($avgScore, 1),
                'avg_time_hours' => round($avgTime, 1),
                'quality_score' => round($qualityScore, 1),
                'completion_rate' => $this->calculateCompletionRate($reviewer),
                'consistency_score' => $this->calculateConsistencyScore($reviewer)
            ];
        }

        // Sort by quality score
        usort($analytics, function($a, $b) {
            return $b['quality_score'] <=> $a['quality_score'];
        });

        return $analytics;
    }

    /**
     * Get abstract category distribution
     */
    public function getCategoryDistribution(): array
    {
        $distribution = AbstractSubmission::selectRaw('
            subtheme as category,
            COUNT(*) as count,
            COUNT(CASE WHEN status = \'accepted\' THEN 1 END) as accepted,
            COUNT(CASE WHEN status = \'rejected\' THEN 1 END) as rejected
        ')
        ->groupBy('subtheme')
        ->get();

        return [
            'categories' => $distribution->pluck('category')->toArray(),
            'counts' => $distribution->pluck('count')->toArray(),
            'accepted' => $distribution->pluck('accepted')->toArray(),
            'rejected' => $distribution->pluck('rejected')->toArray()
        ];
    }

    /**
     * Get session analytics
     */
    public function getSessionAnalytics(): array
    {
        $sessions = ConferenceSession::with(['abstracts'])->get();

        $analytics = [];

        foreach ($sessions as $session) {
            $abstracts = $session->abstracts;
            $totalAbstracts = $abstracts->count();
            $acceptedAbstracts = $abstracts->where('status', 'accepted')->count();

            $analytics[] = [
                'session_id' => $session->id,
                'session_name' => $session->name,
                'total_abstracts' => $totalAbstracts,
                'accepted_abstracts' => $acceptedAbstracts,
                'acceptance_rate' => $totalAbstracts > 0 ? round(($acceptedAbstracts / $totalAbstracts) * 100, 1) : 0,
                'avg_score' => $abstracts->avg('avg_score') ?? 0,
                'capacity_utilization' => $session->capacity > 0 ? round(($totalAbstracts / $session->capacity) * 100, 1) : 0
            ];
        }

        return $analytics;
    }

    /**
     * Get quality metrics
     */
    public function getQualityMetrics(): array
    {
        $totalReviews = AbstractReview::whereNotNull('completed_at')->count();
        $qualityIssues = AbstractReview::whereNotNull('completed_at')
            ->where(function($query) {
                $query->whereNull('technical_quality')
                      ->orWhereNull('novelty')
                      ->orWhereNull('relevance')
                      ->orWhereNull('clarity')
                      ->orWhereRaw('length(comments) < 50');
            })->count();

        $avgCommentLength = AbstractReview::whereNotNull('completed_at')
            ->whereNotNull('comments')
            ->avg(DB::raw('length(comments)')) ?? 0;

        // Calculate variance manually since SQLite doesn't have STDDEV
        $reviews = AbstractReview::whereNotNull('completed_at')->get();
        $scores = $reviews->pluck('score')->filter()->toArray();

        $scoreVariance = 0;
        if (count($scores) > 1) {
            $mean = array_sum($scores) / count($scores);
            $variance = array_sum(array_map(function($score) use ($mean) {
                return pow($score - $mean, 2);
            }, $scores)) / count($scores);
            $scoreVariance = sqrt($variance);
        }

        return [
            'total_reviews' => $totalReviews,
            'quality_issues' => $qualityIssues,
            'quality_issue_rate' => $totalReviews > 0 ? round(($qualityIssues / $totalReviews) * 100, 1) : 0,
            'avg_comment_length' => round($avgCommentLength),
            'score_variance' => round($scoreVariance, 2),
            'consistency_score' => $this->calculateOverallConsistencyScore()
        ];
    }

    /**
     * Get timeline analytics
     */
    public function getTimelineAnalytics(): array
    {
        $submissions = AbstractSubmission::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as submissions
        ')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        $reviews = AbstractReview::selectRaw('
            DATE(completed_at) as date,
            COUNT(*) as reviews
        ')
        ->whereNotNull('completed_at')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return [
            'submission_dates' => $submissions->pluck('date')->toArray(),
            'submission_counts' => $submissions->pluck('submissions')->toArray(),
            'review_dates' => $reviews->pluck('date')->toArray(),
            'review_counts' => $reviews->pluck('reviews')->toArray()
        ];
    }

    /**
     * Calculate reviewer quality score
     */
    private function calculateReviewerQualityScore(User $reviewer): float
    {
        $reviews = $reviewer->reviews()->whereNotNull('completed_at')->get();

        if ($reviews->isEmpty()) return 0;

        $scores = [];

        foreach ($reviews as $review) {
            $score = 0;

            // Completeness (25%)
            $criteriaCount = 0;
            if ($review->technical_quality !== null) $criteriaCount++;
            if ($review->novelty !== null) $criteriaCount++;
            if ($review->relevance !== null) $criteriaCount++;
            if ($review->clarity !== null) $criteriaCount++;
            $score += ($criteriaCount / 4) * 25;

            // Comment quality (25%)
            $commentLength = strlen($review->comments ?? '');
            $commentScore = min(25, ($commentLength / 100) * 25);
            $score += $commentScore;

            // Consistency (25%)
            $criteriaScores = array_filter([$review->technical_quality, $review->novelty, $review->relevance, $review->clarity]);
            if (!empty($criteriaScores)) {
                $variance = $this->calculateVariance($criteriaScores);
                $consistencyScore = max(0, 25 - ($variance * 5));
                $score += $consistencyScore;
            }

            // Timeliness (25%)
            $hoursToComplete = $review->created_at->diffInHours($review->completed_at);
            $timelinessScore = max(0, 25 - ($hoursToComplete / 24) * 10);
            $score += $timelinessScore;

            $scores[] = $score;
        }

        return array_sum($scores) / count($scores);
    }

    /**
     * Calculate completion rate for a reviewer
     */
    private function calculateCompletionRate(User $reviewer): float
    {
        $assignedReviews = $reviewer->reviews()->count();
        $completedReviews = $reviewer->reviews()->whereNotNull('completed_at')->count();

        return $assignedReviews > 0 ? ($completedReviews / $assignedReviews) * 100 : 0;
    }

    /**
     * Calculate consistency score for a reviewer
     */
    private function calculateConsistencyScore(User $reviewer): float
    {
        $reviews = $reviewer->reviews()->whereNotNull('completed_at')->get();

        if ($reviews->isEmpty()) return 0;

        $scores = [];
        foreach ($reviews as $review) {
            $criteriaScores = array_filter([$review->technical_quality, $review->novelty, $review->relevance, $review->clarity]);
            if (!empty($criteriaScores)) {
                $scores[] = $this->calculateVariance($criteriaScores);
            }
        }

        if (empty($scores)) return 0;

        $avgVariance = array_sum($scores) / count($scores);
        return max(0, 100 - ($avgVariance * 20));
    }

    /**
     * Calculate overall consistency score
     */
    private function calculateOverallConsistencyScore(): float
    {
        $reviews = AbstractReview::whereNotNull('completed_at')->get();

        if ($reviews->isEmpty()) return 0;

        $variances = [];
        foreach ($reviews as $review) {
            $criteriaScores = array_filter([$review->technical_quality, $review->novelty, $review->relevance, $review->clarity]);
            if (!empty($criteriaScores)) {
                $variances[] = $this->calculateVariance($criteriaScores);
            }
        }

        if (empty($variances)) return 0;

        $avgVariance = array_sum($variances) / count($variances);
        return max(0, 100 - ($avgVariance * 20));
    }

    /**
     * Calculate variance of an array of numbers
     */
    private function calculateVariance(array $numbers): float
    {
        $count = count($numbers);
        if ($count === 0) return 0;

        $mean = array_sum($numbers) / $count;
        $variance = 0;

        foreach ($numbers as $number) {
            $variance += pow($number - $mean, 2);
        }

        return $variance / $count;
    }

    /**
     * Generate comprehensive report
     */
    public function generateComprehensiveReport(): array
    {
        return [
            'dashboard_stats' => $this->getDashboardStats(),
            'submission_trends' => $this->getSubmissionTrends(),
            'reviewer_analytics' => $this->getReviewerAnalytics(),
            'category_distribution' => $this->getCategoryDistribution(),
            'session_analytics' => $this->getSessionAnalytics(),
            'quality_metrics' => $this->getQualityMetrics(),
            'timeline_analytics' => $this->getTimelineAnalytics(),
            'generated_at' => now()->toISOString()
        ];
    }
}
