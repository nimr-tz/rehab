@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="space-y-8">
    {{-- Header Section --}}
    <div class="relative bg-gradient-to-br from-admin-600 via-admin-700 to-indigo-950 rounded-3xl p-8 text-white shadow-2xl z-20">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path d="M0 100 C 20 0 50 0 100 100 Z" fill="currentColor"></path>
            </svg>
        </div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <nav class="flex mb-4 text-blue-200/80 text-sm font-medium" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                                Dashboard
                             </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-blue-200/40" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                                <span class="ml-1 md:ml-2">Presentations</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-4xl font-extrabold tracking-tight mb-2 flex items-center gap-3">
                    <span class="p-3 bg-white/10 rounded-2xl backdrop-blur-md">🎤</span>
                    Presentation Management
                </h1>
                <p class="text-blue-100 text-lg opacity-90 max-w-2xl font-light">
                    Track and manage presentation materials for accepted abstracts.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <div class="px-6 py-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/10 text-center min-w-[120px]">
                    <span class="block text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Submitted</span>
                    <span class="text-3xl font-black text-white">{{ $stats['uploaded'] }}</span>
                </div>
                <div class="px-6 py-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/10 text-center min-w-[120px]">
                    <span class="block text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Completion</span>
                    <span class="text-3xl font-black text-white">{{ $stats['total'] > 0 ? round(($stats['uploaded'] / $stats['total']) * 100) : 0 }}%</span>
                </div>

                {{-- Bulk Download Dropdown (queues a background ZIP build) --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" type="button"
                            class="px-6 py-4 bg-emerald-500/20 hover:bg-emerald-500/30 backdrop-blur-md rounded-2xl border border-emerald-400/30 transition-all flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <div class="text-left">
                            <span class="block text-emerald-200 text-xs font-bold uppercase tracking-wider">Prepare</span>
                            <span class="text-sm font-bold text-white">Download ZIP</span>
                        </div>
                        <svg class="w-4 h-4 text-emerald-300 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-slate-200 dark:border-gray-700 py-2 z-[100] max-h-96 overflow-y-auto">
                        <div class="px-4 py-2 border-b border-slate-100 dark:border-gray-700">
                            <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">By Type</p>
                        </div>
                        <button type="button" data-download-url="{{ route('admin.presentations.download-all', ['mode' => 'all']) }}"
                           class="js-queue-download w-full text-left flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors">
                            <span class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400">📦</span>
                            All Presentations
                        </button>
                        <button type="button" data-download-url="{{ route('admin.presentations.download-all', ['mode' => 'Oral']) }}"
                           class="js-queue-download w-full text-left flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors">
                            <span class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center text-blue-600 dark:text-blue-400">🎤</span>
                            Oral Only
                        </button>
                        <button type="button" data-download-url="{{ route('admin.presentations.download-all', ['mode' => 'Poster']) }}"
                           class="js-queue-download w-full text-left flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors">
                            <span class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center text-emerald-600 dark:text-emerald-400">📊</span>
                            Posters Only
                        </button>

                        @if(!empty($conferenceDays))
                            <div class="px-4 py-2 border-b border-t border-slate-100 dark:border-gray-700 mt-1">
                                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">By Day</p>
                            </div>
                            @foreach($conferenceDays as $day)
                                <button type="button" data-download-url="{{ route('admin.conference-program.download-day-presentations', $day['number']) }}"
                                   class="js-queue-download w-full text-left flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors">
                                    <span class="w-8 h-8 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center text-amber-600 dark:text-amber-400 font-bold">{{ $day['number'] }}</span>
                                    <span class="leading-tight">Day {{ $day['number'] }}<span class="block text-xs text-slate-400 font-normal">{{ $day['label'] }}</span></span>
                                </button>
                            @endforeach
                        @endif

                        <p class="px-4 pt-2 text-[11px] text-slate-400 dark:text-gray-500 leading-snug">
                            Files are zipped in the background. Grab them from the Downloads panel below when ready.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Background Downloads Panel --}}
    <div id="downloadsPanel"
         class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-slate-200 dark:border-gray-700 p-6 hidden"
         data-jobs-url="{{ route('admin.presentations.download-jobs') }}"
         data-file-url-template="{{ route('admin.presentations.download-file', ['jobId' => '__ID__']) }}"
         data-cancel-url-template="{{ route('admin.presentations.cancel-job', ['jobId' => '__ID__']) }}">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400">⬇️</span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Downloads</h2>
                    <p class="text-xs text-slate-500 dark:text-gray-400">ZIP files prepared in the background. Links stay live for 48 hours.</p>
                </div>
            </div>
            <span id="downloadsSpinner" class="hidden text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                Working…
            </span>
        </div>
        <div id="downloadsList" class="space-y-2"></div>
    </div>

    {{-- Filters & Search --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-slate-200 dark:border-gray-700 p-6">
        <form method="GET" action="{{ route('admin.abstracts.presentations') }}" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Search Abstracts</label>
                <div class="relative">
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           placeholder="Search by title, author, or code..."
                           class="w-full pl-10 pr-4 py-3 rounded-xl border-slate-200 dark:border-gray-600 dark:bg-gray-700 focus:ring-admin-500 focus:border-admin-500 transition-all">
                    <svg class="absolute left-3 top-3.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <div>
                <label for="presentation_status" class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Upload Status</label>
                <select name="presentation_status" id="presentation_status" onchange="this.form.submit()"
                        class="w-full py-3 rounded-xl border-slate-200 dark:border-gray-600 dark:bg-gray-700 focus:ring-admin-500 focus:border-admin-500 transition-all">
                    <option value="all" {{ request('presentation_status', 'all') === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="submitted" {{ request('presentation_status') === 'submitted' ? 'selected' : '' }}>Uploaded</option>
                    <option value="pending" {{ request('presentation_status') === 'pending' ? 'selected' : '' }}>Pending Upload</option>
                </select>
            </div>

            <div>
                <label for="mode" class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Presentation Mode</label>
                <select name="mode" id="mode" onchange="this.form.submit()"
                        class="w-full py-3 rounded-xl border-slate-200 dark:border-gray-600 dark:bg-gray-700 focus:ring-admin-500 focus:border-admin-500 transition-all">
                    <option value="">All Modes</option>
                    <option value="Oral" {{ request('mode') === 'Oral' ? 'selected' : '' }}>Oral</option>
                    <option value="Poster" {{ request('mode') === 'Poster' ? 'selected' : '' }}>Poster</option>
                    <option value="Audio Poster" {{ request('mode') === 'Audio Poster' ? 'selected' : '' }}>Audio Poster</option>
                </select>
            </div>

            @if(request()->hasAny(['search', 'presentation_status', 'mode']))
                <div class="md:col-span-4 flex justify-end">
                    <a href="{{ route('admin.abstracts.presentations') }}" class="text-sm font-medium text-admin-600 hover:text-admin-800 transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear all filters
                    </a>
                </div>
            @endif
        </form>
    </div>

    {{-- Presentations Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-slate-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-gray-700">
                <thead class="bg-slate-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'conference_code', 'sort_order' => request('sort_by') === 'conference_code' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-admin-600 transition-colors">
                                Code
                                @if(request('sort_by', 'conference_code') === 'conference_code')
                                    <svg class="w-3 h-3 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'title', 'sort_order' => request('sort_by') === 'title' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-admin-600 transition-colors">
                                Title & Author
                                @if(request('sort_by') === 'title')
                                    <svg class="w-3 h-3 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'presentation_mode', 'sort_order' => request('sort_by') === 'presentation_mode' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-admin-600 transition-colors">
                                Mode
                                @if(request('sort_by') === 'presentation_mode')
                                    <svg class="w-3 h-3 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'presentation_status', 'sort_order' => request('sort_by') === 'presentation_status' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-admin-600 transition-colors">
                                Status
                                @if(request('sort_by') === 'presentation_status')
                                    <svg class="w-3 h-3 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'presentation_uploaded_at', 'sort_order' => request('sort_by') === 'presentation_uploaded_at' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-admin-600 transition-colors">
                                Last Uploaded
                                @if(request('sort_by') === 'presentation_uploaded_at')
                                    <svg class="w-3 h-3 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                @endif
                            </a>
                        </th>
                        <th class="sticky right-0 z-20 px-6 py-4 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-gray-800/95 backdrop-blur shadow-[-10px_0_15px_-5px_rgba(0,0,0,0.05)]">Materials</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-gray-700 align-middle">
                    @forelse($abstracts as $abstract)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-700/50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono font-bold text-admin-600 dark:text-admin-400 bg-admin-50 dark:bg-admin-900/30 px-2 py-1 rounded">
                                    {{ $abstract->conference_code ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs md:max-w-md">
                                    <div class="text-sm font-bold text-slate-900 dark:text-white truncate" title="{{ $abstract->title }}">
                                        {{ $abstract->title }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-gray-400 mt-0.5">
                                        {{ $abstract->author_name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-gray-300">
                                    @if($abstract->presentation_mode === 'Oral')
                                        🎤 Oral
                                    @elseif($abstract->presentation_mode === 'Poster')
                                        📊 Poster
                                    @elseif($abstract->presentation_mode === 'Audio Poster')
                                        🔊 Audio Poster
                                    @else
                                        {{ $abstract->presentation_mode ?? 'Unset' }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($abstract->presentation_status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.9L9.03 9.069a2.25 2.25 0 002.496 0l6.861-4.17a2.25 2.25 0 00-2.496-3.738L9.03 5.331a2.25 2.25 0 00-2.496 0L2.166 1.162a2.25 2.25 0 000 3.738zM21 15.75c0 .414-.336.75-.75.75H3.75a.75.75 0 01-.75-.75V7.412l6.861 4.17a3.75 3.75 0 004.158 0l6.861-4.17v8.338z" clip-rule="evenodd"></path></svg>
                                        Approved
                                    </span>
                                @elseif($abstract->presentation_status === 'uploaded')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                        Uploaded
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs text-slate-500 dark:text-gray-400">
                                    {{ $abstract->presentation_uploaded_at ? $abstract->presentation_uploaded_at->format('M d, Y H:i') : '-' }}
                                </span>
                            </td>
                            <td class="sticky right-0 z-10 px-6 py-4 whitespace-nowrap text-right bg-white/95 dark:bg-gray-800/95 backdrop-blur shadow-[-10px_0_15px_-5px_rgba(0,0,0,0.05)] group-hover:bg-slate-50/95 dark:group-hover:bg-gray-700/95 transition-colors">
                                @php
                                    $presentationFiles = collect([
                                        $abstract->oral_presentation_file ? ['type' => 'oral', 'label' => 'Oral', 'tone' => 'blue'] : null,
                                        $abstract->poster_presentation_file ? ['type' => 'poster', 'label' => 'Poster Video', 'tone' => 'emerald'] : null,
                                        $abstract->audio_poster_file ? ['type' => 'audio', 'label' => 'Audio', 'tone' => 'amber'] : null,
                                        $abstract->audio_poster_poster_file ? ['type' => 'audio_poster_poster', 'label' => 'Poster Visual', 'tone' => 'indigo'] : null,
                                    ])->filter();

                                    $toneClasses = [
                                        'blue' => 'bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60 border-blue-200 dark:border-blue-800',
                                        'emerald' => 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60 border-emerald-200 dark:border-emerald-800',
                                        'amber' => 'bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60 border-amber-200 dark:border-amber-800',
                                        'indigo' => 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60 border-indigo-200 dark:border-indigo-800',
                                    ];
                                @endphp

                                <div class="flex justify-end items-center gap-3">
                                    @foreach($presentationFiles as $file)
                                        <div class="flex items-center rounded-lg border {{ $toneClasses[$file['tone']] }} overflow-hidden text-xs font-bold">
                                            <a href="{{ route('admin.abstracts.preview-presentation', [$abstract, $file['type']]) }}"
                                               target="_blank"
                                               rel="noopener"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 transition-all"
                                               title="Preview {{ $file['label'] }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                {{ $file['label'] }}
                                            </a>
                                            <a href="{{ route('admin.abstracts.download-presentation', [$abstract, $file['type']]) }}"
                                               class="inline-flex items-center px-2.5 py-1.5 border-l border-current/20 transition-all"
                                               title="Download {{ $file['label'] }}">
                                                <svg class="w-4 h-4 transition-transform hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4-4 4m0 0-4-4m4 4V4"/></svg>
                                            </a>
                                        </div>
                                    @endforeach

                                    @if($presentationFiles->isEmpty())
                                        <span class="text-[10px] uppercase tracking-tighter text-slate-400 font-bold bg-slate-100 dark:bg-gray-700 px-2 py-1 rounded-md">Empty</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 bg-slate-100 dark:bg-gray-700 rounded-full text-slate-400">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 17.242L12.001 20l2.829-2.758m-5.658 0c-3.132 0-5.658-2.584-5.658-5.772a5.83 5.83 0 011.01-3.322 5.658 5.658 0 018.665-1.12c.516.48 1.157 1.258 1.933 2.14m3.053 2.302L18.001 14m0 0l-2.829-2.758m2.829 2.758l2.829-2.758M12.001 4v12"/></svg>
                                    </div>
                                    <p class="text-lg font-bold">No presentations found</p>
                                    <p class="text-sm">Try adjusting your filters or search query.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($abstracts->hasPages())
            <div class="px-6 py-4 bg-slate-50 dark:bg-gray-700/30 border-t border-slate-100 dark:border-gray-700">
                {{ $abstracts->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
</div>

<script>
(function () {
    const panel = document.getElementById('downloadsPanel');
    if (!panel) return;

    const list = document.getElementById('downloadsList');
    const spinner = document.getElementById('downloadsSpinner');
    const jobsUrl = panel.dataset.jobsUrl;
    const fileTemplate = panel.dataset.fileUrlTemplate;
    const cancelTemplate = panel.dataset.cancelUrlTemplate;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let pollTimer = null;

    const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    const fmtSize = (bytes) => {
        if (!bytes) return '';
        const mb = bytes / (1024 * 1024);
        return mb >= 1 ? mb.toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    };

    const statusMeta = {
        queued:     { tone: 'bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-gray-300', label: 'Queued' },
        processing: { tone: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', label: 'Preparing…' },
        ready:      { tone: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', label: 'Ready' },
        empty:      { tone: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', label: 'No files' },
        failed:     { tone: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300', label: 'Failed' },
    };

    function render(jobs) {
        if (!jobs.length) {
            panel.classList.add('hidden');
            return;
        }
        panel.classList.remove('hidden');

        const active = jobs.some(j => j.status === 'queued' || j.status === 'processing');
        spinner.classList.toggle('hidden', !active);

        list.innerHTML = jobs.map(job => {
            const meta = statusMeta[job.status] || statusMeta.queued;
            const detail = job.status === 'ready'
                ? `${job.file_count} file${job.file_count == 1 ? '' : 's'}${job.size ? ' · ' + fmtSize(job.size) : ''}`
                : escapeHtml(job.message || '');
            const inFlight = job.status === 'queued' || job.status === 'processing';
            const cancelBtn = `<button type="button" data-cancel-id="${escapeHtml(job.id)}"
                       class="js-cancel-download inline-flex items-center gap-1.5 px-2.5 py-1.5 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 text-xs font-bold rounded-lg transition-colors"
                       title="${inFlight ? 'Cancel this download' : 'Remove from list'}">
                       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                       ${inFlight ? 'Cancel' : 'Dismiss'}
                   </button>`;

            let action;
            if (job.status === 'ready') {
                action = `<a href="${fileTemplate.replace('__ID__', encodeURIComponent(job.id))}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition-colors">
                       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4-4 4m0 0-4-4m4 4V4"/></svg>
                       Download
                   </a>${cancelBtn}`;
            } else if (inFlight) {
                action = `<svg class="w-4 h-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>${cancelBtn}`;
            } else {
                // failed / empty
                action = cancelBtn;
            }

            return `<div class="flex items-center justify-between gap-4 p-3 rounded-xl border border-slate-100 dark:border-gray-700 bg-slate-50/60 dark:bg-gray-700/30">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold ${meta.tone}">${meta.label}</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-gray-100 truncate">${escapeHtml(job.label || 'Download')}</span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-gray-400 mt-0.5 truncate">${detail}</p>
                </div>
                <div class="flex-shrink-0 flex items-center gap-2">${action}</div>
            </div>`;
        }).join('');
    }

    function poll() {
        fetch(jobsUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : { jobs: [] })
            .then(data => {
                const jobs = data.jobs || [];
                render(jobs);
                const active = jobs.some(j => j.status === 'queued' || j.status === 'processing');
                clearTimeout(pollTimer);
                pollTimer = setTimeout(poll, active ? 3000 : 15000);
            })
            .catch(() => {
                clearTimeout(pollTimer);
                pollTimer = setTimeout(poll, 15000);
            });
    }

    // Queue a download via the dropdown buttons, then poll fast.
    document.querySelectorAll('.js-queue-download').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = btn.dataset.downloadUrl;
            btn.disabled = true;
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}) },
            })
                .then(r => r.json().catch(() => ({})))
                .finally(() => {
                    btn.disabled = false;
                    panel.classList.remove('hidden');
                    spinner.classList.remove('hidden');
                    clearTimeout(pollTimer);
                    pollTimer = setTimeout(poll, 600);
                });
        });
    });

    // Cancel an in-flight job or dismiss a finished one (event-delegated).
    list.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-cancel-id]');
        if (!btn) return;
        const id = btn.dataset.cancelId;
        btn.disabled = true;
        fetch(cancelTemplate.replace('__ID__', encodeURIComponent(id)), {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}) },
        })
            .catch(() => {})
            .finally(() => {
                clearTimeout(pollTimer);
                pollTimer = setTimeout(poll, 300);
            });
    });

    poll();
})();
</script>
@endsection
