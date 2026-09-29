@extends('layouts.app')

@section('title', 'Edit Abstract')

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
@php
    $oldCoauthors = old('coauthors');
    $abstractCoauthors = $abstract->coauthors;

    if (is_string($abstractCoauthors)) {
        $abstractCoauthors = json_decode($abstractCoauthors, true) ?? [];
    }
    $abstractCoauthors = is_array($abstractCoauthors) ? $abstractCoauthors : [];

    $coauthors = $oldCoauthors ?? $abstractCoauthors;
    $coauthors = is_array($coauthors) ? $coauthors : [];
@endphp
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 relative font-sans">
    <!-- Welcome Modal (shows on first visit) -->
    <div id="guidelines-welcome-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 bg-black/70 backdrop-blur-md">
        <div class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-2xl max-w-lg w-full overflow-hidden transform scale-95 opacity-0 transition-all duration-500" id="guidelines-welcome-modal-content">
            <!-- Header with Document Icon -->
            <div class="relative bg-gradient-to-br from-indigo-500 via-blue-500 to-cyan-600 p-10 text-center">
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-10"></div>
                <div class="relative">
                    <div class="w-24 h-24 bg-white/20 backdrop-blur-xl rounded-full flex items-center justify-center mx-auto mb-6 ring-4 ring-white/30">
                        <span class="text-5xl">📋</span>
                    </div>
                    <h2 class="text-3xl font-black text-white mb-2">Submission Guidelines</h2>
                    <p class="text-indigo-100 font-medium">Important Requirements for Your Abstract</p>
                </div>
            </div>

            <!-- Content -->
            <div class="p-10 space-y-8">
                <div class="text-center">
                    <p class="text-slate-600 dark:text-slate-300 font-medium leading-relaxed">
                        Before you update your abstract, please review the submission guidelines to ensure your changes meet all requirements.
                    </p>
                </div>

                <!-- Key Points -->
                <div class="space-y-4">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest text-center">Key Requirements</h3>

                    <div class="grid gap-4">
                        <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-gray-800 rounded-2xl">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center text-indigo-600 font-black flex-shrink-0">📄</div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm">Structured Format</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Background, Methods, Results, and Conclusions sections</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-gray-800 rounded-2xl">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center text-indigo-600 font-black flex-shrink-0">📏</div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm">300-Word Limit</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Maximum length excluding title and author information</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-gray-800 rounded-2xl">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center text-indigo-600 font-black flex-shrink-0">✍️</div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm">English Language</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Clear, concise, and grammatically correct English text</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-gray-800 rounded-2xl">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center text-indigo-600 font-black flex-shrink-0">👥</div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm">Author Details</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Full names, affiliations, and presenting author information</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Notice -->
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-5">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <h4 class="text-sm font-black text-amber-700 dark:text-amber-300">Full Guidelines Available</h4>
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">After you start, you can always review the complete guidelines section below the form header at any time.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3">
                    <button onclick="closeGuidelinesWelcomeModal()" class="flex-1 py-4 bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-slate-300 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-gray-700 transition-colors">
                        Learn More Below
                    </button>
                    <button onclick="closeGuidelinesWelcomeModalAndScroll()" class="flex-1 py-4 bg-gradient-to-r from-indigo-500 to-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:scale-[1.01] transition-all flex items-center justify-center gap-2">
                        <span>Continue</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </div>

                <p class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Questions? Review guidelines anytime below
                </p>
            </div>
        </div>
    </div>

    <style>
        /* Guidelines Welcome Modal */
        #guidelines-welcome-modal.flex {
            display: flex !important;
        }

        #guidelines-welcome-modal-content.initial {
            opacity: 0;
            transform: scale(0.95);
        }

        #guidelines-welcome-modal-content.show {
            opacity: 1;
            transform: scale(1);
        }
    </style>

    <!-- Refined Professional Header -->
    <div class="relative bg-gradient-to-br from-indigo-800 via-indigo-900 to-slate-900 py-12 rounded-b-[3rem] shadow-xl overflow-hidden mb-8">
        <!-- Abstract Precision Background -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.03]"></div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="space-y-2 text-center md:text-left">
                    <h1 class="text-3xl md:text-5xl font-black text-white leading-none tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        Edit <span class="text-indigo-300">Abstract.</span>
                    </h1>
                    <p class="text-base text-indigo-100/60 font-medium tracking-wide">
                        Update your submission: "{{ $abstract->title }}"
                    </p>
                </div>

                <div class="flex items-center gap-4 bg-white/5 backdrop-blur-md px-6 py-3 rounded-2xl border border-white/10 shadow-lg">
                    <div class="text-center">
                        <p class="text-[8px] font-black text-indigo-200/50 uppercase tracking-[0.3em] mb-0.5">Status</p>
                        <p class="text-xl font-black text-white leading-none uppercase">{{ $abstract->status }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 mt-[-2rem] space-y-12 pb-32">
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
        <div class="bg-white dark:bg-gray-800 border-2 border-slate-100 dark:border-gray-700 rounded-3xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <button type="button" onclick="toggleGuidelines()" class="w-full text-left p-5 md:p-6 flex items-center justify-between group">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-wider">Submission Guidelines</h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Verify your abstract content requirements</p>
                    </div>
                </div>
                <div id="guidelinesIcon" class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-gray-700/50 flex items-center justify-center text-slate-400 transition-all duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                </div>
            </button>

            <div id="guidelinesContent" class="hidden border-t border-amber-200 dark:border-amber-800/50">
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
                            <strong>⚠️ Important:</strong> Abstracts that do not comply with these guidelines may be <strong>rejected</strong> or <strong>returned for revision</strong>. Please review the guidelines carefully before updating.
                        </p>
                    </div>

                </div>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('abstracts.update', $abstract) }}" id="abstractForm" class="space-y-8" data-autosave="abstract-edit-{{ $abstract->id }}">
            @csrf
            @method('PUT')

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
                            <input type="text" name="author_name" id="author_name" required value="{{ old('author_name', $abstract->author_name) }}" class="block w-full rounded-2xl border-2 border-slate-200 dark:border-gray-600 bg-slate-50/50 dark:bg-gray-700/50 text-slate-900 dark:text-white pl-12 pr-5 py-4 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-medium" placeholder="e.g. Dr. Jane Doe">
                        </div>
                        <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500 font-medium">Include your full title and honorifics.</p>
                    </div>
                    <div>
                        <label for="author_institute" class="block text-[10px] font-black text-slate-400 dark:text-slate-500 mb-2.5 uppercase tracking-[0.2em]">Institution / Affiliation <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><svg class="w-5 h-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
                            <input type="text" name="author_institute" id="author_institute" required value="{{ old('author_institute', $abstract->author_institute) }}" class="block w-full rounded-2xl border-2 border-slate-200 dark:border-gray-600 bg-slate-50/50 dark:bg-gray-700/50 text-slate-900 dark:text-white pl-12 pr-5 py-4 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-medium" placeholder="e.g. University of Dar es Salaam">
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
                    <input type="text" name="title" id="title" required value="{{ old('title', $abstract->title) }}" class="block w-full rounded-2xl border-2 border-slate-200 dark:border-gray-600 bg-slate-50/50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-bold" placeholder="Enter a clear, descriptive title for your research">
                </div>

                <div>
                    <label for="keywords" class="block text-[10px] font-black text-slate-400 dark:text-slate-500 mb-2.5 uppercase tracking-[0.2em]">Keywords <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        </div>
                        <input type="text" name="keywords" id="keywords" required value="{{ old('keywords', $abstract->keywords) }}" class="block w-full rounded-2xl border-2 border-slate-200 dark:border-gray-600 bg-slate-50/50 dark:bg-gray-700/50 text-slate-900 dark:text-white pl-12 pr-5 py-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-medium" placeholder="e.g. public health, epidemiology, Tanzania (comma separated)">
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500 font-medium">Add 3-5 keywords separated by commas to help categorize your research.</p>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2.5">
                        <label for="quill-editor" class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] cursor-pointer">Abstract Body <span class="text-rose-500">*</span></label>
                        <span class="text-xs font-black px-3 py-1 rounded-full bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-gray-600"><span id="wordCount">0</span> / 300 words</span>
                    </div>
                    <div id="editor-container" class="bg-white dark:bg-gray-800 rounded-2xl border-2 border-slate-200 dark:border-gray-600 overflow-hidden transition-all duration-300 relative">
                        <div id="quill-editor" class="min-h-[320px]">{!! old('description', $abstract->description) !!}</div>
                    </div>
                    <input type="hidden" name="description" id="description" value="{{ old('description', $abstract->description) }}">
                    <p id="wordWarning" class="mt-3 text-xs font-black text-rose-600 hidden flex items-center bg-rose-50 dark:bg-rose-900/20 p-3 rounded-xl border border-rose-100 dark:border-rose-900/30">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.206 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                        WORD LIMIT EXCEEDED! Please condense your abstract to under 300 words.
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 mb-4 uppercase tracking-[0.2em]">Research Subtheme <span class="text-rose-500">*</span></label>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">Browse the scope descriptions below to find the best fit for your research, then select it.</p>
                    <input type="hidden" name="subtheme" id="subtheme" required value="{{ old('subtheme', $abstract->subtheme) }}">
                    <div class="space-y-3" id="subtheme-cards">
                        @php
                        $subthemes = collect(\App\Support\ConferenceTopics::names())->values()->map(fn ($name, $i) => [
                            'num' => $i + 1,
                            'value' => $name,
                            'desc' => \App\Support\ConferenceTopics::description($name),
                        ])->all();
                        $currentSubtheme = old('subtheme', $abstract->subtheme);
                        @endphp

                        @foreach($subthemes as $st)
                        <label class="subtheme-card cursor-pointer block" data-value="{{ $st['value'] }}">
                            <div class="relative rounded-2xl border-2 transition-all
                                {{ $currentSubtheme == $st['value'] ? 'border-indigo-500 bg-indigo-50/40 dark:bg-indigo-900/10 ring-2 ring-indigo-500/10' : 'border-slate-100 dark:border-gray-800 bg-white dark:bg-gray-900/40 hover:border-slate-200 dark:hover:border-gray-700' }}">
                                <!-- Header - always visible -->
                                <div class="flex items-center gap-3 p-4">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <span class="flex-shrink-0 w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-[10px] font-black">{{ $st['num'] }}</span>
                                        <span class="font-bold text-slate-800 dark:text-white text-[13px] leading-tight">{{ $st['value'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button type="button" class="subtheme-toggle text-slate-400 hover:text-indigo-500 dark:text-slate-500 dark:hover:text-indigo-400 transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-800" onclick="event.preventDefault(); event.stopPropagation(); toggleSubthemeDesc(this);" title="Read scope description">
                                            <svg class="w-4 h-4 toggle-icon transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <!-- Radio indicator -->
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors
                                            {{ $currentSubtheme == $st['value'] ? 'border-indigo-500' : 'border-slate-200 dark:border-gray-700' }}">
                                            <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 transition-transform
                                                {{ $currentSubtheme == $st['value'] ? 'scale-100' : 'scale-0' }}"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Description - collapsible -->
                                <div class="subtheme-desc hidden">
                                    <div class="px-4 pb-4 pt-0">
                                        <div class="bg-white/50 dark:bg-gray-800/50 border border-slate-100 dark:border-gray-700 rounded-xl p-3">
                                            <p class="text-[12px] text-slate-600 dark:text-slate-400 leading-relaxed font-medium">{{ $st['desc'] }}</p>
                                        </div>
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
                        <input type="radio" name="presentation_mode" value="Oral" {{ old('presentation_mode', $abstract->presentation_mode) == 'Oral' ? 'checked' : '' }} required class="peer hidden">
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
                        <input type="radio" name="presentation_mode" value="Poster" {{ old('presentation_mode', $abstract->presentation_mode) == 'Poster' ? 'checked' : '' }} required class="peer hidden">
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
                <div id="coauthors-list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Co-authors loop -->
                    @foreach($coauthors as $i => $coauthor)
                    <div class="bg-slate-50/50 dark:bg-gray-900/30 border border-slate-200/60 dark:border-gray-600 rounded-[2rem] p-6 coauthor-entry relative group animate-fade-in-up">
                        <div class="flex items-center justify-between mb-5">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Contributor #{{ $i + 1 }}</span>
                            <button type="button" class="remove-coauthor text-slate-400 hover:text-rose-500 transition-colors p-2 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <input type="text" name="coauthors[{{ $i }}][name]" placeholder="Full Name" value="{{ $coauthor['name'] ?? '' }}"
                                class="block w-full rounded-xl border-slate-200/60 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-900 dark:text-white px-4 py-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 font-medium" />
                            <input type="text" name="coauthors[{{ $i }}][institute]" placeholder="Institution" value="{{ $coauthor['institute'] ?? '' }}"
                                class="block w-full rounded-xl border-slate-200/60 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-900 dark:text-white px-4 py-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 font-medium" />
                        </div>
                    </div>
                    @endforeach
                </div>

                @if(empty($coauthors))
                <div id="no-coauthors-msg" class="text-center py-16 bg-slate-50/50 dark:bg-gray-900/20 rounded-2xl border-2 border-dashed border-slate-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <p class="text-slate-400 dark:text-slate-500 text-sm font-bold">No co-authors added yet</p>
                    <p class="text-slate-400/60 dark:text-slate-600 text-xs mt-1">Click "Add" above to include co-authors.</p>
                </div>
                @endif
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
                        <input type="radio" name="include_in_proceedings" value="1" {{ old('include_in_proceedings', $abstract->include_in_proceedings) == '1' ? 'checked' : '' }} required class="peer hidden">
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
                        <input type="radio" name="include_in_proceedings" value="0" {{ old('include_in_proceedings', $abstract->include_in_proceedings) === '0' || old('include_in_proceedings', $abstract->include_in_proceedings) == 0 && !is_null(old('include_in_proceedings', $abstract->include_in_proceedings)) ? 'checked' : '' }} required class="peer hidden">
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
                            {{ $abstract->status === 'draft' ? 'Update Draft' : 'Update Content' }}
                        </button>

                        @if($abstract->status === 'draft')
                        <button type="button" onclick="openSubmitModal()"
                            class="w-full sm:w-auto px-10 py-3.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 dark:shadow-none transition-all text-xs uppercase tracking-widest">
                            Submit Final
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </form>
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

        let coauthorIndex = {{ count($coauthors ?? []) }};

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
                           class="block w-full rounded-lg border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    <p class="text-[10px] text-slate-400 mt-1">Include title: Dr., Prof., Mr., Mrs., Ms.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5">Institution/Affiliation</label>
                    <input type="text" name="coauthors[${coauthorIndex}][institute]" placeholder="e.g., University of Dar es Salaam"
                           class="block w-full rounded-lg border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
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

        // Subtheme card selection logic
        function toggleSubthemeDesc(btn) {
            const card = btn.closest('.subtheme-card');
            const desc = card.querySelector('.subtheme-desc');
            const icon = btn.querySelector('.toggle-icon');
            desc.classList.toggle('hidden');
            icon.style.transform = desc.classList.contains('hidden') ? '' : 'rotate(180deg)';
        }

        document.querySelectorAll('.subtheme-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't select when clicking the toggle button
                if (e.target.closest('.subtheme-toggle')) return;

                const value = this.dataset.value;
                document.getElementById('subtheme').value = value;
                if (window.FormAutosave) {
                    window.FormAutosave.save(document.getElementById('abstractForm'));
                }

                // Update all card styles
                document.querySelectorAll('.subtheme-card').forEach(c => {
                    const inner = c.querySelector(':scope > div');
                    const radio = c.querySelector('.w-5.h-5.rounded-full');
                    const dot = radio ? radio.querySelector('div') : null;
                    if (c.dataset.value === value) {
                        inner.classList.add('border-indigo-500', 'bg-indigo-50/70', 'dark:bg-indigo-900/20', 'ring-4', 'ring-indigo-500/10');
                        inner.classList.remove('border-slate-100', 'dark:border-gray-800', 'bg-white', 'dark:bg-gray-900/40', 'hover:border-slate-300', 'dark:hover:border-gray-600');
                        if (radio) {
                            radio.classList.add('border-indigo-500');
                            radio.classList.remove('border-slate-300', 'dark:border-gray-600');
                        }
                        if (dot) {
                            dot.classList.add('scale-100');
                            dot.classList.remove('scale-0');
                        }
                        // Auto-expand description on selection
                        const desc = c.querySelector('.subtheme-desc');
                        const icon = c.querySelector('.toggle-icon');
                        if (desc && desc.classList.contains('hidden')) {
                            desc.classList.remove('hidden');
                            if (icon) icon.style.transform = 'rotate(180deg)';
                        }
                    } else {
                        inner.classList.remove('border-indigo-500', 'bg-indigo-50/70', 'dark:bg-indigo-900/20', 'ring-4', 'ring-indigo-500/10');
                        inner.classList.add('border-slate-100', 'dark:border-gray-800', 'bg-white', 'dark:bg-gray-900/40', 'hover:border-slate-300', 'dark:hover:border-gray-600');
                        if (radio) {
                            radio.classList.remove('border-indigo-500');
                            radio.classList.add('border-slate-300', 'dark:border-gray-600');
                        }
                        if (dot) {
                            dot.classList.remove('scale-100');
                            dot.classList.add('scale-0');
                        }
                    }
                });
            });
        });

        // Initialize existing remove buttons
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-expand selected subtheme on page load
            const selectedValue = document.getElementById('subtheme').value;
            if (selectedValue) {
                // Use a more robust selector that handles special characters
                const cards = document.querySelectorAll('.subtheme-card');
                let selectedCard = null;
                cards.forEach(card => {
                    if (card.dataset.value === selectedValue) {
                        selectedCard = card;
                    }
                });

                if (selectedCard) {
                    const desc = selectedCard.querySelector('.subtheme-desc');
                    const icon = selectedCard.querySelector('.toggle-icon');
                    if (desc) desc.classList.remove('hidden');
                    if (icon) icon.style.transform = 'rotate(180deg)';
                }
            }

            document.querySelectorAll('.remove-coauthor').forEach(btn => {
                btn.addEventListener('click', function() {
                    const list = document.getElementById('coauthors-list');
                    const msg = document.getElementById('no-coauthors-msg');
                    this.closest('.coauthor-entry').remove();
                    updateCoauthorIndices();
                    if (list.children.length === 0 && msg) msg.style.display = 'block';
                });
            });
        });
    </script>
    @endpush

    <!-- Submit Confirmation Modal -->
    <div id="submit-confirm-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeSubmitModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-gray-700 w-full max-w-md p-6 transform transition-all scale-100">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h3 class="text-xl font-bold text-slate-900 dark:text-white text-center mb-2">Submit Abstract?</h3>
            <p class="text-slate-600 dark:text-slate-300 text-sm text-center mb-6">
                Once submitted, your abstract will be sent for review. You can no longer edit it unless a committee member requests a revision.
            </p>

            <div class="flex gap-3">
                <button type="button" onclick="closeSubmitModal()" class="flex-1 px-4 py-2.5 bg-white dark:bg-gray-700 border border-slate-200 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-600 text-slate-700 dark:text-slate-200 rounded-xl font-bold transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="confirmSubmit()" class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 text-white rounded-xl font-bold transition-colors shadow-lg shadow-emerald-200 dark:shadow-none">
                    Submit Now
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const submitModal = document.getElementById('submit-confirm-modal');

        function openSubmitModal() {
            submitModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeSubmitModal() {
            submitModal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function confirmSubmit() {
            const btn = document.querySelector('button[onclick="confirmSubmit()"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Submitting...';
            }

            const form = document.getElementById('abstractForm');
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'submit';
            form.appendChild(actionInput);
            form.submit();
        }

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSubmitModal();
            }
        });
    </script>
    @endpush

    @push('end-scripts')
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Check if Quill is loaded
                if (typeof Quill === 'undefined') {
                    console.error('Quill library not loaded');
                    return;
                }

                const editorElement = document.getElementById('quill-editor');
                if (!editorElement) return;

                // Initialize Quill editor
                let quill = new Quill('#quill-editor', {
                    theme: 'snow',
                    placeholder: 'Enter your abstract content here...',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            [{ 'script': 'sub' }, { 'script': 'super' }],
                            ['clean']
                        ]
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

                // Re-hydrate any locally auto-saved, unsaved edits over the
                // server-rendered values (Quill body, subtheme card, co-authors).
                const savedDraft = window.FormAutosave
                    ? window.FormAutosave.get('abstract-edit-{{ $abstract->id }}')
                    : null;
                if (savedDraft) {
                    if (savedDraft.description) {
                        quill.clipboard.dangerouslyPasteHTML(savedDraft.description);
                    }

                    if (savedDraft.subtheme) {
                        document.querySelectorAll('.subtheme-card').forEach(function(card) {
                            if (card.dataset.value === savedDraft.subtheme) card.click();
                        });
                    }

                    // If the draft tracked co-authors, rebuild the rows from it so we
                    // restore exactly what the user had (including added/removed rows).
                    const hadCoauthors = Object.keys(savedDraft).some(function(k) { return /^coauthors\[/.test(k); });
                    if (hadCoauthors) {
                        document.querySelectorAll('.coauthor-entry').forEach(function(e) { e.remove(); });
                        coauthorIndex = 0;
                        Object.keys(savedDraft)
                            .map(function(k) { const m = k.match(/^coauthors\[(\d+)\]\[name\]$/); return m ? parseInt(m[1], 10) : null; })
                            .filter(function(v) { return v !== null; })
                            .sort(function(a, b) { return a - b; })
                            .forEach(function(i) {
                                const name = savedDraft['coauthors[' + i + '][name]'] || '';
                                const institute = savedDraft['coauthors[' + i + '][institute]'] || '';
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
                }
            } catch (error) {
                console.error('Failed to initialize Quill:', error);
            }
        });

        // Toggle submission guidelines
        function toggleGuidelines() {
            const content = document.getElementById('guidelinesContent');
            const icon = document.getElementById('guidelinesIcon');

            if (content && icon) {
                content.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            }
        }

        // Guidelines Welcome Modal logic...
        function showGuidelinesWelcomeModal() {
            const hasSeenWelcome = localStorage.getItem('abstract_guidelines_welcome_seen');
            if (!hasSeenWelcome) {
                setTimeout(() => {
                    const modal = document.getElementById('guidelines-welcome-modal');
                    const content = document.getElementById('guidelines-welcome-modal-content');
                    if (modal && content) {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        setTimeout(() => {
                            content.classList.add('show');
                        }, 10);
                        document.body.style.overflow = 'hidden';
                    }
                }, 500);
            }
        }

        function closeGuidelinesWelcomeModal() {
            const modal = document.getElementById('guidelines-welcome-modal');
            const content = document.getElementById('guidelines-welcome-modal-content');
            if (modal && content) {
                content.classList.remove('show');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                    localStorage.setItem('abstract_guidelines_welcome_seen', 'true');
                }, 500);
            }
        }

        function closeGuidelinesWelcomeModalAndScroll() {
            closeGuidelinesWelcomeModal();
            setTimeout(() => {
                const guidelinesSection = document.querySelector('[id*="guidelinesContent"]')?.closest('[class*="rounded"]');
                if (guidelinesSection) {
                    guidelinesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function() {
            showGuidelinesWelcomeModal();

            // Close modal on background click
            const welcomeModal = document.getElementById('guidelines-welcome-modal');
            if (welcomeModal) {
                welcomeModal.addEventListener('click', function(e) {
                    if (e.target === this) closeGuidelinesWelcomeModal();
                });
            }

            // Close modal on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeGuidelinesWelcomeModal();
            });
        });
    </script>
    @endpush
</div>
@endsection
