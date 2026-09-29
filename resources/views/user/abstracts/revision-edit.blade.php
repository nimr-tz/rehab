@extends('layouts.app')

@section('title', 'Revise Abstract')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<style>
    #quill-editor {
        font-size: 0.9rem;
        line-height: 1.8;
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
    <!-- Refined Professional Header -->
    <div class="relative bg-gradient-to-br from-indigo-800 via-indigo-900 to-slate-900 py-12 rounded-b-[3rem] shadow-xl overflow-hidden mb-8">
        <!-- Abstract Precision Background -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.03]"></div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="space-y-2 text-center md:text-left">
                    <h1 class="text-3xl md:text-5xl font-black text-white leading-none tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        Revise <span class="text-indigo-300">Abstract.</span>
                    </h1>
                    <p class="text-base text-indigo-100/60 font-medium tracking-wide">
                        Round {{ $abstract->revision_round ?? 1 }} - Addressing feedback for submission
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ route('user.dashboard') }}"
                       class="px-5 py-2.5 bg-white/5 backdrop-blur-md border border-white/10 text-white font-bold rounded-xl hover:bg-white/10 transition-all text-[10px] uppercase tracking-widest">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

<div class="max-w-7xl mx-auto px-4 pb-32">
    <div class="bg-amber-50 dark:bg-amber-900/10 border-l-4 border-amber-500 p-6 mb-8 rounded-xl border border-amber-200 dark:border-amber-800">
        <h2 class="text-xl font-bold mb-3 text-amber-900 dark:text-amber-100 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Revision Requested
        </h2>
        <div class="mb-4">
            <strong class="text-xs uppercase tracking-wider text-amber-800/60 dark:text-amber-200/40 font-black block mb-1">Reviewer/Admin Feedback:</strong>
            <div class="text-slate-700 dark:text-slate-300 whitespace-pre-line bg-white/50 dark:bg-black/20 p-4 rounded-lg border border-amber-200/50 dark:border-amber-800/30 leading-relaxed">{{ $abstract->revision_feedback ?? $abstract->admin_comment }}</div>
        </div>
        <div class="flex items-center justify-between text-xs font-bold text-amber-700 dark:text-amber-300/60">
            <span class="uppercase tracking-widest">Revision Round: {{ $abstract->revision_round ?? 1 }}</span>
            <span class="opacity-60">Requested: {{ $abstract->revision_requested_at ? $abstract->revision_requested_at->format('M d, Y') : 'N/A' }}</span>
        </div>
    </div>
    <div class="bg-gray-50 border border-gray-200 p-4 mb-8 rounded-xl">
        <h3 class="text-lg font-semibold mb-2 text-gray-700">Reviewer Comments</h3>
        @php
            // Only show submitted reviews, not draft ones
            $submittedReviews = $abstract->reviews->where('status', 'submitted');
        @endphp
        @if($submittedReviews && $submittedReviews->count() > 0)
            <ul class="space-y-4">
                @foreach($submittedReviews as $review)
                    <li class="p-4 rounded shadow-sm @if($loop->iteration == 1) bg-blue-50 border-l-4 border-blue-400 @elseif($loop->iteration == 2) bg-green-50 border-l-4 border-green-400 @else bg-purple-50 border-l-4 border-purple-400 @endif">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold text-gray-800">Reviewer{{ $loop->iteration }}:</span>
                        </div>
                        <div class="text-gray-700 mb-1"><strong>Score:</strong> {{ $review->score ?? 'N/A' }}</div>
                        <div class="text-gray-700"><strong>Comments:</strong> <span class="whitespace-pre-line">{{ $review->comments ?? 'No comments provided.' }}</span></div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="text-gray-500">No reviewer comments available yet.</div>
        @endif
    </div>
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl mb-6">
            <h3 class="font-semibold mb-2">Please correct the following errors:</h3>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Main Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="{{ route('abstracts.revision.submit', $abstract->id) }}" enctype="multipart/form-data">
            @csrf

            <div class="p-8 md:p-12 space-y-12">

                <!-- Section: Revision Notes -->
                <section>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center text-white shadow-lg shadow-amber-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Revision Response</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Explain how you addressed the feedback</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label for="author_response" class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">
                            Author's Notes To Reviewers <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="author_response" id="author_response" rows="6" required
                            placeholder="Please provide a detailed response explaining how you addressed each point of feedback..."
                            class="block w-full rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-900/50 text-slate-900 dark:text-white px-6 py-5 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-sm font-medium leading-relaxed">{{ old('author_response') }}</textarea>
                        <div class="flex items-center gap-2 px-2 bg-amber-50/50 dark:bg-amber-900/10 p-3 rounded-xl border border-amber-100/50 dark:border-amber-800/30">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-xs font-bold text-amber-700/80 dark:text-amber-200/60">Your response should clearly describe the changes made to the manuscript.</p>
                        </div>
                        @error('author_response')
                            <div class="text-rose-600 text-xs font-black mt-2 bg-rose-50 dark:bg-rose-900/10 p-3 rounded-xl border border-rose-100 dark:border-rose-900/20 italic">{{ $message }}</div>
                        @enderror
                    </div>
                </section>

                <div class="h-px bg-slate-100 dark:bg-gray-700/50"></div>

                <!-- Section: Author Information -->
                <section>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Author Information</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Primary contact and institutional details</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label for="author_name" class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">
                                Full Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="author_name" id="author_name" required
                                value="{{ old('author_name', $abstract->author_name) }}"
                                class="block w-full rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-author-500/10 focus:border-author-500 transition-all text-sm font-medium">
                        </div>

                        <div>
                            <label for="author_institute" class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">
                                Institution/Affiliation <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="author_institute" id="author_institute" required
                                value="{{ old('author_institute', $abstract->author_institute) }}"
                                class="block w-full rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-author-500/10 focus:border-author-500 transition-all text-sm font-medium">
                        </div>
                    </div>
                </section>

                <div class="h-px bg-slate-100 dark:bg-gray-700/50"></div>

                <!-- Section: Abstract Details -->
                <section>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-600/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Revised Abstract Content</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Provide the updated version of your abstract</p>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div>
                            <label for="title" class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">
                                Research Title <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" required
                                value="{{ old('title', $abstract->title) }}"
                                class="block w-full rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-author-500/10 focus:border-author-500 transition-all text-sm font-bold">
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <label for="quill-editor" class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider cursor-pointer">
                                    Abstract Body <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-xs font-black px-3 py-1 rounded-full bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-gray-600">
                                    <span id="wordCount">0</span> / 300 words
                                </span>
                            </div>

                            <div id="editor-container" class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-600 overflow-hidden shadow-inner transition-all duration-300 relative">
                                <div id="quill-editor" class="min-h-[320px]">{!! old('description', $abstract->description) !!}</div>
                            </div>
                            <input type="hidden" name="description" id="description" value="{{ old('description', $abstract->description) }}">

                            <p id="wordWarning" class="mt-3 text-xs font-black text-rose-600 hidden flex items-center bg-rose-50 dark:bg-rose-900/20 p-3 rounded-xl border border-rose-100 dark:border-rose-900/30">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.206 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                WORD LIMIT EXCEEDED! Please condense your abstract to under 300 words.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label for="subtheme" class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">
                                    Research Subtheme <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative group">
                                    <select name="subtheme" id="subtheme" required
                                        class="block w-full rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-author-500/10 focus:border-author-500 transition-all text-sm appearance-none font-medium">
                                        <option value="">Select subtheme</option>
                                    @foreach(\App\Support\ConferenceTopics::names() as $topic)
                                        <option value="{{ $topic }}" {{ old('subtheme', $abstract->subtheme) == $topic ? 'selected' : '' }}>{{ $topic }}</option>
                                    @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-5 text-slate-400 group-hover:text-author-500 transition-colors">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                <div id="subtheme-preamble" class="mt-4 p-6 bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-100/50 dark:border-indigo-800/30 rounded-2xl hidden animate-fade-in shadow-sm">
                                    <div class="flex gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-800/50 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 id="preamble-header" class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-1.5 underline underline-offset-4 decoration-2">Scope Guideline</h4>
                                            <p id="preamble-text" class="text-sm text-indigo-900/70 dark:text-indigo-200/70 leading-relaxed font-bold italic"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="presentation_mode" class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">
                                    Presentation Preference <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative group">
                                    <select name="presentation_mode" id="presentation_mode" required
                                        class="block w-full rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-author-500/10 focus:border-author-500 transition-all text-sm appearance-none font-medium">
                                        <option value="">Select preference</option>
                                        <option value="Oral" {{ old('presentation_mode', $abstract->presentation_mode) == 'Oral' ? 'selected' : '' }}>Oral Presentation</option>
                                        <option value="Poster" {{ old('presentation_mode', $abstract->presentation_mode) == 'Poster' ? 'selected' : '' }}>Poster Presentation</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-5 text-slate-400 group-hover:text-author-500 transition-colors">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="h-px bg-slate-100 dark:bg-gray-700/50"></div>

                <!-- Section: Co-authors -->
                <section>
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-purple-600 flex items-center justify-center text-white shadow-lg shadow-purple-600/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Co-authors <span class="text-slate-400 font-medium font-sans text-[10px] ml-1 lowercase">(Optional)</span></h2>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Update contributing researchers</p>
                            </div>
                        </div>
                        <button type="button" onclick="addCoauthor()"
                            class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 font-bold transition-all text-[11px] uppercase tracking-wider shadow-md shadow-purple-200 dark:shadow-none">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add
                        </button>
                    </div>

                    <div id="coauthors-list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @php
                            $coauthors = is_string($abstract->coauthors) ? json_decode($abstract->coauthors, true) ?? [] : ($abstract->coauthors ?? []);
                            $coauthors = is_array($coauthors) ? $coauthors : [];
                        @endphp
                        @foreach($coauthors as $i => $coauthor)
                            <div class="relative group p-6 bg-slate-50 dark:bg-gray-900/50 rounded-3xl border border-slate-100 dark:border-gray-700">
                                <button type="button" onclick="this.parentElement.remove()"
                                    class="absolute -top-2 -right-2 w-8 h-8 bg-white dark:bg-gray-800 text-rose-500 rounded-full shadow-lg border border-slate-100 dark:border-gray-700 flex items-center justify-center hover:bg-rose-50 transition-colors z-10">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                                <div class="space-y-4">
                                    <input type="text" name="coauthors[{{ $i }}][name]" placeholder="Full Name" value="{{ $coauthor['name'] ?? '' }}"
                                        class="block w-full rounded-xl border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-900 dark:text-white px-4 py-3 focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all text-sm font-bold">
                                    <input type="text" name="coauthors[{{ $i }}][institute]" placeholder="Institution" value="{{ $coauthor['institute'] ?? '' }}"
                                        class="block w-full rounded-xl border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-900 dark:text-white px-4 py-3 focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all text-sm font-medium">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="no-coauthors-msg" class="text-center py-12 bg-slate-50/30 dark:bg-gray-900/20 rounded-[2rem] border border-dashed border-slate-200 dark:border-gray-700 {{ count($coauthors) > 0 ? 'hidden' : '' }}">
                        <p class="text-slate-400 dark:text-slate-400 text-sm font-medium italic">Click "Add Colleague" to include co-authors.</p>
                    </div>
                </section>

                <div class="h-px bg-slate-100 dark:bg-gray-700/50"></div>

                <!-- Section: Conference Options -->
                <section>
                    <label class="group cursor-pointer relative flex items-start p-6 bg-slate-50 dark:bg-gray-900/30 border border-slate-100 dark:border-gray-700 rounded-3xl hover:border-author-200 dark:hover:border-author-900/30 transition-all">
                        <div class="flex items-center h-6">
                            <input type="hidden" name="include_in_proceedings" value="0">
                            <input type="checkbox" name="include_in_proceedings" id="include_in_proceedings" value="1"
                                {{ old('include_in_proceedings', $abstract->include_in_proceedings) ? 'checked' : '' }}
                                class="rounded-lg border-slate-300 text-author-600 shadow-sm focus:ring-4 focus:ring-author-500/10 h-6 w-6 transition-all">
                        </div>
                        <div class="ml-4">
                            <span class="block text-base font-black text-slate-900 dark:text-white group-hover:text-author-600 transition-colors tracking-tight">Include in Official Conference Proceedings</span>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">Your revised abstract will be professionally indexed and published if accepted.</p>
                        </div>
                    </label>
                </section>
            </div>

            <!-- Refined Action Bar -->
            <div class="bg-slate-50 dark:bg-gray-900/30 border border-slate-200 dark:border-gray-800 rounded-3xl p-6 mt-12 mb-12">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    <a href="{{ route('user.dashboard') }}"
                        class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 font-bold transition-all text-xs uppercase tracking-widest px-4 py-2">
                        ← Back to Dashboard
                    </a>

                    <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                        <button type="submit"
                                class="w-full sm:w-auto px-10 py-3.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 dark:shadow-none transition-all text-xs uppercase tracking-widest">
                            Submit Final Revision
                        </button>
                    </div>
                </div>
            </div>
        </form>
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

        // Preamble Logic
        const preambles = @json(\App\Support\ConferenceTopics::descriptions());

        document.addEventListener('DOMContentLoaded', function() {
            const subthemeSelect = document.getElementById('subtheme');
            const preambleContainer = document.getElementById('subtheme-preamble');
            const preambleText = document.getElementById('preamble-text');

            function updatePreamble() {
                if (!subthemeSelect || !preambleContainer || !preambleText) return;
                const selected = subthemeSelect.value;
                if (preambles[selected]) {
                    preambleText.textContent = preambles[selected];
                    preambleContainer.classList.remove('hidden');
                } else {
                    preambleContainer.classList.add('hidden');
                }
            }

            if (subthemeSelect) {
                subthemeSelect.addEventListener('change', updatePreamble);
                // Initialize if value exists
                updatePreamble();
            }
        });

        // Co-author dynamic fields
        let coauthorIndex = {{ count($coauthors) }};
        function addCoauthor(name = '', institute = '') {
            const list = document.getElementById('coauthors-list');
            const div = document.createElement('div');
            div.className = "relative group p-6 bg-slate-50 dark:bg-gray-900/50 rounded-3xl border border-slate-100 dark:border-gray-700 animate-fade-in-up";
            div.innerHTML = `
                <button type="button" onclick="this.parentElement.remove()"
                    class="absolute -top-2 -right-2 w-8 h-8 bg-white dark:bg-gray-800 text-rose-500 rounded-full shadow-lg border border-slate-100 dark:border-gray-700 flex items-center justify-center hover:bg-rose-50 transition-colors z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <div class="space-y-4">
                    <input type="text" name="coauthors[${coauthorIndex}][name]" placeholder="Full Name" value="${name}"
                        class="block w-full rounded-xl border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-900 dark:text-white px-4 py-3 focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all text-sm font-bold">
                    <input type="text" name="coauthors[${coauthorIndex}][institute]" placeholder="Institution" value="${institute}"
                        class="block w-full rounded-xl border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-900 dark:text-white px-4 py-3 focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all text-sm font-medium">
                </div>
            `;
            list.appendChild(div);
            coauthorIndex++;
            if (document.getElementById('no-coauthors-msg')) {
                document.getElementById('no-coauthors-msg').classList.add('hidden');
            }
        }
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
                    placeholder: 'Enter your revised abstract content here...',
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
            } catch (error) {
                console.error('Failed to initialize Quill:', error);
            }
        });
    </script>
    @endpush
@endsection
