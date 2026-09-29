@extends('layouts.app')

@section('title', 'Abstract History - ' . $abstract->title)

@push('styles')
<style>
/* Timeline Styles */
.timeline {
    position: relative;
    margin: 0;
    padding: 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 2rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #3b82f6, #8b5cf6);
    border-radius: 1px;
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
    padding-left: 4.5rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 1.5rem;
    top: 0.5rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    border: 3px solid #ffffff;
    z-index: 10;
}

.timeline-item.review-submitted::before {
    background: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
}

.timeline-item.review-updated::before {
    background: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
}

.timeline-item.reviewer-assigned::before {
    background: #8b5cf6;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.2);
}

.timeline-item.status-changed::before {
    background: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2);
}

.timeline-item.admin-action::before {
    background: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
}

/* Enhanced Card Styles */
.history-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.history-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

/* Score visualization */
.score-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.75rem;
}

.score-excellent { background: linear-gradient(135deg, #10b981, #059669); }
.score-very-good { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.score-good { background: linear-gradient(135deg, #f59e0b, #d97706); }
.score-fair { background: linear-gradient(135deg, #f97316, #ea580c); }
.score-poor { background: linear-gradient(135deg, #ef4444, #dc2626); }

/* Metadata styling */
.metadata-item {
    @apply bg-gray-50 dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700;
}

/* Animation for timeline items */
.timeline-item {
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
}

.timeline-item:nth-child(1) { animation-delay: 0.1s; }
.timeline-item:nth-child(2) { animation-delay: 0.2s; }
.timeline-item:nth-child(3) { animation-delay: 0.3s; }
.timeline-item:nth-child(4) { animation-delay: 0.4s; }
.timeline-item:nth-child(5) { animation-delay: 0.5s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .timeline::before {
        left: 1rem;
    }

    .timeline-item {
        padding-left: 3rem;
    }

    .timeline-item::before {
        left: 0.75rem;
    }
}

/* Status indicator styles */
.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-submitted { @apply bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300; }
.status-under_review { @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300; }
.status-accepted { @apply bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300; }
.status-rejected { @apply bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300; }
.status-revision { @apply bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300; }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4">
    <!-- Header -->
    <div class="history-card rounded-xl p-6 mb-8">
        <div class="flex items-start justify-between mb-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Abstract History</h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                        Admin View
                    </span>
                </div>
                <h2 class="text-lg text-gray-700 dark:text-gray-300 font-medium mb-2">{{ $abstract->title }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Complete timeline of all actions and changes for this abstract submission
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.abstracts.view', $abstract) }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    View Abstract
                </a>
                <a href="{{ route('admin.abstracts.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>

        <!-- Abstract Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">Author:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $abstract->user->name }}</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">Status:</span>
                <span class="status-indicator status-{{ $abstract->status }}">
                    {{ ucfirst(str_replace('_', ' ', $abstract->status)) }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a1.994 1.994 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">Subtheme:</span>
                <span class="text-gray-900 dark:text-white">{{ $abstract->subtheme }}</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 3v2m-6 8h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">ID:</span>
                <span class="font-mono text-gray-900 dark:text-white">#{{ $abstract->id }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Timeline -->
        <div class="lg:col-span-2">
            <div class="history-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Review Timeline
                    <span class="text-sm text-gray-500 dark:text-gray-400 font-normal">
                        ({{ $reviewHistory->count() }} events)
                    </span>
                </h3>

                @if($reviewHistory->count() > 0)
                    <div class="timeline">
                        @foreach($reviewHistory as $history)
                            <div class="timeline-item {{ strtolower(str_replace(' ', '-', $history->action)) }}">
                                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                                    <!-- Event Header -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-white capitalize">
                                                {{ str_replace('_', ' ', $history->action) }}
                                            </h4>
                                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                @if($history->reviewer)
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                        {{ $history->reviewer->name }}
                                                    </span>
                                                @endif
                                                @if($history->reviewer_position)
                                                    <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                                        {{ $history->reviewer_position }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-900 dark:text-white font-medium">
                                                {{ $history->action_date->format('M d, Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $history->action_date->format('h:i A') }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Score (if applicable) -->
                                    @if($history->score)
                                        <div class="flex items-center gap-2 mb-3">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Score:</span>
                                            @php
                                                $scoreClass = '';
                                                if ($history->score >= 9) $scoreClass = 'score-excellent';
                                                elseif ($history->score >= 7) $scoreClass = 'score-very-good';
                                                elseif ($history->score >= 5) $scoreClass = 'score-good';
                                                elseif ($history->score >= 3) $scoreClass = 'score-fair';
                                                else $scoreClass = 'score-poor';
                                            @endphp
                                            <span class="score-badge {{ $scoreClass }}">
                                                {{ number_format($history->score, 1) }}/10
                                            </span>
                                        </div>
                                    @endif

                                    <!-- Comments -->
                                    @if($history->comments)
                                        <div class="mb-3">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Comments:</span>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                                {{ $history->comments }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Reason -->
                                    @if($history->reason)
                                        <div class="mb-3">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Reason:</span>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">
                                                {{ $history->reason }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Metadata -->
                                    @if($history->metadata && count($history->metadata) > 0)
                                        <div class="mt-3">
                                            <span class="text-sm text-gray-600 dark:text-gray-400 mb-2 block">Additional Details:</span>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                @foreach($history->metadata as $key => $value)
                                                    <div class="metadata-item">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ str_replace('_', ' ', $key) }}:</span>
                                                        <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $value }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Admin Action -->
                                    @if($history->admin)
                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Admin action by {{ $history->admin->name }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No History Available</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            This abstract doesn't have any recorded review history yet.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <!-- Current Status -->
            <div class="history-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Current Status
                </h3>
                <div class="space-y-4">
                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                        <div class="mt-1">
                            <span class="status-indicator status-{{ $abstract->status }}">
                                {{ ucfirst(str_replace('_', ' ', $abstract->status)) }}
                            </span>
                        </div>
                    </div>

                    @if($abstract->reviewer1_id || $abstract->reviewer2_id)
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Assigned Reviewers:</span>
                            <div class="mt-1 space-y-1">
                                @if($abstract->reviewer1)
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <span class="text-xs text-gray-500">Reviewer 1:</span> {{ $abstract->reviewer1->name }}
                                    </div>
                                @endif
                                @if($abstract->reviewer2)
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <span class="text-xs text-gray-500">Reviewer 2:</span> {{ $abstract->reviewer2->name }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Submitted:</span>
                        <div class="text-sm text-gray-900 dark:text-white mt-1">
                            {{ $abstract->created_at->format('M d, Y \a\t h:i A') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $abstract->created_at->diffForHumans() }}
                        </div>
                    </div>

                    @if($abstract->updated_at->ne($abstract->created_at))
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Last Updated:</span>
                            <div class="text-sm text-gray-900 dark:text-white mt-1">
                                {{ $abstract->updated_at->format('M d, Y \a\t h:i A') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $abstract->updated_at->diffForHumans() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="history-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Quick Actions
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.abstracts.edit', $abstract) }}"
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Abstract
                    </a>

                    <a href="{{ route('admin.abstracts.view', $abstract) }}"
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View Details
                    </a>

                    <button type="button" onclick="window.print()"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print History
                    </button>
                </div>
            </div>

            <!-- Statistics -->
            <div class="history-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Statistics
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Events:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $reviewHistory->count() }}</span>
                    </div>

                    @php
                        $reviewSubmissions = $reviewHistory->where('action', 'review_submitted')->count();
                        $reviewUpdates = $reviewHistory->where('action', 'review_updated')->count();
                        $averageScore = $reviewHistory->whereNotNull('score')->avg('score');
                    @endphp

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Review Submissions:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $reviewSubmissions }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Review Updates:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $reviewUpdates }}</span>
                    </div>

                    @if($averageScore)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Average Score:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($averageScore, 1) }}/10</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Days Since Submission:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ (int)$abstract->created_at->diffInDays(now()) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Enhanced print styles
    window.addEventListener('beforeprint', function() {
        document.body.classList.add('printing');
    });

    window.addEventListener('afterprint', function() {
        document.body.classList.remove('printing');
    });
});
</script>
@endpush
@endsection
