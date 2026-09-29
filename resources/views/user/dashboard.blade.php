@extends('layouts.app')

@section('content')
@php
    $user = Auth::user();
    $short = config('conference.short_name');
    $year  = config('conference.year');

    $certReleaseAt    = \Carbon\Carbon::parse(config('conference.certificates_release_at'), config('conference.timezone'));
    $certReleased     = now()->gte($certReleaseAt);
    $hasGivenFeedback = \App\Models\ConferenceFeedback::where('user_id', $user->id)->exists();

    $correctable      = $proceedings['abstracts'] ?? collect();
    $correctionsOpen  = ($proceedings['is_open'] ?? false) && $correctable->isNotEmpty();
    $closesAt         = $proceedings['closes_at'] ?? null;

    // The headline states where the conference actually is right now, rather
    // than freezing on "the conference has ended".
    $phase = $correctionsOpen
        ? 'Conference proceedings are open for author corrections'
        : ($certReleased ? 'Post-conference — certificates available' : 'Post-conference');
@endphp

<div class="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans pb-24 transition-colors duration-300">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="relative bg-gradient-to-br from-indigo-700 via-indigo-800 to-blue-900 py-10 md:py-14 rounded-b-[2.5rem] md:rounded-b-[3.5rem] shadow-xl overflow-hidden">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.05]"></div>
            <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-blue-400/15 blur-3xl"></div>
        </div>

        <div class="max-w-6xl mx-auto px-6 sm:px-8 lg:px-10 relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-black text-indigo-200/70 uppercase tracking-[0.3em] mb-3">
                        {{ $short }} {{ $year }} &middot; {{ $phase }}
                    </p>
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-black text-white leading-tight tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        Welcome back, <span class="text-indigo-200">{{ $user->first_name }}</span>.
                    </h1>
                </div>

                @if($stats['total_submissions'] > 0)
                    <div class="flex items-center gap-6 lg:gap-9 bg-white/5 backdrop-blur-md px-6 lg:px-8 py-4 rounded-2xl border border-white/10 shrink-0">
                        <div class="text-center">
                            <p class="text-[9px] font-black text-blue-200/50 uppercase tracking-[0.25em] mb-1">Submitted</p>
                            <p class="text-2xl font-black text-white leading-none">{{ $stats['total_submissions'] }}</p>
                        </div>
                        <div class="h-9 w-px bg-white/10"></div>
                        <div class="text-center">
                            <p class="text-[9px] font-black text-blue-200/50 uppercase tracking-[0.25em] mb-1">Accepted</p>
                            <p class="text-2xl font-black text-white leading-none">{{ $stats['accepted'] }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-6 sm:px-8 lg:px-10 -mt-6 md:-mt-8 relative z-20 space-y-6">

        {{-- ── Action needed: camera-ready corrections ─────────────────── --}}
        @if($correctionsOpen)
            <div class="bg-white dark:bg-gray-900 rounded-3xl border-2 border-violet-300 dark:border-violet-700/60 shadow-xl overflow-hidden">
                <div class="bg-violet-50 dark:bg-violet-900/25 px-6 md:px-8 py-4 border-b border-violet-200 dark:border-violet-800/50">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-2.5 w-2.5 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-violet-600"></span>
                            </span>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-violet-700 dark:text-violet-300">Action needed</span>
                        </div>
                        @if($closesAt)
                            <span class="text-xs font-bold text-violet-700 dark:text-violet-300">
                                Closes {{ $closesAt->timezone(config('app.timezone'))->format('j M Y') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="p-6 md:p-8">
                    <h2 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight mb-2" style="font-family: 'Outfit', sans-serif;">
                        Review your entries for the conference proceedings
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-6 max-w-2xl">
                        Check how {{ $correctable->count() === 1 ? 'your abstract appears' : 'your abstracts appear' }} in print &mdash; the title,
                        author list, affiliations and abstract text &mdash; and correct anything that is wrong before the volume is published.
                        You also choose here whether your full text is included.
                    </p>

                    <div class="space-y-2">
                        @foreach($correctable as $abstract)
                            <a href="{{ route('abstracts.proceedings.edit', $abstract) }}"
                               class="group flex items-center gap-4 rounded-2xl border border-slate-200 dark:border-gray-700 px-4 py-3.5 hover:border-violet-400 hover:bg-violet-50/50 dark:hover:bg-violet-900/15 transition-colors">
                                <span class="font-mono text-[11px] font-black text-white bg-[#152b5e] px-2.5 py-1 rounded shrink-0 tracking-wider">
                                    {{ $abstract->conference_code }}
                                </span>
                                <span class="flex-1 min-w-0">
                                    <span class="block text-sm font-bold text-slate-800 dark:text-slate-100 truncate">
                                        {{ \App\Support\TitleFormatter::sentenceCase($abstract->title) }}
                                    </span>
                                    <span class="block text-[11px] mt-0.5 {{ $abstract->include_in_proceedings ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                        {{ $abstract->include_in_proceedings ? 'Included in the proceedings' : 'Not included in the proceedings' }}
                                        @if($abstract->proceedings_corrected_at)
                                            &middot; last corrected {{ $abstract->proceedings_corrected_at->diffForHumans() }}
                                        @endif
                                    </span>
                                </span>
                                <span class="text-[10px] font-black uppercase tracking-widest text-violet-600 dark:text-violet-300 shrink-0 inline-flex items-center gap-1.5 group-hover:gap-2.5 transition-all">
                                    Review
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Secondary cards ─────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            {{-- My submissions --}}
            <a href="{{ route('abstracts.my') }}" class="group bg-white dark:bg-gray-900 rounded-3xl p-6 md:p-7 shadow-sm border border-slate-200 dark:border-gray-800 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-300 mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-1.5" style="font-family: 'Outfit', sans-serif;">My Submissions</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-5">
                    @if($stats['total_submissions'] === 0)
                        You have no abstracts on record.
                    @else
                        {{ $stats['total_submissions'] }} {{ Str::plural('abstract', $stats['total_submissions']) }},
                        {{ $stats['accepted'] }} accepted.
                    @endif
                </p>
                <span class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-blue-600 dark:text-blue-300 group-hover:gap-3 transition-all">
                    View submissions
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            </a>

            {{-- Certificates --}}
            <a href="{{ route('certificate.index') }}" class="group bg-white dark:bg-gray-900 rounded-3xl p-6 md:p-7 shadow-sm border border-slate-200 dark:border-gray-800 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                <div class="flex items-start justify-between mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 flex items-center justify-center text-emerald-600 dark:text-emerald-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    @if(!$certReleased)
                        <span data-cert-countdown data-release="{{ $certReleaseAt->toIso8601String() }}" class="px-2.5 py-1 text-[9px] font-black bg-slate-900 dark:bg-black text-white rounded-lg tracking-widest tabular-nums whitespace-nowrap">&hellip;</span>
                    @else
                        <span class="px-2.5 py-1 text-[9px] font-black bg-emerald-500 text-white rounded-lg uppercase tracking-widest">Available</span>
                    @endif
                </div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-1.5" style="font-family: 'Outfit', sans-serif;">Certificates</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-5">
                    @if(!$certReleased)
                        Unlock {{ $certReleaseAt->format('j F \a\t H:i') }}.
                    @else
                        Attendance and presentation certificates are ready.
                    @endif
                    @unless($hasGivenFeedback)
                        <span class="text-amber-600 dark:text-amber-400 font-semibold">Feedback required first.</span>
                    @endunless
                </p>
                <span class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-300 group-hover:gap-3 transition-all">
                    {{ $certReleased ? 'Download' : 'View status' }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            </a>

            {{-- Feedback --}}
            <a href="{{ route('feedback.create') }}" class="group bg-white dark:bg-gray-900 rounded-3xl p-6 md:p-7 shadow-sm border border-slate-200 dark:border-gray-800 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                <div class="flex items-start justify-between mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/40 flex items-center justify-center text-amber-600 dark:text-amber-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    @if($hasGivenFeedback)
                        <span class="px-2.5 py-1 text-[9px] font-black bg-slate-200 dark:bg-gray-700 text-slate-600 dark:text-slate-300 rounded-lg uppercase tracking-widest">Submitted</span>
                    @endif
                </div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-1.5" style="font-family: 'Outfit', sans-serif;">Conference Feedback</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-5">
                    @if($hasGivenFeedback)
                        Thank you &mdash; your feedback is recorded. You can update it any time.
                    @else
                        Tell us what worked and what to improve. It also unlocks your certificates.
                    @endif
                </p>
                <span class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-300 group-hover:gap-3 transition-all">
                    {{ $hasGivenFeedback ? 'Update feedback' : 'Share feedback' }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            </a>
        </div>

        {{-- Closed-window note: explains the absence rather than leaving a gap. --}}
        @if(!$correctionsOpen && $stats['accepted'] > 0)
            <div class="rounded-2xl border border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-6 py-4">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-bold text-slate-700 dark:text-slate-200">Conference proceedings.</span>
                    Corrections to your published abstract entries are not open at the moment.
                    You will be notified when you can review how your work appears in the proceedings volume.
                </p>
            </div>
        @endif
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap');
    body { font-family: 'Outfit', sans-serif; }
</style>
@endsection
