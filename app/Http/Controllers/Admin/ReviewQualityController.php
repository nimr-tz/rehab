<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReviewQualityValidationService;
use App\Services\ReviewerPerformanceTrackingService;
use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewQualityController extends Controller
{
    protected $qualityService;
    protected $performanceService;
    
    public function __construct()
    {
        $this->qualityService = new ReviewQualityValidationService();
        $this->performanceService = new ReviewerPerformanceTrackingService();
    }
    
    /**
     * Show reviewer performance dashboard - all reviewers with full load and stats
     */
    public function dashboard()
    {
        $sortBy = request('sort', 'workload');
        $sortDirection = request('direction', 'desc');
        $reviewers = $this->performanceService->getAllReviewersWithStats($sortBy, $sortDirection);
        $qualityStats = $this->qualityService->getQualityStatistics();

        return view('admin.review-quality.dashboard', compact('reviewers', 'qualityStats', 'sortBy', 'sortDirection'));
    }
    
    /**
     * Show reviewer performance details
     */
    public function reviewerPerformance($reviewerId)
    {
        $reviewer = User::findOrFail($reviewerId);
        
        // Get full performance metrics from the service
        $performanceData = $this->performanceService->getReviewerPerformance($reviewerId);
        $fullMetrics = $performanceData['metrics'] ?? null;
        
        // Get all assignments for this reviewer with sorting
        $sortBy = request('sort', 'status');
        $sortDirection = request('direction', 'desc');
        
        $assignmentsQuery = AbstractReview::with(['abstractSubmission.user'])
            ->where('reviewer_id', $reviewerId)
            ->whereIn('status', ['draft', 'submitted'])
            ->whereHas('abstractSubmission', function($asq) use ($reviewerId) {
                $asq->where(function($q) use ($reviewerId) {
                    $q->where('reviewer_id', $reviewerId)
                      ->orWhere('reviewer_2_id', $reviewerId);
                })->whereIn('status', array_merge(
                    $this->performanceService->getActiveReviewStatuses(),
                    ['ready_for_decision', 'minor_revision_required', 'major_revision_required']
                ));
            });
        
        // Keep only the current-round record for each currently assigned abstract.
        $assignmentsQuery->whereRaw(
            'abstract_reviews.review_round = COALESCE((select revision_round from abstract_submissions where abstract_submissions.id = abstract_reviews.abstract_submission_id), 0)'
        );
        
        // Apply sorting
        switch ($sortBy) {
            case 'status':
                $assignmentsQuery->orderByRaw(
                    "CASE " .
                    "WHEN abstract_reviews.status = 'draft' AND COALESCE(abstract_reviews.assigned_at, abstract_reviews.created_at) <= DATE_SUB(NOW(), INTERVAL 4 DAY) THEN 1 " .
                    "WHEN abstract_reviews.status = 'draft' AND COALESCE(abstract_reviews.assigned_at, abstract_reviews.created_at) <= DATE_SUB(NOW(), INTERVAL 3 DAY) THEN 2 " .
                    "WHEN abstract_reviews.status = 'draft' THEN 3 " .
                    "WHEN abstract_reviews.status = 'submitted' THEN 4 " .
                    "ELSE 5 END"
                )->orderBy('assigned_at', 'asc');
                break;
            case 'days':
                $assignmentsQuery->orderByRaw(
                    "COALESCE(abstract_reviews.assigned_at, abstract_reviews.created_at) " .
                    ($sortDirection === 'asc' ? 'desc' : 'asc')
                );
                break;
            case 'score':
                $assignmentsQuery->orderBy('score', $sortDirection);
                break;
            case 'title':
                $assignmentsQuery->join('abstract_submissions', 'abstract_reviews.abstract_submission_id', '=', 'abstract_submissions.id')
                    ->orderBy('abstract_submissions.title', $sortDirection)
                    ->select('abstract_reviews.*');
                break;
            default:
                $assignmentsQuery->orderBy('assigned_at', $sortDirection);
        }
        
        $assignments = $assignmentsQuery->get();

        $submittedHistory = AbstractReview::with(['abstractSubmission.user'])
            ->where('reviewer_id', $reviewerId)
            ->where('status', 'submitted')
            ->orderByRaw('COALESCE(submitted_at, completed_at, updated_at) DESC')
            ->paginate(20, ['*'], 'history_page')
            ->withQueryString();

        $historyStats = [
            'total_reviews' => AbstractReview::where('reviewer_id', $reviewerId)
                ->where('status', 'submitted')
                ->count(),
            'distinct_abstracts' => AbstractReview::where('reviewer_id', $reviewerId)
                ->where('status', 'submitted')
                ->distinct('abstract_submission_id')
                ->count('abstract_submission_id'),
        ];
        
        // Group assignments by status
        $pending = $assignments->where('status', 'draft');
        $completed = $assignments->where('status', 'submitted');
        $portfolioStats = $this->performanceService->getCurrentPortfolioStatsForReviewer($reviewerId);
        $totalAssigned = $portfolioStats['assigned'];
        $totalCompleted = $portfolioStats['completed'];
        $totalPending = $portfolioStats['pending'];
        $completionRate = $totalAssigned > 0 ? round(($totalCompleted / $totalAssigned) * 100) : 0;

        // Find assignments needing attention based on automation rules
        $needsReminder = $pending->filter(function($review) {
            $startAt = $review->assigned_at ?? $review->created_at;
            return $startAt && $startAt->diffInDays(now()) >= 3;
        });

        $overdue = $pending->filter(function($review) {
            $startAt = $review->assigned_at ?? $review->created_at;
            return $startAt && $startAt->diffInDays(now()) >= 4;
        });
        
        // Calculate average review time for completed reviews
        $avgReviewTime = $completed->filter(function($review) {
            return ($review->assigned_at || $review->created_at) && ($review->submitted_at || $review->completed_at);
        })->avg(function($review) {
            $startAt = $review->assigned_at ?? $review->created_at;
            $endAt = $review->submitted_at ?? $review->completed_at;
            return $startAt->diffInHours($endAt);
        });
        
        // Get oldest pending review
        $oldestDays = (int) ($portfolioStats['oldest_days'] ?? 0);
        
        $workloadStats = [
            'total_assigned' => $totalAssigned,
            'total_completed' => $totalCompleted,
            'total_pending' => $totalPending,
            'completion_rate' => $completionRate,
            'needs_reminder_count' => $portfolioStats['needs_reminder_count'] ?? $needsReminder->count(),
            'overdue_count' => $portfolioStats['overdue_count'] ?? $overdue->count(),
            'avg_review_time' => round($avgReviewTime ?? 0, 1),
            'oldest_days' => $oldestDays
        ];

        return view('admin.review-quality.reviewer-performance', compact(
            'reviewer', 
            'assignments', 
            'pending', 
            'completed', 
            'needsReminder',
            'overdue',
            'workloadStats',
            'fullMetrics',
            'submittedHistory',
            'historyStats',
            'sortBy',
            'sortDirection'
        ));
    }
    
    /**
     * Show all reviewer performances
     */
    public function allReviewerPerformances()
    {
        $reviewers = User::whereHas('roles', function($query) {
            $query->where('name', 'reviewer');
        })->get();
        $performances = [];
        
        foreach ($reviewers as $reviewer) {
            $performance = $this->performanceService->getReviewerPerformance($reviewer->id);
            if ($performance) {
                // Calculate overall score
                $metrics = $performance['metrics'];
                $overallScore = (
                    $metrics['basic_stats']['completion_rate'] * 0.3 +
                    $metrics['quality_metrics']['quality_score'] * 0.3 +
                    $metrics['consistency_metrics']['average_consistency_score'] * 0.2 +
                    $metrics['timeliness_metrics']['timeliness_score'] * 0.2
                );

                $performance['overall_score'] = round(max(70, $overallScore), 1);
                $performance['reviewer_name'] = $reviewer->full_name ?? $reviewer->name;
                $performance['reviewer_email'] = $reviewer->email;
                $performance['completion_rate'] = $metrics['basic_stats']['completion_rate'];
                $performance['quality_score'] = $metrics['quality_metrics']['quality_score'];
                $performance['consistency_score'] = $metrics['consistency_metrics']['average_consistency_score'];
                $performance['timeliness_score'] = $metrics['timeliness_metrics']['timeliness_score'];
                $performance['completed_reviews'] = $metrics['basic_stats']['total_completed'];
                $performance['assigned_reviews'] = $metrics['basic_stats']['total_assigned'];
                $performance['percentile_rank'] = 75; // This would be calculated based on all reviewers
                $performances[$reviewer->id] = $performance;
            }
        }
        
        // Sort by overall performance
        uasort($performances, function($a, $b) {
            return $b['overall_score'] <=> $a['overall_score'];
        });
        
        // Create summary data
        $summary = [
            'total_reviewers' => count($performances),
            'active_reviewers' => count(array_filter($performances, fn($p) => $p['metrics']['basic_stats']['total_completed'] > 0)),
            'needs_attention' => count(array_filter($performances, fn($p) => $p['overall_score'] < 60)),
            'top_performers' => count(array_filter($performances, fn($p) => $p['overall_score'] >= 80))
        ];
        
        return view('admin.review-quality.all-performances', compact('performances', 'summary'));
    }
    
    /**
     * Flag an abstract for quality review
     */
    public function flagForQualityReview(Request $request, $abstractId)
    {
        $abstract = AbstractSubmission::findOrFail($abstractId);
        
        $validated = $request->validate([
            'flag_reason' => 'required|string|max:500',
            'priority' => 'required|in:low,medium,high,critical'
        ]);
        
        $abstract->update([
            'quality_flag' => true,
            'quality_flag_reason' => $validated['flag_reason'],
            'quality_flag_priority' => $validated['priority'],
            'quality_flagged_at' => now(),
            'quality_flagged_by' => auth()->id()
        ]);
        
        Log::info("Abstract {$abstractId} flagged for quality review: {$validated['flag_reason']}");
        
        return redirect()->back()->with('success', 'Abstract flagged for quality review.');
    }
    
    /**
     * Resolve quality flag
     */
    public function resolveQualityFlag(Request $request, $abstractId)
    {
        $abstract = AbstractSubmission::findOrFail($abstractId);
        
        $validated = $request->validate([
            'resolution_notes' => 'required|string|max:500',
            'action_taken' => 'required|in:no_action,reassign_reviewer,request_revision,override_decision'
        ]);
        
        $abstract->update([
            'quality_flag' => false,
            'quality_resolution_notes' => $validated['resolution_notes'],
            'quality_resolution_action' => $validated['action_taken'],
            'quality_resolved_at' => now(),
            'quality_resolved_by' => auth()->id()
        ]);
        
        Log::info("Quality flag resolved for abstract {$abstractId}: {$validated['resolution_notes']}");
        
        return redirect()->back()->with('success', 'Quality flag resolved.');
    }
    
    /**
     * Get quality statistics API endpoint
     */
    public function getQualityStats()
    {
        $stats = $this->qualityService->getQualityStatistics();
        
        return response()->json($stats);
    }
    
    /**
     * Get reviewer performance API endpoint
     */
    public function getReviewerPerformance($reviewerId)
    {
        $performance = $this->performanceService->getReviewerPerformance($reviewerId);
        
        if (!$performance) {
            return response()->json(['error' => 'Reviewer not found'], 404);
        }
        
        return response()->json($performance);
    }
    
    /**
     * Export quality report
     */
    public function exportQualityReport(Request $request)
    {
        $format = $request->get('format', 'csv');
        
        $reviews = AbstractReview::with(['abstractSubmission', 'reviewer'])
            ->whereNotNull('completed_at')
            ->get();
        
        $data = [];
        
        foreach ($reviews as $review) {
            $qualityCheck = $this->qualityService->validateReviewQuality($review->abstract_submission_id);
            
            $data[] = [
                'Abstract ID' => $review->abstract_submission_id,
                'Reviewer' => $review->reviewer->full_name,
                'Score' => $review->score,
                'Comment Length' => strlen($review->comments ?? ''),
                'Technical Quality' => $review->technical_quality,
                'Novelty' => $review->novelty,
                'Relevance' => $review->relevance,
                'Clarity' => $review->clarity,
                'Recommendation' => $review->recommendation,
                'Completed At' => $review->completed_at,
                'Quality Issues' => count($qualityCheck['issues'] ?? []),
                'Quality Warnings' => count($qualityCheck['warnings'] ?? []),
                'Quality Valid' => $qualityCheck['valid'] ? 'Yes' : 'No'
            ];
        }
        
        if ($format === 'csv') {
            return $this->exportToCsv($data, 'review_quality_report');
        }
        
        return response()->json($data);
    }
    
    /**
     * Export performance report
     */
    public function exportPerformanceReport(Request $request)
    {
        $format = $request->get('format', 'csv');
        
        $reviewers = User::whereHas('roles', function($query) {
            $query->where('name', 'reviewer');
        })->get();
        $data = [];
        
        foreach ($reviewers as $reviewer) {
            $performance = $this->performanceService->getReviewerPerformance($reviewer->id);
            
            if ($performance) {
                $metrics = $performance['metrics'];
                
                $data[] = [
                    'Reviewer ID' => $reviewer->id,
                    'Reviewer Name' => $reviewer->full_name ?? $reviewer->name,
                    'Email' => $reviewer->email,
                    'Total Assigned' => $metrics['basic_stats']['total_assigned'] ?? 0,
                    'Total Completed' => $metrics['basic_stats']['total_completed'] ?? 0,
                    'Completion Rate' => $metrics['basic_stats']['completion_rate'] ?? 0,
                    'Average Score' => $metrics['basic_stats']['average_score'] ?? 0,
                    'Quality Score' => $metrics['quality_metrics']['quality_score'] ?? 0,
                    'Consistency Score' => $metrics['consistency_metrics']['average_consistency_score'] ?? 0,
                    'Timeliness Score' => $metrics['timeliness_metrics']['timeliness_score'] ?? 0,
                    'Average Review Time' => $metrics['basic_stats']['average_review_time'] ?? 0,
                    'Current Month Reviews' => $metrics['workload_metrics']['current_month_reviews'] ?? 0,
                    'Last Month Reviews' => $metrics['workload_metrics']['last_month_reviews'] ?? 0,
                    'Workload Change' => $metrics['workload_metrics']['workload_change'] ?? 0
                ];
            }
        }
        
        if ($format === 'csv') {
            return $this->exportToCsv($data, 'reviewer_performance_report');
        }
        
        return response()->json($data);
    }
    
    /**
     * Show quality settings
     */
    public function qualitySettings()
    {
        return view('admin.review-quality.settings');
    }
    
    /**
     * Update quality settings
     */
    public function updateQualitySettings(Request $request)
    {
        $validated = $request->validate([
            'min_comment_length' => 'required|integer|min:10|max:200',
            'max_score_difference' => 'required|integer|min:10|max:50',
            'min_review_time' => 'required|integer|min:60|max:3600',
            'max_review_time' => 'required|integer|min:3600|max:72000',
            'enable_quality_flags' => 'boolean',
            'auto_flag_extreme_scores' => 'boolean',
            'auto_flag_short_comments' => 'boolean'
        ]);
        
        // Store settings in config or database
        foreach ($validated as $key => $value) {
            config(["review_quality.{$key}" => $value]);
        }
        
        return redirect()->back()->with('success', 'Quality settings updated successfully.');
    }
    
    /**
     * Helper method to export data to CSV
     */
    private function exportToCsv($data, $filename)
    {
        if (empty($data)) {
            return response()->json(['error' => 'No data to export'], 400);
        }
        
        $headers = array_keys($data[0]);
        
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        
        foreach ($data as $row) {
            fputcsv($csv, $row);
        }
        
        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);
        
        return response($csvContent)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }
} 
