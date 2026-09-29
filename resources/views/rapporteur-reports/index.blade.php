@extends('layouts.app')

@section('title', 'My Rapporteur Reports')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Rapporteur Reports</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Session reports you have filed for {{ config('conference.short_name') }} {{ config('conference.year') }}</p>
        </div>
        <a href="{{ route('rapporteur-reports.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold rounded-lg transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Report
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm font-medium dark:bg-emerald-900/20 dark:border-emerald-700 dark:text-emerald-300">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if($reports->isEmpty())
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <p class="font-semibold text-slate-700 dark:text-slate-300 mb-1">No reports yet</p>
        <p class="text-sm text-slate-400 mb-5">Start by filing a report for a session you attended.</p>
        <a href="{{ route('rapporteur-reports.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold rounded-lg transition-all">
            File your first report
        </a>
    </div>
    @else
    <div class="space-y-3">
        @foreach($reports as $report)
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-5 flex items-center gap-4 hover:border-brand-300 dark:hover:border-brand-600 transition-all">
            {{-- Status dot --}}
            <div class="flex-shrink-0">
                @php
                    $statusStyles = [
                        \App\Models\RapporteurReport::STATUS_APPROVED       => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', 'bg-emerald-500'],
                        \App\Models\RapporteurReport::STATUS_NEEDS_REVISION => ['bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400', 'bg-orange-500'],
                        \App\Models\RapporteurReport::STATUS_UNDER_REVIEW   => ['bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400', 'bg-sky-500'],
                        \App\Models\RapporteurReport::STATUS_SUBMITTED      => ['bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'bg-blue-500'],
                        \App\Models\RapporteurReport::STATUS_DRAFT          => ['bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'bg-amber-500 animate-pulse'],
                    ];
                    [$chip, $dot] = $statusStyles[$report->status] ?? $statusStyles[\App\Models\RapporteurReport::STATUS_DRAFT];
                @endphp
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-full {{ $chip }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>{{ $report->statusLabel() }}
                </span>
            </div>

            {{-- Session info --}}
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-800 dark:text-white truncate">
                    {{ $report->session->name ?? 'Unknown Session' }}
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ $report->session->getPrimaryDayLabel() ?? '' }}
                    @if($report->session->start_time)
                        &bull; {{ $report->session->start_time->format('H:i') }}–{{ $report->session->end_time?->format('H:i') }}
                    @endif
                    @if($report->subtheme)
                        &bull; {{ $report->subtheme }}
                    @endif
                </p>
            </div>

            {{-- Updated at --}}
            <div class="hidden sm:block text-xs text-slate-400 flex-shrink-0">
                {{ $report->updated_at->diffForHumans() }}
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('rapporteur-reports.show', $report) }}"
                   class="px-3 py-1.5 text-xs font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 rounded-lg transition-all dark:bg-brand-900/20 dark:text-brand-400">
                    View
                </a>
                @if($report->isEditableByAuthor())
                <a href="{{ route('rapporteur-reports.edit', $report) }}"
                   class="px-3 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-all dark:bg-slate-700 dark:text-slate-300">
                    {{ $report->needsRevision() ? 'Revise' : 'Edit' }}
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
