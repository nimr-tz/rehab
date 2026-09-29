@extends('layouts.app')

@section('title', 'Chief Rapporteur — Reports')

@php
    use App\Models\RapporteurReport;

    $statusMeta = [
        RapporteurReport::STATUS_SUBMITTED      => ['Submitted', 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', 'bg-blue-500'],
        RapporteurReport::STATUS_UNDER_REVIEW   => ['Under Review', 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300', 'bg-sky-500'],
        RapporteurReport::STATUS_NEEDS_REVISION => ['Needs Revision', 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300', 'bg-orange-500'],
        RapporteurReport::STATUS_APPROVED       => ['Approved', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300', 'bg-emerald-500'],
        RapporteurReport::STATUS_DRAFT          => ['Draft', 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300', 'bg-amber-500'],
    ];
    $chip = fn($status) => $statusMeta[$status] ?? [ucfirst($status), 'bg-slate-100 text-slate-600', 'bg-slate-400'];
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Chief Rapporteur</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Review, refine, and approve session reports — {{ config('conference.short_name') }} {{ config('conference.year') }}</p>
        </div>
        <div class="relative" x-data="{ open: false }" @keydown.escape="open = false">
            <button type="button" @click="open = !open" @click.outside="open = false"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold rounded-lg transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Compile &amp; Download
                <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak x-transition
                 class="absolute right-0 mt-2 w-72 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl z-20 overflow-hidden">
                <p class="px-4 pt-3 pb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">Consolidated report (approved)</p>
                <a href="{{ route('chief-rapporteur.compiled', ['format' => 'pdf']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <span class="text-red-500 font-black text-xs w-9">PDF</span>
                    <span class="text-slate-700 dark:text-slate-200">Final report (print-ready)</span>
                </a>
                <a href="{{ route('chief-rapporteur.compiled', ['format' => 'word']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <span class="text-blue-600 font-black text-xs w-9">DOC</span>
                    <span class="text-slate-700 dark:text-slate-200">Editable Word document</span>
                </a>
                <a href="{{ route('chief-rapporteur.compiled', ['format' => 'html']) }}" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <span class="text-slate-500 font-black text-xs w-9">WEB</span>
                    <span class="text-slate-700 dark:text-slate-200">Preview in browser</span>
                </a>
                <div class="border-t border-slate-100 dark:border-slate-700"></div>
                <p class="px-4 pt-3 pb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">Spreadsheets (all reports)</p>
                <a href="{{ route('chief-rapporteur.export', ['type' => 'reports']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <span class="text-emerald-600 font-black text-xs w-9">CSV</span>
                    <span class="text-slate-700 dark:text-slate-200">All reports (one row each)</span>
                </a>
                <a href="{{ route('chief-rapporteur.export', ['type' => 'recommendations']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 mb-1">
                    <span class="text-emerald-600 font-black text-xs w-9">CSV</span>
                    <span class="text-slate-700 dark:text-slate-200">All recommendations</span>
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-5 flex items-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm font-medium dark:bg-emerald-900/20 dark:border-emerald-700 dark:text-emerald-300">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-6">
        @foreach($statusOrder as $st)
            @php [$label, $chipCls, $dot] = $chip($st); @endphp
            <a href="{{ route('chief-rapporteur.index', ['status' => $st]) }}"
               class="rounded-xl border p-4 bg-white dark:bg-slate-800 transition-all hover:shadow-md {{ ($filters['status'] ?? '') === $st ? 'border-sky-400 ring-2 ring-sky-200 dark:ring-sky-900' : 'border-slate-200 dark:border-slate-700' }}">
                <div class="flex items-center gap-1.5 mb-2">
                    <span class="w-2 h-2 rounded-full {{ $dot }}"></span>
                    <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $label }}</span>
                </div>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $counts[$st] ?? 0 }}</p>
            </a>
        @endforeach
        <a href="{{ route('chief-rapporteur.index') }}"
           class="rounded-xl border border-dashed border-slate-300 dark:border-slate-600 p-4 bg-slate-50 dark:bg-slate-800/40 flex flex-col justify-center items-center text-center hover:border-sky-400 transition-all">
            <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Sessions w/o report</span>
            <p class="text-2xl font-black {{ $sessionsWithoutReport->count() > 0 ? 'text-orange-500' : 'text-emerald-500' }}">{{ $sessionsWithoutReport->count() }}</p>
        </a>
    </div>

    {{-- Duplicate reports warning --}}
    @if($duplicateGroups->isNotEmpty())
    <div class="mb-6 bg-amber-50 dark:bg-amber-900/15 border border-amber-200 dark:border-amber-800 rounded-xl p-5">
        <div class="flex items-start gap-3 mb-3">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <p class="text-sm font-black text-amber-800 dark:text-amber-300">Possible duplicate submissions</p>
                <p class="text-xs text-amber-700/80 dark:text-amber-400/80">{{ $duplicateGroups->count() }} {{ \Illuminate\Support\Str::plural('title', $duplicateGroups->count()) }} {{ $duplicateGroups->count() === 1 ? 'has' : 'have' }} more than one submitted report. Review each to confirm they aren't the same report filed twice. (Drafts are ignored.)</p>
            </div>
        </div>
        <div class="space-y-2">
            @foreach($duplicateGroups as $group)
                @php $session = $group->first()->session; @endphp
                <div class="bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-800/60 rounded-lg p-3">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <p class="text-sm font-bold text-slate-800 dark:text-white truncate">{{ $session->name ?? 'Unknown session' }}</p>
                        <span class="flex-shrink-0 text-[10px] font-black uppercase tracking-wide px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ $group->count() }} reports</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($group as $dup)
                            @php [$dLabel, $dChip, $dDot] = $chip($dup->status); @endphp
                            <a href="{{ route('chief-rapporteur.show', $dup) }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/40 hover:border-sky-300 transition-all">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dDot }}"></span>
                                {{ trim(($dup->user->first_name ?? '') . ' ' . ($dup->user->last_name ?? '')) ?: ($dup->user->email ?? 'Unknown') }}
                                <span class="text-slate-400">· {{ $dLabel }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('chief-rapporteur.index') }}" class="mb-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Search</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rapporteur or session…"
                   class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-white">
        </div>
        <div class="min-w-[150px]">
            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-white">
                <option value="">All</option>
                @foreach($statusOrder as $st)
                    <option value="{{ $st }}" {{ ($filters['status'] ?? '') === $st ? 'selected' : '' }}>{{ $chip($st)[0] }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[120px]">
            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Day</label>
            <select name="day" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-white">
                <option value="">All</option>
                @foreach($days as $d)
                    <option value="{{ $d }}" {{ ($filters['day'] ?? '') === $d ? 'selected' : '' }}>{{ $d }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[200px]">
            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Subtheme</label>
            <select name="subtheme" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-white">
                <option value="">All</option>
                @foreach($subthemes as $sub)
                    <option value="{{ $sub }}" {{ ($filters['subtheme'] ?? '') === $sub ? 'selected' : '' }}>{{ \Illuminate\Support\Str::limit($sub, 40) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 rounded-lg transition-all">Filter</button>
            <a href="{{ route('chief-rapporteur.index') }}" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 rounded-lg transition-all">Reset</a>
        </div>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Reports list --}}
        <div class="lg:col-span-2 space-y-3">
            @forelse($reports as $report)
                @php [$label, $chipCls, $dot] = $chip($report->status); @endphp
                <a href="{{ route('chief-rapporteur.show', $report) }}"
                   class="block bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 hover:border-sky-300 dark:hover:border-sky-600 transition-all">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-800 dark:text-white truncate">{{ $report->session->name ?? 'Unknown Session' }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ trim(($report->user->title ?? '') . ' ' . ($report->user->first_name ?? '') . ' ' . ($report->user->last_name ?? '')) ?: ($report->user->email ?? '—') }}
                                @if(!empty($report->session?->schedule_days)) &bull; {{ implode(', ', $report->session->schedule_days) }} @endif
                                @if($report->subtheme) &bull; {{ \Illuminate\Support\Str::limit($report->subtheme, 30) }} @endif
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-full flex-shrink-0 {{ $chipCls }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>{{ $label }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 mt-2 text-[11px] text-slate-400">
                        <span>Updated {{ $report->updated_at->diffForHumans() }}</span>
                        @if($report->reviewer)<span>&bull; Reviewed by {{ $report->reviewer->first_name }} {{ $report->reviewer->last_name }}</span>@endif
                    </div>
                </a>
            @empty
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-10 text-center text-sm text-slate-400">
                    No reports match these filters.
                </div>
            @endforelse
        </div>

        {{-- Coverage + rapporteurs panels --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-black uppercase tracking-widest text-slate-500">Coverage</h2>
                    <span class="text-xs font-bold text-slate-400">{{ $totalReportable - $sessionsWithoutReport->count() }}/{{ $totalReportable }} sessions</span>
                </div>
                @php $pct = $totalReportable > 0 ? round((($totalReportable - $sessionsWithoutReport->count()) / $totalReportable) * 100) : 0; @endphp
                <div class="w-full h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden mb-4">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $pct }}%"></div>
                </div>

                @if($sessionsWithoutReport->isEmpty())
                    <p class="text-sm text-emerald-600 font-semibold">🎉 Every scientific session has a report.</p>
                @else
                    <p class="text-xs font-bold uppercase tracking-wide text-orange-500 mb-2">Awaiting a report</p>
                    <div class="space-y-1.5 max-h-[28rem] overflow-y-auto pr-1">
                        @foreach($sessionsWithoutReport as $s)
                        <div class="text-sm px-3 py-2 rounded-lg bg-orange-50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/30">
                            <p class="font-medium text-slate-700 dark:text-slate-200 truncate">{{ $s->name }}</p>
                            <p class="text-[11px] text-slate-400">
                                @if(!empty($s->schedule_days)){{ implode(', ', $s->schedule_days) }}@endif
                                @if($s->start_time) &bull; {{ $s->start_time->format('H:i') }}@endif
                                @if($s->room_location) &bull; {{ $s->room_location }}@endif
                            </p>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Rapporteurs leaderboard --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-black uppercase tracking-widest text-slate-500">Rapporteurs</h2>
                    <span class="text-xs font-bold text-slate-400">{{ $rapporteurStats->count() }} {{ \Illuminate\Support\Str::plural('person', $rapporteurStats->count()) }}</span>
                </div>
                <p class="text-[11px] text-slate-400 mb-3">Distinct submitted sessions per rapporteur (same title counted once).</p>

                @forelse($rapporteurStats as $i => $stat)
                    @php $p = $stat['user']; @endphp
                    <div class="flex items-center gap-3 py-2 {{ !$loop->last ? 'border-b border-slate-100 dark:border-slate-700/50' : '' }}">
                        <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-lg text-[11px] font-black
                            {{ $i === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400' }}">
                            {{ $i + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">
                                {{ trim(($p->title ?? '') . ' ' . ($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) ?: ($p->email ?? '—') }}
                            </p>
                            @if($p->affiliation)
                            <p class="text-[11px] text-slate-400 truncate">{{ $p->affiliation }}</p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-base font-black text-slate-900 dark:text-white leading-none">{{ $stat['total'] }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $stat['total'] === 1 ? 'session' : 'sessions' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 text-center py-6">No reports filed yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
