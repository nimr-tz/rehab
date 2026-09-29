@extends('layouts.app')

@section('title', 'View Abstract | ' . $abstract->title)

@section('content')
@php
    $coauthors = $abstract->coauthors;
    if (is_string($coauthors)) {
        $coauthors = json_decode($coauthors, true) ?? [];
    }
    $coauthors = is_array($coauthors) ? $coauthors : [];
    $hasCoauthors = count($coauthors) > 0;
@endphp
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 font-sans pb-20">
    <!-- Sophisticated Professional Header -->
    <div class="relative bg-gradient-to-br from-indigo-700 via-indigo-800 to-blue-900 py-12 md:py-16 lg:py-20 rounded-b-[2.5rem] md:rounded-b-[4rem] lg:rounded-b-[6rem] shadow-2xl overflow-hidden md:mb-[-2rem] lg:mb-[-4rem]">
        <!-- Decorative Elements -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.05]"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-400/10 rounded-full blur-3xl"></div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 relative z-10">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-x-8 gap-y-10">
                <div class="space-y-6 max-w-4xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 animate-fade-in">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-[10px] font-black text-white uppercase tracking-[0.2em]">
                            {{ $abstract->conference_code ?: 'Abstract Submission #' . str_pad($abstract->id, 4, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>

                    <h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-white leading-[1.1] tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        {{ $abstract->title }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-y-4 gap-x-8">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center border border-white/20">
                                <svg class="w-5 h-5 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-white/50 uppercase tracking-widest leading-none mb-1">Lead Author</p>
                                <p class="text-sm font-bold text-white">{{ $abstract->author_name }}</p>
                            </div>
                        </div>

                        @if($abstract->subtheme)
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center border border-white/20">
                                <svg class="w-5 h-5 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-white/50 uppercase tracking-widest leading-none mb-1">Research Track</p>
                                <p class="text-sm font-bold text-white">{{ $abstract->subtheme }}</p>
                            </div>
                        </div>
                        @endif

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center border border-white/20">
                                <svg class="w-5 h-5 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-white/50 uppercase tracking-widest leading-none mb-1">Current Status</p>
                                <div class="flex items-center">
                                    {!! $abstract->getPresentationStatusBadge() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-4 flex-shrink-0 lg:mb-2">
                    <a href="{{ route('abstracts.my') }}" class="group px-6 py-3 bg-white/10 backdrop-blur-xl border border-white/20 text-white font-bold rounded-2xl hover:bg-white/20 transition-all flex items-center gap-2 text-xs uppercase tracking-widest">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back
                    </a>

                    @if($abstract->status === 'draft')
                        <form action="{{ route('abstracts.submit', $abstract) }}" method="POST" id="submit-form-{{ $abstract->id }}" class="hidden">
                            @csrf
                        </form>
                        <button type="button"
                                class="js-open-submit-modal px-8 py-3 bg-emerald-500 hover:bg-emerald-400 text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-emerald-900/40 text-xs uppercase tracking-widest flex items-center gap-2"
                                data-form-id="submit-form-{{ $abstract->id }}">
                            <svg class="w-4 h-4 font-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            Submit Final
                        </button>
                        <a href="{{ route('abstracts.edit', $abstract) }}" class="px-8 py-3 bg-blue-500 hover:bg-blue-400 text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-blue-900/40 text-xs uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit Draft
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 mt-10 md:mt-12 lg:mt-16 relative z-20">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">

            <!-- Left Column: Content (8 cols) -->
            <div class="lg:col-span-8 space-y-8">
                <!-- Abstract Description Card -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-gray-700 overflow-hidden transform transition-all hover:shadow-2xl">
                    <div class="px-8 py-6 bg-slate-50/80 dark:bg-gray-700/50 border-b border-slate-100 dark:border-gray-600 flex justify-between items-center">
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-3">
                            <span class="w-1.5 h-6 bg-indigo-600 rounded-full"></span>
                            Abstract Content
                        </h2>
                        <div class="flex items-center gap-3">
                             <button onclick="copyToClipboard('abstract-text')" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-100 dark:border-gray-600" title="Copy Content">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m-3 8h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                             </button>
                        </div>
                    </div>
                    <div class="p-10">
                        <div id="abstract-text" class="prose prose-lg max-w-none dark:prose-invert text-slate-700 dark:text-slate-300 leading-relaxed" style="font-family: 'Inter', sans-serif;">{!! $abstract->description !!}</div>
                    </div>
                </div>

                <!-- Review Timeline Section -->
                @php
                    $submittedReviews = $abstract->reviews->where('status', 'submitted');
                    $hasRevisionMarkers = ($abstract->revision_round ?? 0) > 0
                        || !is_null($abstract->revision_requested_at)
                        || !is_null($abstract->revision_submitted_at)
                        || !empty(trim((string) $abstract->revision_feedback));
                    $canSeeReviews = $abstract->hasAuthorVisibleFeedback()
                        || $hasRevisionMarkers
                        || in_array($abstract->status, [
                            'accepted', 'rejected', 'revision_required', 'revision_requested',
                            'revision_submitted', 'revision_under_review', 'revision_review',
                            'under_review', 'ready_for_decision'
                        ], true);
                    $reviewsByRound = $submittedReviews->groupBy('review_round');
                    $currentRevisionRound = $abstract->revision_round ?? 0;
                @endphp

                @if($submittedReviews->count() > 0 && $canSeeReviews)
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-8 py-6 bg-indigo-600 dark:bg-indigo-700 flex justify-between items-center">
                        <h2 class="text-xl font-black text-white uppercase tracking-tight flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            {{ $abstract->status === 'accepted' ? 'Presentation Improvement Notes' : 'Peer Review History' }}
                        </h2>
                        <span class="px-4 py-1.5 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold text-white uppercase tracking-widest border border-white/20">
                            {{ $submittedReviews->count() }} Reviews Total
                        </span>
                    </div>

                    <div class="p-10 space-y-12 relative">
                        <!-- Vertical Connector Line -->
                        <div class="absolute left-[3.25rem] top-12 bottom-12 w-1 bg-slate-100 dark:bg-gray-700 rounded-full"></div>

                        @foreach($reviewsByRound as $round => $reviews)
                            <div class="relative pl-20 animate-fade-in-up">
                                <!-- Round Marker -->
                                <div class="absolute left-0 top-0 w-12 h-12 bg-white dark:bg-gray-800 border-4 border-indigo-600 dark:border-indigo-500 rounded-2xl shadow-lg flex items-center justify-center z-10">
                                    <span class="text-indigo-600 dark:text-indigo-400 font-black text-xl">{{ $loop->iteration }}</span>
                                </div>

                                <div class="mb-4">
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                        Review Round #{{ $round }}
                                    </h3>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                                        {{ $reviews->first()->submitted_at ? $reviews->first()->submitted_at->format('M d, Y') : 'Original Submission' }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @foreach($reviews as $review)
                                    <div class="bg-slate-50 dark:bg-gray-700/30 rounded-3xl p-6 border border-slate-100 dark:border-gray-600 transition-all hover:bg-white hover:shadow-xl group">
                                        <div class="flex items-start mb-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                                    {{ $review->reviewer_number }}
                                                </div>
                                                <p class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-tight">Reviewer</p>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed italic">
                                                "{{ $review->comments }}"
                                            </div>

                                            <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-gray-600">
                                               <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Recommendation:</span>
                                               <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">
                                                   {{ str_replace('_', ' ', $review->recommendation) }}
                                               </span>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if($abstract->admin_comment)
                            <div class="relative pl-20 animate-fade-in-up">
                                <div class="absolute left-0 top-0 w-12 h-12 bg-emerald-500 rounded-2xl shadow-lg flex items-center justify-center z-10 text-white">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="bg-emerald-50 dark:bg-emerald-900/10 rounded-3xl p-8 border-2 border-emerald-100 dark:border-emerald-800/50">
                                    <h3 class="text-lg font-black text-emerald-900 dark:text-emerald-400 uppercase tracking-tight mb-2">Final Editorial Decision</h3>
                                    <p class="text-sm text-emerald-800 dark:text-emerald-300 leading-relaxed font-medium">
                                        {{ $abstract->admin_comment }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column: Info Cards (4 cols) -->
            <div class="lg:col-span-4 space-y-8">

                <!-- Presentation Details -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-gray-700 overflow-hidden transform transition-all hover:-translate-y-1">
                    <div class="p-8">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Submission Facts</h3>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl bg-slate-50 dark:bg-gray-700 flex items-center justify-center text-slate-600 dark:text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-600 dark:text-slate-400">Format</p>
                                </div>
                                <span class="bg-slate-100 dark:bg-gray-700 px-3 py-1 rounded-xl text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                    {{ $abstract->presentation_mode }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl bg-slate-50 dark:bg-gray-700 flex items-center justify-center text-slate-600 dark:text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-600 dark:text-slate-400">Proceedings</p>
                                </div>
                                @if($abstract->include_in_proceedings)
                                    <span class="text-emerald-500 font-black text-[10px] uppercase tracking-widest flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        Included
                                    </span>
                                @else
                                    <span class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Excluded</span>
                                @endif
                            </div>

                             <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl bg-slate-50 dark:bg-gray-700 flex items-center justify-center text-slate-600 dark:text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-600 dark:text-slate-400">Last Updated</p>
                                </div>
                                <span class="text-slate-900 dark:text-white font-black text-xs">
                                    {{ $abstract->updated_at->format('M d, Y') }}
                                </span>
                            </div>

                            <div class="pt-4 border-t border-slate-50 dark:border-gray-700">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Keywords</p>
                                <div class="flex flex-wrap gap-2">
                                    @if($abstract->keywords)
                                        @foreach(explode(',', $abstract->keywords) as $keyword)
                                            <span class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-[10px] font-black text-blue-600 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                                {{ trim($keyword) }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">None provided</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Author Details Card -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-gray-700 overflow-hidden transform transition-all hover:-translate-y-1">
                    <div class="p-8">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Investigator Group</h3>

                        <!-- Primary Author -->
                        <div class="flex items-start gap-4 mb-8">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                                <span class="text-lg font-black">{{ substr($abstract->author_name, 0, 1) }}</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-slate-900 dark:text-white leading-tight uppercase tracking-tight">{{ $abstract->author_name }}</h4>
                                <p class="text-xs font-bold text-slate-500 mt-1">{{ $abstract->author_institute }}</p>
                                <span class="inline-block mt-2 px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/30 text-[9px] font-black text-indigo-600 dark:text-indigo-400 rounded-lg uppercase tracking-widest">Lead Investigator</span>
                            </div>
                        </div>

                        <!-- Co-authors -->
                        @if($hasCoauthors)
                        <div class="space-y-4 pt-6 border-t border-slate-100 dark:border-gray-700">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Co-Contributors</p>
                            @foreach($coauthors as $coauthor)
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-slate-50 dark:bg-gray-700 flex items-center justify-center border border-slate-100 dark:border-gray-600 text-slate-400 font-bold text-xs uppercase">
                                    {{ substr($coauthor['name'] ?? '?', 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-xs font-black text-slate-800 dark:text-white tracking-tight leading-none">{{ $coauthor['name'] }}</p>
                                    @if(isset($coauthor['institute']))
                                    <p class="text-[9px] font-bold text-slate-500 mt-0.5 truncate max-w-[180px]">{{ $coauthor['institute'] }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-4 bg-slate-50 dark:bg-gray-700/50 rounded-2xl border border-dashed border-slate-200 dark:border-gray-600">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">No Co-authors Listed</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Presentation Media Card (if accepted) -->
                @if($abstract->status === 'accepted')
                    @php
                        $hasPresentation = $abstract->hasActualPresentationFiles();
                        $presentationMode = strtolower($abstract->presentation_mode ?? '');
                    @endphp
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-[2.5rem] shadow-xl text-white overflow-hidden p-8 relative">
                        <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>

                        <h3 class="text-xs font-black text-blue-100/60 uppercase tracking-[0.2em] mb-6">Presentation Assets</h3>

                        @if($hasPresentation)
                            <div class="bg-white/10 backdrop-blur-md rounded-[2rem] p-6 border border-white/20 mb-6">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-400 flex items-center justify-center text-slate-900 shadow-lg shadow-emerald-900/40">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-widest">File Verified</p>
                                        <p class="text-[10px] text-blue-100 font-medium">Synced: {{ $abstract->presentation_uploaded_at?->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="text-[11px] font-medium text-white/80 line-clamp-1 italic">
                                    @if($presentationMode === 'oral' && $abstract->oral_presentation_file) {{ $abstract->oral_presentation_file }}
                                    @elseif($presentationMode === 'poster' && $abstract->poster_presentation_file) {{ $abstract->poster_presentation_file }}
                                    @elseif($presentationMode === 'audio_poster')
                                        {{ $abstract->audio_poster_poster_file ?: ($abstract->audio_poster_file ?: 'Archive.zip') }}
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="py-6 px-4 bg-white/5 border border-dashed border-white/20 rounded-[2rem] mb-6 text-center">
                                <p class="text-sm font-bold text-blue-100 mb-1">Awaiting Upload</p>
                                <p class="text-[10px] text-blue-200/60 font-medium">Deadline approaching soon</p>
                            </div>
                        @endif

                        <a href="{{ route('presentations.show', $abstract) }}"
                           class="flex items-center justify-center w-full py-4 bg-white text-indigo-700 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-lg shadow-blue-900/40 hover:-translate-y-1 transition-all active:scale-95 group">
                            {{ $hasPresentation ? 'Manage Assets' : 'Upload Now' }}
                            <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Submit Confirmation Modal -->
<div id="submit-confirm-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeSubmitModal()"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-gray-700 w-full max-w-md p-10 transform transition-all scale-100">
        <div class="flex items-center justify-center w-20 h-20 mx-auto mb-8 rounded-3xl bg-emerald-50 dark:bg-emerald-900/30 shadow-lg shadow-emerald-100 dark:shadow-none">
            <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>

        <h3 class="text-2xl font-black text-slate-900 dark:text-white text-center mb-4 uppercase tracking-tight">Finalize Submission?</h3>
        <p class="text-slate-500 dark:text-slate-400 text-center mb-10 leading-relaxed font-medium">
            Once submitted, your research will be locked and sent for peer review. No further edits can be made until the review phase is complete.
        </p>

        <div class="flex gap-4">
            <button type="button" onclick="closeSubmitModal()" class="flex-1 px-6 py-4 bg-slate-100 dark:bg-gray-700 hover:bg-slate-200 text-slate-700 dark:text-slate-200 rounded-2xl font-black text-xs uppercase tracking-widest transition-all">
                Maybe Later
            </button>
            <button type="button" id="submit-confirm-btn" class="flex-1 px-6 py-4 bg-emerald-500 hover:bg-emerald-400 text-slate-900 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-200 dark:shadow-none transition-all active:scale-95">
                Submit Now
            </button>
        </div>
    </div>
</div>

<script>
    function copyToClipboard(elementId) {
        const text = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(text).then(() => {
            // Toast notification
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-8 left-1/2 -translate-x-1/2 px-6 py-3 bg-slate-900 text-white text-xs font-black uppercase tracking-widest rounded-2xl shadow-2xl z-[200] animate-bounce';
            toast.textContent = '✨ Abstract Copied to Clipboard';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        });
    }

    (function() {
        const modal = document.getElementById('submit-confirm-modal');
        const confirmBtn = document.getElementById('submit-confirm-btn');
        let pendingFormId = null;

        window.openSubmitModal = function(formId) {
            pendingFormId = formId;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            modal.querySelector('.transform').classList.add('animate-fade-in-up');
        };

        window.closeSubmitModal = function() {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
            pendingFormId = null;
        };

        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('.js-open-submit-modal');
            if (trigger) {
                e.preventDefault();
                const formId = trigger.getAttribute('data-form-id');
                if (formId) openSubmitModal(formId);
            }
        });

        confirmBtn && confirmBtn.addEventListener('click', function() {
            if (pendingFormId) {
                const form = document.getElementById(pendingFormId);
                if (form) form.submit();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSubmitModal();
        });
    })();
</script>

<style>
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fade-in-up 0.4s ease-out forwards;
    }
    .animate-fade-in {
        animation: fade-in 1s ease-out forwards;
    }
    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>
@endsection
