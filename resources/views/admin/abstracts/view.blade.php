@extends('layouts.app')

@section('title', 'Abstract Details - ' . $abstract->title)

@section('content')
@php
    $coauthors = $abstract->coauthors;
    if (is_string($coauthors)) {
        $coauthors = json_decode($coauthors, true) ?? [];
    }
    $coauthors = is_array($coauthors) ? $coauthors : [];
    $isRevisionFlow = ($abstract->revision_round ?? 0) > 0 || in_array($abstract->status, ['revision_submitted', 'revision_under_review', 'revision_review', 'revision_required', 'revision_requested'], true);
    $acceptRecommendations = ['accept', 'accept_oral', 'accept_poster'];
    $previousRound = max(0, (int) ($abstract->revision_round ?? 0) - 1);
    $previousRoundReviews = $abstract->reviews->where('review_round', $previousRound)->where('status', 'submitted');
    $currentRoundSubmittedReviews = $abstract->reviews
        ->where('review_round', (int) ($abstract->revision_round ?? 0))
        ->where('status', 'submitted');
    $reviewer1PreviouslyAccepted = $abstract->reviewer_id
        ? $previousRoundReviews->where('reviewer_id', $abstract->reviewer_id)->contains(function ($review) use ($acceptRecommendations) {
            return in_array(strtolower((string) $review->recommendation), $acceptRecommendations, true);
        })
        : false;
    $reviewer2PreviouslyAccepted = $abstract->reviewer_2_id
        ? $previousRoundReviews->where('reviewer_id', $abstract->reviewer_2_id)->contains(function ($review) use ($acceptRecommendations) {
            return in_array(strtolower((string) $review->recommendation), $acceptRecommendations, true);
        })
        : false;
    $hasSingleRevisionReviewer = $isRevisionFlow && ((bool) $abstract->reviewer_id xor (bool) $abstract->reviewer_2_id);
    $displayReviews = $currentRoundSubmittedReviews->isNotEmpty()
        ? $currentRoundSubmittedReviews->sortBy('reviewer_number')->values()
        : $previousRoundReviews->sortBy('reviewer_number')->values();
    $displayAverageScore = $displayReviews->count() > 0 ? $displayReviews->avg('score') : null;
    $displayReviewScopeLabel = $currentRoundSubmittedReviews->isNotEmpty()
        ? 'Current round review progress'
        : (($isRevisionFlow && $previousRoundReviews->isNotEmpty()) ? 'Previous round review history' : 'Review progress');
@endphp

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-12">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <!-- Premium Header -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden mb-8">
            <div class="px-8 py-10 bg-gradient-to-r from-slate-900 to-slate-800 dark:from-black dark:to-gray-900">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="space-y-4 max-w-4xl">
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-[10px] font-black uppercase tracking-widest border border-blue-500/30">
                                Abstract ID: #{{ str_pad($abstract->id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                            @if($abstract->conference_code)
                                <span class="px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-black uppercase tracking-widest border border-indigo-500/30">
                                    {{ $abstract->conference_code }}
                                </span>
                            @endif
                        </div>
                        <h1 class="text-3xl md:text-4xl font-black text-white leading-tight">
                            {{ $abstract->title }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-4 text-slate-400 text-sm">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span class="font-bold text-slate-200">{{ $abstract->author_name }}</span>
                            </div>
                            <div class="flex items-center gap-2 border-l border-slate-700 pl-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <span>{{ $abstract->author_institute }}</span>
                            </div>
                            <div class="flex items-center gap-2 border-l border-slate-700 pl-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>{{ $abstract->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.abstracts.index') }}" class="px-5 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold transition-all border border-white/10 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to List
                        </a>
                        <a href="{{ route('admin.abstracts.edit', $abstract) }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-blue-900/40 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit Abstract
                        </a>
                        @if(!$abstract->reviewer_id || !$abstract->reviewer_2_id)
                            <a href="{{ route('admin.abstracts.assign-reviewers-page', ['abstract' => $abstract->id]) }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-emerald-900/40 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                {{ $isRevisionFlow ? 'Manage Re-reviewers' : 'Assign Reviewers' }}
                            </a>
                        @endif
                        <a href="{{ route('admin.decisions.show', $abstract) }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-900/40 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Decision Portal
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content Area -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Abstract Description -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Scientific Abstract
                        </h2>
                    </div>
                    <div class="p-8">
                        <div class="prose prose-lg dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 leading-relaxed">
                            {!! $abstract->description !!}
                        </div>
                    </div>
                </div>

                <!-- Co-Authors if any -->
                @if(count($coauthors) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            Contributing Co-Authors
                        </h2>
                    </div>
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($coauthors as $coauthor)
                                <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-50 dark:bg-gray-700/30 border border-slate-100 dark:border-gray-700 transition-all hover:border-indigo-300 dark:hover:border-indigo-700">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                        {{ substr($coauthor['name'] ?? '?', 0, 1) }}
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">{{ $coauthor['name'] ?? 'Anonymous' }}</h3>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $coauthor['institute'] ?? 'No Institute Provided' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-8">
                <!-- Metadata Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Submission Metadata</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center py-2 border-b border-slate-50 dark:border-gray-700 last:border-0">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Status</span>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                {{ ucfirst(str_replace('_', ' ', $abstract->status)) }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1 py-2 border-b border-slate-50 dark:border-gray-700 last:border-0">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Track/Subtheme</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 leading-tight">{{ $abstract->subtheme }}</span>
                        </div>
                        <div class="flex flex-col gap-1 py-2 border-b border-slate-50 dark:border-gray-700 last:border-0">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Keywords</span>
                            <div class="flex flex-wrap gap-1.5 mt-1">
                                @if(!empty($abstract->keywords))
                                    @foreach(explode(',', $abstract->keywords) as $keyword)
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-gray-700 text-[10px] font-bold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-gray-600">
                                            {{ trim($keyword) }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-[10px] text-slate-400 italic">None provided</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-50 dark:border-gray-700 last:border-0">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Presentation</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase">{{ $abstract->presentation_mode }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-50 dark:border-gray-700 last:border-0">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Proceedings</span>
                            @if($abstract->include_in_proceedings)
                                <span class="text-[10px] font-black text-emerald-500 uppercase">Included</span>
                            @else
                                <span class="text-[10px] font-black text-slate-400 uppercase">Excluded</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Reviewer Assignment Status -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">{{ $isRevisionFlow ? 'Revision Reviewer Tracking' : 'Reviewer Tracking' }}</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        @if($hasSingleRevisionReviewer)
                            <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                                This abstract is in a revision round. Only the reviewer who requested changes is currently assigned for re-review, so a single active reviewer here is expected.
                            </div>
                        @endif

                        <!-- Reviewer 1 -->
                        <div class="space-y-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">{{ $isRevisionFlow ? 'Revision Reviewer 1' : 'Scientific Reviewer 1' }}</span>
                            @if($abstract->reviewer1)
                                <div class="p-3 bg-slate-50 dark:bg-gray-700/30 rounded-xl border border-slate-100 dark:border-gray-700">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $abstract->reviewer1->first_name }} {{ $abstract->reviewer1->last_name }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $abstract->reviewer1->email }}</p>
                                </div>
                            @else
                                <div class="p-3 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-dashed border-amber-200 dark:border-amber-800 text-center">
                                    <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest">Unassigned</p>
                                </div>
                            @endif
                        </div>

                        <!-- Reviewer 2 -->
                        <div class="space-y-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">{{ $isRevisionFlow ? 'Revision Reviewer 2' : 'Scientific Reviewer 2' }}</span>
                            @if($abstract->reviewer2)
                                <div class="p-3 bg-slate-50 dark:bg-gray-700/30 rounded-xl border border-slate-100 dark:border-gray-700">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $abstract->reviewer2->first_name }} {{ $abstract->reviewer2->last_name }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $abstract->reviewer2->email }}</p>
                                </div>
                            @else
                                <div class="p-3 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-dashed border-amber-200 dark:border-amber-800 text-center">
                                    <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest">{{ $isRevisionFlow ? 'Not needed for this re-review' : 'Unassigned' }}</p>
                                </div>
                            @endif
                        </div>

                        @if($isRevisionFlow && ($reviewer1PreviouslyAccepted || $reviewer2PreviouslyAccepted))
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800 space-y-1">
                                <p class="font-bold uppercase tracking-wide">Previous Round Acceptance Context</p>
                                @if($reviewer1PreviouslyAccepted)
                                    <p>Reviewer 1 already accepted in the prior round and may not need to re-review.</p>
                                @endif
                                @if($reviewer2PreviouslyAccepted)
                                    <p>Reviewer 2 already accepted in the prior round and may not need to re-review.</p>
                                @endif
                            </div>
                        @endif

                                @if($displayReviews->isNotEmpty())
                                    <div class="pt-4 mt-4 border-t border-slate-50 dark:border-gray-700">
                                        <div class="flex items-center justify-between text-xs mb-3">
                                            <span class="text-slate-500">{{ $displayReviewScopeLabel }}</span>
                                            <span class="font-bold text-slate-900 dark:text-white">{{ $displayReviews->count() }}/2</span>
                                        </div>
                                        <div class="w-full bg-slate-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden mb-4">
                                            <div class="bg-blue-600 h-full rounded-full" style="width: {{ min(($displayReviews->count() / 2) * 100, 100) }}%"></div>
                                        </div>

                                        <div class="space-y-3">
                                            @foreach($displayReviews as $displayReview)
                                                <div class="p-2.5 rounded-xl border border-slate-100 dark:border-gray-700 bg-slate-50/50 dark:bg-gray-800/50">
                                                    <div class="flex items-center justify-between gap-2 mb-1">
                                                        <span class="text-[10px] font-bold text-slate-500 uppercase">
                                                            Reviewer {{ $displayReview->reviewer_number }} Score
                                                        </span>
                                                        <span class="text-xs font-black text-slate-900 dark:text-white">{{ number_format($displayReview->score, 1) }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-3 mb-2">
                                                        <span class="text-[10px] text-slate-500">
                                                            {{ $displayReview->reviewer?->first_name }} {{ $displayReview->reviewer?->last_name }}
                                                        </span>
                                                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">
                                                            {{ str_replace('_', ' ', $displayReview->recommendation) }}
                                                        </span>
                                                    </div>
                                                    <div class="w-full bg-slate-200 dark:bg-gray-600 h-1 rounded-full overflow-hidden">
                                                        <div class="h-full {{ ($displayReview->score ?? 0) >= 70 ? 'bg-emerald-500' : (($displayReview->score ?? 0) >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}" 
                                                             style="width: {{ $displayReview->score ?? 0 }}%"></div>
                                                    </div>
                                                </div>
                                            @endforeach

                                            @if($displayAverageScore !== null)
                                            <div class="pt-2 border-t border-slate-100 dark:border-gray-700 flex items-center justify-between">
                                                <span class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase underline decoration-blue-500 decoration-2">Average Score</span>
                                                <span class="text-sm font-black text-blue-600 dark:text-blue-400">{{ number_format($displayAverageScore, 1) }}%</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
            </div>
        </div>
    </div>
</div>
@endsection
