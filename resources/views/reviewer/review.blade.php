@extends('layouts.app')

@section('title', 'Review Abstract')

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 font-sans pb-20">
    <!-- Premium Header -->
    <div class="relative bg-gradient-to-br from-reviewer-700 via-reviewer-800 to-purple-900 pt-16 pb-24 rounded-b-[4rem] shadow-2xl overflow-hidden mb-[-2rem]">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-[0.03]"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>

        <div class="max-w-7xl mx-auto px-6 sm:px-10 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="space-y-3">
                    <div class="flex items-center gap-3 flex-wrap">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20">
                            <span class="text-[10px] font-black text-white uppercase tracking-[0.2em]">#{{ str_pad($abstract->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        @if($currentRevisionRound > 0)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-lg animate-pulse">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Re-Review R{{ $currentRevisionRound }}
                            </span>
                        @endif
                        @if($isBlindReview)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-white/10 text-white/80 border border-white/20">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                                Blind
                            </span>
                        @endif
                    </div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight leading-snug max-w-2xl" style="font-family: 'Outfit', sans-serif;">
                        Review Abstract
                    </h1>
                    <p class="text-reviewer-200/70 text-sm font-medium">
                        @if($currentRevisionRound > 0)
                            Re-review the author's revisions and your previous feedback
                        @else
                            Evaluate the submission and provide constructive feedback
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    @if(!$hasSubmitted && !in_array($abstract->status, ['revision_required', 'revision_requested', 'accepted', 'rejected']))
                        <button
                            type="button"
                            class="inline-flex items-center px-5 py-2.5 bg-red-500/15 backdrop-blur-md border border-red-300/30 rounded-xl text-sm font-bold text-white hover:bg-red-500/25 transition-all"
                            onclick="openDeclineAssignmentModal()">
                            Not My Expertise
                        </button>
                    @endif
                    @if($currentRevisionRound > 0)
                        <a href="{{ route('reviewer.comparison', $abstract) }}"
                           class="inline-flex items-center px-5 py-2.5 bg-orange-500 text-white font-bold rounded-xl hover:bg-orange-600 transition-all shadow-lg text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            View Changes
                        </a>
                    @endif
                    <a href="{{ route('reviewer.abstracts') }}"
                       class="inline-flex items-center px-5 py-2.5 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl text-sm font-bold text-white hover:bg-white/20 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-10 mt-8 relative z-20">
        <!-- Re-Review Alert Banner with Quick Summary -->
        @if($currentRevisionRound > 0 && !($hasAlreadyAccepted ?? false))
            <div class="mb-8 bg-gradient-to-r from-orange-500 to-amber-500 rounded-2xl shadow-xl shadow-orange-200/30 dark:shadow-none overflow-hidden">
                <div class="p-5">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-black text-white">⚡ Quick Re-Review - Round {{ $currentRevisionRound }}</h3>
                            <p class="text-orange-100 text-sm font-medium">
                                Author has addressed feedback. Review changes and update your decision.
                                @if($abstract->revision_submitted_at)
                                    <span class="opacity-80">• Submitted {{ $abstract->revision_submitted_at->diffForHumans() }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Quick Summary Grid -->
                    @if($previousReviews->count() > 0)
                        @php $lastReview = $previousReviews->first(); @endphp
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                            <!-- Your Previous Score -->
                            <div class="bg-white/15 backdrop-blur-md rounded-xl p-3 border border-white/20">
                                <p class="text-orange-200 text-[10px] uppercase tracking-wider font-bold mb-1">Your Previous Score</p>
                                <p class="text-2xl font-black text-white">{{ $lastReview->score ?? 'N/A' }}<span class="text-sm font-normal">/100</span></p>
                            </div>

                            <!-- Your Recommendation -->
                            <div class="bg-white/15 backdrop-blur-md rounded-xl p-3 border border-white/20">
                                <p class="text-orange-200 text-[10px] uppercase tracking-wider font-bold mb-1">Your Recommendation</p>
                                @php
                                    $recEmoji = [
                                        'accept' => '✅',
                                        'accept_with_revisions' => '📝',
                                        'reject' => '❌',
                                    ];
                                @endphp
                                <p class="text-lg font-black text-white">{{ $recEmoji[$lastReview->recommendation] ?? '' }} {{ ucfirst(str_replace('_', ' ', $lastReview->recommendation ?? 'N/A')) }}</p>
                            </div>

                            <!-- Other Reviewer -->
                            @if($otherReviewerDecision)
                                <div class="bg-white/15 backdrop-blur-md rounded-xl p-3 border border-white/20">
                                    <p class="text-orange-200 text-[10px] uppercase tracking-wider font-bold mb-1">Other Reviewer</p>
                                    <p class="text-lg font-black text-white">{{ $recEmoji[$otherReviewerDecision['recommendation']] ?? '' }} {{ ucfirst(str_replace('_', ' ', $otherReviewerDecision['recommendation'] ?? 'N/A')) }}</p>
                                </div>
                            @endif

                            <!-- Revision Round -->
                            <div class="bg-white/15 backdrop-blur-md rounded-xl p-3 border border-white/20">
                                <p class="text-orange-200 text-[10px] uppercase tracking-wider font-bold mb-1">Revision Round</p>
                                <p class="text-2xl font-black text-white">{{ $currentRevisionRound }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Your Previous Comments (Shown Automatically if not an accept) -->
                @php
                    $lastRec = $previousReviews->count() > 0 ? $previousReviews->first()->recommendation : null;
                    $isAccept = in_array($lastRec, ['accept']);
                @endphp

                @if($previousReviews->count() > 0 && $previousReviews->first()->comments && !$isAccept)
                    <div class="bg-white/10 px-5 py-4 border-t border-white/20">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-bold text-white text-sm">📋 Your Previous Comments</span>
                        </div>
                        <div class="bg-white/10 rounded-xl p-4 text-sm text-orange-50 leading-relaxed">
                            {{ $previousReviews->first()->comments }}
                        </div>
                    </div>
                @endif

                <!-- Author's Response to Feedback -->
                @if($abstract->revision_feedback)
                    <div class="bg-teal-500/20 px-5 py-4 border-t border-white/20">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-white text-sm mb-2">✍️ Author's Response</p>
                                <div class="bg-white/10 rounded-xl p-4 text-sm text-teal-50 leading-relaxed">
                                    {{ $abstract->revision_feedback }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
            <!-- Left Column: Abstract Content (and Re-Review Context if applicable) -->
            <div class="xl:col-span-7">
                <!-- Abstract Content Card -->
                <div id="abstractDetailsCard" class="sticky top-6 bg-white dark:bg-gray-800 rounded-[2rem] shadow-xl border border-slate-100 dark:border-gray-700 overflow-hidden flex flex-col max-h-[calc(100vh-3rem)]">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-gray-700 dark:to-gray-800 px-8 py-5 border-b border-slate-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-reviewer-100 dark:bg-reviewer-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-reviewer-600 dark:text-reviewer-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Abstract Details</h2>
                                    <p class="text-xs text-slate-500 dark:text-gray-400 font-medium">Review the submission content</p>
                                </div>
                            </div>
                            @php
                                $subtheme = $reviewData->subtheme;
                                $topicColor = \App\Support\ConferenceTopics::color($subtheme);
                                $colorClass = "from-{$topicColor}-500 to-{$topicColor}-600";
                            @endphp
                            <span class="px-4 py-1.5 rounded-xl text-xs font-black text-white uppercase tracking-wider bg-gradient-to-r {{ $colorClass }} shadow-lg">
                                {{ $subtheme }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body (scrollable) -->
                    <div class="p-8 space-y-6 overflow-y-auto flex-1">
                        <!-- Title -->
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Title</label>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white leading-relaxed">{{ $reviewData->title }}</h3>
                        </div>

                        <!-- Author & Institution -->
                        <div class="grid grid-cols-2 gap-6 pt-4 border-t border-slate-100 dark:border-gray-700">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Author</label>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ $isBlindReview ? ($reviewData->anonymized_author ?? 'Anonymous Author') : ($reviewData->author_name ?? 'Not specified') }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Institution</label>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ $isBlindReview ? ($reviewData->anonymized_institute ?? 'Anonymous Institution') : ($reviewData->author_institute ?? 'Not specified') }}
                                </p>
                            </div>
                        </div>

                        <!-- Keywords -->
                        <div class="pt-4 border-t border-slate-100 dark:border-gray-700">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Keywords</label>
                            <div class="flex flex-wrap gap-2">
                                @if(!empty($reviewData->keywords))
                                    @foreach(explode(',', $reviewData->keywords) as $keyword)
                                        <span class="px-3 py-1 rounded-lg bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-gray-600">
                                            {{ trim($keyword) }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-xs text-slate-400 italic font-medium">No keywords provided</span>
                                @endif
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="pt-4 border-t border-slate-100 dark:border-gray-700">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Abstract Content</label>
                            <div class="bg-slate-50 dark:bg-gray-900/50 p-6 rounded-2xl border border-slate-100 dark:border-gray-700">
                                <div class="prose dark:prose-invert max-w-none text-sm text-slate-700 dark:text-gray-300 leading-relaxed">{!! $reviewData->description !!}</div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>

            <!-- Right Sidebar: Review Form or Completed State -->
            <div class="xl:col-span-5 space-y-6">
                @if(!$isCompleted)
                    <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-xl border border-slate-100 dark:border-gray-700 overflow-hidden">
                        <!-- Form Header -->
                        <div class="bg-gradient-to-r from-reviewer-600 to-purple-700 px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-white/10 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/20">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-black text-white">
                                        {{ $currentRevisionRound > 1 ? 'Submit Re-Review' : 'Submit Review' }}
                                    </h2>
                                    <p class="text-reviewer-200 text-xs font-medium">Score and recommendation</p>
                                </div>
                            </div>
                        </div>

                        <!-- Form Body -->
                        <form id="reviewForm" method="POST" action="{{ route('reviewer.submit-review', $abstract) }}" class="p-6">
                            @csrf

                            <div class="sticky top-4 z-20 mb-6">
                                <div class="rounded-2xl border border-reviewer-100 dark:border-gray-600 bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl shadow-lg px-4 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Current Abstract</p>
                                            <h3 class="text-sm font-black text-slate-900 dark:text-white leading-snug line-clamp-2">
                                                {{ $reviewData->title }}
                                            </h3>
                                            <p class="text-xs font-medium text-slate-500 dark:text-gray-400 mt-2 line-clamp-2">
                                                {{ $reviewData->subtheme }}
                                            </p>
                                        </div>
                                        <a href="#abstractDetailsCard"
                                           class="inline-flex items-center px-3 py-2 rounded-xl bg-reviewer-50 dark:bg-reviewer-900/30 text-reviewer-700 dark:text-reviewer-300 text-xs font-black uppercase tracking-wider whitespace-nowrap border border-reviewer-100 dark:border-reviewer-800 hover:bg-reviewer-100 dark:hover:bg-reviewer-900/40 transition-colors">
                                            View Abstract
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Multi-Criteria Scoring -->
                            <div class="space-y-6 mb-8 bg-slate-50 dark:bg-gray-900/40 p-5 rounded-2xl border border-slate-100 dark:border-gray-700">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Marking Criteria (25% each)</h3>

                                <!-- Section 1: Title -->
                                <div class="bg-slate-50 dark:bg-gray-700/30 p-4 rounded-2xl border border-slate-100 dark:border-gray-600 mb-4">
                                    <h4 class="text-xs font-black text-reviewer-600 dark:text-reviewer-400 uppercase tracking-widest mb-4">1. Abstract Title</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">
                                                        Title of the Abstract <span class="text-xs font-normal text-slate-400">(Maximum 20 Words)</span>
                                                    </label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Is the title within 20 words? Is it clear, specific, and reflective of the study focus, population, and design?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_title" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black shadow-sm border border-slate-100 dark:border-gray-600">{{ old('title_score', $currentReview->title_score ?? 0) }}/4</span>
                                            </div>
                                            <input type="range" name="title_score" min="0" max="4" step="0.5"
                                                   value="{{ old('title_score', $currentReview->title_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('title', this.value)">
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 2: Quality & Structure -->
                                <div class="bg-slate-50 dark:bg-gray-700/30 p-4 rounded-2xl border border-slate-100 dark:border-gray-600 mb-4">
                                    <h4 class="text-xs font-black text-reviewer-600 dark:text-reviewer-400 uppercase tracking-widest mb-4">2. Quality & Structure</h4>
                                    <div class="space-y-6">
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Abstract word count (300)</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Does the abstract comply with the 300-word limit? Is content balanced across sections?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_word_count" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('word_count_score', $currentReview->word_count_score ?? 0) }}/3</span>
                                            </div>
                                            <input type="range" name="word_count_score" min="0" max="3" step="0.5"
                                                   value="{{ old('word_count_score', $currentReview->word_count_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('word_count', this.value)">
                                        </div>
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Well written and concise</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Is the language clear, professional, and free of redundancy or grammatical errors? Are statements precise and evidence-based?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_writing_quality" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('writing_quality_score', $currentReview->writing_quality_score ?? 0) }}/4</span>
                                            </div>
                                            <input type="range" name="writing_quality_score" min="0" max="4" step="0.5"
                                                   value="{{ old('writing_quality_score', $currentReview->writing_quality_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('writing_quality', this.value)">
                                        </div>
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Well structured (*BOMRC format)</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Does the abstract clearly follow Background, Objective, Methods, Results, and Conclusion (BOMRC)? Are sections logically ordered?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_structure" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('structure_score', $currentReview->structure_score ?? 0) }}/4</span>
                                            </div>
                                            <input type="range" name="structure_score" min="0" max="4" step="0.5"
                                                   value="{{ old('structure_score', $currentReview->structure_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('structure', this.value)">
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 3: Background & Rationale -->
                                <div class="bg-slate-50 dark:bg-gray-700/30 p-4 rounded-2xl border border-slate-100 dark:border-gray-600 mb-4">
                                    <h4 class="text-xs font-black text-reviewer-600 dark:text-reviewer-400 uppercase tracking-widest mb-4">3. Background & Rationale</h4>
                                    <div class="space-y-6">
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Provide brief background</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Is the research problem clearly described? Is its public health or clinical importance established?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_background" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('background_score', $currentReview->background_score ?? 0) }}/5</span>
                                            </div>
                                            <input type="range" name="background_score" min="0" max="5" step="0.5"
                                                   value="{{ old('background_score', $currentReview->background_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('background', this.value)">
                                        </div>
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Provide study rationale</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Is the knowledge gap or justification for the study clearly explained?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_rationale" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('rationale_score', $currentReview->rationale_score ?? 0) }}/5</span>
                                            </div>
                                            <input type="range" name="rationale_score" min="0" max="5" step="0.5"
                                                   value="{{ old('rationale_score', $currentReview->rationale_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('rationale', this.value)">
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 4: Objective -->
                                <div class="bg-slate-50 dark:bg-gray-700/30 p-4 rounded-2xl border border-slate-100 dark:border-gray-600 mb-4">
                                    <h4 class="text-xs font-black text-reviewer-600 dark:text-reviewer-400 uppercase tracking-widest mb-4">4. Objective</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Describes the objective of the research</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Is the objective explicitly and clearly stated? Is it specific and aligned with the study design?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_objective" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('objective_score', $currentReview->objective_score ?? 0) }}/10</span>
                                            </div>
                                            <input type="range" name="objective_score" min="0" max="10" step="0.5"
                                                   value="{{ old('objective_score', $currentReview->objective_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('objective', this.value)">
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 5: Methodology -->
                                <div class="bg-slate-50 dark:bg-gray-700/30 p-4 rounded-2xl border border-slate-100 dark:border-gray-600 mb-4">
                                    <h4 class="text-xs font-black text-reviewer-600 dark:text-reviewer-400 uppercase tracking-widest mb-4">5. Methodology</h4>
                                    <div class="space-y-6">
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Study design and data collection</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Is the study design appropriate and clearly described? Are population, sampling, and data collection methods specified?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_methodology_design" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('methodology_design_score', $currentReview->methodology_design_score ?? 0) }}/7.5</span>
                                            </div>
                                            <input type="range" name="methodology_design_score" min="0" max="7.5" step="0.5"
                                                   value="{{ old('methodology_design_score', $currentReview->methodology_design_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('methodology_design', this.value)">
                                        </div>
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Data analysis</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Are analytical methods clearly described? Are statistical tests or qualitative approaches appropriate?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_methodology_analysis" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('methodology_analysis_score', $currentReview->methodology_analysis_score ?? 0) }}/7.5</span>
                                            </div>
                                            <input type="range" name="methodology_analysis_score" min="0" max="7.5" step="0.5"
                                                   value="{{ old('methodology_analysis_score', $currentReview->methodology_analysis_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('methodology_analysis', this.value)">
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 6: Results -->
                                <div class="bg-slate-50 dark:bg-gray-700/30 p-4 rounded-2xl border border-slate-100 dark:border-gray-600 mb-4">
                                    <h4 class="text-xs font-black text-reviewer-600 dark:text-reviewer-400 uppercase tracking-widest mb-4">6. Results</h4>
                                    <div class="space-y-6">
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Results logically follow the described methods</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Do the results align with the methods and stated objective?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_results_logic" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('results_logic_score', $currentReview->results_logic_score ?? 0) }}/10</span>
                                            </div>
                                            <input type="range" name="results_logic_score" min="0" max="10" step="0.5"
                                                   value="{{ old('results_logic_score', $currentReview->results_logic_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('results_logic', this.value)">
                                        </div>
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Provide salient findings of the study</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Are key findings clearly presented with relevant statistics or themes?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_results_findings" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('results_findings_score', $currentReview->results_findings_score ?? 0) }}/10</span>
                                            </div>
                                            <input type="range" name="results_findings_score" min="0" max="10" step="0.5"
                                                   value="{{ old('results_findings_score', $currentReview->results_findings_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('results_findings', this.value)">
                                        </div>
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Adequate data presented to reach a conclusion</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Is sufficient evidence provided to justify the conclusions drawn?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_results_data" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('results_data_score', $currentReview->results_data_score ?? 0) }}/10</span>
                                            </div>
                                            <input type="range" name="results_data_score" min="0" max="10" step="0.5"
                                                   value="{{ old('results_data_score', $currentReview->results_data_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('results_data', this.value)">
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 7: Conclusion -->
                                <div class="bg-slate-50 dark:bg-gray-700/30 p-4 rounded-2xl border border-slate-100 dark:border-gray-600 mb-4">
                                    <h4 class="text-xs font-black text-reviewer-600 dark:text-reviewer-400 uppercase tracking-widest mb-4">7. Conclusion</h4>
                                    <div class="space-y-6">
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Conclusion and interpretation based on the data presented</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Does the conclusion directly reflect the results without overstatement?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_conclusion_interpretation" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('conclusion_interpretation_score', $currentReview->conclusion_interpretation_score ?? 0) }}/7.5</span>
                                            </div>
                                            <input type="range" name="conclusion_interpretation_score" min="0" max="7.5" step="0.5"
                                                   value="{{ old('conclusion_interpretation_score', $currentReview->conclusion_interpretation_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('conclusion_interpretation', this.value)">
                                        </div>
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Study has potential to influence health practice and policy</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-500 text-[10px] font-bold text-white shadow-sm hover:bg-indigo-600 dark:bg-indigo-400 dark:hover:bg-indigo-300">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Does the study demonstrate clear practical, clinical, or policy relevance?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_conclusion_impact" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('conclusion_impact_score', $currentReview->conclusion_impact_score ?? 0) }}/7.5</span>
                                            </div>
                                            <input type="range" name="conclusion_impact_score" min="0" max="7.5" step="0.5"
                                                   value="{{ old('conclusion_impact_score', $currentReview->conclusion_impact_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('conclusion_impact', this.value)">
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 8: Relevance -->
                                <div class="bg-slate-50 dark:bg-gray-700/30 p-4 rounded-2xl border border-slate-100 dark:border-gray-600 mb-4">
                                    <h4 class="text-xs font-black text-reviewer-600 dark:text-reviewer-400 uppercase tracking-widest mb-4">8. Relevance</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Relevance to conference themes</label>
                                                    <div class="relative group">
                                                        <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full border border-slate-300 dark:border-gray-500 text-[10px] font-bold text-slate-500 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-slate-100 dark:hover:bg-gray-700">
                                                            i
                                                        </button>
                                                        <div class="absolute z-50 bottom-full mb-2 -right-4 md:right-0 w-64 sm:w-72 origin-bottom-right rounded-xl bg-slate-900 text-white text-xs font-medium shadow-xl p-3 opacity-0 group-hover:opacity-100 transform scale-95 group-hover:scale-100 transition ease-out duration-150 pointer-events-none">
                                                            <p>Is the abstract aligned with the conference track and themes?</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span id="val_relevance_theme" class="px-2 py-0.5 rounded-lg bg-white dark:bg-gray-800 text-reviewer-700 dark:text-reviewer-300 text-xs font-black border border-slate-100 dark:border-gray-600">{{ old('relevance_theme_score', $currentReview->relevance_theme_score ?? 0) }}/5</span>
                                            </div>
                                            <input type="range" name="relevance_theme_score" min="0" max="5" step="0.5"
                                                   value="{{ old('relevance_theme_score', $currentReview->relevance_theme_score ?? 0) }}"
                                                   class="w-full h-2 bg-slate-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-reviewer-600 criteria-slider"
                                                   oninput="updateCriteria('relevance_theme', this.value)">
                                        </div>
                                    </div>
                                </div>

                                <!-- Final Calculated Score -->
                                <div class="pt-6 border-t-2 border-slate-200 dark:border-gray-700 mt-6 sticky bottom-0 bg-white dark:bg-gray-800 py-4 shadow-[-5px_0_15px_rgba(0,0,0,0.05)] rounded-b-2xl">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-1">Cumulative Grade</span>
                                            <span class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Total Score (%)</span>
                                        </div>
                                        <div class="flex items-baseline gap-2">
                                            <span id="live_total_score" class="text-5xl font-black text-reviewer-600 dark:text-reviewer-400 tabular-nums">{{ $currentReview->score ?? 0 }}</span>
                                            <span class="text-xl text-slate-400 font-bold">/ 100</span>
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-2 italic font-medium">Weighted according to official {{ config('conference.short_name') }} scientific evaluation standards</p>
                                </div>
                            </div>

                            <!-- Recommendation -->
                            <div class="mb-5">
                                <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Recommendation</label>
                                <select name="recommendation" required
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-700 border-2 border-slate-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-reviewer-500 focus:border-reviewer-500 dark:text-white transition-all font-bold">
                                    <option value="">Select...</option>
                                    <option value="accept" {{ old('recommendation', $currentReview->recommendation ?? '') === 'accept' ? 'selected' : '' }}>✅ Accept</option>
                                    <option value="accept_with_revisions" {{ old('recommendation', $currentReview->recommendation ?? '') === 'accept_with_revisions' ? 'selected' : '' }}>📝 Accepted with Revisions</option>
                                    <option value="reject" {{ old('recommendation', $currentReview->recommendation ?? '') === 'reject' ? 'selected' : '' }}>❌ Reject</option>
                                </select>
                            </div>

                            <div class="mb-5">
                                <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Subtheme Fit</label>
                                <select name="subtheme_relevance" required
                                        id="subtheme_relevance"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-700 border-2 border-slate-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-reviewer-500 focus:border-reviewer-500 dark:text-white transition-all font-bold">
                                    <option value="">Select...</option>
                                    <option value="relevant" {{ old('subtheme_relevance', $currentReview->subtheme_relevance ?? '') === 'relevant' ? 'selected' : '' }}>Relevant to current subtheme</option>
                                    <option value="suggest_change" {{ old('subtheme_relevance', $currentReview->subtheme_relevance ?? '') === 'suggest_change' ? 'selected' : '' }}>Recommend subtheme change</option>
                                </select>
                            </div>

                            <div class="mb-6 {{ $currentReview && $currentReview->subtheme_relevance === 'suggest_change' ? '' : 'hidden' }}" id="suggested_subtheme_wrapper">
                                <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">
                                    Suggested Subtheme
                                    <span class="text-reviewer-500 font-normal text-xs">(Required if recommending change)</span>
                                </label>
                                <select name="suggested_subtheme"
                                        id="suggested_subtheme"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-700 border-2 border-slate-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-reviewer-500 focus:border-reviewer-500 dark:text-white transition-all font-bold">
                                    <option value="">Select new subtheme...</option>
                                    @foreach($availableSubthemes as $subthemeOption)
                                        <option value="{{ $subthemeOption }}" {{ old('suggested_subtheme', $currentReview->suggested_subtheme ?? '') === $subthemeOption ? 'selected' : '' }}>
                                            {{ $subthemeOption }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Comments -->
                            <div class="mb-6">
                                <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">
                                    Comments
                                    <span class="text-reviewer-500 font-normal text-xs">(Required for revisions & rejection)</span>
                                </label>
                                <textarea name="comments" rows="5"
                                      placeholder="Provide specific, constructive feedback..."
                                      class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-700 border-2 border-slate-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-reviewer-500 focus:border-reviewer-500 dark:text-white resize-y transition-all text-sm">{{ old('comments', $currentReview->comments ?? '') }}</textarea>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Save Draft Button -->
                                <button type="submit"
                                        formaction="{{ route('reviewer.save-draft', $abstract) }}"
                                        onclick="window.isDraftSave = true;"
                                        class="py-4 bg-white dark:bg-gray-700 border-2 border-slate-200 dark:border-gray-600 text-slate-700 dark:text-white font-black rounded-xl hover:bg-slate-50 dark:hover:bg-gray-600 transition-all shadow-lg text-sm uppercase tracking-wider flex items-center justify-center gap-2 group">
                                    <svg class="w-5 h-4 text-slate-400 group-hover:text-reviewer-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                    </svg>
                                    Save Draft
                                </button>

                                <!-- Submit Button -->
                                <button type="submit"
                                        onclick="window.isDraftSave = false;"
                                        class="py-4 bg-gradient-to-r from-reviewer-600 to-purple-700 hover:from-reviewer-700 hover:to-purple-800 text-white font-black rounded-xl transition-all shadow-xl shadow-reviewer-200 dark:shadow-none text-sm uppercase tracking-wider flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $currentRevisionRound > 1 ? 'Submit Re-Review' : 'Submit Review' }}
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <!-- Review Completed State -->
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-[2rem] shadow-xl p-8 text-center relative overflow-hidden sticky top-6">
                        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>

                        <div class="relative z-10">
                            <div class="w-16 h-16 mx-auto mb-4 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/30">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-black text-white mb-2">Review Completed</h3>
                            <p class="text-emerald-100 text-sm font-medium mb-6">Your feedback has been submitted</p>

                            @if($currentReview)
                                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 space-y-4">
                                    <div class="flex justify-between items-center border-b border-white/10 pb-2">
                                        <span class="text-emerald-200 text-sm font-medium">Final Score</span>
                                        <span class="text-2xl font-black text-white">{{ $currentReview->score }}%</span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-[10px] uppercase font-bold tracking-wider">
                                        <div class="p-2 bg-black/20 rounded-lg">
                                            <span class="text-emerald-300 block mb-1 opacity-60">1. Title</span>
                                            <span class="text-white text-xs">{{ $currentReview->title_score }}/4</span>
                                        </div>
                                        <div class="p-2 bg-black/20 rounded-lg">
                                            <span class="text-emerald-300 block mb-1 opacity-60">2. Quality</span>
                                            <span class="text-white text-xs">{{ $currentReview->word_count_score + $currentReview->writing_quality_score + $currentReview->structure_score }}/11</span>
                                        </div>
                                        <div class="p-2 bg-black/20 rounded-lg">
                                            <span class="text-emerald-300 block mb-1 opacity-60">3. Background</span>
                                            <span class="text-white text-xs">{{ $currentReview->background_score + $currentReview->rationale_score }}/10</span>
                                        </div>
                                        <div class="p-2 bg-black/20 rounded-lg">
                                            <span class="text-emerald-300 block mb-1 opacity-60">4. Objective</span>
                                            <span class="text-white text-xs">{{ $currentReview->objective_score }}/10</span>
                                        </div>
                                        <div class="p-2 bg-black/20 rounded-lg">
                                            <span class="text-emerald-300 block mb-1 opacity-60">5. Methodology</span>
                                            <span class="text-white text-xs">{{ $currentReview->methodology_design_score + $currentReview->methodology_analysis_score }}/15</span>
                                        </div>
                                        <div class="p-2 bg-black/20 rounded-lg">
                                            <span class="text-emerald-300 block mb-1 opacity-60">6. Results</span>
                                            <span class="text-white text-xs">{{ $currentReview->results_logic_score + $currentReview->results_findings_score + $currentReview->results_data_score }}/30</span>
                                        </div>
                                        <div class="p-2 bg-black/20 rounded-lg">
                                            <span class="text-emerald-300 block mb-1 opacity-60">7. Conclusion</span>
                                            <span class="text-white text-xs">{{ $currentReview->conclusion_interpretation_score + $currentReview->conclusion_impact_score }}/15</span>
                                        </div>
                                        <div class="p-2 bg-black/20 rounded-lg">
                                            <span class="text-emerald-300 block mb-1 opacity-60">8. Relevance</span>
                                            <span class="text-white text-xs">{{ $currentReview->relevance_theme_score }}/5</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center pt-2 border-t border-white/10">
                                        <span class="text-emerald-200 text-sm font-medium">Decision</span>
                                        @php
                                            $recColors = [
                                                'accept' => 'bg-white text-emerald-600',
                                                'accept_with_revisions' => 'bg-amber-400 text-amber-900',
                                                'reject' => 'bg-red-400 text-red-900',
                                            ];
                                        @endphp
                                        <span class="px-3 py-1 rounded-lg text-xs font-black uppercase {{ $recColors[$currentReview->recommendation] ?? 'bg-gray-400 text-gray-900' }}">
                                            {{ str_replace('_', ' ', $currentReview->recommendation) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-emerald-200 text-sm font-medium">Submitted</span>
                                        <span class="text-white font-bold text-sm">{{ $currentReview->submitted_at->format('M d, Y') }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Floating Score Bar (always visible while scoring) -->
@if(!$isCompleted)
<div id="floatingScoreBar" class="fixed bottom-0 left-0 right-0 z-40 transform transition-transform duration-300">
    <div class="max-w-3xl mx-auto px-4 pb-4">
        <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200 dark:border-gray-700 px-6 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-4 flex-1">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Score</span>
                    <div class="flex items-baseline gap-1">
                        <span id="floating_total_score" class="text-3xl font-black text-reviewer-600 dark:text-reviewer-400 tabular-nums">{{ $currentReview->score ?? 0 }}</span>
                        <span class="text-sm text-slate-400 font-bold">/100</span>
                    </div>
                </div>
                <!-- Progress bar -->
                <div class="flex-1 hidden sm:block">
                    <div class="w-full bg-slate-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div id="floating_progress_bar" class="h-full rounded-full bg-gradient-to-r from-reviewer-500 to-purple-600 transition-all duration-500 ease-out" style="width: {{ ($currentReview->score ?? 0) }}%"></div>
                    </div>
                </div>
            </div>
            <!-- Score badge -->
            <div id="floating_score_badge" class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-slate-400">
                Not scored
            </div>
        </div>
    </div>
</div>
@endif

<!-- Validation Modal -->
<div id="validationModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full transform transition-all p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-center w-16 h-16 mx-auto bg-rose-100 dark:bg-rose-900/30 rounded-2xl mb-6">
            <svg class="w-8 h-8 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.732-1.333-2.464 0L4.35 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        <div class="text-center mb-8">
            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">Validation Error</h3>
            <p id="modalMessage" class="text-sm text-gray-600 dark:text-gray-400"></p>
        </div>

        <button type="button" onclick="closeModal('validationModal')"
                class="w-full bg-gray-900 hover:bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-3.5 px-4 rounded-xl transition-colors">
            Close
        </button>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full transform transition-all p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-center w-16 h-16 mx-auto bg-amber-100 dark:bg-amber-900/30 rounded-2xl mb-6">
            <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <div class="text-center mb-8">
            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">Are you sure?</h3>
            <p id="confirmMessage" class="text-sm text-gray-600 dark:text-gray-400"></p>
        </div>

        <div class="flex gap-3">
            <button type="button" onclick="closeModal('confirmationModal')"
                    class="flex-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-bold py-3 rounded-xl transition-colors">
                Cancel
            </button>
            <button type="button" id="confirmSubmitBtn"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-indigo-500/30">
                Yes, Submit
            </button>
        </div>
    </div>
</div>

<script>
let touchedCriteria = new Set();
// If we have initial values from currentReview, mark them as touched
@if($currentReview)
    @php
        $criteriaFields = [
            'title_score' => 'title',
            'word_count_score' => 'word_count',
            'writing_quality_score' => 'writing_quality',
            'structure_score' => 'structure',
            'background_score' => 'background',
            'rationale_score' => 'rationale',
            'objective_score' => 'objective',
            'methodology_design_score' => 'methodology_design',
            'methodology_analysis_score' => 'methodology_analysis',
            'results_logic_score' => 'results_logic',
            'results_findings_score' => 'results_findings',
            'results_data_score' => 'results_data',
            'conclusion_interpretation_score' => 'conclusion_interpretation',
            'conclusion_impact_score' => 'conclusion_impact',
            'relevance_theme_score' => 'relevance_theme'
        ];
    @endphp
    @foreach($criteriaFields as $field => $key)
        @if(isset($currentReview->$field)) touchedCriteria.add('{{ $key }}'); @endif
    @endforeach
@endif

// Also mark fields from validation errors as touched
@if(old())
    @php
        $criteriaFieldsList = [
            'title_score' => 'title',
            'word_count_score' => 'word_count',
            'writing_quality_score' => 'writing_quality',
            'structure_score' => 'structure',
            'background_score' => 'background',
            'rationale_score' => 'rationale',
            'objective_score' => 'objective',
            'methodology_design_score' => 'methodology_design',
            'methodology_analysis_score' => 'methodology_analysis',
            'results_logic_score' => 'results_logic',
            'results_findings_score' => 'results_findings',
            'results_data_score' => 'results_data',
            'conclusion_interpretation_score' => 'conclusion_interpretation',
            'conclusion_impact_score' => 'conclusion_impact',
            'relevance_theme_score' => 'relevance_theme'
        ];
    @endphp
    @foreach(old() as $key => $val)
        @if(array_key_exists($key, $criteriaFieldsList))
            touchedCriteria.add('{{ $criteriaFieldsList[$key] }}');
        @endif
    @endforeach
@endif

const criteriaMaxScores = {
    'title': 4,
    'word_count': 3,
    'writing_quality': 4,
    'structure': 4,
    'background': 5,
    'rationale': 5,
    'objective': 10,
    'methodology_design': 7.5,
    'methodology_analysis': 7.5,
    'results_logic': 10,
    'results_findings': 10,
    'results_data': 10,
    'conclusion_interpretation': 7.5,
    'conclusion_impact': 7.5,
    'relevance_theme': 5
};

function updateCriteria(type, value) {
    const max = criteriaMaxScores[type];
    document.getElementById('val_' + type).textContent = value + '/' + max;
    touchedCriteria.add(type);
    calculateTotal();
}

function calculateTotal() {
    let total = 0;

    // Sum up all criteria by selecting inputs by name
    const inputs = document.querySelectorAll('input.criteria-slider');
    inputs.forEach(input => {
        total += parseFloat(input.value) || 0;
    });

    const formattedTotal = total.toFixed(total % 1 === 0 ? 0 : 1);

    const totalDisplay = document.getElementById('live_total_score');
    totalDisplay.textContent = formattedTotal;

    // Visual feedback for score
    if (total >= 80) {
        totalDisplay.className = 'text-5xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums';
    } else if (total >= 60) {
        totalDisplay.className = 'text-5xl font-black text-amber-600 dark:text-amber-400 tabular-nums';
    } else if (total >= 40) {
        totalDisplay.className = 'text-5xl font-black text-orange-600 dark:text-orange-400 tabular-nums';
    } else {
        totalDisplay.className = 'text-5xl font-black text-rose-600 dark:text-rose-400 tabular-nums';
    }

    // Update floating score bar
    const floatingScore = document.getElementById('floating_total_score');
    const floatingBar = document.getElementById('floating_progress_bar');
    const floatingBadge = document.getElementById('floating_score_badge');

    if (floatingScore) {
        floatingScore.textContent = formattedTotal;

        // Color the floating score
        if (total >= 80) {
            floatingScore.className = 'text-3xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums';
        } else if (total >= 60) {
            floatingScore.className = 'text-3xl font-black text-amber-600 dark:text-amber-400 tabular-nums';
        } else if (total >= 40) {
            floatingScore.className = 'text-3xl font-black text-orange-600 dark:text-orange-400 tabular-nums';
        } else {
            floatingScore.className = 'text-3xl font-black text-rose-600 dark:text-rose-400 tabular-nums';
        }
    }

    if (floatingBar) {
        floatingBar.style.width = Math.min(total, 100) + '%';

        // Color the progress bar
        if (total >= 80) {
            floatingBar.className = 'h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500 ease-out';
        } else if (total >= 60) {
            floatingBar.className = 'h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-500 transition-all duration-500 ease-out';
        } else if (total >= 40) {
            floatingBar.className = 'h-full rounded-full bg-gradient-to-r from-orange-500 to-rose-500 transition-all duration-500 ease-out';
        } else {
            floatingBar.className = 'h-full rounded-full bg-gradient-to-r from-rose-500 to-red-600 transition-all duration-500 ease-out';
        }
    }

    if (floatingBadge) {
        if (total >= 80) {
            floatingBadge.textContent = 'Excellent';
            floatingBadge.className = 'px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400';
        } else if (total >= 60) {
            floatingBadge.textContent = 'Good';
            floatingBadge.className = 'px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400';
        } else if (total >= 40) {
            floatingBadge.textContent = 'Fair';
            floatingBadge.className = 'px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400';
        } else if (total > 0) {
            floatingBadge.textContent = 'Low';
            floatingBadge.className = 'px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400';
        } else {
            floatingBadge.textContent = 'Not scored';
            floatingBadge.className = 'px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-slate-400';
        }
    }
}

function showModal(message) {
    document.getElementById('modalMessage').textContent = message;
    document.getElementById('validationModal').classList.remove('hidden');
}

function showConfirmModal(message, callback) {
    document.getElementById('confirmMessage').textContent = message;
    const confirmBtn = document.getElementById('confirmSubmitBtn');

    // Remote old event listeners
    const newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener('click', function() {
        closeModal('confirmationModal');
        callback();
    });

    document.getElementById('confirmationModal').classList.remove('hidden');
}

function closeModal(id) {
    if (id) {
        document.getElementById(id).classList.add('hidden');
    } else {
        document.getElementById('validationModal').classList.add('hidden');
        document.getElementById('confirmationModal').classList.add('hidden');
    }
}

document.getElementById('validationModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
    // If saving as draft, skip mandatory validation
    if (window.isDraftSave) return;

    const recommendation = document.querySelector('select[name="recommendation"]').value;
    const comments = document.querySelector('textarea[name="comments"]').value;
    const subthemeRelevance = document.querySelector('select[name="subtheme_relevance"]').value;
    const suggestedSubtheme = document.querySelector('select[name="suggested_subtheme"]').value;

    let total = 0;
    document.querySelectorAll('input.criteria-slider').forEach(input => {
        total += parseFloat(input.value) || 0;
    });

    if (!recommendation) {
        e.preventDefault();
        showModal('Please select a recommendation before submitting your review');
        return;
    }

    if (!subthemeRelevance) {
        e.preventDefault();
        showModal('Please indicate whether the abstract fits the current subtheme.');
        return;
    }

    if (subthemeRelevance === 'suggest_change' && !suggestedSubtheme) {
        e.preventDefault();
        showModal('Please select the subtheme you recommend for this abstract.');
        return;
    }

    if (['accept', 'accept_with_revisions'].includes(recommendation)) {
        if (total === 0) {
           e.preventDefault();
           showModal('You cannot recommend acceptance with a score of 0. Please evaluate the abstract using the sliders.');
           return;
        }

        let untouched = [];
        Object.keys(criteriaMaxScores).forEach(key => {
            if (!touchedCriteria.has(key)) {
                untouched.push(key.replace(/_/g, ' '));
            }
        });

        if (untouched.length > 0) {
            e.preventDefault();
            showConfirmModal(`You have not adjusted ${untouched.length} criteria. Are you sure you want to submit with default (0) values for these?`, function() {
                document.getElementById('reviewForm').submit();
            });
            return;
        }
    }

    if (['accept_with_revisions', 'reject'].includes(recommendation) && !comments.trim()) {
        e.preventDefault();
        showModal('Comments are required when recommending revisions or rejection. Please provide detailed feedback to help the author.');
        return;
    }
});

// Initialize floating score bar on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();

    const subthemeRelevance = document.getElementById('subtheme_relevance');
    const suggestedWrapper = document.getElementById('suggested_subtheme_wrapper');
    const suggestedSelect = document.getElementById('suggested_subtheme');

    const syncSuggestedSubtheme = function() {
        const shouldShow = subthemeRelevance && subthemeRelevance.value === 'suggest_change';
        if (suggestedWrapper) {
            suggestedWrapper.classList.toggle('hidden', !shouldShow);
        }
        if (!shouldShow && suggestedSelect) {
            suggestedSelect.value = '';
        }
    };

    if (subthemeRelevance) {
        subthemeRelevance.addEventListener('change', syncSuggestedSubtheme);
        syncSuggestedSubtheme();
    }
});
</script>

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
                        This will remove the abstract from your queue immediately and stop it from being assigned back to you later.
                    </p>
                </div>
                <button type="button" class="rounded-full p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-gray-700" onclick="closeDeclineAssignmentModal()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <form id="declineAssignmentForm" method="POST" action="{{ route('reviewer.decline-assignment', $abstract) }}" class="px-6 py-5 space-y-5">
            @csrf
            <div class="rounded-2xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-700 p-4">
                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Abstract</p>
                <p class="mt-2 text-sm font-semibold text-slate-800 dark:text-gray-100">{{ $reviewData->title }}</p>
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

<script>
function openDeclineAssignmentModal() {
    const modal = document.getElementById('declineAssignmentModal');
    const reason = document.getElementById('declineAssignmentReason');
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
@endsection
