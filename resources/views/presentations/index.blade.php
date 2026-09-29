@extends('layouts.app')

@section('title', 'My Presentations')

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 relative font-sans">
    <!-- Header -->
    <div class="relative bg-gradient-to-br from-indigo-700 via-indigo-800 to-blue-900 py-16 rounded-b-[4rem] shadow-2xl overflow-hidden mb-8">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path d="M0 100 C 20 0 50 0 100 100 Z" fill="currentColor"></path>
            </svg>
        </div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-black/20 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="space-y-3">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('dashboard') }}" class="text-blue-200 hover:text-white transition-colors flex items-center gap-1 text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Dashboard
                        </a>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black text-white leading-tight tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        My Presentations
                    </h1>
                    <p class="text-blue-200 text-sm font-medium">
                        Upload presentation materials for your accepted abstracts
                    </p>
                </div>

                @php
                    $conferenceStart  = \Carbon\Carbon::parse(config('conference.start_date', '2026-06-09'));
                    $uploadDeadline   = \Carbon\Carbon::parse('2026-06-08 23:59:59');
                    $daysToConf       = (int) now()->diffInDays($conferenceStart, false);
                    $deadlineMs       = $uploadDeadline->timestamp * 1000;
                    $uploadedCount    = $submissions->filter(fn($s) => $s->hasActualPresentationFiles())->count();
                    $pendingCount     = $submissions->filter(fn($s) => !$s->hasActualPresentationFiles())->count();
                    $scheduledCount   = $submissions->whereNotNull('presentation_date')->count();
                @endphp
                <div class="flex flex-wrap items-center gap-3">
                    <div class="text-center px-5 py-3 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20">
                        <p class="text-2xl font-black text-white">{{ $submissions->count() }}</p>
                        <p class="text-[10px] text-blue-200 font-bold uppercase tracking-wider">Accepted</p>
                    </div>
                    <div class="text-center px-5 py-3 bg-emerald-500/20 backdrop-blur-sm rounded-xl border border-emerald-300/30">
                        <p class="text-2xl font-black text-white">{{ $uploadedCount }}</p>
                        <p class="text-[10px] text-emerald-200 font-bold uppercase tracking-wider">Uploaded</p>
                    </div>
                    <div class="text-center px-5 py-3 bg-amber-500/20 backdrop-blur-sm rounded-xl border border-amber-300/30">
                        <p class="text-2xl font-black text-white">{{ $pendingCount }}</p>
                        <p class="text-[10px] text-amber-200 font-bold uppercase tracking-wider">Pending</p>
                    </div>
                    <div class="text-center px-5 py-3 bg-purple-500/20 backdrop-blur-sm rounded-xl border border-purple-300/30">
                        <p class="text-2xl font-black text-white">{{ $scheduledCount }}</p>
                        <p class="text-[10px] text-purple-200 font-bold uppercase tracking-wider">Scheduled</p>
                    </div>
                    <div class="text-center px-5 py-3 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20 min-w-[140px]">
                        <p class="text-[10px] font-bold text-blue-200 uppercase tracking-wider mb-1">Upload Deadline</p>
                        <p class="text-xl font-black text-white tabular-nums leading-none" id="upload-countdown-timer">--d --:--:--</p>
                        <p class="text-[10px] text-blue-300 font-medium mt-1">Jun 8, 2026 · 23:59 EAT</p>
                    </div>
                    <script>
                        (function() {
                            var deadline = {{ $deadlineMs }};
                            function tick() {
                                var diff = deadline - Date.now();
                                var el = document.getElementById('upload-countdown-timer');
                                if (!el) return;
                                if (diff <= 0) { el.textContent = 'Deadline passed'; clearInterval(interval); return; }
                                var d = Math.floor(diff / 86400000);
                                var h = Math.floor((diff % 86400000) / 3600000);
                                var m = Math.floor((diff % 3600000) / 60000);
                                var s = Math.floor((diff % 60000) / 1000);
                                el.textContent = d + 'd ' + String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                            }
                            tick();
                            var interval = setInterval(tick, 1000);
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-8 pb-12">

        {{-- Presentation Instructions --}}
        <div class="mb-8 bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-xl overflow-hidden" x-data="{ tab: 'oral' }">
            <div class="flex border-b border-slate-200 dark:border-gray-700">
                <button @click="tab='oral'"
                        :class="tab==='oral' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-900/10' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="flex-1 sm:flex-none px-6 py-4 text-sm font-bold border-b-2 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                    Oral Presentation
                </button>
                <button @click="tab='poster'"
                        :class="tab==='poster' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-900/10' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="flex-1 sm:flex-none px-6 py-4 text-sm font-bold border-b-2 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Digital Screen Poster
                </button>
            </div>

            {{-- Oral Presentation Instructions --}}
            <div x-show="tab==='oral'" x-transition class="p-6 md:p-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white mb-1">Oral Presentation Guidelines</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Follow these instructions to prepare a clear, concise, and impactful oral presentation.</p>
                        </div>

                        <div class="space-y-5">
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Time Allocation</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Each oral presentation is allocated <strong>10 minutes total</strong>: 7 minutes for your presentation followed by 3 minutes for audience questions and discussion. Time yourself carefully during practice.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Slides &amp; File Format</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Prepare <strong>10–12 slides maximum</strong> in PowerPoint or PDF format. Accepted formats: <code class="bg-slate-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-xs font-mono">.ppt, .pptx, .pdf</code> — maximum file size <strong>50 MB</strong>. A conference slide template is available for download below.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Required Content Structure</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Your slides must include: <strong>Title slide</strong> (with your name, affiliation, and abstract code), <strong>Background &amp; Rationale</strong>, <strong>Objectives</strong>, <strong>Methods</strong>, <strong>Results/Findings</strong>, and <strong>Conclusions</strong>. The title must match your submitted abstract.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center text-amber-600 dark:text-amber-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Visual Design &amp; Readability</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Use clear, consistent fonts (Arial, Calibri, or Helvetica) at minimum <strong>18pt</strong> for body text. Keep slides uncluttered — one key message per slide. Favour graphs, figures, and concise labels over dense text blocks or large tables.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center text-purple-600 dark:text-purple-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Session Presence</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Presenters must be present at the session room <strong>at least 15 minutes before</strong> their scheduled slot to load their slides and meet the session chair. Be prepared to answer questions from the audience and panel.</p>
                                </div>
                            </div>
                        </div>

                        @if($slideTemplate = config('conference.slide_template_path'))
                        <a href="{{ asset($slideTemplate) }}" download
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download Slide Template
                        </a>
                        @endif
                    </div>

                    {{-- Oral Checklist --}}
                    <div class="bg-slate-50 dark:bg-gray-700/40 rounded-2xl p-6 border border-slate-200 dark:border-gray-600 h-fit">
                        <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider mb-4">Submission Checklist</h4>
                        <ul class="space-y-3">
                            @foreach([
                                'Presentation prepared in PowerPoint or PDF',
                                'Maximum 10–12 slides',
                                'Title slide includes name, affiliation, and abstract code',
                                'All required sections covered (Background, Objectives, Methods, Results, Conclusions)',
                                'Font size minimum 18pt throughout',
                                'File exported as .ppt, .pptx, or .pdf',
                                'File size is under 50 MB',
                            ] as $item)
                            <li class="flex items-start gap-3 text-sm text-slate-700 dark:text-slate-300">
                                <span class="flex-shrink-0 mt-0.5 w-4 h-4 rounded border-2 border-slate-300 dark:border-gray-500"></span>
                                {{ $item }}
                            </li>
                            @endforeach
                        </ul>
                        <div class="mt-5 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                            <p class="text-xs text-amber-800 dark:text-amber-300 font-medium"><strong>Note:</strong> Oral sessions will proceed even if slides are not pre-uploaded. However, uploading in advance ensures technical readiness on the day.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Digital Screen Poster Instructions --}}
            <div x-show="tab==='poster'" x-transition class="p-6 md:p-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white mb-1">Digital Screen Poster Preparation Instructions</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 italic">For conference poster sessions and rotating screen displays</p>
                        </div>

                        <div class="space-y-5">
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Poster Purpose</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">The digital poster presentation should provide a clear and concise visual summary of the submitted abstract. It must communicate the key message of the study clearly and support discussion during the poster session.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Poster Format &amp; Layout</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Prepare the poster as a <strong>single-slide digital display</strong> suitable for presentation on conference screens. The poster must include: title, background/rationale, objectives, methods, results/findings, and conclusions. The title must match the abstract submission and accurately reflect the poster content.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-rose-100 dark:bg-rose-900/40 rounded-xl flex items-center justify-center text-rose-600 dark:text-rose-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.869v6.262a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">File Format &amp; Submission</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Submit the final poster presentation as a <strong>video file in MP4 format</strong>. The video may be created by recording a narrated PowerPoint slide or by using a screen recording with voice-over. Ensure the video plays clearly, the audio is audible, and all text and visuals remain readable on a large screen. Maximum file size: <strong>100 MB</strong>.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-teal-100 dark:bg-teal-900/40 rounded-xl flex items-center justify-center text-teal-600 dark:text-teal-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Digital Screen Size &amp; Orientation</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Use a <strong>widescreen landscape format, 16:9</strong>. Keep the layout clean, avoid overcrowding, and make sure the main message can be understood quickly while the poster is displayed on screen.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center text-amber-600 dark:text-amber-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Narrated Video &amp; Duration</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">The narrated poster video must be a <strong>maximum of 5 minutes</strong>. It should briefly explain the study background, methods, key findings, and conclusions. Clear audio is required; high production quality is not necessary.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Poster Session Presence</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Presenters should be available during the scheduled poster session to explain their work, answer questions, and engage with participants. The oral explanation should highlight the study background, methods, key findings, conclusions, and significance.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center text-purple-600 dark:text-purple-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Visual Design &amp; Readability</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Use clear, consistent fonts such as Arial, Calibri, or Helvetica. For screen display, use <strong>large readable text</strong>, short section headings, and enough spacing between elements. Emphasise graphs, figures, images, and concise labels instead of large blocks of text or dense tables. All visuals should include short, informative captions.</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                            <p class="text-sm text-amber-800 dark:text-amber-300"><strong>Note:</strong> Digital screen posters are intended to keep your presentation accessible even after your scheduled session has ended. A clear and well-prepared presentation will help communicate your message effectively. Poster sessions will still take place, and presenters are expected to be available to present their work and engage with the audience.</p>
                        </div>
                    </div>

                    {{-- Poster Checklist --}}
                    <div class="bg-slate-50 dark:bg-gray-700/40 rounded-2xl p-6 border border-slate-200 dark:border-gray-600 h-fit">
                        <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider mb-4">Submission Checklist</h4>
                        <ul class="space-y-3">
                            @foreach([
                                'Poster prepared as a single-slide digital display',
                                'Poster prepared in 16:9 widescreen landscape format',
                                'Final narrated presentation exported as MP4 video',
                                'Video length is maximum 5 minutes',
                                'Audio is clear and audible',
                                'Text and visuals readable on conference display screens',
                                'Video clearly summarises background, methods, findings, and conclusions',
                            ] as $item)
                            <li class="flex items-start gap-3 text-sm text-slate-700 dark:text-slate-300">
                                <span class="flex-shrink-0 mt-0.5 w-4 h-4 rounded border-2 border-slate-300 dark:border-gray-500"></span>
                                {{ $item }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-emerald-700 dark:text-emerald-300 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-rose-100 dark:bg-rose-900/40 rounded-lg flex items-center justify-center text-rose-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <p class="text-rose-700 dark:text-rose-300 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if($submissions->count() > 0)
            <!-- Presentations List -->
            <div class="space-y-4">
                @foreach($submissions as $submission)
                    @php
                        $hasFiles = $submission->hasActualPresentationFiles();
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border {{ $hasFiles ? 'border-emerald-200 dark:border-emerald-800/50' : 'border-slate-200 dark:border-gray-700' }} overflow-hidden hover:shadow-md transition-all">
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start gap-3">
                                        @if($hasFiles)
                                            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600 flex-shrink-0 mt-1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                        @else
                                            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center text-amber-600 flex-shrink-0 mt-1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                                <h3 class="text-lg font-black text-slate-900 dark:text-white truncate">{{ $submission->title }}</h3>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-[10px] font-bold text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800 uppercase tracking-tight">
                                                    {{ $submission->subtheme }}
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500 dark:text-slate-400">
                                                <span class="flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                    {{ $submission->author_name }}
                                                </span>
                                                <span class="flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    Last Update: {{ $submission->updated_at->format('M d, Y') }}
                                                </span>
                                                <span class="flex items-center gap-1.5 font-bold text-indigo-600 dark:text-indigo-400">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                                    {{ $submission->conference_code ?: 'ABS-'.str_pad($submission->id, 3, '0', STR_PAD_LEFT) }}
                                                </span>
                                            </div>

                                            <!-- Presentation Timeline -->
                                            <div class="mt-6">
                                                <div class="relative flex items-center justify-between max-w-md">
                                                    <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-[2px] bg-slate-100 dark:bg-gray-700 -z-10"></div>
                                                    <div class="absolute left-0 top-1/2 -translate-y-1/2 h-[2px] bg-emerald-500 -z-10 transition-all duration-1000" style="width: {{ $hasFiles ? '100' : '75' }}%"></div>

                                                    <div class="flex flex-col items-center gap-1.5">
                                                        <div class="w-6 h-6 rounded-full bg-emerald-500 border-4 border-white dark:border-gray-800 shadow-sm flex items-center justify-center">
                                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        </div>
                                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Submit</span>
                                                    </div>

                                                    <div class="flex flex-col items-center gap-1.5">
                                                        <div class="w-6 h-6 rounded-full bg-emerald-500 border-4 border-white dark:border-gray-800 shadow-sm flex items-center justify-center">
                                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        </div>
                                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Review</span>
                                                    </div>

                                                    <div class="flex flex-col items-center gap-1.5">
                                                        <div class="w-6 h-6 rounded-full bg-emerald-500 border-4 border-white dark:border-gray-800 shadow-sm flex items-center justify-center">
                                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        </div>
                                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Decision</span>
                                                    </div>

                                                    <div class="flex flex-col items-center gap-1.5">
                                                        <div class="w-6 h-6 rounded-full {{ $hasFiles ? 'bg-emerald-500' : 'bg-white dark:bg-gray-800 border-slate-200 dark:border-gray-600 animate-pulse' }} border-4 shadow-sm flex items-center justify-center">
                                                            @if($hasFiles)
                                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                            @else
                                                                <div class="w-1.5 h-1.5 bg-amber-500 rounded-full"></div>
                                                            @endif
                                                        </div>
                                                        <span class="text-[9px] font-black {{ $hasFiles ? 'text-slate-400' : 'text-amber-600 animate-pulse' }} uppercase tracking-widest">Uploads</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center">
                                    <a href="{{ route('presentations.show', $submission) }}" class="inline-flex items-center gap-2 px-5 py-2.5 {{ $hasFiles ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white rounded-xl text-sm font-bold transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        {{ $hasFiles ? 'Manage Files' : 'Upload Files' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-12 text-center">
                <div class="w-16 h-16 bg-slate-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center text-slate-400 mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Accepted Presentations</h3>
                <p class="text-slate-500 dark:text-slate-400 mb-6">You don't have any accepted abstracts requiring presentation uploads yet.</p>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
                    Back to Dashboard
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
