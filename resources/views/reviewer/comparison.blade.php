@extends('layouts.app')

@section('title', 'Compare Submissions')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Revision Comparison
                </h1>
                <p class="mt-1 text-gray-500 dark:text-gray-400">
                    Compare original submission (Round 1) with revised version (Round {{ $currentRound }})
                </p>
            </div>
            <a href="{{ route('reviewer.review', $abstract) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Review
            </a>
        </div>

        <!-- What Changed Summary -->
        @if($comparison['summary']['history'])
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-6">
            <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Revision Summary
            </h2>
            <div class="space-y-4">
                @foreach($comparison['summary']['history'] as $history)
                    <div class="flex items-start space-x-3 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 flex-shrink-0">
                            Round {{ $history['round'] }}
                        </span>
                        <div class="flex-1">
                            <p class="text-gray-900 dark:text-gray-100">
                                <strong class="font-semibold">{{ ucfirst(str_replace('_', ' ', $history['action'])) }}</strong>
                                @if($history['user'])
                                    by {{ $history['user'] }}
                                @endif
                            </p>
                            @if($history['feedback'])
                                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $history['feedback'] }}</p>
                            @endif
                            @if($history['author_response'])
                                <div class="mt-2 pl-4 border-l-2 border-green-500 dark:border-green-400">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Author's Response</p>
                                    <p class="text-gray-700 dark:text-gray-300">{{ $history['author_response'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Changed Fields Indicator -->
        @php
            $changedFields = collect($comparison['changes'])->filter(fn($change) => isset($change['changed']) && $change['changed'])->keys();
        @endphp
        
        @if($changedFields->isNotEmpty())
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.732-1.333-2.464 0L4.35 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    <strong>{{ $changedFields->count() }} {{ Str::plural('field', $changedFields->count()) }} changed:</strong>
                    {{ $changedFields->map(fn($f) => ucfirst(str_replace('_', ' ', $f)))->join(', ') }}
                </p>
            </div>
        </div>
        @endif

        <!-- Side-by-Side Comparison -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Original Submission (Round 1) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col h-full">
                <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 rounded-t-xl">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span>
                        Original Submission (Round 1)
                    </h3>
                </div>
                <div class="p-6 space-y-6 flex-1">
                    
                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Title
                            @if(isset($comparison['changes']['title']['changed']) && $comparison['changes']['title']['changed'])
                                <span class="ml-2 text-amber-600 dark:text-amber-400 text-xs normal-case font-medium">● Changed</span>
                            @endif
                        </label>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-100 dark:border-gray-700">
                            <p class="text-gray-900 dark:text-white leading-relaxed">{{ $comparison['original']['title'] }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Description
                            @if(isset($comparison['changes']['description']['changed']) && $comparison['changes']['description']['changed'])
                                <span class="ml-2 text-amber-600 dark:text-amber-400 text-xs normal-case font-medium">● Changed</span>
                            @endif
                        </label>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-100 dark:border-gray-700">
                            <p class="text-gray-900 dark:text-white whitespace-pre-wrap text-sm leading-relaxed">{{ $comparison['original']['description'] }}</p>
                        </div>
                    </div>

                    <!-- Author Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Author</label>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                <p class="text-gray-900 dark:text-white text-sm font-medium">{{ $comparison['original']['author_name'] }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Institution</label>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                <p class="text-gray-900 dark:text-white text-sm font-medium">{{ $comparison['original']['author_institute'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Sub-theme</label>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                <p class="text-gray-900 dark:text-white text-sm font-medium">{{ $comparison['original']['subtheme'] }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Presentation Mode</label>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                <p class="text-gray-900 dark:text-white text-sm font-medium">{{ ucfirst($comparison['original']['presentation_mode']) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revised Submission (Current Round) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col h-full ring-1 ring-emerald-500/20 dark:ring-emerald-500/40">
                <div class="bg-emerald-50 dark:bg-emerald-900/20 px-6 py-4 border-b border-emerald-100 dark:border-emerald-800 rounded-t-xl">
                    <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-100 flex items-center">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 mr-2"></span>
                        Revised Submission (Round {{ $currentRound }})
                    </h3>
                </div>
                <div class="p-6 space-y-6 flex-1">
                    
                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Title
                            @if(isset($comparison['changes']['title']['changed']) && $comparison['changes']['title']['changed'])
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">
                                    Modified
                                </span>
                            @endif
                        </label>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border {{ isset($comparison['changes']['title']['changed']) && $comparison['changes']['title']['changed'] ? 'border-emerald-500 dark:border-emerald-500 ring-1 ring-emerald-500' : 'border-gray-100 dark:border-gray-700' }}">
                            <p class="text-gray-900 dark:text-white leading-relaxed">{{ $comparison['revised']['title'] }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Description
                            @if(isset($comparison['changes']['description']['changed']) && $comparison['changes']['description']['changed'])
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">
                                    Modified
                                </span>
                            @endif
                        </label>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border {{ isset($comparison['changes']['description']['changed']) && $comparison['changes']['description']['changed'] ? 'border-emerald-500 dark:border-emerald-500 ring-1 ring-emerald-500' : 'border-gray-100 dark:border-gray-700' }}">
                            <p class="text-gray-900 dark:text-white whitespace-pre-wrap text-sm leading-relaxed">{{ $comparison['revised']['description'] }}</p>
                        </div>
                    </div>

                    <!-- Author Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Author</label>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                <p class="text-gray-900 dark:text-white text-sm font-medium">{{ $comparison['revised']['author_name'] }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Institution</label>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                <p class="text-gray-900 dark:text-white text-sm font-medium">{{ $comparison['revised']['author_institute'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Sub-theme</label>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                <p class="text-gray-900 dark:text-white text-sm font-medium">{{ $comparison['revised']['subtheme'] }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Presentation Mode</label>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                <p class="text-gray-900 dark:text-white text-sm font-medium">{{ ucfirst($comparison['revised']['presentation_mode']) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

