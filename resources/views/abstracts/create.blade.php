@extends('layouts.app')

@section('title', 'Submit New Abstract')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<style>
    #quill-editor {
        font-size: 0.95rem;
        line-height: 1.8;
        background: transparent;
    }

    #quill-editor .ql-editor {
        min-height: 320px !important;
        padding: 1.5rem;
        cursor: text;
    }

    #quill-editor .ql-editor.ql-blank::before {
        font-style: normal;
        color: #94a3b8;
        left: 1.5rem;
    }

    .ql-toolbar.ql-snow {
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        background: #f8fafc;
        border-radius: 1rem 1rem 0 0;
        padding: 0.75rem 1rem !important;
    }

    .ql-container.ql-snow {
        border: none !important;
    }

    .dark .ql-toolbar.ql-snow {
        background: rgba(30, 41, 59, 0.5);
        border-color: rgba(75, 85, 99, 0.4) !important;
    }

    .dark .ql-toolbar.ql-snow .ql-stroke {
        stroke: #94a3b8;
    }

    .dark .ql-toolbar.ql-snow .ql-fill {
        fill: #94a3b8;
    }

    .dark .ql-toolbar.ql-snow .ql-picker-label {
        color: #94a3b8;
    }

    .dark #quill-editor .ql-editor {
        color: #f1f5f9;
        background-color: transparent;
    }

    .dark #quill-editor .ql-editor.ql-blank::before {
        color: #64748b;
    }

    /* Ensure toolbar is visible and clickable */
    .ql-toolbar.ql-snow {
        border-top: none !important;
        border-left: none !important;
        border-right: none !important;
        border-bottom: 2px solid #f1f5f9 !important;
        background: #f8fafc !important;
        border-radius: 1rem 1rem 0 0;
        padding: 0.75rem 1rem !important;
        position: relative !important;
        z-index: 10;
        pointer-events: auto !important;
    }

    .dark .ql-toolbar.ql-snow {
        background: rgba(30, 41, 59, 0.7) !important;
        border-bottom-color: rgba(71, 85, 105, 0.4) !important;
    }

    /* Active editor state */
    #editor-container.is-active {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 relative font-sans">


    <!-- Refined Professional Header -->
    <div class="relative bg-gradient-to-br from-indigo-800 via-indigo-900 to-slate-900 py-10 md:py-12 lg:py-16 rounded-b-[2.5rem] md:rounded-b-[3rem] shadow-xl overflow-hidden mb-6 md:mb-8">
        <!-- Abstract Precision Background -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.03]"></div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="space-y-2 text-center md:text-left">
                    <h1 class="text-3xl md:text-5xl font-black text-white leading-none tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        Submit <span class="text-indigo-300">Abstract.</span>
                    </h1>
                    <p class="text-sm md:text-base text-indigo-100/60 font-medium tracking-wide">
                        {{ config('conference.short_name') }} {{ config('conference.year') }} Scientific Community Submission Portal
                    </p>
                </div>

                <div class="flex items-center gap-4 bg-white/5 backdrop-blur-md px-6 py-2.5 md:py-3 rounded-2xl border border-white/10 shadow-lg">
                    <div class="text-center">
                        <p class="text-[8px] font-black text-indigo-200/50 uppercase tracking-[0.3em] mb-0.5">Status</p>
                        <p class="text-lg md:text-xl font-black text-white leading-none uppercase">Open</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 relative z-20 mt-[-1.5rem] md:mt-[-2rem] space-y-10 md:space-y-12 pb-32">

        @if ($errors->any())
        <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-4 mb-6 shadow-sm animate-fade-in-up">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-rose-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-bold text-rose-800 dark:text-rose-200">Please fix the following errors:</h3>
                    <ul class="mt-1 list-disc list-inside text-sm text-rose-700 dark:text-rose-300 space-y-1">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- Submission Guidelines Accordion -->
        <div class="bg-white dark:bg-gray-800 border-2 border-amber-300 dark:border-amber-700 rounded-3xl overflow-hidden shadow-md shadow-amber-100/50 dark:shadow-none">
            <button type="button" onclick="toggleGuidelines()" class="w-full text-left p-5 md:p-6 flex items-center justify-between group bg-amber-50/50 dark:bg-amber-900/10">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-wider">Submission Guidelines</h3>
                            <span class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-[9px] font-black uppercase tracking-wider">Must Read</span>
                        </div>
                        <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold">Please review these requirements before submitting your abstract</p>
                    </div>
                </div>
                <div id="guidelinesIcon" class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 transition-all duration-300 rotate-180">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                </div>
            </button>

            <div id="guidelinesContent" class="border-t border-amber-200 dark:border-amber-800/50">
                <div class="p-6 md:p-8 space-y-8 bg-white/50 dark:bg-gray-800/30">

                    <!-- Guideline 1: Abstract Type and Structure -->
                    <div class="space-y-3">
                        <h4 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-600 dark:bg-indigo-500 text-white flex items-center justify-center text-xs font-bold">1</span>
                            Abstract Type and Structure
                        </h4>
                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed ml-8">
                            Abstracts must be <strong>structured and clearly organized</strong> under the following headings:
                        </p>
                        <ul class="ml-8 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                            <li class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">•</span>
                                <span><strong>Background / Introduction</strong> - Context and motivation</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">•</span>
                                <span><strong>Methods</strong> - Study design and procedures</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">•</span>
                                <span><strong>Results</strong> - Key findings</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">•</span>
                                <span><strong>Conclusions and Recommendations</strong> - Implications</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Guideline 2: Length and Language -->
                    <div class="space-y-3">
                        <h4 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-600 dark:bg-indigo-500 text-white flex items-center justify-center text-xs font-bold">2</span>
                            Length and Language
                        </h4>
                        <ul class="ml-8 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                            <li class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">•</span>
                                <span><strong>Maximum length:</strong> 300 words (excluding title and author information)</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">•</span>
                                <span><strong>Language:</strong> Clear, concise, and grammatically correct English</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">•</span>
                                <span><strong>Abbreviations:</strong> Define all abbreviations at first use</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Guideline 3: Content Restrictions -->
                    <div class="space-y-3">
                        <h4 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-600 dark:bg-indigo-500 text-white flex items-center justify-center text-xs font-bold">3</span>
                            Content Restrictions
                        </h4>
                        <ul class="ml-8 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                            <li class="flex gap-2">
                                <span class="text-rose-600 dark:text-rose-400 font-bold">✕</span>
                                <span><strong>Not permitted:</strong> Tables, figures, graphics, and references</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-rose-600 dark:text-rose-400 font-bold">✕</span>
                                <span>Review-style abstracts are discouraged (unless explicitly invited)</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-rose-600 dark:text-rose-400 font-bold">✕</span>
                                <span><strong>Do not include:</strong> Acknowledgements or funding information in the abstract text</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Guideline 4: Author Information -->
                    <div class="space-y-3">
                        <h4 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-600 dark:bg-indigo-500 text-white flex items-center justify-center text-xs font-bold">4</span>
                            Author Information
                        </h4>
                        <ul class="ml-8 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                            <li class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">•</span>
                                <span><strong>Full names:</strong> Provide full names of all authors</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">•</span>
                                <span><strong>Affiliations:</strong> Include institutional affiliations (department, institution, city, country)</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">•</span>
                                <span><strong>Presenting author:</strong> Clearly indicate who will present</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Guideline 5: Compliance -->
                    <div class="space-y-3 bg-rose-50 dark:bg-rose-900/20 p-4 rounded-xl border border-rose-200 dark:border-rose-800/50">
                        <h4 class="font-black text-rose-900 dark:text-rose-200 flex items-center gap-2">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-rose-600 dark:bg-rose-500 text-white flex items-center justify-center text-xs font-bold">5</span>
                            Compliance
                        </h4>
                        <p class="ml-8 text-sm text-rose-800 dark:text-rose-300 leading-relaxed">
                            <strong>⚠️ Important:</strong> Abstracts that do not comply with these guidelines may be <strong>rejected</strong> or <strong>returned for revision</strong>. Please review the guidelines carefully before submitting.
                        </p>
                    </div>

                </div>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('abstracts.store') }}" id="abstractForm" class="space-y-10" data-autosave="abstract-create">
            @csrf

        <!-- 1. Author Information Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
            <div class="border-b border-slate-100 dark:border-gray-700 px-6 md:px-8 py-5 flex items-center gap-4 bg-slate-50/50 dark:bg-gray-900/10">
                <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Author Information</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Primary contact and institutional details</p>
                </div>
            </div>
            <div class="p-6 md:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="author_name" class="block text-[10px] font-black text-slate-400 dark:text-slate-500 mb-2.5 uppercase tracking-[0.2em]">Full Name <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><svg class="w-5 h-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
                            <input type="text" name="author_name" id="author_name" required value="{{ old('author_name') }}" class="block w-full rounded-2xl border-2 border-slate-200 dark:border-gray-600 bg-slate-50/50 dark:bg-gray-700/50 text-slate-900 dark:text-white pl-12 pr-5 py-4 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-medium" placeholder="e.g. Dr. Jane Doe">
                        </div>
                        <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500 font-medium">Include your full title and honorifics.</p>
                    </div>
                    <div>
                        <label for="author_institute" class="block text-[10px] font-black text-slate-400 dark:text-slate-500 mb-2.5 uppercase tracking-[0.2em]">Institution / Affiliation <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><svg class="w-5 h-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
                            <input type="text" name="author_institute" id="author_institute" required value="{{ old('author_institute') }}" class="block w-full rounded-2xl border-2 border-slate-200 dark:border-gray-600 bg-slate-50/50 dark:bg-gray-700/50 text-slate-900 dark:text-white pl-12 pr-5 py-4 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-medium" placeholder="e.g. University of Dar es Salaam">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Abstract Content Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
            <div class="border-b border-slate-100 dark:border-gray-700 px-6 md:px-8 py-5 flex items-center gap-4 bg-slate-50/50 dark:bg-gray-900/10">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-600/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Abstract Content</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Research title, body, and classification</p>
                </div>
            </div>
            <div class="p-6 md:p-8 space-y-8">
                <div>
                    <label for="title" class="block text-[10px] font-black text-slate-400 dark:text-slate-500 mb-2.5 uppercase tracking-[0.2em]">Research Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" id="title" required value="{{ old('title') }}" class="block w-full rounded-2xl border-2 border-slate-200 dark:border-gray-600 bg-slate-50/50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-bold" placeholder="Enter a clear, descriptive title for your research">
                </div>

                <div>
                    <label for="keywords" class="block text-[10px] font-black text-slate-400 dark:text-slate-500 mb-2.5 uppercase tracking-[0.2em]">Keywords <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        </div>
                        <input type="text" name="keywords" id="keywords" required value="{{ old('keywords') }}" class="block w-full rounded-2xl border-2 border-slate-200 dark:border-gray-600 bg-slate-50/50 dark:bg-gray-700/50 text-slate-900 dark:text-white pl-12 pr-5 py-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-medium" placeholder="e.g. public health, epidemiology, Tanzania (comma separated)">
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500 font-medium">Add 3-5 keywords separated by commas to help categorize your research.</p>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2.5">
                        <label for="quill-editor" class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] cursor-pointer">Abstract Body <span class="text-rose-500">*</span></label>
                        <span class="text-xs font-black px-3 py-1 rounded-full bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-gray-600"><span id="wordCount">0</span> / 300 words</span>
                    </div>
                    <div id="editor-container" class="bg-white dark:bg-gray-800 rounded-2xl border-2 border-slate-200 dark:border-gray-600 overflow-hidden transition-all duration-300 relative">
                        <div id="quill-editor" class="min-h-[320px]">{!! old('description') !!}</div>
                    </div>
                    <input type="hidden" name="description" id="description" value="{{ old('description') }}">
                    <p id="wordWarning" class="mt-3 text-xs font-black text-rose-600 hidden flex items-center bg-rose-50 dark:bg-rose-900/20 p-3 rounded-xl border border-rose-100 dark:border-rose-900/30">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.206 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                        WORD LIMIT EXCEEDED! Please condense your abstract to under 300 words.
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 mb-4 uppercase tracking-[0.2em]">Research Subtheme <span class="text-rose-500">*</span></label>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">Read the scope description for each sub-theme below and select the one that best fits your research.</p>
                    <input type="hidden" name="subtheme" id="subtheme" required value="{{ old('subtheme') }}">
                    <div class="space-y-4" id="subtheme-cards">
                        @php
                        $subthemes = collect(\App\Support\ConferenceTopics::names())->values()->map(fn ($name, $i) => [
                            'num' => $i + 1,
                            'value' => $name,
                            'short' => $name,
                            'desc' => \App\Support\ConferenceTopics::description($name),
                        ])->all();
                        @endphp

                        @foreach($subthemes as $st)
                        <label class="subtheme-card cursor-pointer block" data-value="{{ $st['value'] }}">
                            <div class="relative rounded-2xl border-2 transition-all duration-300
                                {{ old('subtheme') == $st['value'] ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/15 ring-4 ring-indigo-500/10 shadow-lg shadow-indigo-500/5' : 'border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900/40 hover:border-slate-300 dark:hover:border-gray-600 hover:shadow-sm' }}">
                                <!-- Header -->
                                <div class="flex items-center gap-3 p-4 pb-3">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <span class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center text-[11px] font-black transition-colors duration-300
                                            {{ old('subtheme') == $st['value'] ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-slate-100 dark:bg-gray-800 text-slate-500 dark:text-slate-400' }}">{{ $st['num'] }}</span>
                                        <span class="font-bold text-slate-800 dark:text-white text-sm leading-tight">{{ $st['short'] }}</span>
                                    </div>
                                    <!-- Radio indicator -->
                                    <div class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all duration-300
                                        {{ old('subtheme') == $st['value'] ? 'border-indigo-500 bg-indigo-500' : 'border-slate-300 dark:border-gray-600' }}">
                                        <svg class="w-3.5 h-3.5 text-white transition-transform duration-300 {{ old('subtheme') == $st['value'] ? 'scale-100' : 'scale-0' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </div>
                                <!-- Scope / Preamble - always visible -->
                                <div class="subtheme-preamble px-4 pb-4">
                                    <div class="rounded-xl p-3.5 transition-all duration-300 border-l-4
                                        {{ old('subtheme') == $st['value'] ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-500' : 'bg-slate-50/80 dark:bg-gray-800/40 border-slate-200 dark:border-gray-700' }}">
                                        <p class="text-[10px] font-black uppercase tracking-[0.15em] mb-1.5 transition-colors duration-300
                                            {{ old('subtheme') == $st['value'] ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' }}">
                                            <svg class="w-3 h-3 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Scope
                                        </p>
                                        <p class="text-[12px] leading-relaxed font-medium transition-colors duration-300
                                            {{ old('subtheme') == $st['value'] ? 'text-slate-700 dark:text-slate-300' : 'text-slate-500 dark:text-slate-400' }}">{{ $st['desc'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Presentation Mode Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
            <div class="border-b border-slate-100 dark:border-gray-700 px-6 md:px-8 py-5 flex items-center gap-4 bg-slate-50/50 dark:bg-gray-900/10">
                <div class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center text-white shadow-lg shadow-amber-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Presentation Preference</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">How would you like to present your research?</p>
                </div>
            </div>
            <div class="p-6 md:p-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="cursor-pointer block">
                        <input type="radio" name="presentation_mode" value="Oral" {{ old('presentation_mode') == 'Oral' ? 'checked' : '' }} required class="peer hidden">
                        <div class="relative p-6 rounded-2xl border-2 border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50/70 dark:peer-checked:bg-amber-900/20 hover:border-slate-300 dark:hover:border-gray-600">
                            <div class="flex items-center gap-4 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                                </div>
                                <p class="font-black text-slate-900 dark:text-white text-lg tracking-tight">Oral Presentation</p>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Present your findings live with slides to the audience and panel.</p>
                            <div class="absolute top-4 right-4 w-6 h-6 rounded-full border-2 border-slate-300 dark:border-gray-600 flex items-center justify-center peer-checked:border-amber-500 transition-colors">
                                <div class="w-3 h-3 rounded-full bg-amber-500 scale-0 peer-checked:scale-100 transition-transform"></div>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer block">
                        <input type="radio" name="presentation_mode" value="Poster" {{ old('presentation_mode') == 'Poster' ? 'checked' : '' }} required class="peer hidden">
                        <div class="relative p-6 rounded-2xl border-2 border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50/70 dark:peer-checked:bg-amber-900/20 hover:border-slate-300 dark:hover:border-gray-600">
                            <div class="flex items-center gap-4 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="font-black text-slate-900 dark:text-white text-lg tracking-tight">Poster Presentation</p>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Display your research visually on a poster board for interactive discussion.</p>
                            <div class="absolute top-4 right-4 w-6 h-6 rounded-full border-2 border-slate-300 dark:border-gray-600 flex items-center justify-center peer-checked:border-amber-500 transition-colors">
                                <div class="w-3 h-3 rounded-full bg-amber-500 scale-0 peer-checked:scale-100 transition-transform"></div>
                            </div>
                        </div>
                    </label>
                </div>
                <p class="mt-4 text-xs text-slate-400 dark:text-slate-500 font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    The committee may reassign your presentation type based on session availability.
                </p>
            </div>
        </div>

        <!-- 4. Co-authors Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
            <div class="border-b border-slate-100 dark:border-gray-700 px-6 md:px-8 py-5 flex items-center justify-between bg-slate-50/50 dark:bg-gray-900/10">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-purple-600 flex items-center justify-center text-white shadow-lg shadow-purple-600/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Co-authors <span class="text-slate-400 font-medium text-[10px] ml-1 lowercase">(Optional)</span></h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Add contributing researchers</p>
                    </div>
                </div>
                <button type="button" onclick="addCoauthor()" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 font-bold transition-all text-[11px] uppercase tracking-wider shadow-md shadow-purple-200 dark:shadow-none">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Add
                </button>
            </div>
            <div class="p-6 md:p-8">
                <div id="coauthors-list" class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>
                <div id="no-coauthors-msg" class="text-center py-16 bg-slate-50/50 dark:bg-gray-900/20 rounded-2xl border-2 border-dashed border-slate-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <p class="text-slate-400 dark:text-slate-500 text-sm font-bold">No co-authors added yet</p>
                    <p class="text-slate-400/60 dark:text-slate-600 text-xs mt-1">Click "Add" above to include co-authors.</p>
                </div>
            </div>
        </div>

        <!-- 5. Conference Proceedings Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
            <div class="border-b border-slate-100 dark:border-gray-700 px-6 md:px-8 py-5 flex items-center gap-4 bg-slate-50/50 dark:bg-gray-900/10">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-lg shadow-emerald-600/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Conference Proceedings</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Index your work in official publications</p>
                </div>
            </div>
            <div class="p-6 md:p-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Yes Option --}}
                    <label class="cursor-pointer block">
                        <input type="radio" name="include_in_proceedings" value="1" {{ old('include_in_proceedings') == '1' ? 'checked' : '' }} required class="peer hidden">
                        <div class="relative p-6 rounded-2xl border-2 border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50/70 dark:peer-checked:bg-emerald-900/20 hover:border-slate-300 dark:hover:border-gray-600">
                            <div class="flex items-center gap-4 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <p class="font-black text-slate-900 dark:text-white text-lg tracking-tight">Yes, include</p>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">My abstract will be professionally indexed and published in the official {{ config('conference.short_name') }} {{ config('conference.year') }} proceedings book if accepted.</p>
                            <div class="absolute top-4 right-4 w-6 h-6 rounded-full border-2 border-slate-300 dark:border-gray-600 flex items-center justify-center peer-checked:border-emerald-500 transition-colors">
                                <div class="w-3 h-3 rounded-full bg-emerald-500 scale-0 peer-checked:scale-100 transition-transform"></div>
                            </div>
                        </div>
                    </label>
                    {{-- No Option --}}
                    <label class="cursor-pointer block">
                        <input type="radio" name="include_in_proceedings" value="0" {{ old('include_in_proceedings') === '0' ? 'checked' : '' }} required class="peer hidden">
                        <div class="relative p-6 rounded-2xl border-2 border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all peer-checked:border-slate-400 peer-checked:bg-slate-50 dark:peer-checked:bg-gray-700 hover:border-slate-300 dark:hover:border-gray-600">
                            <div class="flex items-center gap-4 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-700 flex items-center justify-center text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </div>
                                <p class="font-black text-slate-900 dark:text-white text-lg tracking-tight">No, skip</p>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">I do not wish to include my abstract in the conference proceedings at this time.</p>
                            <div class="absolute top-4 right-4 w-6 h-6 rounded-full border-2 border-slate-300 dark:border-gray-600 flex items-center justify-center peer-checked:border-slate-500 transition-colors">
                                <div class="w-3 h-3 rounded-full bg-slate-500 scale-0 peer-checked:scale-100 transition-transform"></div>
                            </div>
                        </div>
                    </label>
                </div>
                <p class="mt-4 text-xs text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Most authors choose to include their work — it increases visibility and citation potential.
                </p>
            </div>
        </div>

            <!-- Refined Action Bar -->
            <div class="bg-slate-50 dark:bg-gray-900/30 border border-slate-200 dark:border-gray-800 rounded-3xl p-6 mt-12 mb-12">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    <a href="{{ route('abstracts.my') }}"
                        class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 font-bold transition-all text-xs uppercase tracking-widest px-4 py-2">
                        ← Back to My Abstracts
                    </a>

                    <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                        <button type="submit" name="action" value="draft"
                            class="w-full sm:w-auto px-8 py-3.5 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-gray-700 rounded-xl font-bold hover:bg-slate-50 dark:hover:bg-gray-700 shadow-sm transition-all text-xs uppercase tracking-widest">
                            Save as Draft
                        </button>

                        <button type="button" onclick="showSubmitModal()"
                            class="w-full sm:w-auto px-10 py-3.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 dark:shadow-none transition-all text-xs uppercase tracking-widest">
                            Submit for Review
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Submit Confirmation Modal -->
    <div id="submit-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6 transform transition-all scale-100 animate-fade-in-up">
            <div class="flex items-center justify-center w-12 h-12 bg-author-100 dark:bg-author-900/30 rounded-full mb-4 mx-auto">
                <svg class="w-6 h-6 text-author-600 dark:text-author-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2 text-center">Submit for Review?</h3>
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-6 text-center">Once submitted, your abstract will be sent for peer review. You cannot edit it unless revisions are requested.</p>
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeSubmitModal()"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-white dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-600 font-bold transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="submitForm()"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-author-600 hover:bg-author-700 text-white font-bold shadow-lg shadow-author-200 dark:shadow-none transition-colors">
                    Confirm Submit
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function countWords(text) {
            const words = text ? text.trim().split(/\s+/).filter(w => w.length > 0).length : 0;
            const wordCountElement = document.getElementById('wordCount');
            const warningElement = document.getElementById('wordWarning');

            wordCountElement.textContent = words;

            if (words > 300) {
                wordCountElement.className = 'font-bold text-rose-600';
                warningElement.classList.remove('hidden');
            } else {
                wordCountElement.className = 'font-bold text-emerald-600';
                warningElement.classList.add('hidden');
            }
        }

        let coauthorIndex = 0;

        function addCoauthor() {
            const list = document.getElementById('coauthors-list');
            const msg = document.getElementById('no-coauthors-msg');
            if (msg) msg.style.display = 'none';

            const div = document.createElement('div');
            div.className = "bg-slate-50 dark:bg-gray-700/30 border border-slate-200 dark:border-gray-600 rounded-xl p-5 coauthor-entry relative group animate-fade-in-up";
            div.innerHTML = `
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    Co-author <span class="coauthor-number"></span>
                </h4>
                <button type="button" class="remove-coauthor text-slate-400 hover:text-rose-500 transition-colors p-1 rounded-md hover:bg-rose-50 dark:hover:bg-rose-900/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5">Full Name (with Title)</label>
                    <input type="text" name="coauthors[${coauthorIndex}][name]" placeholder="e.g., Dr. John Smith"
                           class="block w-full rounded-lg border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-author-500 focus:border-author-500" />
                    <p class="text-[10px] text-slate-400 mt-1">Include title: Dr., Prof., Mr., Mrs., Ms.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5">Institution/Affiliation</label>
                    <input type="text" name="coauthors[${coauthorIndex}][institute]" placeholder="e.g., University of Dar es Salaam"
                           class="block w-full rounded-lg border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-author-500 focus:border-author-500" />
                </div>
            </div>
        `;
            list.appendChild(div);

            updateCoauthorIndices();
            div.querySelector('.remove-coauthor').addEventListener('click', function() {
                div.remove();
                updateCoauthorIndices();
                if (list.children.length === 0 && msg) msg.style.display = 'block';
            });
            coauthorIndex++;
        }

        function updateCoauthorIndices() {
            const entries = document.querySelectorAll('.coauthor-entry');
            entries.forEach((entry, idx) => {
                entry.querySelector('.coauthor-number').textContent = idx + 1;
            });
        }

        function showSubmitModal() {
            document.getElementById('submit-modal').classList.remove('hidden');
            document.getElementById('submit-modal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeSubmitModal() {
            document.getElementById('submit-modal').classList.add('hidden');
            document.getElementById('submit-modal').classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function submitForm() {
            const btn = document.querySelector('button[onclick="submitForm()"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Submitting...';
            }
            
            const form = document.getElementById('abstractForm');
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'action';
            hidden.value = 'submit';
            form.appendChild(hidden);
            form.submit();
        }

        // Subtheme card selection logic
        document.querySelectorAll('.subtheme-card').forEach(card => {
            card.addEventListener('click', function(e) {
                const value = this.dataset.value;
                document.getElementById('subtheme').value = value;
                if (window.FormAutosave) {
                    window.FormAutosave.save(document.getElementById('abstractForm'));
                }

                // Update all card styles
                document.querySelectorAll('.subtheme-card').forEach(c => {
                    const inner = c.querySelector(':scope > div');
                    const numBadge = c.querySelector('.w-8.h-8.rounded-xl');
                    const radioCircle = c.querySelector('.w-6.h-6.rounded-full');
                    const checkIcon = radioCircle ? radioCircle.querySelector('svg') : null;
                    const preambleBox = c.querySelector('.subtheme-preamble > div');
                    const scopeLabel = c.querySelector('.subtheme-preamble p:first-child');
                    const scopeText = c.querySelector('.subtheme-preamble p:last-child');

                    if (c.dataset.value === value) {
                        // Selected card
                        inner.classList.add('border-indigo-500', 'bg-indigo-50/50', 'dark:bg-indigo-900/15', 'ring-4', 'ring-indigo-500/10', 'shadow-lg', 'shadow-indigo-500/5');
                        inner.classList.remove('border-slate-200', 'dark:border-gray-700', 'bg-white', 'dark:bg-gray-900/40', 'hover:border-slate-300', 'dark:hover:border-gray-600', 'hover:shadow-sm');
                        if (numBadge) {
                            numBadge.classList.add('bg-indigo-600', 'text-white', 'shadow-md', 'shadow-indigo-600/30');
                            numBadge.classList.remove('bg-slate-100', 'dark:bg-gray-800', 'text-slate-500', 'dark:text-slate-400');
                        }
                        if (radioCircle) {
                            radioCircle.classList.add('border-indigo-500', 'bg-indigo-500');
                            radioCircle.classList.remove('border-slate-300', 'dark:border-gray-600');
                        }
                        if (checkIcon) {
                            checkIcon.classList.add('scale-100');
                            checkIcon.classList.remove('scale-0');
                        }
                        if (preambleBox) {
                            preambleBox.classList.add('bg-indigo-50', 'dark:bg-indigo-900/20', 'border-indigo-500');
                            preambleBox.classList.remove('bg-slate-50/80', 'dark:bg-gray-800/40', 'border-slate-200', 'dark:border-gray-700');
                        }
                        if (scopeLabel) {
                            scopeLabel.classList.add('text-indigo-600', 'dark:text-indigo-400');
                            scopeLabel.classList.remove('text-slate-400', 'dark:text-slate-500');
                        }
                        if (scopeText) {
                            scopeText.classList.add('text-slate-700', 'dark:text-slate-300');
                            scopeText.classList.remove('text-slate-500', 'dark:text-slate-400');
                        }
                        // Smooth scroll into view
                        setTimeout(() => {
                            c.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 100);
                    } else {
                        // Deselected card
                        inner.classList.remove('border-indigo-500', 'bg-indigo-50/50', 'dark:bg-indigo-900/15', 'ring-4', 'ring-indigo-500/10', 'shadow-lg', 'shadow-indigo-500/5');
                        inner.classList.add('border-slate-200', 'dark:border-gray-700', 'bg-white', 'dark:bg-gray-900/40', 'hover:border-slate-300', 'dark:hover:border-gray-600', 'hover:shadow-sm');
                        if (numBadge) {
                            numBadge.classList.remove('bg-indigo-600', 'text-white', 'shadow-md', 'shadow-indigo-600/30');
                            numBadge.classList.add('bg-slate-100', 'dark:bg-gray-800', 'text-slate-500', 'dark:text-slate-400');
                        }
                        if (radioCircle) {
                            radioCircle.classList.remove('border-indigo-500', 'bg-indigo-500');
                            radioCircle.classList.add('border-slate-300', 'dark:border-gray-600');
                        }
                        if (checkIcon) {
                            checkIcon.classList.remove('scale-100');
                            checkIcon.classList.add('scale-0');
                        }
                        if (preambleBox) {
                            preambleBox.classList.remove('bg-indigo-50', 'dark:bg-indigo-900/20', 'border-indigo-500');
                            preambleBox.classList.add('bg-slate-50/80', 'dark:bg-gray-800/40', 'border-slate-200', 'dark:border-gray-700');
                        }
                        if (scopeLabel) {
                            scopeLabel.classList.remove('text-indigo-600', 'dark:text-indigo-400');
                            scopeLabel.classList.add('text-slate-400', 'dark:text-slate-500');
                        }
                        if (scopeText) {
                            scopeText.classList.remove('text-slate-700', 'dark:text-slate-300');
                            scopeText.classList.add('text-slate-500', 'dark:text-slate-400');
                        }
                    }
                });
            });
        });
    </script>
    @endpush

    @push('end-scripts')
    <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Check if Quill is loaded
                if (typeof Quill === 'undefined') {
                    console.error('Quill library not loaded');
                    // Add a visible notification if in dev
                    return;
                }

                const editorElement = document.getElementById('quill-editor');
                if (!editorElement) return;

                // Initialize Quill editor with explicit toolbar container
                let quill = new Quill('#quill-editor', {
                    theme: 'snow',
                    placeholder: 'Enter your abstract content here...\n\nInclude: Objective, Methods, Results, and Conclusion',
                    modules: {
                        toolbar: {
                            container: [
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                                [{ 'script': 'sub' }, { 'script': 'super' }],
                                ['clean']
                            ]
                        }
                    }
                });

                // Add focus class to container
                const container = document.getElementById('editor-container');
                quill.on('selection-change', function(range) {
                    if (range) {
                        container.classList.add('is-active');
                    } else {
                        container.classList.remove('is-active');
                    }
                });

                // Sync Quill content to hidden input on text change
                quill.on('text-change', function() {
                    const descriptionInput = document.getElementById('description');
                    if (descriptionInput) {
                        descriptionInput.value = quill.root.innerHTML;
                    }
                    countWordsFromQuill();
                    // Hidden-input writes don't bubble input events, so persist explicitly.
                    if (window.FormAutosave) {
                        window.FormAutosave.save(document.getElementById('abstractForm'));
                    }
                });

                function countWordsFromQuill() {
                    const text = quill.getText().trim();
                    if (typeof countWords === 'function') {
                        countWords(text);
                    }
                }

                // Initial word count and synchronization
                countWordsFromQuill();

                // Make it clickable everywhere
                container.addEventListener('click', function() {
                    quill.focus();
                });

                // Ensure label focus works
                const label = document.querySelector('label[for="quill-editor"]');
                if (label) {
                    label.addEventListener('click', function(e) {
                        e.preventDefault();
                        quill.focus();
                    });
                }

                // Make it globally accessible
                window.quillEditor = quill;

                // Re-hydrate a locally auto-saved draft into the rich widgets that
                // the generic autosave can't restore on its own (Quill, the subtheme
                // cards, and the dynamic co-author rows).
                const savedDraft = window.FormAutosave ? window.FormAutosave.get('abstract-create') : null;
                if (savedDraft) {
                    if (savedDraft.description && quill.getLength() <= 1) {
                        quill.clipboard.dangerouslyPasteHTML(savedDraft.description);
                    }

                    if (savedDraft.subtheme) {
                        document.querySelectorAll('.subtheme-card').forEach(function(card) {
                            if (card.dataset.value === savedDraft.subtheme) card.click();
                        });
                    }

                    Object.keys(savedDraft)
                        .filter(function(k) { return /^coauthors\[\d+\]\[name\]$/.test(k); })
                        .forEach(function(nameKey) {
                            const idx = nameKey.match(/\d+/)[0];
                            const name = savedDraft['coauthors[' + idx + '][name]'] || '';
                            const institute = savedDraft['coauthors[' + idx + '][institute]'] || '';
                            if (!name && !institute) return;
                            addCoauthor();
                            const entries = document.querySelectorAll('.coauthor-entry');
                            const last = entries[entries.length - 1];
                            if (!last) return;
                            const inputs = last.querySelectorAll('input');
                            if (inputs[0]) inputs[0].value = name;
                            if (inputs[1]) inputs[1].value = institute;
                        });
                }
            } catch (error) {
                console.error('Failed to initialize Quill:', error);
            }
        });

        // Toggle submission guidelines
        function toggleGuidelines() {
            const content = document.getElementById('guidelinesContent');
            const icon = document.getElementById('guidelinesIcon');

            content.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }

        // Show welcome modal on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Modal logic removed
        });
    </script>
    @endpush
</div>
@endsection
