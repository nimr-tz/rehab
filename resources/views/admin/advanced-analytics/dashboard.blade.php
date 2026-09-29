@extends('layouts.app')

@section('title', 'Advanced Analytics Dashboard')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">Advanced Analytics Dashboard</h1>
                    <p class="text-gray-600">Comprehensive analytics and insights for the {{ config('conference.short_name') }} {{ config('conference.year') }} Conference.</p>
                </div>

                <!-- Key Metrics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-blue-600">Total Abstracts</p>
                                <p class="text-2xl font-semibold text-blue-900" id="total-abstracts">-</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-green-600">Accepted</p>
                                <p class="text-2xl font-semibold text-green-900" id="accepted-abstracts">-</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-yellow-600">Avg Review Time</p>
                                <p class="text-2xl font-semibold text-yellow-900" id="avg-review-time">-</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-purple-600">Active Reviewers</p>
                                <p class="text-2xl font-semibold text-purple-900" id="active-reviewers">-</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Submission Trends -->
                    <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="w-1 h-6 bg-blue-600 rounded-full"></span>
                            Submission Trends
                        </h3>
                        <div class="h-64">
                            <canvas id="submission-trends-chart"></canvas>
                        </div>
                    </div>

                    <!-- Category Distribution -->
                    <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md lg:col-span-2">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="w-1 h-6 bg-emerald-500 rounded-full"></span>
                            Category Distribution
                        </h3>
                        <div class="h-64">
                            <canvas id="category-distribution-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Reviewer Performance -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Reviewer Performance</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Reviews</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Score</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quality Score</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Time (hrs)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="reviewer-performance-table">
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quality Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quality Metrics</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Total Reviews</span>
                                <span class="text-sm font-medium text-gray-900" id="total-reviews">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Quality Issues</span>
                                <span class="text-sm font-medium text-gray-900" id="quality-issues">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Issue Rate</span>
                                <span class="text-sm font-medium text-gray-900" id="issue-rate">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Avg Comment Length</span>
                                <span class="text-sm font-medium text-gray-900" id="avg-comment-length">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Session Analytics</h3>
                        <div class="space-y-4" id="session-analytics">
                            <!-- Data will be loaded dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Export Section -->
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Export Reports</h3>
                    <div class="flex space-x-4">
                        <a href="{{ route('admin.advanced-analytics.export') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export Comprehensive Report
                        </a>
                        <button onclick="refreshData()"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let submissionChart, categoryChart;

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadCharts();
});

function loadDashboardData() {
    fetch('/admin/advanced-analytics/stats')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-abstracts').textContent = data.total_abstracts || 0;
            document.getElementById('accepted-abstracts').textContent = data.accepted_abstracts || 0;
            document.getElementById('avg-review-time').textContent = (data.avg_review_time_hours || 0) + 'h';
            document.getElementById('active-reviewers').textContent = data.active_reviewers || 0;
        })
        .catch(error => console.error('Error loading dashboard data:', error));
}

function loadCharts() {
    // Load submission trends
    fetch('/admin/advanced-analytics/trends')
        .then(response => response.json())
        .then(data => {
            createSubmissionChart(data);
        })
        .catch(error => console.error('Error loading trends:', error));

    // Load category distribution
    fetch('/admin/advanced-analytics/categories')
        .then(response => response.json())
        .then(data => {
            createCategoryChart(data);
        })
        .catch(error => console.error('Error loading categories:', error));

    // Load reviewer analytics
    fetch('/admin/advanced-analytics/reviewers')
        .then(response => response.json())
        .then(data => {
            populateReviewerTable(data);
        })
        .catch(error => console.error('Error loading reviewers:', error));

    // Load quality metrics
    fetch('/admin/advanced-analytics/quality')
        .then(response => response.json())
        .then(data => {
            populateQualityMetrics(data);
        })
        .catch(error => console.error('Error loading quality metrics:', error));
}

function createSubmissionChart(data) {
    const ctx = document.getElementById('submission-trends-chart').getContext('2d');

    if (submissionChart) {
        submissionChart.destroy();
    }

    submissionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Submissions',
                data: data.submissions || [],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createCategoryChart(data) {
    const ctx = document.getElementById('category-distribution-chart').getContext('2d');

    if (categoryChart) {
        categoryChart.destroy();
    }

    categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.categories || [],
            datasets: [{
                data: data.counts || [],
                backgroundColor: [
                    '#3B82F6',
                    '#10B981',
                    '#F59E0B',
                    '#EF4444',
                    '#8B5CF6',
                    '#06B6D4'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function populateReviewerTable(data) {
    const tbody = document.getElementById('reviewer-performance-table');
    tbody.innerHTML = '';

    data.forEach(reviewer => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${reviewer.reviewer_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reviewer.total_reviews}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reviewer.avg_score}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reviewer.quality_score}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reviewer.avg_time_hours}</td>
        `;
        tbody.appendChild(row);
    });
}

function populateQualityMetrics(data) {
    document.getElementById('total-reviews').textContent = data.total_reviews || 0;
    document.getElementById('quality-issues').textContent = data.quality_issues || 0;
    document.getElementById('issue-rate').textContent = (data.quality_issue_rate || 0) + '%';
    document.getElementById('avg-comment-length').textContent = data.avg_comment_length || 0;
}

function refreshData() {
    loadDashboardData();
    loadCharts();
}
</script>
@endsection
