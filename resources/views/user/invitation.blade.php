@extends('layouts.app')

@section('content')
@php
    $acceptedCount = $acceptedAbstracts->count();
    $otherAbstracts = $abstracts->whereNotIn('status', ['accepted', 'approved']);
    $statusLabel = $invitation?->status ? ucfirst($invitation->status) : 'Available';
@endphp

<div class="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans pb-24">
    <div class="relative bg-gradient-to-br from-blue-700 via-indigo-800 to-slate-950 py-12 md:py-16 rounded-b-[2.5rem] md:rounded-b-[4rem] shadow-2xl overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.05]"></div>
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 relative z-10">
            <div class="max-w-4xl">
                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-blue-200/70 mb-4">Official Documentation</p>
                <h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-white leading-none tracking-tight" style="font-family: 'Outfit', sans-serif;">
                    Invitation Letters
                </h1>
                <p class="text-base lg:text-lg text-indigo-100/70 font-medium mt-4 max-w-2xl">
                    Download a participant invitation letter, or generate a presenter-specific letter for each accepted abstract.
                </p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-8 -mt-8 relative z-20 space-y-8">
        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-bold">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-2xl flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span class="text-sm font-bold">{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-1 bg-white dark:bg-gray-950 rounded-[2rem] shadow-sm border border-slate-200 dark:border-gray-900 overflow-hidden">
                <div class="p-8">
                    <div class="w-14 h-14 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-950 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 text-[9px] font-black uppercase tracking-[0.2em] border border-emerald-100 dark:border-emerald-900/40">{{ $statusLabel }}</span>
                        <span class="px-3 py-1 rounded-full bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-300 text-[9px] font-black uppercase tracking-[0.2em]">Participant</span>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Participant Invitation Letter</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 leading-relaxed">
                        For delegates attending {{ config('conference.short_name') }} {{ config('conference.year') }} without referencing a specific abstract. Use this for institutional clearance, visa support, or travel documentation.
                    </p>
                    <a href="{{ route('invitation.download', ['type' => 'participant']) }}" class="mt-8 inline-flex w-full items-center justify-center gap-3 px-6 py-4 rounded-2xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-950 text-xs font-black uppercase tracking-widest transition-all shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download Participant Letter
                    </a>
                </div>
            </div>

            <div class="xl:col-span-2 bg-white dark:bg-gray-950 rounded-[2rem] shadow-sm border border-slate-200 dark:border-gray-900 overflow-hidden">
                <div class="px-8 py-7 border-b border-slate-100 dark:border-gray-900 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-sky-600 dark:text-sky-300 mb-2">Accepted Abstracts</p>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Presenter Invitation Letters</h2>
                    </div>
                    <span class="px-4 py-2 rounded-full bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-300 text-[10px] font-black uppercase tracking-[0.18em] border border-sky-100 dark:border-sky-900/40">
                        {{ $acceptedCount }} Eligible
                    </span>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-gray-900">
                    @forelse($acceptedAbstracts as $abstract)
                        <div class="p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            <div class="min-w-0">
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <span class="px-3 py-1 rounded-lg bg-emerald-500 text-white text-[8px] font-black uppercase tracking-widest">Accepted</span>
                                    <span class="px-3 py-1 rounded-lg bg-slate-800 dark:bg-slate-700 text-white text-[8px] font-black uppercase tracking-widest">{{ $abstract->conference_code ?: 'Code Pending' }}</span>
                                </div>
                                <h3 class="text-base md:text-lg font-black text-slate-900 dark:text-white leading-snug">{{ $abstract->title }}</h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 font-bold uppercase tracking-widest">
                                    {{ $abstract->presentation_mode ? str_replace('_', ' ', $abstract->presentation_mode) : 'Presentation' }}
                                </p>
                            </div>
                            <a href="{{ route('invitation.download', ['type' => 'presenter', 'abstract_id' => $abstract->id]) }}" class="inline-flex items-center justify-center gap-3 px-6 py-4 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-sky-600/20 shrink-0">
                                Download Presenter Letter
                            </a>
                        </div>
                    @empty
                        <div class="p-10 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-white/5 text-slate-400 flex items-center justify-center mx-auto mb-5">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 8v4m0 4h.01M21 12A9 9 0 113 12a9 9 0 0118 0z"/></svg>
                            </div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">No accepted abstracts yet</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                                Presenter invitation letters become available after an abstract is accepted. You can still download the participant letter.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        @if($otherAbstracts->isNotEmpty())
            <div class="bg-white dark:bg-gray-950 rounded-[2rem] shadow-sm border border-slate-200 dark:border-gray-900 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 dark:border-gray-900">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-400 mb-2">Not Eligible For Presenter Letter Yet</p>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Other Abstracts</h2>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-gray-900">
                    @foreach($otherAbstracts as $abstract)
                        <div class="px-8 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $abstract->title }}</h3>
                                <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-1">{{ str_replace('_', ' ', $abstract->status) }}</p>
                            </div>
                            <span class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-white/5 text-slate-400 text-[9px] font-black uppercase tracking-widest shrink-0">
                                Presenter Letter Locked
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap');
    body { font-family: 'Outfit', sans-serif; }
</style>
@endsection
