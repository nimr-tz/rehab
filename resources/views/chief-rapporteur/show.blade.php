@extends('layouts.app')

@section('title', 'Review Report — ' . ($report->session->name ?? 'Session'))

@php
    use App\Models\RapporteurReport;
    $author = $report->user;
    $session = $report->session;

    $statusMeta = [
        RapporteurReport::STATUS_SUBMITTED      => ['Submitted', 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', 'bg-blue-500'],
        RapporteurReport::STATUS_UNDER_REVIEW   => ['Under Review', 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300', 'bg-sky-500'],
        RapporteurReport::STATUS_NEEDS_REVISION => ['Needs Revision', 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300', 'bg-orange-500'],
        RapporteurReport::STATUS_APPROVED       => ['Approved', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300', 'bg-emerald-500'],
        RapporteurReport::STATUS_DRAFT          => ['Draft', 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300', 'bg-amber-500'],
    ];
    [$stLabel, $stChip, $stDot] = $statusMeta[$report->status] ?? [ucfirst($report->status), 'bg-slate-100 text-slate-600', 'bg-slate-400'];
@endphp

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8" x-data="{ panel: null }">

    {{-- Back + status --}}
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('chief-rapporteur.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-sky-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            All Reports
        </a>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full {{ $stChip }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $stDot }}"></span>{{ $stLabel }}
        </span>
    </div>

    @if(session('success'))
    <div class="mb-5 flex items-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm font-medium dark:bg-emerald-900/20 dark:border-emerald-700 dark:text-emerald-300">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 dark:bg-red-900/20 dark:border-red-700 dark:text-red-400">
        {{ $errors->first() }}
    </div>
    @endif

    {{-- Report header --}}
    <div class="bg-gradient-to-r from-sky-600 to-blue-700 rounded-xl p-6 text-white mb-5">
        <p class="text-xs font-black uppercase tracking-widest text-white/70 mb-1">Session Rapporteur Report</p>
        <h1 class="text-xl font-bold">{{ $session->name ?? 'Unknown Session' }}</h1>
        <div class="flex flex-wrap gap-4 mt-3 text-sm text-white/80">
            <span>Filed by {{ trim(($author->title ?? '') . ' ' . ($author->first_name ?? '') . ' ' . ($author->last_name ?? '')) ?: ($author->email ?? '—') }}</span>
            @if(!empty($session?->schedule_days))<span>&bull; {{ implode(', ', $session->schedule_days) }}</span>@endif
            @if($session?->start_time)<span>&bull; {{ $session->start_time->format('H:i') }}–{{ $session->end_time?->format('H:i') }}</span>@endif
            @if($session?->room_location)<span>&bull; {{ $session->room_location }}</span>@endif
        </div>
    </div>

    {{-- ── Review action bar ── --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ route('chief-rapporteur.edit', $report) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-900/20 hover:bg-sky-100 rounded-lg border border-sky-200 dark:border-sky-800 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit in place
        </a>
        <a href="{{ route('chief-rapporteur.download', [$report, 'format' => 'pdf']) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            PDF
        </a>
        <a href="{{ route('chief-rapporteur.download', [$report, 'format' => 'word']) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Word
        </a>
        <button type="button" @click="panel = panel === 'return' ? null : 'return'"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-orange-700 dark:text-orange-300 bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 rounded-lg border border-orange-200 dark:border-orange-800 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
            Return for revision
        </button>
        <button type="button" @click="panel = panel === 'approve' ? null : 'approve'"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all ml-auto {{ $report->isApproved() ? 'opacity-60' : '' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ $report->isApproved() ? 'Approved' : 'Approve' }}
        </button>
    </div>

    {{-- Approve panel --}}
    <div x-show="panel === 'approve'" x-cloak class="bg-white dark:bg-slate-800 border border-emerald-200 dark:border-emerald-800 rounded-xl p-5 mb-5">
        <form method="POST" action="{{ route('chief-rapporteur.approve', $report) }}">
            @csrf
            <label class="block text-xs font-black uppercase tracking-widest text-emerald-600 mb-2">Approve this report</label>
            <textarea name="note" rows="2" placeholder="Optional note to the rapporteur…"
                      class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-white"></textarea>
            <div class="flex justify-end gap-2 mt-3">
                <button type="button" @click="panel = null" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Confirm Approval</button>
            </div>
        </form>
    </div>

    {{-- Return-for-revision panel --}}
    <div x-show="panel === 'return'" x-cloak class="bg-white dark:bg-slate-800 border border-orange-200 dark:border-orange-800 rounded-xl p-5 mb-5">
        <form method="POST" action="{{ route('chief-rapporteur.return', $report) }}">
            @csrf
            <label class="block text-xs font-black uppercase tracking-widest text-orange-600 mb-2">Return to rapporteur — what needs changing?</label>
            <textarea name="feedback" rows="4" required placeholder="Be specific so the rapporteur knows exactly what to fix…"
                      class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-white">{{ old('feedback') }}</textarea>
            <div class="flex justify-end gap-2 mt-3">
                <button type="button" @click="panel = null" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-lg">Send Back for Revision</button>
            </div>
        </form>
    </div>

    {{-- Last feedback given (if any) --}}
    @if($report->chief_feedback && $report->needsRevision())
    <div class="mb-5 px-4 py-3 bg-orange-50 border border-orange-200 rounded-xl dark:bg-orange-900/15 dark:border-orange-800 text-sm">
        <p class="text-xs font-black uppercase tracking-widest text-orange-600 mb-1">Awaiting rapporteur — your feedback</p>
        <p class="text-orange-800 dark:text-orange-200 whitespace-pre-wrap">{{ $report->chief_feedback }}</p>
    </div>
    @endif

    {{-- ── Report body ── --}}
    @include('rapporteur-reports.partials.report-body', ['report' => $report, 'author' => $author])

    {{-- ── Review history ── --}}
    @if(!empty($report->review_history))
    <div class="rr-show-section">
        <h2 class="rr-show-title"><span class="rr-show-num" style="background:#0ea5e9">★</span> Review History</h2>
        <ol class="relative border-l border-slate-200 dark:border-slate-700 ml-2 space-y-4">
            @foreach(array_reverse($report->review_history) as $h)
            <li class="ml-4">
                <span class="absolute -left-1.5 w-3 h-3 rounded-full
                    {{ match($h['action'] ?? '') {
                        'approved' => 'bg-emerald-500',
                        'returned_for_revision' => 'bg-orange-500',
                        'edited' => 'bg-sky-500',
                        'resubmitted' => 'bg-blue-500',
                        default => 'bg-slate-400',
                    } }}"></span>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ ucfirst(str_replace('_', ' ', $h['action'] ?? 'update')) }}
                    </span>
                    <span class="text-xs text-slate-400">by {{ $h['by_name'] ?? 'System' }}</span>
                    @if(!empty($h['at']))<span class="text-xs text-slate-400">&bull; {{ \Carbon\Carbon::parse($h['at'])->format('d M Y, H:i') }}</span>@endif
                </div>
                @if(!empty($h['note']))
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 whitespace-pre-wrap">{{ $h['note'] }}</p>
                @endif
            </li>
            @endforeach
        </ol>
    </div>
    @endif

    <div class="text-center text-xs text-slate-400 mt-6">
        Report created {{ $report->created_at->format('d M Y') }} &bull; Last updated {{ $report->updated_at->diffForHumans() }}
    </div>
</div>

@push('styles')
<style>
.rr-show-section { background: white; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.25rem; }
.dark .rr-show-section { background: #1e293b; border-color: #334155; }
.rr-show-title { display: flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem; }
.dark .rr-show-title { color: white; }
.rr-show-num { display: inline-flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; background: #3a86ff; color: white; font-size: 0.75rem; font-weight: 900; border-radius: 0.5rem; flex-shrink: 0; }
.rr-show-row { display: flex; flex-direction: column; gap: 0.25rem; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; }
.dark .rr-show-row { border-bottom-color: #334155; }
.rr-show-row:last-child { border-bottom: none; }
.rr-show-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; }
.rr-show-value { font-size: 0.875rem; color: #334155; white-space: pre-wrap; }
.dark .rr-show-value { color: #cbd5e1; }
.rr-th { padding: 0.5rem 0.75rem; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.dark .rr-th { background: #0f172a; border-color: #334155; color: #94a3b8; }
.rr-td { padding: 0.5rem 0.75rem; font-size: 0.8rem; color: #475569; border-bottom: 1px solid #f1f5f9; vertical-align: top; min-width: 100px; }
.dark .rr-td { color: #94a3b8; border-color: #1e293b; }
[x-cloak] { display: none !important; }
</style>
@endpush
@endsection
