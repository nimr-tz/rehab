<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\User;
use App\Models\ReviewHistory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReviewerPerformanceTrackingService
{
    private const REVIEW_REMINDER_DAYS = 3;
    private const REVIEW_OVERDUE_DAYS = 4;
    private const ACTIVE_REVIEW_STATUSES = [
        'reviewer_assigned',
        'under_review',
        'revision_submitted',
        'revision_under_review',
    ];
    private const PORTFOLIO_REVIEW_STATUSES = [
        'reviewer_assigned',
        'under_review',
        'revision_submitted',
        'revision_under_review',
        'ready_for_decision',
        'minor_revision_required',
        'major_revision_required',
    ];

    public function getActiveReviewStatuses(): array
    {
        return self::ACTIVE_REVIEW_STATUSES;
    }

    /**
     * Get comprehensive performance metrics for a reviewer
     */
    public function getReviewerPerformance($reviewerId)
    {
        $reviewer = User::find($reviewerId);
        if (!$reviewer) {
            return null;
        }
        
        // Get submitted reviews by this reviewer (drafts should not affect quality/performance)
        $reviews = AbstractReview::where('reviewer_id', $reviewerId)
            ->where('status', 'submitted')
            ->with(['abstractSubmission'])
            ->get();
        
        // Get assignment history
        $assignments = $this->getAssignmentHistory($reviewerId);
        
        // Calculate metrics
        $metrics = [
            'basic_stats' => $this->calculateBasicStats($reviews, $assignments),
            'quality_metrics' => $this->calculateQualityMetrics($reviews),
            'consistency_metrics' => $this->calculateConsistencyMetrics($reviews),
            'timeliness_metrics' => $this->calculateTimelinessMetrics($reviews, $assignments),
            'workload_metrics' => $this->calculateWorkloadMetrics($reviewerId),
            'comparison_metrics' => $this->calculateComparisonMetrics($reviews)
        ];
        
        return [
            'reviewer' => $reviewer,
            'metrics' => $metrics,
            'recent_activity' => $this->getRecentActivity($reviewerId),
            'performance_trends' => $this->getPerformanceTrends($reviewerId)
        ];
    }
    
    /**
     * Calculate basic performance statistics
     */
    private function calculateBasicStats($reviews, $assignments)
    {
        $totalAssigned = $assignments->count();
        $totalCompleted = $reviews->count();
        // If no assignment history exists but reviews are present, use completed count as denominator
        $denominator = $totalAssigned > 0 ? $totalAssigned : $totalCompleted;
        $completionRate = $denominator > 0 ? ($totalCompleted / $denominator) * 100 : 0;
        
        $averageScore = $reviews->avg('score');
        $averageCommentLength = $reviews->avg(fn($r) => strlen($r->comments ?? ''));
        
        return [
            'total_assigned' => $totalAssigned,
            'total_completed' => $totalCompleted,
            'completion_rate' => round($completionRate, 2),
            'average_score' => round($averageScore, 2),
            'average_comment_length' => round($averageCommentLength, 0),
            'total_review_time' => $this->calculateTotalReviewTime($reviews),
            'average_review_time' => $this->calculateAverageReviewTime($reviews)
        ];
    }
    
    /**
     * Calculate quality metrics.
     *
     * Per-review score = 70 (base for submitting) + comment bonus (0–15) + criteria bonus (0–15).
     * Overall quality score is the average of all per-review scores, so the minimum is 70.
     */
    private function calculateQualityMetrics($reviews)
    {
        $shortComments = 0;
        $missingCriteria = 0;
        $extremeScores = 0;
        $qualityIssues = 0;
        $perReviewScores = [];

        foreach ($reviews as $review) {
            $commentLen = strlen($review->comments ?? '');

            if ($commentLen < 50) {
                $shortComments++;
            }

            // Comment bonus: 0–15 pts
            if ($commentLen === 0) {
                $commentBonus = 0;
            } elseif ($commentLen < 50) {
                $commentBonus = 5;
            } elseif ($commentLen < 150) {
                $commentBonus = 10;
            } else {
                $commentBonus = 15;
            }

            // Criteria completeness bonus: 0–15 pts (proportional to fields filled)
            $usingNew = !is_null($review->title_score);
            if ($usingNew) {
                $criteriaFields = [
                    $review->word_count_score, $review->writing_quality_score,
                    $review->structure_score, $review->background_score,
                    $review->rationale_score, $review->objective_score,
                    $review->methodology_design_score, $review->methodology_analysis_score,
                    $review->results_logic_score, $review->results_findings_score,
                    $review->results_data_score, $review->conclusion_interpretation_score,
                    $review->conclusion_impact_score, $review->relevance_theme_score,
                ];
            } else {
                $criteriaFields = [
                    $review->technical_quality, $review->novelty,
                    $review->relevance, $review->clarity,
                ];
            }

            $total = count($criteriaFields);
            $filled = count(array_filter($criteriaFields, fn($v) => !is_null($v)));
            $criteriaBonus = $total > 0 ? ($filled / $total) * 15 : 15;

            if ($filled < $total) {
                $missingCriteria++;
            }

            // Track legacy quality issues count for informational purposes
            if ($commentLen < 50 || $filled < $total) {
                $qualityIssues++;
            }

            // Check for extreme scores
            if ($review->score <= 20 || $review->score >= 90) {
                $extremeScores++;
            }

            $perReviewScores[] = 70 + $commentBonus + $criteriaBonus;
        }

        $totalReviews = $reviews->count();
        $qualityScore = $totalReviews > 0
            ? array_sum($perReviewScores) / $totalReviews
            : 0;

        return [
            'quality_score' => round($qualityScore, 2),
            'short_comments' => $shortComments,
            'missing_criteria' => $missingCriteria,
            'extreme_scores' => $extremeScores,
            'quality_issues' => $qualityIssues,
            'total_reviews' => $totalReviews,
        ];
    }
    
    /**
     * Calculate consistency metrics
     */
    private function calculateConsistencyMetrics($reviews)
    {
        $consistencyScores = [];
        $scoreDifferences = [];
        
        // Group reviews by abstract to compare with other reviewers
        $abstractGroups = $reviews->groupBy('abstract_submission_id');
        
        foreach ($abstractGroups as $abstractId => $reviewGroup) {
            if ($reviewGroup->count() > 1) {
                // Multiple reviews for same abstract (shouldn't happen normally)
                continue;
            }
            
            $review = $reviewGroup->first();
            $abstract = $review->abstractSubmission;
            
            // Get the other reviewer's score
            $otherReview = AbstractReview::where('abstract_submission_id', $abstractId)
                ->where('reviewer_id', '!=', $review->reviewer_id)
                ->where('status', 'submitted')
                ->first();
            
            if ($otherReview) {
                $scoreDiff = abs($review->score - $otherReview->score);
                $scoreDifferences[] = $scoreDiff;
                
                // Calculate consistency score (lower difference = higher consistency)
                $consistencyScore = max(0, 100 - ($scoreDiff * 2)); // 20% difference = 60% consistency
                $consistencyScores[] = $consistencyScore;
            }
        }
        
        return [
            'average_consistency_score' => count($consistencyScores) > 0 ? round(array_sum($consistencyScores) / count($consistencyScores), 2) : 0,
            'average_score_difference' => count($scoreDifferences) > 0 ? round(array_sum($scoreDifferences) / count($scoreDifferences), 2) : 0,
            'consistency_comparisons' => count($consistencyScores),
            'high_disagreement_reviews' => count(array_filter($scoreDifferences, fn($diff) => $diff > 20))
        ];
    }
    
    /**
     * Calculate timeliness metrics
     */
    private function calculateTimelinessMetrics($reviews, $assignments)
    {
        $onTimeReviews = 0;
        $lateReviews = 0;
        $earlyReviews = 0;
        $reviewTimes = [];
        
        foreach ($reviews as $review) {
            $abstract = $review->abstractSubmission;
            
            $endAt = $review->submitted_at ?? $review->completed_at;
            if ($endAt) {
                $startAt = $review->assigned_at ?? $abstract->assigned_at ?? $review->created_at;
                $reviewTime = $startAt ? $endAt->diffInHours($startAt) : null;
                if (!is_null($reviewTime)) {
                    $reviewTimes[] = $reviewTime;
                }
                
                // Measure timeliness against the live reviewer SLA:
                // reminder at 72 hours, overdue at 96 hours.
                $assignmentDate = $startAt ?? $endAt;
                $expectedDeadline = Carbon::parse($assignmentDate)->addDays(self::REVIEW_OVERDUE_DAYS);
                
                if ($endAt <= $expectedDeadline) {
                    $onTimeReviews++;
                } else {
                    $lateReviews++;
                }
                
                // Check if review was completed too quickly (< 1 hour)
                if (!is_null($reviewTime) && $reviewTime < 1) {
                    $earlyReviews++;
                }
            }
        }
        
        $totalReviews = $reviews->count();
        $timelinessScore = $totalReviews > 0 ? ($onTimeReviews / $totalReviews) * 100 : 0;
        
        return [
            'timeliness_score' => round($timelinessScore, 2),
            'on_time_reviews' => $onTimeReviews,
            'late_reviews' => $lateReviews,
            'early_reviews' => $earlyReviews,
            'average_review_time_hours' => count($reviewTimes) > 0 ? round(array_sum($reviewTimes) / count($reviewTimes), 2) : 0,
            'total_reviews' => $totalReviews
        ];
    }
    
    /**
     * Calculate workload metrics
     */
    private function calculateWorkloadMetrics($reviewerId)
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        $currentMonthReviews = AbstractReview::where('reviewer_id', $reviewerId)
            ->where('status', 'submitted')
            ->where(function ($q) use ($currentMonth) {
                $q->where('submitted_at', '>=', $currentMonth)
                  ->orWhere('completed_at', '>=', $currentMonth);
            })
            ->count();
            
        $lastMonthReviews = AbstractReview::where('reviewer_id', $reviewerId)
            ->where('status', 'submitted')
            ->where(function ($q) use ($lastMonth, $currentMonth) {
                $q->whereBetween('submitted_at', [$lastMonth, $currentMonth])
                  ->orWhereBetween('completed_at', [$lastMonth, $currentMonth]);
            })
            ->count();
        
        $reviewer = User::find($reviewerId);
        
        $currentAssignments = ($reviewer && $reviewer->reviewer_preferences_set) ? AbstractSubmission::where(function($query) use ($reviewerId) {
            $query->where('reviewer_id', $reviewerId)
                  ->orWhere('reviewer_2_id', $reviewerId);
        })->where('status', '!=', 'accepted')
          ->where('status', '!=', 'rejected')
          ->count() : 0;
        
        return [
            'current_month_reviews' => $currentMonthReviews,
            'last_month_reviews' => $lastMonthReviews,
            'current_assignments' => $currentAssignments,
            'workload_change' => $lastMonthReviews > 0 ? 
                round((($currentMonthReviews - $lastMonthReviews) / $lastMonthReviews) * 100, 2) : 0
        ];
    }
    
    /**
     * Calculate comparison metrics against other reviewers
     */
    private function calculateComparisonMetrics($reviews)
    {
        if ($reviews->count() === 0) {
            return [
                'average_score_percentile' => 0,
                'completion_rate_percentile' => 0,
                'quality_score_percentile' => 0
            ];
        }
        
        $reviewerId = $reviews->first()->reviewer_id;
        
        // Get all reviewers' metrics for comparison
        $allReviewers = User::whereHas('roles', function($query) {
            $query->where('name', 'reviewer');
        })->get();
        $reviewerMetrics = [];
        
        foreach ($allReviewers as $reviewer) {
            $reviewerReviews = AbstractReview::where('reviewer_id', $reviewer->id)
                ->where('status', 'submitted')
                ->get();
            
            if ($reviewerReviews->count() > 0) {
                $reviewerMetrics[] = [
                    'reviewer_id' => $reviewer->id,
                    'average_score' => $reviewerReviews->avg('score'),
                    'completion_rate' => $this->calculateCompletionRate($reviewer->id),
                    'quality_score' => $this->calculateQualityScore($reviewerReviews)
                ];
            }
        }
        
        // Calculate percentiles
        $currentReviewerMetrics = $reviewerMetrics[array_search($reviewerId, array_column($reviewerMetrics, 'reviewer_id'))] ?? null;
        
        if (!$currentReviewerMetrics) {
            return [
                'average_score_percentile' => 0,
                'completion_rate_percentile' => 0,
                'quality_score_percentile' => 0
            ];
        }
        
        $averageScores = array_column($reviewerMetrics, 'average_score');
        $completionRates = array_column($reviewerMetrics, 'completion_rate');
        $qualityScores = array_column($reviewerMetrics, 'quality_score');
        
        sort($averageScores);
        sort($completionRates);
        sort($qualityScores);
        
        return [
            'average_score_percentile' => $this->calculatePercentile($currentReviewerMetrics['average_score'], $averageScores),
            'completion_rate_percentile' => $this->calculatePercentile($currentReviewerMetrics['completion_rate'], $completionRates),
            'quality_score_percentile' => $this->calculatePercentile($currentReviewerMetrics['quality_score'], $qualityScores)
        ];
    }
    
    /**
     * Get assignment history for a reviewer
     */
    private function getAssignmentHistory($reviewerId)
    {
        return ReviewHistory::where('reviewer_id', $reviewerId)
            ->where('action', 'assigned')
            ->orderBy('action_date', 'desc')
            ->get();
    }
    
    /**
     * Calculate total review time
     */
    private function calculateTotalReviewTime($reviews)
    {
        $totalTime = 0;
        
        foreach ($reviews as $review) {
            $endAt = $review->submitted_at ?? $review->completed_at;
            if ($endAt) {
                $startAt = $review->assigned_at ?? $review->created_at;
                if ($startAt) {
                    $totalTime += $endAt->diffInHours($startAt);
                }
            }
        }
        
        return $totalTime;
    }
    
    /**
     * Calculate average review time
     */
    private function calculateAverageReviewTime($reviews)
    {
        $reviewTimes = [];
        
        foreach ($reviews as $review) {
            $endAt = $review->submitted_at ?? $review->completed_at;
            if ($endAt) {
                $startAt = $review->assigned_at ?? $review->created_at;
                if ($startAt) {
                    $reviewTimes[] = $endAt->diffInHours($startAt);
                }
            }
        }
        
        return count($reviewTimes) > 0 ? round(array_sum($reviewTimes) / count($reviewTimes), 2) : 0;
    }
    
    /**
     * Calculate completion rate for a reviewer
     */
    private function calculateCompletionRate($reviewerId)
    {
        $assigned = AbstractSubmission::where(function($query) use ($reviewerId) {
            $query->where('reviewer_id', $reviewerId)
                  ->orWhere('reviewer_2_id', $reviewerId);
        })->count();
        
        $completed = AbstractReview::where('reviewer_id', $reviewerId)
            ->where('status', 'submitted')
            ->count();
        
        return $assigned > 0 ? ($completed / $assigned) * 100 : 0;
    }
    
    /**
     * Calculate quality score for reviews (used in comparison/percentile metrics).
     * Mirrors the graduated formula in calculateQualityMetrics.
     */
    private function calculateQualityScore($reviews)
    {
        $totalReviews = $reviews->count();
        if ($totalReviews === 0) {
            return 0;
        }

        $perReviewScores = [];

        foreach ($reviews as $review) {
            $commentLen = strlen($review->comments ?? '');

            if ($commentLen === 0) {
                $commentBonus = 0;
            } elseif ($commentLen < 50) {
                $commentBonus = 5;
            } elseif ($commentLen < 150) {
                $commentBonus = 10;
            } else {
                $commentBonus = 15;
            }

            $usingNew = !is_null($review->title_score);
            if ($usingNew) {
                $criteriaFields = [
                    $review->word_count_score, $review->writing_quality_score,
                    $review->structure_score, $review->background_score,
                    $review->rationale_score, $review->objective_score,
                    $review->methodology_design_score, $review->methodology_analysis_score,
                    $review->results_logic_score, $review->results_findings_score,
                    $review->results_data_score, $review->conclusion_interpretation_score,
                    $review->conclusion_impact_score, $review->relevance_theme_score,
                ];
            } else {
                $criteriaFields = [
                    $review->technical_quality, $review->novelty,
                    $review->relevance, $review->clarity,
                ];
            }

            $total = count($criteriaFields);
            $filled = count(array_filter($criteriaFields, fn($v) => !is_null($v)));
            $criteriaBonus = $total > 0 ? ($filled / $total) * 15 : 15;

            $perReviewScores[] = 70 + $commentBonus + $criteriaBonus;
        }

        return array_sum($perReviewScores) / $totalReviews;
    }
    
    /**
     * Calculate percentile
     */
    private function calculatePercentile($value, $array)
    {
        if (empty($array)) return 0;
        
        $count = 0;
        foreach ($array as $item) {
            if ($item <= $value) {
                $count++;
            }
        }
        
        return round(($count / count($array)) * 100, 2);
    }
    
    /**
     * Get recent activity for a reviewer
     */
    private function getRecentActivity($reviewerId)
    {
        return AbstractReview::where('reviewer_id', $reviewerId)
            ->where('status', 'submitted')
            ->with(['abstractSubmission'])
            ->orderByRaw('COALESCE(submitted_at, completed_at) DESC')
            ->take(10)
            ->get();
    }
    
    /**
     * Get performance trends over time
     */
    private function getPerformanceTrends($reviewerId)
    {
        $trends = [];
        $months = 6;
        
        for ($i = 0; $i < $months; $i++) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            
            $reviews = AbstractReview::where('reviewer_id', $reviewerId)
                ->where('status', 'submitted')
                ->where(function ($q) use ($monthStart, $monthEnd) {
                    $q->whereBetween('submitted_at', [$monthStart, $monthEnd])
                      ->orWhereBetween('completed_at', [$monthStart, $monthEnd]);
                })
                ->get();
            
            $trends[] = [
                'month' => $monthStart->format('M Y'),
                'reviews_completed' => $reviews->count(),
                'average_score' => $reviews->avg('score'),
                'average_comment_length' => $reviews->avg(fn($r) => strlen($r->comments ?? ''))
            ];
        }
        
        return array_reverse($trends);
    }
    
    /**
     * Get all reviewers with full performance stats (load, pending, completed, avg time, etc.)
     * Includes users with reviewer role OR users assigned as reviewer on any abstract.
     * Sorted by oldest pending first, then by completed count
     */
    public function getAllReviewersWithStats(string $sortBy = 'workload', string $direction = 'desc')
    {
        $reviewerIds = User::whereHas('roles', fn ($q) => $q->where('name', 'reviewer'))->pluck('id');
        $assignedIds = collect(
            DB::table('abstract_submissions')
                ->select('reviewer_id as reviewer_id')
                ->whereNotNull('reviewer_id')
                ->union(
                    DB::table('abstract_submissions')
                        ->select('reviewer_2_id as reviewer_id')
                        ->whereNotNull('reviewer_2_id')
                )
                ->pluck('reviewer_id')
        );

        $ids = $reviewerIds->merge($assignedIds)->unique()->filter()->values();
        $reviewers = User::whereIn('id', $ids)->get()->keyBy('id');

        $portfolioByReviewer = $this->getDashboardPortfolioStatsByReviewer($ids->all());
        $submittedMetrics = $this->getDashboardSubmittedMetricsByReviewer($ids->all());

        $result = [];
        foreach ($reviewers as $reviewerId => $reviewer) {
            $portfolio = $portfolioByReviewer[$reviewerId] ?? $this->emptyDashboardPortfolio();
            $submitted = $submittedMetrics[$reviewerId] ?? ['avg_review_time_hours' => 0, 'late_reviews' => 0];
            $completed = (int) ($portfolio['completed'] ?? 0);
            $assigned = (int) ($portfolio['assigned'] ?? 0);

            $result[] = [
                'reviewer' => $reviewer,
                'assigned' => $assigned,
                'completed' => $completed,
                'pending_count' => $portfolio['pending'],
                'oldest_days' => (int) ($portfolio['oldest_days'] ?? 0),
                'oldest_age_label' => $portfolio['oldest_age_label'] ?? null,
                'oldest_assigned_at' => $portfolio['oldest_assigned_at'],
                'pending_abstracts' => $portfolio['pending_abstracts'] ?? [],
                'needs_reminder_count' => $portfolio['needs_reminder_count'] ?? 0,
                'overdue_count' => $portfolio['overdue_count'] ?? 0,
                'avg_review_time_hours' => $submitted['avg_review_time_hours'] ?? 0,
                'completion_rate' => $assigned > 0 ? round(($completed / $assigned) * 100, 1) : 0,
                'late_reviews' => $submitted['late_reviews'] ?? 0,
            ];
        }

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        // Dashboard sorts should reflect the visible controls.
        usort($result, function ($a, $b) use ($sortBy, $direction) {
            $compare = 0;

            switch ($sortBy) {
                case 'oldest':
                    $compare = $a['oldest_days'] <=> $b['oldest_days'];
                    if ($compare === 0) {
                        $compare = $a['pending_count'] <=> $b['pending_count'];
                    }
                    break;

                case 'success_rate':
                    $compare = $a['completion_rate'] <=> $b['completion_rate'];
                    if ($compare === 0) {
                        $compare = $a['completed'] <=> $b['completed'];
                    }
                    break;

                case 'workload':
                default:
                    $compare = $a['pending_count'] <=> $b['pending_count'];
                    if ($compare === 0) {
                        $compare = $a['oldest_days'] <=> $b['oldest_days'];
                    }
                    break;
            }

            if ($compare === 0) {
                $compare = strcmp(
                    $a['reviewer']->full_name ?? $a['reviewer']->email,
                    $b['reviewer']->full_name ?? $b['reviewer']->email
                );
            }

            return $direction === 'asc' ? $compare : -$compare;
        });

        return $result;
    }

    private function getDashboardPortfolioStatsByReviewer(array $reviewerIds): array
    {
        if (empty($reviewerIds)) {
            return [];
        }

        $slotOne = DB::table('abstract_submissions as a')
            ->leftJoin('abstract_reviews as ar', function ($join) {
                $join->on('ar.abstract_submission_id', '=', 'a.id')
                    ->on('ar.reviewer_id', '=', 'a.reviewer_id')
                    ->whereRaw('ar.review_round = COALESCE(a.revision_round, 0)');
            })
            ->whereNotNull('a.reviewer_id')
            ->whereIn('a.status', self::PORTFOLIO_REVIEW_STATUSES)
            ->whereIn('a.reviewer_id', $reviewerIds)
            ->selectRaw('
                a.reviewer_id as reviewer_id,
                a.id as abstract_id,
                a.title,
                a.status as abstract_status,
                COALESCE(a.revision_round, 0) as review_round,
                ar.status as review_status,
                COALESCE(ar.assigned_at, a.assigned_at, a.created_at) as assigned_at
            ');

        $rows = DB::table('abstract_submissions as a')
            ->leftJoin('abstract_reviews as ar', function ($join) {
                $join->on('ar.abstract_submission_id', '=', 'a.id')
                    ->on('ar.reviewer_id', '=', 'a.reviewer_2_id')
                    ->whereRaw('ar.review_round = COALESCE(a.revision_round, 0)');
            })
            ->whereNotNull('a.reviewer_2_id')
            ->whereIn('a.status', self::PORTFOLIO_REVIEW_STATUSES)
            ->whereIn('a.reviewer_2_id', $reviewerIds)
            ->selectRaw('
                a.reviewer_2_id as reviewer_id,
                a.id as abstract_id,
                a.title,
                a.status as abstract_status,
                COALESCE(a.revision_round, 0) as review_round,
                ar.status as review_status,
                COALESCE(ar.assigned_at, a.assigned_at, a.created_at) as assigned_at
            ')
            ->unionAll($slotOne)
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $reviewerId = (int) $row->reviewer_id;
            $grouped[$reviewerId] ??= $this->emptyDashboardPortfolio();

            if ($row->review_status === 'submitted') {
                $grouped[$reviewerId]['assigned']++;
                $grouped[$reviewerId]['completed']++;
                continue;
            }

            $isActivelyAwaitingReview = in_array($row->abstract_status, self::ACTIVE_REVIEW_STATUSES, true);
            $hasActionableDraft = $row->review_status === 'draft';

            if (!$isActivelyAwaitingReview || !$hasActionableDraft) {
                continue;
            }

            $assignedAt = $row->assigned_at ? Carbon::parse($row->assigned_at) : null;
            $days = $assignedAt ? (int) $assignedAt->diffInDays(now()) : 0;

            $grouped[$reviewerId]['assigned']++;
            $grouped[$reviewerId]['pending']++;
            $grouped[$reviewerId]['pending_abstracts'][] = [
                'id' => (int) $row->abstract_id,
                'title' => $row->title,
                'days' => $days,
                'age_label' => $this->formatElapsedShort($assignedAt),
                'assigned_at' => $assignedAt,
            ];

            if ($days >= self::REVIEW_REMINDER_DAYS) {
                $grouped[$reviewerId]['needs_reminder_count']++;
            }

            if ($days >= self::REVIEW_OVERDUE_DAYS) {
                $grouped[$reviewerId]['overdue_count']++;
            }

            if (is_null($grouped[$reviewerId]['oldest_assigned_at']) || $days > $grouped[$reviewerId]['oldest_days']) {
                $grouped[$reviewerId]['oldest_days'] = $days;
                $grouped[$reviewerId]['oldest_age_label'] = $this->formatElapsedShort($assignedAt);
                $grouped[$reviewerId]['oldest_assigned_at'] = $assignedAt;
            }
        }

        foreach ($grouped as &$stats) {
            usort($stats['pending_abstracts'], fn ($a, $b) => $b['days'] <=> $a['days']);
        }

        return $grouped;
    }

    private function getDashboardSubmittedMetricsByReviewer(array $reviewerIds): array
    {
        if (empty($reviewerIds)) {
            return [];
        }

        return DB::table('abstract_reviews')
            ->selectRaw('
                reviewer_id,
                AVG(TIMESTAMPDIFF(HOUR, COALESCE(assigned_at, created_at), COALESCE(submitted_at, completed_at))) as avg_review_time_hours,
                SUM(CASE
                    WHEN TIMESTAMPDIFF(HOUR, COALESCE(assigned_at, created_at), COALESCE(submitted_at, completed_at)) > ?
                    THEN 1 ELSE 0
                END) as late_reviews
            ', [self::REVIEW_OVERDUE_DAYS * 24])
            ->whereIn('reviewer_id', $reviewerIds)
            ->where('status', 'submitted')
            ->groupBy('reviewer_id')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    (int) $row->reviewer_id => [
                        'avg_review_time_hours' => round((float) ($row->avg_review_time_hours ?? 0), 2),
                        'late_reviews' => (int) ($row->late_reviews ?? 0),
                    ],
                ];
            })
            ->all();
    }

    private function emptyDashboardPortfolio(): array
    {
        return [
            'assigned' => 0,
            'completed' => 0,
            'pending' => 0,
            'needs_reminder_count' => 0,
            'overdue_count' => 0,
            'oldest_days' => 0,
            'oldest_age_label' => null,
            'oldest_assigned_at' => null,
            'pending_abstracts' => [],
        ];
    }

    /**
     * Get pending reviews for a reviewer - includes each abstract with days unreviewed (int)
     */
    private function getPendingReviewsForReviewer($reviewerId)
    {
        $portfolio = $this->getCurrentPortfolioStatsForReviewer($reviewerId);

        return [
            'count' => $portfolio['pending'],
            'oldest_days' => (int) ($portfolio['oldest_days'] ?? 0),
            'oldest_age_label' => $portfolio['oldest_age_label'] ?? null,
            'oldest_assigned_at' => $portfolio['oldest_assigned_at'],
            'pending_abstracts' => $portfolio['pending_abstracts'] ?? [],
        ];
    }

    /**
     * Get current workload stats for a reviewer based on the abstracts they are currently assigned.
     */
    public function getCurrentPortfolioStatsForReviewer(int $reviewerId): array
    {
        $abstracts = AbstractSubmission::with(['reviews' => function ($query) use ($reviewerId) {
            $query->where('reviewer_id', $reviewerId);
        }])->where(function ($q) use ($reviewerId) {
            $q->where('reviewer_id', $reviewerId)->orWhere('reviewer_2_id', $reviewerId);
        })->whereIn('status', self::PORTFOLIO_REVIEW_STATUSES)->get();

        $assigned = 0;
        $completed = 0;
        $pending = 0;
        $needsReminderCount = 0;
        $overdueCount = 0;
        $oldestDays = 0;
        $oldestAgeLabel = null;
        $oldestAssignedAt = null;
        $pendingAbstracts = [];

        foreach ($abstracts as $abstract) {
            $currentRound = $abstract->revision_round ?? 0;
            $currentReview = $abstract->reviews->firstWhere('review_round', $currentRound);
            $isCompleted = $currentReview && $currentReview->status === 'submitted';

            if ($isCompleted) {
                $assigned++;
                $completed++;
                continue;
            }

            $isActivelyAwaitingReview = in_array($abstract->status, self::ACTIVE_REVIEW_STATUSES, true);
            $hasActionableDraft = $currentReview && $currentReview->status === 'draft';

            if (!$isActivelyAwaitingReview || !$hasActionableDraft) {
                continue;
            }

            $assigned++;
            $pending++;
            $assignedAt = $currentReview?->assigned_at ?? $abstract->assigned_at ?? $abstract->created_at;
            $days = (int) ($assignedAt ? Carbon::parse($assignedAt)->diffInDays(now()) : 0);

            if ($days >= self::REVIEW_REMINDER_DAYS) {
                $needsReminderCount++;
            }

            if ($days >= self::REVIEW_OVERDUE_DAYS) {
                $overdueCount++;
            }

            $pendingAbstracts[] = [
                'id' => $abstract->id,
                'title' => $abstract->title,
                'days' => $days,
                'age_label' => $this->formatElapsedShort($assignedAt),
                'assigned_at' => $assignedAt,
            ];

            if (is_null($oldestAssignedAt) || $days > $oldestDays) {
                $oldestDays = $days;
                $oldestAgeLabel = $this->formatElapsedShort($assignedAt);
                $oldestAssignedAt = $assignedAt;
            }
        }

        usort($pendingAbstracts, fn ($a, $b) => $b['days'] <=> $a['days']);

        return [
            'assigned' => $assigned,
            'completed' => $completed,
            'pending' => $pending,
            'needs_reminder_count' => $needsReminderCount,
            'overdue_count' => $overdueCount,
            'oldest_days' => $oldestDays,
            'oldest_age_label' => $oldestAgeLabel,
            'oldest_assigned_at' => $oldestAssignedAt,
            'pending_abstracts' => $pendingAbstracts,
        ];
    }

    private function formatElapsedShort($from): ?string
    {
        if (!$from) {
            return null;
        }

        $timestamp = Carbon::parse($from);
        $minutes = (int) floor($timestamp->diffInMinutes(now()));

        if ($minutes < 1) {
            return '<1m';
        }

        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $hours = (int) floor($timestamp->diffInHours(now()));
        if ($hours < 24) {
            return $hours . 'h';
        }

        return ((int) floor($timestamp->diffInDays(now()))) . 'd';
    }

    /**
     * Get top performing reviewers
     */
    public function getTopPerformers($limit = 10)
    {
        $reviewers = User::whereHas('roles', function($query) {
            $query->where('name', 'reviewer');
        })->get();
        $performerMetrics = [];
        
        foreach ($reviewers as $reviewer) {
            $performance = $this->getReviewerPerformance($reviewer->id);
            
            if ($performance && $performance['metrics']['basic_stats']['total_completed'] > 0) {
                $performerMetrics[] = [
                    'reviewer' => $reviewer,
                    'completion_rate' => $performance['metrics']['basic_stats']['completion_rate'],
                    'quality_score' => $performance['metrics']['quality_metrics']['quality_score'],
                    'average_score' => $performance['metrics']['basic_stats']['average_score'],
                    'consistency_score' => $performance['metrics']['consistency_metrics']['average_consistency_score'],
                    'total_reviews' => $performance['metrics']['basic_stats']['total_completed']
                ];
            }
        }
        
        // Sort by overall performance score
        usort($performerMetrics, function($a, $b) {
            $scoreA = ($a['completion_rate'] + $a['quality_score'] + $a['consistency_score']) / 3;
            $scoreB = ($b['completion_rate'] + $b['quality_score'] + $b['consistency_score']) / 3;
            return $scoreB <=> $scoreA;
        });
        
        return array_slice($performerMetrics, 0, $limit);
    }
    
    /**
     * Get reviewers needing attention
     */
    public function getReviewersNeedingAttention()
    {
        $reviewers = User::whereHas('roles', function($query) {
            $query->where('name', 'reviewer');
        })->get();
        $attentionNeeded = [];
        
        foreach ($reviewers as $reviewer) {
            $performance = $this->getReviewerPerformance($reviewer->id);
            
            if ($performance) {
                $metrics = $performance['metrics'];
                
                $issues = [];
                
                if ($metrics['basic_stats']['completion_rate'] < 70) {
                    $issues[] = "Low completion rate ({$metrics['basic_stats']['completion_rate']}%)";
                }
                
                if ($metrics['quality_metrics']['quality_score'] < 80) {
                    $issues[] = "Low quality score ({$metrics['quality_metrics']['quality_score']}%)";
                }
                
                if ($metrics['consistency_metrics']['average_score_difference'] > 25) {
                    $issues[] = "High score differences ({$metrics['consistency_metrics']['average_score_difference']}%)";
                }
                
                if ($metrics['timeliness_metrics']['timeliness_score'] < 80) {
                    $issues[] = "Late reviews ({$metrics['timeliness_metrics']['late_reviews']} late)";
                }
                
                if (!empty($issues)) {
                    $attentionNeeded[] = [
                        'reviewer' => $reviewer,
                        'issues' => $issues,
                        'metrics' => $metrics
                    ];
                }
            }
        }
        
        return $attentionNeeded;
    }
} 
