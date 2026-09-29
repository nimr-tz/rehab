@extends('layouts.app')

@section('title', 'Conference Program')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- ── Header ── --}}
    <div class="relative overflow-hidden rounded-3xl bg-[#06153D] text-white px-8 py-10 shadow-2xl">
        {{-- subtle grid texture --}}
        <svg class="absolute inset-0 w-full h-full opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="hdr-grid" width="32" height="32" patternUnits="userSpaceOnUse">
                    <path d="M 32 0 L 0 0 0 32" fill="none" stroke="white" stroke-width="0.6"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#hdr-grid)"/>
        </svg>
        {{-- glow blob --}}
        <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-blue-500/20 blur-3xl pointer-events-none"></div>

        <div class="relative flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8">

            {{-- Left: title + breadcrumb --}}
            <div>
                <nav class="flex items-center gap-2 text-sm text-blue-300/70 mb-4">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors">Dashboard</a>
                    <svg class="w-4 h-4 opacity-40" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-white/80">Conference Program</span>
                </nav>

                <h1 class="text-3xl font-black tracking-tight mb-1">Conference Program</h1>
                <p class="text-blue-200/60 text-sm font-medium">{{ config('conference.short_name') }} {{ config('conference.year') }} · June 9–11 · {{ config('conference.city') }}</p>
            </div>

            {{-- Right: three headline numbers --}}
            <div class="flex items-center gap-6">
                @php
                    $pct = $stats['accepted_abstracts'] > 0
                        ? round(($stats['assigned_codes'] / $stats['accepted_abstracts']) * 100)
                        : 0;
                    $c   = 2 * M_PI * 36;
                    $off = $c - ($pct / 100) * $c;
                @endphp
                {{-- progress ring --}}
                <div class="relative w-24 h-24 shrink-0">
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 84 84">
                        <circle cx="42" cy="42" r="36" stroke="rgba(255,255,255,0.12)" stroke-width="7" fill="none"/>
                        <circle cx="42" cy="42" r="36" stroke="#60a5fa" stroke-width="7" fill="none"
                                stroke-dasharray="{{ $c }}" stroke-dashoffset="{{ $off }}"
                                stroke-linecap="round"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-black leading-none">{{ $pct }}%</span>
                        <span class="text-[10px] text-blue-300/70 mt-0.5">coded</span>
                    </div>
                </div>

                <div class="hidden sm:flex flex-col gap-3">
                    <div>
                        <div class="text-2xl font-black leading-none">{{ $stats['accepted_abstracts'] }}</div>
                        <div class="text-[11px] text-blue-300/60 uppercase tracking-wider mt-0.5">Accepted</div>
                    </div>
                    <div>
                        <div class="text-2xl font-black leading-none">{{ $stats['total_sessions'] }}</div>
                        <div class="text-[11px] text-blue-300/60 uppercase tracking-wider mt-0.5">Sessions</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pipeline bar --}}
        <div class="relative mt-8 pt-8 border-t border-white/10">
            <div class="flex items-center gap-0">
                @php
                    $steps = [
                        ['n' => 1, 'label' => 'Code Assignment',   'sub' => $stats['pending_codes'] > 0 ? $stats['pending_codes'].' pending' : 'All assigned',   'done' => $stats['pending_codes'] === 0],
                        ['n' => 2, 'label' => 'Session Building',   'sub' => $stats['total_sessions'].' sessions',          'done' => $stats['total_sessions'] > 0],
                        ['n' => 3, 'label' => 'Programme Review',   'sub' => 'Preview & validate',                          'done' => false],
                        ['n' => 4, 'label' => 'Publish & Export',   'sub' => 'PDF · CSV · Book',                            'done' => false],
                    ];
                @endphp

                @foreach($steps as $i => $step)
                    <div class="flex items-center {{ $i < count($steps)-1 ? 'flex-1' : '' }}">
                        <div class="flex items-center gap-3 shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors
                                {{ $step['done'] ? 'bg-blue-400 border-blue-400 text-white' : 'border-white/25 text-white/50' }}">
                                @if($step['done'])
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    {{ $step['n'] }}
                                @endif
                            </div>
                            <div class="hidden sm:block">
                                <div class="text-sm font-semibold {{ $step['done'] ? 'text-white' : 'text-white/50' }}">{{ $step['label'] }}</div>
                                <div class="text-xs {{ $step['done'] ? 'text-blue-300' : 'text-white/30' }}">{{ $step['sub'] }}</div>
                            </div>
                        </div>
                        @if($i < count($steps)-1)
                            <div class="flex-1 mx-3 h-px {{ $step['done'] ? 'bg-blue-400/50' : 'bg-white/10' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Stat strip ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $statCards = [
                ['val' => $stats['total_abstracts'],   'label' => 'Total Abstracts',  'color' => 'text-slate-700 dark:text-slate-200', 'bg' => 'bg-white dark:bg-slate-800'],
                ['val' => $stats['accepted_abstracts'],'label' => 'Accepted',          'color' => 'text-emerald-700 dark:text-emerald-300', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20'],
                ['val' => $stats['assigned_codes'],    'label' => 'Codes Assigned',   'color' => 'text-blue-700 dark:text-blue-300',   'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                ['val' => $stats['pending_codes'],     'label' => 'Codes Pending',    'color' => $stats['pending_codes'] > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-400', 'bg' => $stats['pending_codes'] > 0 ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-white dark:bg-slate-800'],
            ];
        @endphp
        @foreach($statCards as $card)
            <div class="{{ $card['bg'] }} rounded-2xl border border-slate-100 dark:border-slate-700 px-6 py-5 shadow-sm">
                <div class="text-3xl font-black {{ $card['color'] }}">{{ $card['val'] }}</div>
                <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-1">{{ $card['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── Main action grid ── --}}
    <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5">

        {{-- Assign Codes --}}
        <a href="{{ route('admin.conference-program.assigned', ['assignment_status' => 'all']) }}"
           class="group flex flex-col rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 hover:shadow-lg hover:border-amber-300 dark:hover:border-amber-600 transition-all duration-200">
            <div class="w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 mb-4 group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <div class="font-bold text-slate-900 dark:text-white mb-1">Assign Codes</div>
            <div class="text-sm text-slate-500 dark:text-slate-400 flex-1 mb-4">Review and manage conference codes for accepted abstracts</div>
            @if($stats['pending_codes'] > 0)
                <span class="self-start px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                    {{ $stats['pending_codes'] }} pending
                </span>
            @else
                <span class="self-start px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    All assigned
                </span>
            @endif
        </a>

        {{-- Program Builder --}}
        <a href="{{ route('admin.conference-program.builder') }}"
           class="group flex flex-col rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-200">
            <div class="w-11 h-11 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-4 group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="font-bold text-slate-900 dark:text-white mb-1">Program Builder</div>
            <div class="text-sm text-slate-500 dark:text-slate-400 flex-1 mb-4">Drag abstracts into session slots to build the schedule</div>
            <span class="self-start px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                Drag & Drop
            </span>
        </a>

        {{-- View Program --}}
        <a href="{{ route('admin.conference-program.view') }}"
           class="group flex flex-col rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 hover:shadow-lg hover:border-emerald-300 dark:hover:border-emerald-600 transition-all duration-200">
            <div class="w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-4 group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
            <div class="font-bold text-slate-900 dark:text-white mb-1">View Programme</div>
            <div class="text-sm text-slate-500 dark:text-slate-400 flex-1 mb-4">Preview the full schedule organised by day and time slot</div>
            <span class="self-start px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                Preview
            </span>
        </a>

    </div>

    {{-- ── Bottom row: Exports + Controls + Activity ── --}}
    <div class="grid lg:grid-cols-3 gap-5">

        {{-- Exports --}}
        <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h2 class="font-bold text-slate-900 dark:text-white text-sm uppercase tracking-wider">Export</h2>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                <a href="{{ route('admin.conference-program.export-pdf') }}"
                   class="flex items-center gap-3 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-500 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-slate-800 dark:text-white">Programme PDF</div>
                        <div class="text-xs text-slate-400">Printable schedule</div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                @php
                    $abstractBookExists  = (bool) ($abstractBookStatus['has_existing_pdf'] ?? false);
                    $abstractBookRunning = in_array($abstractBookStatus['status'] ?? 'idle', ['queued', 'processing']);
                    $abstractBookPct     = (int) ($abstractBookStatus['progress'] ?? 0);
                    $abstractBookMsg     = $abstractBookStatus['message'] ?? 'Starting…';
                    $abstractBookQueuedAt = $abstractBookStatus['queued_at'] ?? $abstractBookStatus['started_at'] ?? null;
                @endphp
                <div
                    id="abstract-book-status"
                    data-status-url="{{ route('admin.conference-program.abstract-book.status') }}"
                    data-reset-url="{{ route('admin.conference-program.abstract-book.reset') }}"
                    data-current-status="{{ $abstractBookStatus['status'] ?? 'idle' }}"
                    data-queued-at="{{ $abstractBookQueuedAt }}"
                    class="px-5 py-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-500 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-slate-800 dark:text-white">Abstract Book</div>
                            <div class="text-xs text-slate-400 abstract-book-subtitle">{{ $abstractBookRunning ? 'Generating — please wait…' : 'Generate in the background or download the latest PDF' }}</div>
                            <div class="mt-3 grid grid-cols-1 gap-2">

                                {{-- Generate / Regenerate button --}}
                                <a href="{{ route('admin.conference-program.generate-abstract-book') }}"
                                   class="abstract-book-generate-btn inline-flex items-center justify-between gap-2 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100 dark:border-violet-800/60 dark:bg-violet-900/20 dark:text-violet-200 dark:hover:bg-violet-900/35 transition-colors {{ $abstractBookRunning ? 'pointer-events-none opacity-50' : '' }}">
                                    <span class="abstract-book-generate-label">{{ $abstractBookExists ? 'Regenerate abstract book' : 'Generate abstract book' }}</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0019 5M19 5h-5m5 0v5"/>
                                    </svg>
                                </a>

                                {{-- Progress bar (visible while generating) --}}
                                <div id="abstract-book-progress" class="{{ $abstractBookRunning ? 'block' : 'hidden' }}">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="abstract-book-progress-msg text-xs text-violet-600 dark:text-violet-400 font-medium truncate pr-2">{{ $abstractBookMsg }}</span>
                                        <span class="abstract-book-progress-pct text-xs font-bold text-violet-700 dark:text-violet-300 shrink-0">{{ $abstractBookPct }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5">
                                        <div class="abstract-book-progress-bar bg-violet-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $abstractBookPct }}%"></div>
                                    </div>
                                </div>

                                {{-- Force-reset button — shown by JS when stuck >3 min --}}
                                <button id="abstract-book-reset-btn"
                                    class="hidden w-full items-center justify-between gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100 dark:border-red-800/60 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/35 transition-colors">
                                    <span>Generation stuck — force reset</span>
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0019 5"/></svg>
                                </button>

                                {{-- Download button (hidden while generating) --}}
                                <a href="{{ route('admin.conference-program.abstract-book.download') }}"
                                   class="{{ ($abstractBookExists && !$abstractBookRunning) ? 'inline-flex' : 'hidden' }} items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-900/20 dark:text-emerald-200 dark:hover:bg-emerald-900/35 transition-colors abstract-book-download-link">
                                    <span>Download latest PDF</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/>
                                    </svg>
                                </a>

                                {{-- No PDF yet placeholder (hidden while generating or if PDF exists) --}}
                                <div class="{{ (!$abstractBookExists && !$abstractBookRunning) ? 'block' : 'hidden' }} rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-500 abstract-book-download-placeholder">
                                    Download latest PDF appears after the first generation finishes.
                                </div>

                                {{-- Camera-ready corrections: authors editing their own book entries. --}}
                                <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/60">
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Author corrections</span>
                                        @if($proceedingsCorrections['is_open'])
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Open</span>
                                        @else
                                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-700 dark:text-slate-400">Closed</span>
                                        @endif
                                    </div>

                                    @if($proceedingsCorrections['is_open'] && $proceedingsCorrections['closes_at'])
                                        <p class="mb-2 text-[11px] text-slate-500 dark:text-slate-400">
                                            Closes {{ $proceedingsCorrections['closes_at']->timezone(config('app.timezone'))->format('j M Y') }}
                                        </p>
                                    @endif

                                    @if($proceedingsCorrections['changed_since_last_build'] > 0)
                                        <p class="mb-2 text-[11px] font-semibold text-amber-700 dark:text-amber-300">
                                            {{ $proceedingsCorrections['changed_since_last_build'] }} entr{{ $proceedingsCorrections['changed_since_last_build'] === 1 ? 'y has' : 'ies have' }} changed since the last build — regenerate to include {{ $proceedingsCorrections['changed_since_last_build'] === 1 ? 'it' : 'them' }}.
                                        </p>
                                    @else
                                        <p class="mb-2 text-[11px] text-slate-400 dark:text-slate-500">
                                            No corrections since the last build.
                                        </p>
                                    @endif

                                    <form method="POST" action="{{ route('admin.conference-program.proceedings-corrections.toggle') }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="action" value="{{ $proceedingsCorrections['is_open'] ? 'close' : 'open' }}">
                                        @unless($proceedingsCorrections['is_open'])
                                            <input type="datetime-local" name="closes_at"
                                                   class="min-w-0 flex-1 rounded-md border border-slate-300 bg-white px-2 py-1 text-[11px] text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                                                   title="Optional auto-close time — leave blank to close by hand">
                                        @endunless
                                        <button type="submit"
                                                class="shrink-0 rounded-md px-2.5 py-1 text-[11px] font-bold transition-colors {{ $proceedingsCorrections['is_open'] ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-900/25 dark:text-rose-300' : 'bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-900/25 dark:text-violet-300' }}">
                                            {{ $proceedingsCorrections['is_open'] ? 'Close window' : 'Open window' }}
                                        </button>
                                    </form>
                                </div>

                                {{-- Conference proceedings — a separate Word volume, opted-in abstracts only. --}}
                                <div class="mt-1 rounded-lg border border-sky-200 bg-sky-50/60 px-3 py-2.5 dark:border-sky-800/60 dark:bg-sky-900/15">
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Conference proceedings</span>
                                        <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">PDF</span>
                                    </div>

                                    <p class="mb-2 text-[11px] text-slate-500 dark:text-slate-400">
                                        Abstracts only, from authors who opted in — separate from the abstract book.
                                    </p>

                                    <div class="mb-2 flex items-baseline gap-3">
                                        <span class="text-lg font-black leading-none text-sky-700 dark:text-sky-300">{{ $proceedingsStats['included'] }}</span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                            included &middot; {{ $proceedingsStats['excluded'] }} opted out of {{ $proceedingsStats['total_accepted'] }} accepted
                                        </span>
                                    </div>

                                    @if($proceedingsStats['included'] === 0)
                                        <p class="rounded-md bg-amber-50 px-2 py-1.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-900/25 dark:text-amber-200">
                                            No abstracts are marked for the proceedings yet — the volume would come out empty.
                                        </p>
                                    @else
                                        <a href="{{ route('admin.conference-program.proceedings.download') }}"
                                           class="inline-flex w-full items-center justify-between gap-2 rounded-lg border border-sky-200 bg-white px-3 py-2 text-xs font-bold text-sky-700 transition-colors hover:bg-sky-50 dark:border-sky-800/60 dark:bg-slate-800 dark:text-sky-300 dark:hover:bg-sky-900/30">
                                            <span>Download proceedings (PDF)</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.proceedings.entries') }}"
                                       class="mt-2 inline-flex w-full items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700/60">
                                        <span>Edit entries on authors' behalf</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('admin.conference-program.export') }}"
                   class="flex items-center gap-3 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-500 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-slate-800 dark:text-white">Export CSV</div>
                        <div class="text-xs text-slate-400">Spreadsheet format</div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                <a href="{{ route('admin.conference-program.view-pdf') }}"
                   class="flex items-center gap-3 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-slate-800 dark:text-white">View in Browser</div>
                        <div class="text-xs text-slate-400">Programme preview</div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        {{-- Programme Controls --}}
        <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h2 class="font-bold text-slate-900 dark:text-white text-sm uppercase tracking-wider">Programme Controls</h2>
            </div>
            <div class="p-5 space-y-3">
                {{-- Finalize Codes --}}
                <div class="rounded-xl border border-slate-100 dark:border-slate-700 p-4">
                    <div class="font-semibold text-slate-800 dark:text-white text-sm mb-1">Finalize Codes</div>
                    <div class="text-xs text-slate-400 mb-3">Re-number codes in session order and lock them. Run this after session placement is complete.</div>
                    <button id="btn-finalize"
                            onclick="finalizeCodesFromSession()"
                            class="w-full py-2 rounded-lg bg-[#06153D] hover:bg-blue-900 text-white text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Finalize &amp; Lock Codes
                    </button>
                    <div id="finalize-result" class="hidden mt-2 text-xs font-medium rounded-lg px-3 py-2"></div>
                </div>

                {{-- Detect Session Topics --}}
                <div class="rounded-xl border border-slate-100 dark:border-slate-700 p-4">
                    <div class="font-semibold text-slate-800 dark:text-white text-sm mb-1">Detect Session Topics</div>
                    <div class="text-xs text-slate-400 mb-3">Auto-detect topics for all abstracts that don't have one, using title and keywords.</div>
                    <form method="POST" action="{{ route('admin.conference-program.detect-topics') }}">
                        @csrf
                        <button type="submit"
                                class="w-full py-2 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            Run Topic Detection
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <h2 class="font-bold text-slate-900 dark:text-white text-sm uppercase tracking-wider">Recent Assignments</h2>
                <a href="{{ route('admin.conference-program.assigned', ['assignment_status' => 'all']) }}"
                   class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">View all</a>
            </div>
            @if($recentActivity->isEmpty())
                <div class="px-5 py-10 text-center text-slate-400 dark:text-slate-500 text-sm">
                    No codes assigned yet
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($recentActivity as $abstract)
                        <div class="px-5 py-3 flex items-start gap-3">
                            <span class="mt-0.5 shrink-0 text-xs font-bold font-mono px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                {{ $abstract->conference_code }}
                            </span>
                            <div class="min-w-0">
                                <div class="text-sm text-slate-800 dark:text-slate-200 font-medium truncate">{{ $abstract->title }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ \App\Support\TitleFormatter::personName($abstract->author_name) }} ·
                                    {{ $abstract->code_assigned_at?->diffForHumans() ?? '—' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>

<script>
async function finalizeCodesFromSession() {
    const btn = document.getElementById('btn-finalize');
    const result = document.getElementById('finalize-result');

    if (!confirm('This will re-number ALL non-invited conference codes based on current session order and lock them. Continue?')) return;

    btn.disabled = true;
    btn.innerHTML = `<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Finalizing…`;

    try {
        const resp = await fetch('{{ route('admin.conference-program.finalize-codes') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const data = await resp.json();
        result.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-emerald-50', 'text-emerald-700');
        if (data.success) {
            result.classList.add('bg-emerald-50', 'text-emerald-700', 'dark:bg-emerald-900/20', 'dark:text-emerald-300');
            result.textContent = data.message;
        } else {
            result.classList.add('bg-red-50', 'text-red-700');
            result.textContent = 'Something went wrong.';
        }
    } catch {
        result.classList.remove('hidden');
        result.classList.add('bg-red-50', 'text-red-700');
        result.textContent = 'Request failed.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg> Finalize &amp; Lock Codes`;
        result.classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const statusBox = document.getElementById('abstract-book-status');
    if (!statusBox) return;

    const progressWrap    = document.getElementById('abstract-book-progress');
    const progressBar     = progressWrap?.querySelector('.abstract-book-progress-bar');
    const progressPct     = progressWrap?.querySelector('.abstract-book-progress-pct');
    const progressMsg     = progressWrap?.querySelector('.abstract-book-progress-msg');
    const resetBtn        = document.getElementById('abstract-book-reset-btn');
    const downloadLinks   = document.querySelectorAll('.abstract-book-download-link');
    const placeholders    = document.querySelectorAll('.abstract-book-download-placeholder');
    const generateLabels  = document.querySelectorAll('.abstract-book-generate-label');
    const generateBtns    = document.querySelectorAll('.abstract-book-generate-btn');
    const subtitle        = document.querySelector('.abstract-book-subtitle');
    const statusUrl       = statusBox.dataset.statusUrl;
    const resetUrl        = statusBox.dataset.resetUrl;

    // Track the timestamp when generation started (for stale detection)
    let queuedAt = statusBox.dataset.queuedAt ? new Date(statusBox.dataset.queuedAt) : null;
    let lastProgress = {{ $abstractBookPct }};

    function isStuck(data) {
        if (!['queued', 'processing'].includes(data.status)) return false;
        const ts = data.queued_at || data.started_at;
        if (!ts) return false;
        const age = (Date.now() - new Date(ts).getTime()) / 1000;
        // Stuck if at 0% for >3 min, or no progress change for >10 min
        return (data.progress === 0 && age > 180) || age > 600;
    }

    function applyStatus(data) {
        const running        = ['queued', 'processing'].includes(data.status);
        const hasExistingPdf = Boolean(data.has_existing_pdf);
        const pct            = data.progress ?? 0;
        const msg            = data.message  ?? 'Working…';
        const stuck          = isStuck(data);

        // Generate button — disabled while running
        generateBtns.forEach(btn => {
            btn.classList.toggle('pointer-events-none', running);
            btn.classList.toggle('opacity-50', running);
        });
        generateLabels.forEach(label => {
            label.textContent = hasExistingPdf ? 'Regenerate abstract book' : 'Generate abstract book';
        });

        // Subtitle
        if (subtitle) subtitle.textContent = running
            ? (stuck ? 'Generation appears stuck — you can force reset below' : 'Generating — please wait…')
            : 'Generate in the background or download the latest PDF';

        // Progress bar
        if (progressWrap) progressWrap.classList.toggle('hidden', !running);
        if (progressBar)  progressBar.style.width = pct + '%';
        if (progressPct)  progressPct.textContent  = pct + '%';
        if (progressMsg)  progressMsg.textContent   = msg;

        // Reset button — shown only when stuck
        if (resetBtn) {
            resetBtn.classList.toggle('hidden',        !stuck);
            resetBtn.classList.toggle('inline-flex',    stuck);
        }

        // Download button — only when ready and PDF exists
        const showDownload = hasExistingPdf && !running;
        downloadLinks.forEach(el => {
            el.classList.toggle('hidden',      !showDownload);
            el.classList.toggle('inline-flex',  showDownload);
        });

        // Placeholder — only when no PDF and not running
        placeholders.forEach(el => {
            el.classList.toggle('hidden', hasExistingPdf || running);
            el.classList.toggle('block',  !hasExistingPdf && !running);
        });
    }

    // Reset button handler
    if (resetBtn) {
        resetBtn.addEventListener('click', async () => {
            resetBtn.disabled = true;
            resetBtn.querySelector('span').textContent = 'Resetting…';
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                await fetch(resetUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                });
                window.location.reload();
            } catch {
                window.location.reload();
            }
        });
    }

    async function refreshStatus() {
        try {
            const response = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            applyStatus(data);
            if (['queued', 'processing'].includes(data.status)) {
                window.setTimeout(refreshStatus, 3000);
            }
        } catch (error) {
            console.error('Failed to refresh abstract book status', error);
        }
    }

    if (['queued', 'processing'].includes(statusBox.dataset.currentStatus)) {
        refreshStatus();
    }
});
</script>
@endsection
