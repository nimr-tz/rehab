@extends('layouts.app')

@section('title', 'Admin - Edit Abstract')

@push('styles')
<style>
/* Enhanced form styling for admin */
.admin-form-container {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.form-section {
    @apply bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-lg border border-white/20 dark:border-gray-700/50 p-6 shadow-sm;
}

.admin-badge {
    @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300;
}

.status-badge {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
}

.status-submitted { @apply bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300; }
.status-under_review { @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300; }
.status-accepted { @apply bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300; }
.status-rejected { @apply bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300; }
.status-revision { @apply bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300; }

/* Quill Editor Styles */
#quill-editor {
    font-size: 0.95rem;
    line-height: 1.8;
    background: transparent;
    border: none !important;
}

#quill-editor .ql-editor {
    min-height: 320px !important;
    padding: 1.5rem;
    cursor: text;
}

.ql-toolbar.ql-snow {
    border: none !important;
    border-bottom: 1px solid #e2e8f0 !important;
    background: #f8fafc;
    border-radius: 1.5rem 1.5rem 0 0;
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
}

#editor-container.is-active {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
}
</style>
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
@endpush

@section('content')
<div class="max-w-4xl mx-auto pt-8 pb-32 px-4">
    <!-- Header -->
    <div class="admin-form-container rounded-xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Abstract
                    <span class="admin-badge">Admin Mode</span>
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Administrative editing of abstract submission
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="status-badge status-{{ $abstract->status }}">
                    {{ ucfirst(str_replace('_', ' ', $abstract->status)) }}
                </span>
                <a href="{{ route('admin.abstracts.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Abstracts
                </a>
            </div>
        </div>

        <!-- Abstract Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">Author:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $abstract->user->name }}</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 3v2m-6 8h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">{{ $abstract->conference_code ? 'Code:' : 'ID:' }}</span>
                <span class="font-mono text-gray-900 dark:text-white">{{ $abstract->conference_code ?: '#' . $abstract->id }}</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">Submitted:</span>
                <span class="text-gray-900 dark:text-white">{{ $abstract->created_at->format('M d, Y') }}</span>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-6 py-4 rounded-lg mb-6">
            <div class="flex items-center mb-2">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h4 class="font-medium">Please correct the following errors:</h4>
            </div>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Admin Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="{{ route('admin.abstracts.update', $abstract) }}" id="abstractForm">
            @csrf
            @method('PUT')

            <div class="p-8 md:p-12 space-y-12">

                <!-- Section: Base Information -->
                <section>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label for="author_name" class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">
                                Author Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="author_name" id="author_name" required
                                value="{{ old('author_name', $abstract->author_name) }}"
                                class="block w-full rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-sm font-medium">
                        </div>

                        <div>
                            <label for="author_institute" class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">
                                Institution/Affiliation <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="author_institute" id="author_institute" required
                                value="{{ old('author_institute', $abstract->author_institute) }}"
                                class="block w-full rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-sm font-medium">
                        </div>
                    </div>

                    <div class="mt-8">
                        <label for="conference_code" class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">
                            Conference Code
                        </label>
                        <input type="text" name="conference_code" id="conference_code"
                            value="{{ old('conference_code', $abstract->conference_code) }}"
                            class="block w-full md:w-64 rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-sm font-mono font-bold uppercase"
                            placeholder="e.g. HSS-001">
                    </div>
                </section>

                <div class="h-px bg-slate-100 dark:bg-gray-700/50"></div>

                <!-- Section: Co-Authors -->
                @php
                    $existingCoauthors = old('coauthors', $abstract->coauthors ?? []);
                    $existingCoauthors = is_array($existingCoauthors) ? $existingCoauthors : [];
                @endphp
                <section>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Co-Authors</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Add, remove, or correct co-author names and affiliations.</p>
                        </div>
                        <button type="button" id="addCoauthorBtn"
                                class="ml-auto inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-bold transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Co-Author
                        </button>
                    </div>

                    <div id="coauthors-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($existingCoauthors as $i => $coauthor)
                        <div class="coauthor-card bg-slate-50 dark:bg-gray-700/40 rounded-2xl border border-slate-200 dark:border-gray-600 p-5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Co-Author {{ $i + 1 }}</span>
                                <button type="button" onclick="this.closest('.coauthor-card').remove(); updateCoauthorNumbers();"
                                        class="text-rose-400 hover:text-rose-600 transition-colors p-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="space-y-3">
                                <input type="text" name="coauthors[{{ $i }}][name]"
                                       value="{{ $coauthor['name'] ?? '' }}"
                                       placeholder="Full name"
                                       class="block w-full rounded-xl border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                                <input type="text" name="coauthors[{{ $i }}][institute]"
                                       value="{{ $coauthor['institute'] ?? '' }}"
                                       placeholder="Institution / Affiliation"
                                       class="block w-full rounded-xl border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @if(empty($existingCoauthors))
                    <div id="no-coauthors-msg" class="flex flex-col items-center justify-center py-10 rounded-2xl border-2 border-dashed border-purple-200 dark:border-purple-800 bg-purple-50/40 dark:bg-purple-900/10 gap-4">
                        <svg class="w-10 h-10 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-sm text-slate-500 dark:text-slate-400">No co-authors on this abstract yet.</p>
                        <button type="button" onclick="document.getElementById('addCoauthorBtn').click()"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-bold transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Co-Author
                        </button>
                    </div>
                    @else
                    <div id="no-coauthors-msg" class="hidden flex flex-col items-center justify-center py-10 rounded-2xl border-2 border-dashed border-purple-200 dark:border-purple-800 bg-purple-50/40 dark:bg-purple-900/10 gap-4">
                        <p class="text-sm text-slate-500 dark:text-slate-400">No co-authors on this abstract yet.</p>
                        <button type="button" onclick="document.getElementById('addCoauthorBtn').click()"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-bold transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Co-Author
                        </button>
                    </div>
                    @endif
                </section>

                <div class="h-px bg-slate-100 dark:bg-gray-700/50"></div>

                <!-- Section: Abstract Details -->
                <section>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Abstract Content</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Review or modify the submission's core content.</p>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div>
                            <label for="title" class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">
                                Research Title <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" required
                                value="{{ old('title', $abstract->title) }}"
                                class="block w-full rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-sm font-bold">
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <label for="description" class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                    Abstract Body <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-xs font-black px-3 py-1 rounded-full bg-white dark:bg-gray-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-gray-600">
                                    <span id="wordCount">0</span> / 300 words
                                </span>
                            </div>

                            <input type="hidden" name="description" id="description" value="{{ old('description', $abstract->description) }}">
                            <div id="editor-container" class="rounded-3xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 overflow-hidden border transition-all">
                                <div id="quill-editor">
                                    {!! old('description', $abstract->description) !!}
                                </div>
                            </div>

                            <div class="flex justify-between items-center mt-3 px-2">
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 bg-slate-100 dark:bg-gray-900/50 px-2 py-1 rounded-md">
                                    ID: <span class="text-slate-900 dark:text-white ml-1">#{{ $abstract->id }}</span>
                                </div>
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 bg-slate-100 dark:bg-gray-900/50 px-2 py-1 rounded-md">
                                    Chars: <span id="charCount" class="text-slate-900 dark:text-white ml-1">0</span> / 3000
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="subtheme" class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">
                                Research Subtheme <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative group">
                                <select name="subtheme" id="subtheme" required
                                    class="block w-full rounded-2xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white px-5 py-4 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-sm appearance-none font-medium">
                                    <option value="">Select subtheme</option>
                                    @foreach(\App\Support\ConferenceTopics::names() as $topic)
                                        <option value="{{ $topic }}" {{ old('subtheme', $abstract->subtheme) == $topic ? 'selected' : '' }}>{{ $topic }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-5 text-slate-400 group-hover:text-amber-500 transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            <!-- Preamble Div -->
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
                    </div>
                </section>

                <div class="h-px bg-slate-100 dark:bg-gray-700/50"></div>

                <!-- Section: Admin Privileges -->
                <section>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-sm border border-amber-500/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Administrative Control</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Restricted actions for submission management.</p>
                        </div>
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-3xl p-6 flex flex-col md:flex-row items-center gap-6">
                        <div class="w-16 h-16 rounded-2xl bg-white dark:bg-gray-800 flex items-center justify-center shadow-lg border border-amber-200 dark:border-amber-800 flex-shrink-0 font-black text-amber-600 text-xl">
                            !
                        </div>
                        <div class="text-center md:text-left">
                            <p class="text-base font-black text-amber-900 dark:text-amber-100 leading-tight mb-1">Administrative Submission Authority</p>
                            <p class="text-sm text-amber-700/80 dark:text-amber-300/80 font-medium">You are overriding the author's original input. Any changes made here are permanent and will be logged as system actions.</p>
                        </div>
                    </div>
                </section>
            </div>

        <!-- Floating Action Bar (Card Style) -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-amber-100 dark:border-amber-900/30 p-6 mt-8 mb-12 transform transition-all hover:scale-[1.005]">
            <div class="flex items-center justify-between gap-6">
                <a href="{{ route('admin.abstracts.index') }}"
                   class="inline-flex items-center px-8 py-4 border border-slate-200 dark:border-gray-600 rounded-2xl text-slate-600 dark:text-slate-300 bg-white dark:bg-gray-800 hover:bg-slate-50 dark:hover:bg-gray-700 font-black transition-all text-xs uppercase tracking-widest hover:border-slate-300 dark:hover:border-gray-500">
                    Cancel
                </a>

                <div class="flex items-center gap-4">
                    <button type="button" id="previewBtn"
                            class="hidden md:inline-flex items-center px-8 py-4 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 border border-blue-100 dark:border-blue-800/50 rounded-2xl hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-all duration-200 font-black text-xs uppercase tracking-widest">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Preview
                    </button>

                    <button type="submit"
                            class="inline-flex items-center px-10 py-4 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-2xl font-black hover:from-amber-500 hover:to-orange-500 transition-all duration-200 shadow-xl shadow-amber-200 dark:shadow-amber-900/20 text-sm uppercase tracking-widest hover:-translate-y-0.5 active:translate-y-0 text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update Abstract
                    </button>
                </div>
            </div>
        </div>
    </form>
    </div>
</div>

@push('scripts')
<script>
// Co-author dynamic add/remove
(function() {
    var index = document.getElementById('coauthors-list')
                    ? document.getElementById('coauthors-list').children.length
                    : 0;

    function syncVisibility() {
        var list = document.getElementById('coauthors-list');
        var msg  = document.getElementById('no-coauthors-msg');
        if (!list || !msg) return;
        msg.classList.toggle('hidden', list.children.length > 0);
    }

    window.updateCoauthorNumbers = function() {
        document.querySelectorAll('.coauthor-card').forEach(function(card, i) {
            var label = card.querySelector('span');
            if (label) label.textContent = 'Co-Author ' + (i + 1);
            card.querySelectorAll('input').forEach(function(inp) {
                inp.name = inp.name.replace(/coauthors\[\d+\]/, 'coauthors[' + i + ']');
            });
        });
        syncVisibility();
    };

    document.addEventListener('DOMContentLoaded', function() {
        syncVisibility();

        document.getElementById('addCoauthorBtn').addEventListener('click', function() {
            var list = document.getElementById('coauthors-list');
            var card = document.createElement('div');
            card.className = 'coauthor-card bg-slate-50 dark:bg-gray-700/40 rounded-2xl border border-slate-200 dark:border-gray-600 p-5';
            card.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Co-Author ${list.children.length + 1}</span>
                    <button type="button" onclick="this.closest('.coauthor-card').remove(); updateCoauthorNumbers();"
                            class="text-rose-400 hover:text-rose-600 transition-colors p-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-3">
                    <input type="text" name="coauthors[${index}][name]" placeholder="Full name"
                           class="block w-full rounded-xl border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                    <input type="text" name="coauthors[${index}][institute]" placeholder="Institution / Affiliation"
                           class="block w-full rounded-xl border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                </div>`;
            list.appendChild(card);
            index++;
            syncVisibility();
        });
    });
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize word count from start
    const initialText = document.getElementById('description').value;
    updateWordStats(initialText);

    function getPlainTextForWordCount(value) {
        const text = String(value || '');

        if (/<[a-z][\s\S]*>/i.test(text)) {
            const parser = document.createElement('div');
            parser.innerHTML = text;
            return parser.textContent || parser.innerText || '';
        }

        return text;
    }

    function countAbstractWords(value) {
        const cleanText = getPlainTextForWordCount(value)
            .replace(/\u00a0/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        return cleanText === '' ? 0 : cleanText.split(/\s+/).length;
    }

    function updateWordStats(text) {
        const words = countAbstractWords(text);
        const chars = text.length;

        document.getElementById('wordCount').textContent = words;
        document.getElementById('charCount').textContent = chars;

        const wordCountEl = document.getElementById('wordCount');
        if (words > 300) {
            wordCountEl.className = 'text-red-500 font-black';
        } else if (words > 250) {
            wordCountEl.className = 'text-amber-500 font-black';
        } else {
            wordCountEl.className = 'text-slate-900 dark:text-white';
        }
    }

    window.updateWordStats = updateWordStats;

    // Form validation
    document.getElementById('abstractForm').addEventListener('submit', function(e) {
        const quill = window.adminAbstractEditor;
        const descriptionInput = document.getElementById('description');
        const description = quill ? quill.getText() : descriptionInput.value;
        const words = countAbstractWords(description);

        if (quill) {
            const html = quill.root.innerHTML;
            descriptionInput.value = html === '<p><br></p>' ? '' : html;
        }

        updateWordStats(description);

        if (words > 300) {
            e.preventDefault();
            alert('Description exceeds 300 words limit. Please reduce the word count.');
            return false;
        }
    });

    // Preview functionality
    document.getElementById('previewBtn').addEventListener('click', function() {
        // You can implement a modal preview here
        alert('Preview functionality can be implemented based on your requirements.');
    });

    // Auto-save draft (optional)
    let autoSaveTimeout;
    const formInputs = document.querySelectorAll('#abstractForm input, #abstractForm textarea, #abstractForm select');

    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                // Implement auto-save logic here if needed
                console.log('Auto-saving draft...');
            }, 2000);
        });
    });
    // Preamble Logic
    const preambles = @json(\App\Support\ConferenceTopics::descriptions());

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
        updatePreamble();
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Quill === 'undefined') return;

    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Enter abstract content...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'script': 'sub' }, { 'script': 'super' }],
                ['clean']
            ]
        }
    });

    const container = document.getElementById('editor-container');
    const descriptionInput = document.getElementById('description');
    window.adminAbstractEditor = quill;

    quill.on('selection-change', function(range) {
        if (range) container.classList.add('is-active');
        else container.classList.remove('is-active');
    });

    quill.on('text-change', function() {
        const html = quill.root.innerHTML;
        descriptionInput.value = html === '<p><br></p>' ? '' : html;
        window.updateWordStats(quill.getText().trim());
    });

    // Initial sync
    window.updateWordStats(quill.getText().trim());
});
</script>
@endpush
@endsection
