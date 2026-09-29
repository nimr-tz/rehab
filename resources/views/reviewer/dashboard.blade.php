@extends('layouts.app')

@section('title', 'Reviewer Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @php
            $completionPercentage = $stats['total_assigned'] > 0
                ? round(($stats['completed'] / $stats['total_assigned']) * 100)
                : 0;
            $greeting = now()->hour < 12 ? 'Good Morning' : (now()->hour < 18 ? 'Good Afternoon' : 'Good Evening');
        @endphp

        <!-- Hero Section -->
        <div class="bg-indigo-600 rounded-2xl shadow-lg mb-8 overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <!-- Left: Profile & Welcome -->
                    <div class="flex items-center gap-5">
                        <div class="relative">
                            @if(Auth::user()->profile_image)
                                <img src="{{ asset('storage/' . Auth::user()->profile_image) }}"
                                     class="h-16 w-16 lg:h-18 lg:w-18 rounded-2xl object-cover ring-4 ring-white/20"
                                     alt="Profile">
                            @else
                                @php $initials = strtoupper(substr(Auth::user()->first_name, 0, 1) . substr(Auth::user()->last_name, 0, 1)); @endphp
                                <div class="h-16 w-16 lg:h-18 lg:w-18 rounded-2xl bg-white/20 flex items-center justify-center text-white font-bold text-2xl ring-4 ring-white/10">
                                    {{ $initials }}
                                </div>
                            @endif
                            <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-emerald-400 rounded-full border-2 border-indigo-600 flex items-center justify-center shadow-sm">
                                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-indigo-200 mb-1">{{ $greeting }}</p>
                            <h1 class="text-2xl lg:text-3xl font-bold text-white mb-2">
                                {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                            </h1>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white">
                                    <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Reviewer
                                </span>
                                @if(Auth::user()->affiliation)
                                <span class="text-sm text-indigo-200">{{ Auth::user()->affiliation }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right: Quick Stats & Actions -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 lg:gap-6">
                        <!-- Mini Stats -->
                        <div class="flex items-center gap-4 px-4 py-3 bg-white/10 backdrop-blur rounded-xl">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-white">{{ $stats['total_assigned'] }}</p>
                                <p class="text-xs text-indigo-200">Assigned</p>
                            </div>
                            <div class="w-px h-10 bg-white/20"></div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-emerald-300">{{ $stats['completed'] }}</p>
                                <p class="text-xs text-indigo-200">Done</p>
                            </div>
                            <div class="w-px h-10 bg-white/20"></div>
                            <div class="text-center">
                                <p class="text-2xl font-bold {{ $stats['pending_total'] > 0 ? 'text-amber-300' : 'text-indigo-300' }}">{{ $stats['pending_total'] }}</p>
                                <p class="text-xs text-indigo-200">Pending</p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2">
                            <a href="{{ route('reviewer.my-submissions') }}"
                               class="inline-flex items-center gap-2 px-4 py-2.5 text-indigo-600 bg-white hover:bg-indigo-50 font-medium rounded-lg transition text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                My Submissions
                            </a>
                            <a href="{{ route('reviewer.abstracts') }}"
                               class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-800 hover:bg-indigo-900 text-white font-medium rounded-lg transition text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                View All Reviews
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Priority Alert Banner -->
        @if($stats['re_reviews'] > 0)
        <div class="mb-8 bg-amber-500 rounded-xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Re-Reviews Required</h3>
                    <p class="text-white/80 text-sm">{{ $stats['re_reviews'] }} revised abstract{{ $stats['re_reviews'] > 1 ? 's' : '' }} need{{ $stats['re_reviews'] == 1 ? 's' : '' }} your attention</p>
                </div>
            </div>
            <a href="{{ route('reviewer.abstracts') }}?status=revisions"
               class="inline-flex items-center justify-center px-5 py-2.5 bg-white text-amber-600 font-semibold rounded-lg hover:bg-amber-50 transition">
                Review Now
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
        @endif

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Total Assigned -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $stats['total_assigned'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Assigned</p>
            </div>

            <!-- Completed -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 text-xs font-semibold rounded-full">{{ $completionPercentage }}%</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $stats['historical_completed_reviews'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">All Reviews Done</p>
            </div>

            <!-- Pending New -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    @if($stats['pending_new'] > 0)
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                    </span>
                    @endif
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $stats['pending_new'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pending New</p>
            </div>

            <!-- Re-Reviews -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    @if($stats['re_reviews'] > 0)
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                    </span>
                    @endif
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $stats['re_reviews'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Re-Reviews</p>
            </div>
        </div>

        <!-- Progress Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Review Progress</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['completed'] }} of {{ $stats['total_assigned'] }} completed</p>
                    </div>
                </div>
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $completionPercentage }}%</p>
            </div>

            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                <div class="h-3 rounded-full bg-indigo-600 transition-all duration-500" style="width: {{ $completionPercentage }}%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                <span>{{ $stats['completed'] }} completed</span>
                <span>{{ $stats['total_assigned'] - $stats['completed'] }} remaining</span>
            </div>
        </div>

        <!-- Quick Actions Grid -->
        @if($stats['pending_new'] > 0 || $stats['re_reviews'] > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            @if($stats['pending_new'] > 0)
            <a href="{{ route('reviewer.abstracts') }}?status=pending"
               class="group bg-blue-600 hover:bg-blue-700 rounded-xl p-5 transition">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-white">Start New Reviews</h4>
                            <p class="text-blue-100 text-sm">{{ $stats['pending_new'] }} abstracts awaiting evaluation</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-white/60 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
            @endif

            @if($stats['re_reviews'] > 0)
            <a href="{{ route('reviewer.abstracts') }}?status=revisions"
               class="group bg-amber-500 hover:bg-amber-600 rounded-xl p-5 transition">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-white">Re-evaluate Revisions</h4>
                            <p class="text-amber-100 text-sm">{{ $stats['re_reviews'] }} revised submissions need attention</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-white/60 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
            @endif
        </div>
        @endif

        <!-- Info Cards Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Review Guidelines -->
            <div class="bg-indigo-600 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white">Scoring Criteria (100%)</h3>
                </div>

                <div class="space-y-2">
                    @foreach([
                        ['1', 'Abstract Title', '4'],
                        ['2', 'Quality & Structure', '11'],
                        ['3', 'Background & Rationale', '10'],
                        ['4', 'Objective', '10'],
                        ['5', 'Methodology', '15'],
                        ['6', 'Results', '30'],
                        ['7', 'Conclusion', '15'],
                        ['8', 'Relevance to Conference Themes', '5'],
                    ] as $item)
                    <div class="flex items-center justify-between px-3 py-2 bg-white/10 rounded-lg">
                        <span class="text-white/90 text-sm"><span class="font-bold text-white">{{ $item[0] }}.</span> {{ $item[1] }}</span>
                        <span class="text-xs font-bold text-indigo-200 bg-white/10 px-2 py-0.5 rounded">{{ $item[2] }}%</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Important Notes -->
            <div class="bg-emerald-600 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white">Important Notes</h3>
                </div>

                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <span class="w-1.5 h-1.5 bg-emerald-300 rounded-full mt-2 flex-shrink-0"></span>
                        <span class="text-white/90 text-sm"><strong class="text-white">Blind Review:</strong> Author identities are hidden to ensure unbiased evaluation</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-1.5 h-1.5 bg-emerald-300 rounded-full mt-2 flex-shrink-0"></span>
                        <span class="text-white/90 text-sm"><strong class="text-white">Scoring:</strong> 15 criteria across 8 sections, totalling 100 marks. Use the sliders to score each criterion</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-1.5 h-1.5 bg-emerald-300 rounded-full mt-2 flex-shrink-0"></span>
                        <span class="text-white/90 text-sm"><strong class="text-white">Recommendations:</strong> Accept, Accept with Revisions, or Reject</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-1.5 h-1.5 bg-emerald-300 rounded-full mt-2 flex-shrink-0"></span>
                        <span class="text-white/90 text-sm"><strong class="text-white">Comments:</strong> Required for "Accept with Revisions" and "Reject" — provide specific, constructive feedback</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-1.5 h-1.5 bg-emerald-300 rounded-full mt-2 flex-shrink-0"></span>
                        <span class="text-white/90 text-sm"><strong class="text-white">Re-Reviews:</strong> For revised abstracts, compare the author's changes against your previous feedback</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-1.5 h-1.5 bg-emerald-300 rounded-full mt-2 flex-shrink-0"></span>
                        <span class="text-white/90 text-sm"><strong class="text-white">Structure:</strong> Abstracts should follow the BOMRC format (Background, Objective, Methods, Results, Conclusion)</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Recent Assignments Preview -->
        @if($assignedAbstracts->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Recent Assignments</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Your latest review assignments</p>
                    </div>
                </div>
                <a href="{{ route('reviewer.abstracts') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 text-sm font-medium flex items-center gap-1 transition">
                    View All
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="space-y-3">
                @foreach($assignedAbstracts->take(3) as $abstract)
                @php
                    $reviewerPosition = $abstract->reviewer_id === auth()->id() ? 1 : 2;
                    $abstractRound = $abstract->revision_round ?? 0;

                    $hasAlreadyAccepted = $abstract->reviews()
                        ->where('reviewer_id', auth()->id())
                        ->where('status', 'submitted')
                        ->whereIn('recommendation', ['accept', 'accept_oral', 'accept_poster'])
                        ->exists();

                    $hasSubmittedCurrent = $abstract->reviews()
                        ->where('reviewer_id', auth()->id())
                        ->where('review_round', $abstractRound)
                        ->where('status', 'submitted')
                        ->exists();

                    $isAssignedToCurrentRound = $abstract->reviews()
                        ->where('reviewer_id', auth()->id())
                        ->where('review_round', $abstractRound)
                        ->exists();

                    $isReReview = ($abstractRound > 0) && $isAssignedToCurrentRound && !$hasSubmittedCurrent && !$hasAlreadyAccepted;

                    $isDraft = $isAssignedToCurrentRound && $abstract->reviews()
                        ->where('reviewer_id', auth()->id())
                        ->where('review_round', $abstractRound)
                        ->where('status', 'draft')
                        ->exists();

                    $isCompleted = $hasSubmittedCurrent || $hasAlreadyAccepted;
                @endphp

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 dark:text-white truncate mb-2">{{ Str::limit($abstract->title, 60) }}</h4>
                            <div class="flex items-center flex-wrap gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                                    {{ $abstract->subtheme ?? 'General' }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                                    Reviewer {{ $reviewerPosition }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $abstract->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($isCompleted)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Completed
                                </span>
                            @elseif($isReReview)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Re-Review
                                </span>
                            @elseif($isDraft)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Draft
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-400">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    New
                                </span>
                            @endif
                            <a href="{{ route('reviewer.review', $abstract) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition">
                                {{ $isCompleted ? 'View' : 'Review' }}
                                <svg class="w-3 h-3 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($completedReviewHistory->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mt-8">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Completed Review History</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Your recently submitted reviews across all rounds</p>
                    </div>
                </div>
                <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $stats['historical_completed_reviews'] }} total reviews</span>
            </div>

            <div class="space-y-3">
                @foreach($completedReviewHistory as $review)
                    @php
                        $abstract = $review->abstractSubmission;
                        $submittedAt = $review->submitted_at ?? $review->completed_at ?? $review->updated_at;
                        $canOpenAbstract = $abstract && ($abstract->reviewer_id === auth()->id() || $abstract->reviewer_2_id === auth()->id());
                    @endphp
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white truncate mb-2">{{ Str::limit($abstract->title ?? 'Untitled Abstract', 70) }}</h4>
                                <div class="flex items-center flex-wrap gap-2">
                                    @if($abstract && $abstract->subtheme)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                                            {{ $abstract->subtheme }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                                        Round {{ (int) $review->review_round + 1 }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Submitted {{ $submittedAt?->diffForHumans() ?? 'Unknown date' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if(!is_null($review->score))
                                    <div class="text-right">
                                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $review->score }}</div>
                                        <div class="text-[10px] uppercase tracking-wide text-gray-400">Score</div>
                                    </div>
                                @endif
                                @if($canOpenAbstract)
                                    <a href="{{ route('reviewer.review', $abstract) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition">
                                        View
                                        <svg class="w-3 h-3 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                @elseif($abstract)
                                    <span class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-medium rounded-lg">
                                        Archived
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
@endsection
