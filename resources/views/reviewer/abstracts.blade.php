@extends('layouts.app')

@section('title', 'Review Assignments')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-gray-900 pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero Section -->
        <div class="bg-indigo-600 rounded-2xl shadow-lg mb-6 md:mb-8 overflow-hidden">
            <div class="p-5 md:p-6 lg:p-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 md:gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 mb-3">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="text-xs font-semibold text-white uppercase tracking-wider">Review Queue</span>
                        </div>
                        <h1 class="text-xl md:text-2xl lg:text-3xl font-bold text-white mb-1.5 md:mb-2">Review Assignments</h1>
                        <p class="text-indigo-200 text-xs md:text-sm">Manage and complete your assigned abstract reviews</p>
                    </div>

                    <a href="{{ route('reviewer.dashboard') }}"
                       class="inline-flex items-center px-4 py-2.5 bg-white hover:bg-indigo-50 text-indigo-600 rounded-lg text-sm font-medium transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Summary Pills -->
        <div class="flex flex-wrap gap-3 mb-6">
            <div class="inline-flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="w-9 h-9 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-base font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['total_assigned'] ?? 0 }}</span>
                </div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total</span>
            </div>
            <div class="inline-flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="w-9 h-9 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-base font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending'] ?? 0 }}</span>
                </div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pending</span>
            </div>
            <div class="inline-flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="w-9 h-9 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-base font-bold text-orange-600 dark:text-orange-400">{{ $stats['revisions'] ?? 0 }}</span>
                </div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Re-Reviews</span>
            </div>
            <div class="inline-flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-base font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['completed'] ?? 0 }}</span>
                </div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Completed</span>
            </div>
        </div>

        <!-- Filters & Search Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Filter Tabs -->
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('reviewer.abstracts') }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition {{ request('status') === null ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        All Reviews
                    </a>

                    <a href="{{ route('reviewer.abstracts') }}?status=pending"
                       class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition {{ request('status') === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Pending
                        @if(($stats['pending'] ?? 0) > 0)
                        <span class="ml-2 px-2 py-0.5 rounded-full text-xs {{ request('status') === 'pending' ? 'bg-white/20' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400' }}">{{ $stats['pending'] }}</span>
                        @endif
                    </a>

                    <a href="{{ route('reviewer.abstracts') }}?status=revisions"
                       class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition {{ request('status') === 'revisions' ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Re-Reviews
                        @if(($stats['revisions'] ?? 0) > 0)
                        <span class="ml-2 px-2 py-0.5 rounded-full text-xs {{ request('status') === 'revisions' ? 'bg-white/20' : 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400' }}">{{ $stats['revisions'] }}</span>
                        @endif
                    </a>

                    <a href="{{ route('reviewer.abstracts') }}?status=completed"
                       class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition {{ request('status') === 'completed' ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Completed
                    </a>
                </div>

                <!-- Search -->
                <form method="GET" class="flex w-full lg:w-auto">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif

                    <div class="relative w-full lg:w-72">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search by title..."
                               class="w-full pl-12 pr-4 py-3 border-2 border-slate-200 dark:border-gray-600 rounded-xl text-sm font-medium focus:ring-2 focus:ring-reviewer-500 focus:border-reviewer-500 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 transition-all">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Assignments List -->
        @if($assignedAbstracts->count() > 0)
            <div class="space-y-4">
                @foreach($assignedAbstracts as $abstract)
                    @php
                        $reviewer = auth()->user();
                        $isRevisionRequired = $abstract->status === 'revision_required';
                        $isWaitingForAuthor = in_array($abstract->status, ['revision_required', 'revision_requested']);

                        // Current round of the abstract
                        $abstractRound = $abstract->revision_round ?? 0;

                        // 1. Try to find a review for the CURRENT round
                        $currentReview = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                            ->where('reviewer_id', auth()->id())
                            ->where('review_round', $abstractRound)
                            ->first();

                        // 2. If no current round review, find the LATEST submitted review from any round
                        if (!$currentReview) {
                            $currentReview = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                                ->where('reviewer_id', auth()->id())
                                ->where('status', 'submitted')
                                ->orderBy('review_round', 'desc')
                                ->first();
                        }

                        // Has the reviewer ever submitted an ACCEPT review for this abstract?
                        $hasAlreadyAccepted = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                            ->where('reviewer_id', auth()->id())
                            ->where('status', 'submitted')
                            ->whereIn('recommendation', ['accept', 'accept_oral', 'accept_poster'])
                            ->exists();

                        $hasDraft = $currentReview && $currentReview->status === 'draft' && $currentReview->review_round == $abstractRound;
                        $hasSubmitted = ($currentReview && $currentReview->status === 'submitted') || $hasAlreadyAccepted;

                        // Is the reviewer actually assigned to the current active round?
                        $isAssignedToCurrentRound = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                            ->where('reviewer_id', auth()->id())
                            ->where('review_round', $abstractRound)
                            ->exists();

                        $showReReviewBadge = ($abstractRound > 0) && $isAssignedToCurrentRound && !$hasSubmitted && !$hasAlreadyAccepted;
                        $displayRound = $abstractRound;
                    @endphp

                    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-slate-100 dark:border-gray-700 p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            <!-- Left: Abstract Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-4">
                                    <!-- Status Icon -->
                                    <div class="flex-shrink-0">
                                        @if($hasSubmitted)
                                            <div class="w-11 h-11 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                        @elseif($hasDraft)
                                            <div class="w-11 h-11 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </div>
                                        @elseif($isWaitingForAuthor)
                                            <div class="w-11 h-11 bg-slate-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-11 h-11 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Title & Meta -->
                                    <div class="flex-1 min-w-0">
                                        @if($showReReviewBadge)
                                        <div class="mb-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-orange-500 text-white">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Round {{ $displayRound }}
                                            </span>
                                        </div>
                                        @endif
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-snug mb-2 line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                            {{ $abstract->title }}
                                        </h3>
                                        <div class="flex items-center gap-3 flex-wrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                {{ $abstract->subtheme ?? 'General' }}
                                            </span>
                                            @if($hasSubmitted)
                                                <span class="inline-flex items-center text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5"></span>
                                                    Completed
                                                </span>
                                            @elseif($hasDraft)
                                                <span class="inline-flex items-center text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                                    <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full mr-1.5"></span>
                                                    Draft Saved
                                                </span>
                                            @elseif($isWaitingForAuthor)
                                                <span class="inline-flex items-center text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1.5"></span>
                                                    Waiting for Author
                                                </span>
                                            @else
                                                <span class="inline-flex items-center text-xs font-medium text-amber-600 dark:text-amber-400">
                                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full mr-1.5 animate-pulse"></span>
                                                    Pending Review
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Score & Action -->
                            <div class="flex items-center gap-6 flex-shrink-0">
                                @if($hasSubmitted && $currentReview)
                                    <!-- Score Display -->
                                    <div class="text-center">
                                        <div class="text-3xl font-black text-slate-900 dark:text-white">{{ $currentReview->score }}</div>
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">/100</div>
                                    </div>

                                    <!-- Recommendation Badge -->
                                    @if($currentReview->recommendation)
                                        @php
                                            $recommendation = $currentReview->recommendation;
                                            $badgeConfig = match($recommendation) {
                                                'accept' => ['bg' => 'bg-emerald-600', 'label' => 'Accepted'],
                                                'accept_with_revisions', 'minor_revisions', 'major_revisions' => ['bg' => 'bg-amber-500', 'label' => 'Revision'],
                                                'reject' => ['bg' => 'bg-red-500', 'label' => 'Rejected'],
                                                default => ['bg' => 'bg-gray-500', 'label' => 'Unknown'],
                                            };
                                        @endphp
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white uppercase tracking-wide {{ $badgeConfig['bg'] }}">
                                            {{ $badgeConfig['label'] }}
                                        </span>
                                    @endif

                                    <a href="{{ route('reviewer.review', $abstract) }}"
                                       class="px-5 py-3 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-gray-600 transition-all text-sm">
                                        View
                                    </a>
                                @elseif($isWaitingForAuthor)
                                    <button disabled class="px-5 py-3 bg-slate-100 dark:bg-gray-800 text-slate-400 font-bold rounded-xl cursor-not-allowed text-sm border border-slate-200 dark:border-gray-700">
                                        Waiting for Author
                                    </button>
                                @else
                                    <div class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            data-action="{{ route('reviewer.decline-assignment', $abstract) }}"
                                            data-title="{{ $abstract->title }}"
                                            class="inline-flex items-center px-4 py-2.5 border border-red-200 dark:border-red-900/40 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 font-medium rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition text-sm"
                                            onclick="openDeclineAssignmentModal(this.dataset.action, this.dataset.title)">
                                            Not My Expertise
                                        </button>
                                        <a href="{{ route('reviewer.review', $abstract) }}"
                                           class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition text-sm">
                                            {{ $hasDraft ? 'Continue Review' : 'Start Review' }}
                                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                            </svg>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-xl border border-slate-100 dark:border-gray-700 p-16 text-center">
                <div class="w-24 h-24 bg-slate-100 dark:bg-gray-700 rounded-3xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-slate-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-3">No Assignments Found</h3>
                <p class="text-slate-500 dark:text-gray-400 max-w-md mx-auto mb-6">
                    @if(request('status') || request('search'))
                        No abstracts match your current filters.
                    @else
                        You haven't been assigned any abstracts to review yet.
                    @endif
                </p>
                @if(request('status') || request('search'))
                    <a href="{{ route('reviewer.abstracts') }}"
                       class="inline-flex items-center px-6 py-3 bg-reviewer-600 text-white font-bold rounded-xl hover:bg-reviewer-700 transition-all shadow-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Clear Filters
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

<div
    id="declineAssignmentModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4"
    aria-hidden="true"
>
    <div class="w-full max-w-xl rounded-3xl bg-white dark:bg-gray-800 shadow-2xl border border-slate-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 dark:border-gray-700">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-red-500">Reviewer Reassignment</p>
                    <h3 class="mt-2 text-xl font-black text-slate-900 dark:text-white">This Abstract Is Outside My Expertise</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">
                        We’ll remove it from your queue immediately and keep it from being assigned back to you later.
                    </p>
                </div>
                <button type="button" class="rounded-full p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-gray-700" onclick="closeDeclineAssignmentModal()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <form id="declineAssignmentForm" method="POST" class="px-6 py-5 space-y-5">
            @csrf
            <div class="rounded-2xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-700 p-4">
                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Abstract</p>
                <p id="declineAssignmentTitle" class="mt-2 text-sm font-semibold text-slate-800 dark:text-gray-100"></p>
            </div>
            <div>
                <label for="declineAssignmentReason" class="block text-sm font-semibold text-slate-700 dark:text-gray-200 mb-2">
                    Brief reason
                </label>
                <textarea
                    id="declineAssignmentReason"
                    name="reason"
                    rows="4"
                    required
                    maxlength="500"
                    class="w-full rounded-2xl border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-slate-900 dark:text-white focus:border-red-400 focus:ring-red-400"
                    placeholder="Example: My expertise is in epidemiology, but this abstract is primarily molecular laboratory work."
                ></textarea>
            </div>
            <div class="flex items-center justify-end gap-3">
                <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-gray-600 text-sm font-semibold text-slate-600 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-gray-700" onclick="closeDeclineAssignmentModal()">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-sm font-semibold text-white">
                    Remove From My Queue
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openDeclineAssignmentModal(formAction, abstractTitle) {
        const modal = document.getElementById('declineAssignmentModal');
        const form = document.getElementById('declineAssignmentForm');
        const title = document.getElementById('declineAssignmentTitle');
        const reason = document.getElementById('declineAssignmentReason');

        form.action = formAction;
        title.textContent = abstractTitle;
        reason.value = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        reason.focus();
    }

    function closeDeclineAssignmentModal() {
        const modal = document.getElementById('declineAssignmentModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDeclineAssignmentModal();
        }
    });
</script>
@endpush
