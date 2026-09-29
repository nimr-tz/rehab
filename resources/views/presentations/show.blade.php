@extends('layouts.app')

@section('title', 'Upload Presentation • ' . $submission->title)

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 relative font-sans">
    <!-- Professional Header matching My Submissions page -->
    <div class="relative bg-gradient-to-br from-indigo-700 via-indigo-800 to-blue-900 py-16 rounded-b-[4rem] shadow-2xl overflow-hidden mb-8">
        <!-- Subtle Pattern Background -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.05]"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-black/20 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="space-y-3">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('presentations.index') }}" class="text-blue-200 hover:text-white transition-colors flex items-center gap-1 text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Back to Presentations
                        </a>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black text-white leading-tight tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        {{ Str::limit($submission->title, 60) }}
                    </h1>
                    <div class="flex items-center gap-4 text-blue-200 text-sm font-medium">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            {{ $submission->conference_code ?: 'ABS-'.str_pad($submission->id, 3, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="opacity-40">•</span>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-bold uppercase tracking-wide">
                            {{ ucwords(str_replace('_', ' ', $submission->presentation_mode)) }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-col items-start md:items-end gap-2">
                    <p class="text-[10px] font-bold text-blue-300 uppercase tracking-[0.2em]">Upload Status</p>
                    <div class="scale-110 origin-left md:origin-right">
                        {!! $submission->getPresentationStatusBadge() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-8 pb-12">
        <!-- Professional Guidance Alert -->
        <div class="mb-8 p-4 bg-white/50 dark:bg-gray-800/50 backdrop-blur-md rounded-2xl border border-indigo-100 dark:border-indigo-900/30 flex items-center gap-4 animate-fade-in-up">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-tight">Upload Guidance</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    You may update, replace, or remove your presentation files as many times as needed until the final deadline on <span class="font-bold text-indigo-600">June 8, 2026</span>. Please ensure the final version is uploaded by then.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Main Content - Upload Form -->
            <div class="lg:col-span-2 space-y-6">
                <form id="presentation-upload-form" enctype="multipart/form-data">
                    @csrf

                    <!-- Upload Section Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 dark:border-gray-700">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                Presentation Files
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Upload your presentation materials for the conference</p>
                        </div>

                        <div class="p-6 space-y-6">
                            @if(strtolower($submission->presentation_mode) === 'oral')
                                <!-- Oral Presentation Upload -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                            <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                                            Oral Presentation Slides
                                        </label>
                                        @if($submission->oral_presentation_file)
                                            <span class="text-xs text-emerald-600 font-bold flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                Uploaded
                                            </span>
                                        @endif
                                    </div>

                                    @if($submission->oral_presentation_file)
                                        <div class="flex items-center justify-between p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </div>
                                                <div class="truncate">
                                                    <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-100 truncate">{{ $submission->oral_presentation_file }}</p>
                                                    <p class="text-xs text-emerald-600 dark:text-emerald-400">PowerPoint Document • Uploaded</p>
                                                </div>
                                            </div>
                                            <button type="button" class="px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors delete-file-btn" data-file-type="oral" data-file-name="{{ $submission->oral_presentation_file }}">
                                                Remove
                                            </button>
                                        </div>
                                    @else
                                        <div class="file-upload-zone relative bg-slate-50 dark:bg-gray-700/30 border-2 border-dashed border-slate-300 dark:border-gray-600 rounded-xl p-8 transition-all hover:bg-white dark:hover:bg-gray-700/50 hover:border-indigo-500 cursor-pointer text-center group" data-file-type="oral_presentation">
                                            <input type="file" name="oral_presentation" id="oral_presentation" accept=".ppt,.pptx" class="file-input absolute inset-0 opacity-0 z-20 cursor-pointer">
                                            <div class="space-y-3 relative z-10">
                                                <div class="w-12 h-12 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-600 mx-auto flex items-center justify-center text-slate-400 group-hover:text-indigo-600 transition-all">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700 dark:text-white group-hover:text-indigo-600 transition-colors">Click to upload or drag and drop</p>
                                                    <p class="text-xs text-slate-400 mt-1">PPT, PPTX (max 50MB)</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if(strtolower($submission->presentation_mode) === 'poster')
                                <!-- Poster Presentation Upload -->
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Poster Video -->
                                    <div class="space-y-3">
                                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                            <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                                            Poster Video (MP4)
                                        </label>
                                        @if($submission->poster_presentation_file)
                                            <div class="flex items-center justify-between p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl min-h-[100px]">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    </div>
                                                    <div class="truncate">
                                                        <p class="text-xs font-semibold text-emerald-900 dark:text-emerald-100 truncate">{{ $submission->poster_presentation_file }}</p>
                                                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">MP4 video file</p>
                                                    </div>
                                                </div>
                                                <button type="button" class="text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded px-2 py-1 delete-file-btn" data-file-type="poster" data-file-name="{{ $submission->poster_presentation_file }}">Remove</button>
                                            </div>
                                        @else
                                            <div class="file-upload-zone relative bg-slate-50 dark:bg-gray-700/30 border-2 border-dashed border-slate-300 dark:border-gray-600 rounded-xl p-6 transition-all hover:border-indigo-500 cursor-pointer text-center group min-h-[132px] flex items-center justify-center" data-file-type="poster_presentation">
                                                <input type="file" name="poster_presentation" id="poster_presentation" accept=".mp4,video/mp4" class="file-input absolute inset-0 opacity-0 z-20 cursor-pointer">
                                                <div class="space-y-1 relative z-10">
                                                    <p class="text-sm font-semibold text-slate-700 dark:text-white group-hover:text-indigo-600 transition-colors">Select Poster Video</p>
                                                    <p class="text-xs text-slate-400">MP4, 16:9 landscape, max 5 minutes, max 100MB</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                </div>
                            @endif

                            @if(strtolower($submission->presentation_mode) === 'audio_poster')
                                <!-- Audio Poster Upload -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Audio File -->
                                    <div class="space-y-3">
                                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                            <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                                            Audio Narration (MP3)
                                        </label>
                                        @if($submission->audio_poster_file)
                                            <div class="flex items-center justify-between p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl h-[100px]">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    </div>
                                                    <div class="truncate">
                                                        <p class="text-xs font-semibold text-emerald-900 dark:text-emerald-100 truncate">{{ $submission->audio_poster_file }}</p>
                                                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">Uploaded ✓</p>
                                                    </div>
                                                </div>
                                                <button type="button" class="text-xs font-bold text-rose-600 delete-file-btn" data-file-type="audio_poster" data-file-name="{{ $submission->audio_poster_file }}">Remove</button>
                                            </div>
                                        @else
                                            <div class="file-upload-zone relative bg-slate-50 dark:bg-gray-700/30 border-2 border-dashed border-slate-300 dark:border-gray-600 rounded-xl p-6 transition-all hover:border-orange-500 cursor-pointer text-center group h-[100px] flex items-center justify-center" data-file-type="audio_file">
                                                <input type="file" name="audio_file" id="audio_file" accept=".mp3,.wav,.aac" class="file-input absolute inset-0 opacity-0 z-20 cursor-pointer">
                                                <div class="space-y-1 relative z-10">
                                                    <p class="text-sm font-semibold text-slate-700 dark:text-white group-hover:text-orange-600 transition-colors">Select Audio File</p>
                                                    <p class="text-xs text-slate-400">Max 100MB</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Poster File -->
                                    <div class="space-y-3">
                                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                            <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                                            Poster Visual (PPTX)
                                        </label>
                                        @if($submission->audio_poster_poster_file)
                                            <div class="flex items-center justify-between p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl h-[100px]">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    </div>
                                                    <div class="truncate">
                                                        <p class="text-xs font-semibold text-emerald-900 dark:text-emerald-100 truncate">{{ $submission->audio_poster_poster_file }}</p>
                                                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">Uploaded ✓</p>
                                                    </div>
                                                </div>
                                                <button type="button" class="text-xs font-bold text-rose-600 delete-file-btn" data-file-type="audio_poster" data-file-name="{{ $submission->audio_poster_poster_file }}">Remove</button>
                                            </div>
                                        @else
                                            <div class="file-upload-zone relative bg-slate-50 dark:bg-gray-700/30 border-2 border-dashed border-slate-300 dark:border-gray-600 rounded-xl p-6 transition-all hover:border-indigo-500 cursor-pointer text-center group h-[100px] flex items-center justify-center" data-file-type="poster_file">
                                                <input type="file" name="poster_file" id="poster_file" accept=".ppt,.pptx" class="file-input absolute inset-0 opacity-0 z-20 cursor-pointer">
                                                <div class="space-y-1 relative z-10">
                                                    <p class="text-sm font-semibold text-slate-700 dark:text-white group-hover:text-indigo-600 transition-colors">Select Poster File</p>
                                                    <p class="text-xs text-slate-400">Max 25MB</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div id="footer-actions" class="{{ $submission->hasActualPresentationFiles() ? 'hidden' : '' }} mt-6">
                        <button type="submit" form="presentation-upload-form" id="upload-btn" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-200 dark:shadow-none flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <span id="upload-btn-text">{{ $submission->hasActualPresentationFiles() ? 'Update Presentation' : 'Upload Presentation' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <div class="lg:sticky lg:top-8 space-y-6">
                    <!-- Presentation Type Card -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white overflow-hidden relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/20 to-transparent"></div>
                        <div class="relative z-10 space-y-2">
                            <p class="text-xs font-bold text-indigo-300 uppercase tracking-wider">Presentation Type</p>
                            <p class="text-2xl font-black tracking-tight" style="font-family: 'Outfit', sans-serif;">
                                {{ ucwords(str_replace('_', ' ', $submission->presentation_mode)) }}
                            </p>
                        </div>
                    </div>

                    <!-- Requirements Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-6">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            Upload Requirements
                        </h4>
                        @php
                            $requirements = $submission->getPresentationRequirements();
                            $formatList = collect($requirements['formats'] ?? [])
                                ->flatten()
                                ->map(fn($format) => strtoupper($format))
                                ->implode(', ');
                        @endphp

                        <div class="space-y-4">
                            @if(isset($requirements['primary']))
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-wide">Required</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">{{ $requirements['primary'] }}</p>
                                </div>
                            @endif

                            <div class="h-px bg-slate-100 dark:bg-gray-700"></div>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-xs text-slate-400 uppercase tracking-wide mb-1">Max Size</p>
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $requirements['max_size'] ?? '50MB' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 uppercase tracking-wide mb-1">Formats</p>
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $formatList ?: 'PPTX' }}</p>
                                </div>
                            </div>

                            @if(isset($requirements['duration']))
                                <div class="pt-2 border-t border-slate-100 dark:border-gray-700">
                                    <p class="text-xs text-slate-400 uppercase tracking-wide mb-1">Duration Limit</p>
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $requirements['duration'] }}</p>
                                </div>
                            @endif

                            @if(isset($requirements['dimensions']))
                                <div class="pt-2 border-t border-slate-100 dark:border-gray-700">
                                    <p class="text-xs text-slate-400 uppercase tracking-wide mb-1">Screen Format</p>
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $requirements['dimensions'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Technical Instructions Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-6">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Speaker Technical Instructions
                        </h4>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">On-Site Presentation</p>
                                <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2 list-none">
                                    <li class="flex items-start gap-2">
                                        <span class="text-indigo-500 mt-0.5">&bull;</span>
                                        <span>Presentation files will be loaded onto session computers in advance.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-indigo-500 mt-0.5">&bull;</span>
                                        <span>Use the shared {{ config('conference.short_name') }} PowerPoint template for oral presentations.</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="h-px bg-slate-100 dark:bg-gray-700"></div>

                            <div class="space-y-2">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Poster Guidelines</p>
                                <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2 list-none">
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-500 mt-0.5">•</span>
                                        <span>Submit poster presentations as a narrated <strong>MP4 video</strong>, maximum 5 minutes.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-500 mt-0.5">•</span>
                                        <span>Use a clean 16:9 landscape single-slide layout with readable text, clear audio, and concise sections.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-500 mt-0.5">&bull;</span>
                                        <span>Use the accepted abstract title and include background, objectives, methods, results or findings, and conclusions.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-500 mt-0.5">&bull;</span>
                                        <span>Export as MP4 using 720p or 1080p H.264 settings and keep the final file under 100MB.</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="pt-2">
                                <p class="text-[10px] text-slate-400 italic">Please bring a backup of your file on a USB drive to the conference.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-900/30">
                        <p class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wide mb-1">Need Help?</p>
                        <p class="text-sm text-blue-600 dark:text-blue-300">{{ config('conference.contact_email') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div id="progress-modal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[1000] flex items-center justify-center p-8 hidden">
    <div class="max-w-sm w-full bg-white dark:bg-gray-800 rounded-[2rem] p-10 space-y-8 text-center shadow-2xl border border-white/20">
        <div class="battery-loader" aria-hidden="true">
            <div class="battery-shell">
                <div class="battery-fill" id="battery-fill"></div>
                <div class="battery-bolt">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M11.3 1.05 4.7 10.4c-.32.45 0 1.08.55 1.08h3.4l-1.2 7.08c-.12.68.76 1.05 1.17.49l6.7-9.22c.33-.45.01-1.09-.55-1.09h-3.5l1.15-7.19c.11-.68-.77-1.04-1.12-.5Z"/>
                    </svg>
                </div>
            </div>
            <div class="battery-cap"></div>
        </div>

        <div class="space-y-4">
            <div class="space-y-1">
                <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight" id="progress-title">Uploading your file</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Please keep this page open while we save your presentation.</p>
            </div>

            <div class="pt-1">
                <span class="text-sm font-black text-indigo-600" id="progress-percent">0%</span>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[2000] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-6 text-center">
        <div class="w-14 h-14 bg-rose-100 dark:bg-rose-900/30 rounded-xl flex items-center justify-center text-rose-600 mx-auto">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <div class="space-y-2">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Remove File?</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">This will permanently delete this file from your presentation.</p>
        </div>
        <div class="flex gap-3">
            <button onclick="closeDeleteModal()" class="flex-1 py-3 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-gray-700 rounded-xl transition-colors">Cancel</button>
            <button id="confirm-delete-btn" class="flex-1 py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-bold transition-colors">Delete</button>
        </div>
    </div>
</div>

<style>
    .file-upload-zone.drag-over {
        border-color: #4f46e5;
        background-color: rgba(79, 70, 229, 0.07);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
        transform: translateY(-1px);
    }

    .file-upload-zone.file-selected {
        border-color: #10b981;
        background-color: rgba(16, 185, 129, 0.08);
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.08);
    }

    .file-upload-zone .selected-file-confirmation {
        display: none;
    }

    .file-upload-zone.file-selected .selected-file-confirmation {
        display: inline-flex;
    }

    .battery-loader {
        position: relative;
        width: 96px;
        height: 58px;
        margin: 0 auto;
    }

    .battery-shell {
        position: absolute;
        left: 6px;
        top: 10px;
        width: 72px;
        height: 38px;
        border: 4px solid #4f46e5;
        border-radius: 14px;
        overflow: hidden;
        background: rgba(79, 70, 229, 0.05);
        box-shadow: 0 16px 36px -18px rgba(79, 70, 229, 0.75);
    }

    .dark .battery-shell {
        border-color: #818cf8;
        background: rgba(129, 140, 248, 0.08);
    }

    .battery-cap {
        position: absolute;
        left: 79px;
        top: 22px;
        width: 9px;
        height: 14px;
        border-radius: 0 6px 6px 0;
        background: #4f46e5;
    }

    .dark .battery-cap { background: #818cf8; }

    .battery-fill {
        position: absolute;
        left: 0;
        top: 0;
        width: 8%;
        height: 100%;
        background: linear-gradient(90deg, #22c55e, #14b8a6);
        transition: width 0.35s ease;
    }

    .battery-fill::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
        animation: charge-shimmer 1.4s linear infinite;
    }

    .battery-bolt {
        position: absolute;
        inset: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #4f46e5;
        opacity: 0.9;
    }

    .dark .battery-bolt { color: #c7d2fe; }

    @keyframes charge-shimmer {
        from { transform: translateX(-100%); }
        to { transform: translateX(100%); }
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('presentation-upload-form');
    const uploadBtn = document.getElementById('upload-btn');
    const progressModal = document.getElementById('progress-modal');
    const progressPercent = document.getElementById('progress-percent');
    const batteryFill = document.getElementById('battery-fill');
    const uploadZones = document.querySelectorAll('.file-upload-zone');
    const fileInputs = document.querySelectorAll('.file-input');
    const footerActions = document.getElementById('footer-actions');
    const initialHasFiles = {{ $submission->hasActualPresentationFiles() ? 'true' : 'false' }};
    const maxFileSizes = {
        oral_presentation: 50 * 1024 * 1024,
        poster_presentation: 100 * 1024 * 1024,
        audio_file: 100 * 1024 * 1024,
        poster_file: 25 * 1024 * 1024,
    };

    function checkChanges() {
        const hasNewFiles = Array.from(fileInputs).some(input => input.files.length > 0);
        if (hasNewFiles || !initialHasFiles) {
            footerActions.classList.remove('hidden');
        } else if (initialHasFiles) {
            footerActions.classList.add('hidden');
        }
    }

    uploadZones.forEach(zone => {
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', e => { e.preventDefault(); zone.classList.remove('drag-over'); });
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            const input = zone.querySelector('.file-input');
            if (input && e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                handleFileSelect({ target: input });
                checkChanges();
            }
        });
    });

    fileInputs.forEach(input => {
        input.addEventListener('change', e => { handleFileSelect(e); checkChanges(); });
    });

    document.addEventListener('click', e => {
        if (e.target.closest('.delete-file-btn')) handleFileDelete(e.target.closest('.delete-file-btn'));
    });

    form.addEventListener('submit', handleFormSubmit);

    function handleFileSelect(e) {
        const zone = e.target.closest('.file-upload-zone');
        if (e.target.files.length > 0) {
            const file = e.target.files[0];
            const maxFileSize = maxFileSizes[e.target.name];
            if (maxFileSize && file.size > maxFileSize) {
                e.target.value = '';
                alert(`${file.name} is too large. The maximum allowed size is ${maxFileSize / (1024 * 1024)} MB.`);
                checkChanges();
                return;
            }

            const label = zone.querySelector('.text-sm');
            const helper = zone.querySelector('.text-xs');
            const iconWrap = zone.querySelector('.w-12, .w-10');
            const fileSize = file.size ? ` (${(file.size / (1024 * 1024)).toFixed(1)} MB)` : '';

            if (label) {
                label.textContent = file.name;
                label.classList.remove('text-slate-700', 'dark:text-white');
                label.classList.add('text-emerald-700', 'dark:text-emerald-300');
            }

            if (helper) {
                helper.textContent = `Ready to upload${fileSize}`;
                helper.classList.remove('text-slate-400');
                helper.classList.add('text-emerald-600', 'dark:text-emerald-400', 'font-bold');
            }

            if (iconWrap) {
                iconWrap.classList.remove('text-slate-400');
                iconWrap.classList.add('text-emerald-600', 'border-emerald-200', 'bg-emerald-50', 'dark:bg-emerald-900/20', 'dark:border-emerald-800');
                iconWrap.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
            }

            if (!zone.querySelector('.selected-file-confirmation')) {
                const confirmation = document.createElement('div');
                confirmation.className = 'selected-file-confirmation mt-3 items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-[10px] font-black uppercase tracking-widest';
                confirmation.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>File selected</span>';
                const content = zone.querySelector('.relative.z-10');
                if (content) content.appendChild(confirmation);
            }

            zone.classList.add('file-selected');
            zone.classList.remove('drag-over');
        }
    }

    const deleteModal = document.getElementById('delete-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    let pendingDelete = { fileType: null, fileName: null };

    function handleFileDelete(button) {
        pendingDelete.fileType = button.dataset.fileType;
        pendingDelete.fileName = button.dataset.fileName;
        deleteModal.classList.remove('hidden');
    }

    window.closeDeleteModal = () => deleteModal.classList.add('hidden');

    confirmDeleteBtn.addEventListener('click', () => {
        if (pendingDelete.fileType) {
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('file_type', pendingDelete.fileType);
            formData.append('file_name', pendingDelete.fileName);
            fetch(`{{ route('presentations.delete', $submission) }}`, { method: 'POST', body: formData }).then(() => location.reload());
        }
    });

    function handleFormSubmit(e) {
        e.preventDefault();
        progressModal.classList.remove('hidden');
        uploadBtn.disabled = true;

        const formData = new FormData(form);
        const request = new XMLHttpRequest();

        request.upload.addEventListener('progress', event => {
            if (!event.lengthComputable) return;

            const progress = Math.round((event.loaded / event.total) * 100);
            if (batteryFill) batteryFill.style.width = `${Math.max(progress, 8)}%`;
            progressPercent.textContent = `${progress}%`;
        });

        request.addEventListener('load', () => {
            let data = {};
            try {
                data = JSON.parse(request.responseText);
            } catch (error) {
                data = {};
            }

            if (request.status >= 200 && request.status < 300 && data.success) {
                if (batteryFill) batteryFill.style.width = '100%';
                progressPercent.textContent = '100%';
                setTimeout(() => window.location.href = data.redirect || window.location.href, 500);
                return;
            }

            progressModal.classList.add('hidden');
            uploadBtn.disabled = false;
            if (request.status === 413) {
                alert('Upload failed because the selected files are too large. Please reduce the file size and try again.');
                return;
            }

            alert(data.message || data.error || `Upload failed. The server returned status ${request.status}.`);
        });

        request.addEventListener('error', () => {
            progressModal.classList.add('hidden');
            uploadBtn.disabled = false;
            alert('Upload failed because the server could not be reached. Please check your connection and try again.');
        });

        request.open('POST', `{{ route('presentations.upload', $submission) }}`);
        request.send(formData);
    }
});
</script>
@endpush
@endsection
