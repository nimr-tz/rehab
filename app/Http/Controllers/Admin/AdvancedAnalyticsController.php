<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdvancedAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AdvancedAnalyticsController extends Controller
{
    protected AdvancedAnalyticsService $analyticsService;

    public function __construct(AdvancedAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Show advanced analytics dashboard
     */
    public function dashboard(): View
    {
        $stats = $this->analyticsService->getDashboardStats();
        $trends = $this->analyticsService->getSubmissionTrends();
        $reviewerAnalytics = $this->analyticsService->getReviewerAnalytics();
        $categoryDistribution = $this->analyticsService->getCategoryDistribution();
        $qualityMetrics = $this->analyticsService->getQualityMetrics();

        return view('admin.advanced-analytics.dashboard', compact(
            'stats',
            'trends',
            'reviewerAnalytics',
            'categoryDistribution',
            'qualityMetrics'
        ));
    }

    /**
     * Get dashboard statistics via AJAX
     */
    public function getDashboardStats(): JsonResponse
    {
        $stats = $this->analyticsService->getDashboardStats();

        return response()->json($stats);
    }

    /**
     * Get submission trends
     */
    public function getSubmissionTrends(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $trends = $this->analyticsService->getSubmissionTrends($days);

        return response()->json($trends);
    }

    /**
     * Get review trends
     */
    public function getReviewerAnalytics(): JsonResponse
    {
        $analytics = $this->analyticsService->getReviewerAnalytics();

        return response()->json($analytics);
    }

    /**
     * Get category distribution
     */
    public function getCategoryDistribution(): JsonResponse
    {
        $distribution = $this->analyticsService->getCategoryDistribution();

        return response()->json($distribution);
    }

    /**
     * Get session analytics
     */
    public function getSessionAnalytics(): JsonResponse
    {
        $analytics = $this->analyticsService->getSessionAnalytics();

        return response()->json($analytics);
    }

    /**
     * Get quality metrics
     */
    public function getQualityMetrics(): JsonResponse
    {
        $metrics = $this->analyticsService->getQualityMetrics();

        return response()->json($metrics);
    }

    /**
     * Get timeline analytics
     */
    public function getTimelineAnalytics(): JsonResponse
    {
        $analytics = $this->analyticsService->getTimelineAnalytics();

        return response()->json($analytics);
    }

    /**
     * Generate comprehensive report
     */
    public function generateReport(): JsonResponse
    {
        $report = $this->analyticsService->generateComprehensiveReport();

        return response()->json($report);
    }

    /**
     * Export analytics report as CSV
     */
    public function exportReport(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $report = $this->analyticsService->generateComprehensiveReport();

        $filename = 'analytics_report_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $csvContent = $this->generateCsvContent($report);

        return response($csvContent)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Generate CSV content from report data
     */
    private function generateCsvContent(array $report): string
    {
        $csv = [];

        // Dashboard Stats
        $csv[] = ['Dashboard Statistics'];
        $csv[] = ['Metric', 'Value'];
        foreach ($report['dashboard_stats'] as $key => $value) {
            $csv[] = [str_replace('_', ' ', ucfirst($key)), $value];
        }
        $csv[] = [];

        // Submission Trends
        $csv[] = ['Submission Trends'];
        $csv[] = ['Date', 'Submissions', 'Accepted', 'Rejected'];
        for ($i = 0; $i < count($report['submission_trends']['labels']); $i++) {
            $csv[] = [
                $report['submission_trends']['labels'][$i],
                $report['submission_trends']['submissions'][$i],
                $report['submission_trends']['accepted'][$i],
                $report['submission_trends']['rejected'][$i]
            ];
        }
        $csv[] = [];

        // Reviewer Analytics
        $csv[] = ['Reviewer Performance Analytics'];
        $csv[] = ['Reviewer Name', 'Total Reviews', 'Avg Score', 'Quality Score', 'Completion Rate', 'Consistency Score'];
        foreach ($report['reviewer_analytics'] as $reviewer) {
            $csv[] = [
                $reviewer['reviewer_name'],
                $reviewer['total_reviews'],
                $reviewer['avg_score'],
                $reviewer['quality_score'],
                $reviewer['completion_rate'],
                $reviewer['consistency_score']
            ];
        }
        $csv[] = [];

        // Category Distribution
        $csv[] = ['Category Distribution'];
        $csv[] = ['Category', 'Count', 'Accepted', 'Rejected'];
        for ($i = 0; $i < count($report['category_distribution']['categories']); $i++) {
            $csv[] = [
                $report['category_distribution']['categories'][$i],
                $report['category_distribution']['counts'][$i],
                $report['category_distribution']['accepted'][$i],
                $report['category_distribution']['rejected'][$i]
            ];
        }
        $csv[] = [];

        // Quality Metrics
        $csv[] = ['Quality Metrics'];
        $csv[] = ['Metric', 'Value'];
        foreach ($report['quality_metrics'] as $key => $value) {
            $csv[] = [str_replace('_', ' ', ucfirst($key)), $value];
        }

        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}
