@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Review Quality Settings</h2>
            <p class="mt-1 text-sm text-gray-600">Configure quality validation thresholds and automation rules</p>
        </div>
    </div>

    <!-- Settings Form -->
    <form method="POST" action="{{ route('admin.review-quality.update-settings') }}" class="space-y-8">
        @csrf
        
        <!-- Quality Validation Thresholds -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quality Validation Thresholds</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="min_comment_length" class="block text-sm font-medium text-gray-700">
                        Minimum Comment Length (characters)
                    </label>
                    <input type="number" name="min_comment_length" id="min_comment_length" 
                           value="{{ $settings['min_comment_length'] ?? 50 }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           min="10" max="1000">
                    <p class="mt-1 text-sm text-gray-500">Minimum characters required for quality reviews</p>
                </div>

                <div>
                    <label for="max_score_difference" class="block text-sm font-medium text-gray-700">
                        Maximum Score Difference (%)
                    </label>
                    <input type="number" name="max_score_difference" id="max_score_difference" 
                           value="{{ $settings['max_score_difference'] ?? 30 }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           min="5" max="100">
                    <p class="mt-1 text-sm text-gray-500">Maximum allowed difference between reviewer scores</p>
                </div>

                <div>
                    <label for="min_review_time" class="block text-sm font-medium text-gray-700">
                        Minimum Review Time (seconds)
                    </label>
                    <input type="number" name="min_review_time" id="min_review_time" 
                           value="{{ $settings['min_review_time'] ?? 300 }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           min="60" max="3600">
                    <p class="mt-1 text-sm text-gray-500">Minimum time required for thorough reviews</p>
                </div>

                <div>
                    <label for="max_review_time" class="block text-sm font-medium text-gray-700">
                        Maximum Review Time (seconds)
                    </label>
                    <input type="number" name="max_review_time" id="max_review_time" 
                           value="{{ $settings['max_review_time'] ?? 7200 }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           min="1800" max="14400">
                    <p class="mt-1 text-sm text-gray-500">Maximum time before flagging as suspicious</p>
                </div>
            </div>
        </div>

        <!-- Performance Tracking Settings -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Tracking Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="performance_threshold_excellent" class="block text-sm font-medium text-gray-700">
                        Excellent Performance Threshold (%)
                    </label>
                    <input type="number" name="performance_threshold_excellent" id="performance_threshold_excellent" 
                           value="{{ $settings['performance_threshold_excellent'] ?? 80 }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           min="70" max="95">
                    <p class="mt-1 text-sm text-gray-500">Score threshold for excellent performance</p>
                </div>

                <div>
                    <label for="performance_threshold_good" class="block text-sm font-medium text-gray-700">
                        Good Performance Threshold (%)
                    </label>
                    <input type="number" name="performance_threshold_good" id="performance_threshold_good" 
                           value="{{ $settings['performance_threshold_good'] ?? 60 }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           min="50" max="85">
                    <p class="mt-1 text-sm text-gray-500">Score threshold for good performance</p>
                </div>

                <div>
                    <label for="attention_threshold" class="block text-sm font-medium text-gray-700">
                        Attention Needed Threshold (%)
                    </label>
                    <input type="number" name="attention_threshold" id="attention_threshold" 
                           value="{{ $settings['attention_threshold'] ?? 40 }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           min="20" max="60">
                    <p class="mt-1 text-sm text-gray-500">Score threshold for flagging reviewers needing attention</p>
                </div>

                <div>
                    <label for="trend_period_months" class="block text-sm font-medium text-gray-700">
                        Trend Analysis Period (months)
                    </label>
                    <input type="number" name="trend_period_months" id="trend_period_months" 
                           value="{{ $settings['trend_period_months'] ?? 6 }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           min="3" max="12">
                    <p class="mt-1 text-sm text-gray-500">Number of months for trend analysis</p>
                </div>
            </div>
        </div>

        <!-- Automation Rules -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Automation Rules</h3>
            <div class="space-y-4">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="auto_flag_quality_issues" id="auto_flag_quality_issues" 
                               value="1" {{ ($settings['auto_flag_quality_issues'] ?? true) ? 'checked' : '' }}
                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="auto_flag_quality_issues" class="font-medium text-gray-700">Automatically flag quality issues</label>
                        <p class="text-gray-500">Automatically flag abstracts with quality problems for admin decision review</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="auto_notify_poor_performance" id="auto_notify_poor_performance" 
                               value="1" {{ ($settings['auto_notify_poor_performance'] ?? true) ? 'checked' : '' }}
                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="auto_notify_poor_performance" class="font-medium text-gray-700">Notify poor performers</label>
                        <p class="text-gray-500">Automatically notify reviewers with consistently poor performance</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="auto_assign_top_reviewers" id="auto_assign_top_reviewers" 
                               value="1" {{ ($settings['auto_assign_top_reviewers'] ?? false) ? 'checked' : '' }}
                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="auto_assign_top_reviewers" class="font-medium text-gray-700">Prefer top performers</label>
                        <p class="text-gray-500">Prefer top-performing reviewers when assigning new reviews</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="auto_export_reports" id="auto_export_reports" 
                               value="1" {{ ($settings['auto_export_reports'] ?? false) ? 'checked' : '' }}
                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="auto_export_reports" class="font-medium text-gray-700">Auto-export reports</label>
                        <p class="text-gray-500">Automatically generate and export performance reports weekly</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conflict Detection Settings -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Conflict Detection Settings</h3>
            <div class="space-y-4">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="detect_institution_conflicts" id="detect_institution_conflicts" 
                               value="1" {{ ($settings['detect_institution_conflicts'] ?? true) ? 'checked' : '' }}
                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="detect_institution_conflicts" class="font-medium text-gray-700">Detect institution conflicts</label>
                        <p class="text-gray-500">Flag reviews where reviewer and author are from same institution</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="detect_coauthor_conflicts" id="detect_coauthor_conflicts" 
                               value="1" {{ ($settings['detect_coauthor_conflicts'] ?? true) ? 'checked' : '' }}
                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="detect_coauthor_conflicts" class="font-medium text-gray-700">Detect co-author conflicts</label>
                        <p class="text-gray-500">Flag reviews where reviewer is listed as co-author</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="detect_self_review" id="detect_self_review" 
                               value="1" {{ ($settings['detect_self_review'] ?? true) ? 'checked' : '' }}
                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="detect_self_review" class="font-medium text-gray-700">Detect self-review attempts</label>
                        <p class="text-gray-500">Flag attempts where author tries to review their own work</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.review-quality.dashboard') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validate form inputs
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const minCommentLength = parseInt(document.getElementById('min_comment_length').value);
        const maxScoreDifference = parseInt(document.getElementById('max_score_difference').value);
        const minReviewTime = parseInt(document.getElementById('min_review_time').value);
        const maxReviewTime = parseInt(document.getElementById('max_review_time').value);
        
        if (minReviewTime >= maxReviewTime) {
            e.preventDefault();
            alert('Minimum review time must be less than maximum review time.');
            return;
        }
        
        if (maxScoreDifference > 100) {
            e.preventDefault();
            alert('Maximum score difference cannot exceed 100%.');
            return;
        }
    });
});
</script>
@endpush 
