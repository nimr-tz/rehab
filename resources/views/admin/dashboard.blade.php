@extends('layouts.app')

@section('title', 'Conference Readiness - Admin')

@section('content')
{{-- Participant list modal --}}
<div x-data="participantModal()" x-cloak>
    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" @keydown.escape.window="close()">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close()"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white" x-text="title"></h3>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="subtitle"></p>
                </div>
                <div class="flex items-center gap-3">
                    <a :href="exportUrl" class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download Excel
                    </a>
                    <button @click="close()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="overflow-y-auto flex-1 px-6 py-4">
                <div x-show="loading" class="flex items-center justify-center py-16">
                    <div class="w-8 h-8 border-2 border-slate-200 border-t-indigo-500 rounded-full animate-spin"></div>
                </div>

                <div x-show="!loading && isAbstractFilter" class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">#</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Title</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Author</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Email</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Mode</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Code</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Scheduled</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in rows" :key="i">
                                <tr class="border-b border-slate-50 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="py-2 px-3 text-slate-400 tabular-nums" x-text="i + 1"></td>
                                    <td class="py-2 px-3 text-slate-700 dark:text-slate-200 max-w-[240px] truncate" x-text="row.title" :title="row.title"></td>
                                    <td class="py-2 px-3 text-slate-600 dark:text-slate-300 whitespace-nowrap" x-text="row.author"></td>
                                    <td class="py-2 px-3 text-slate-500 dark:text-slate-400" x-text="row.email"></td>
                                    <td class="py-2 px-3 capitalize" x-text="row.mode"></td>
                                    <td class="py-2 px-3 font-mono text-slate-500" x-text="row.conference_code || '—'"></td>
                                    <td class="py-2 px-3">
                                        <span :class="row.scheduled ? 'text-emerald-600' : 'text-slate-400'" x-text="row.scheduled ? 'Yes' : 'No'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="!loading && !isAbstractFilter" class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">#</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Name</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Email</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Category</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Paid</th>
                                <th class="text-left py-2 px-3 font-black text-slate-400 uppercase tracking-widest">Abstract</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in rows" :key="i">
                                <tr class="border-b border-slate-50 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="py-2 px-3 text-slate-400 tabular-nums" x-text="i + 1"></td>
                                    <td class="py-2 px-3 text-slate-700 dark:text-slate-200 whitespace-nowrap" x-text="row.name || '—'"></td>
                                    <td class="py-2 px-3 text-slate-500 dark:text-slate-400" x-text="row.email"></td>
                                    <td class="py-2 px-3 text-slate-600 dark:text-slate-300 capitalize" x-text="(row.registration_category || '').replace(/_/g, ' ')"></td>
                                    <td class="py-2 px-3">
                                        <span :class="row.paid ? 'text-emerald-600 font-bold' : 'text-red-500'" x-text="row.paid ? 'Paid' : 'Unpaid'"></span>
                                    </td>
                                    <td class="py-2 px-3">
                                        <span :class="row.has_abstracts ? 'text-indigo-600' : 'text-slate-300'" x-text="row.has_abstracts ? 'Yes' : 'No'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <p x-show="!loading && rows.length === 0" class="text-center text-sm text-slate-400 py-12">No records found.</p>
            </div>

            <div class="px-6 py-3 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-400 flex items-center justify-between">
                <span x-text="rows.length + ' record' + (rows.length !== 1 ? 's' : '')"></span>
                <span>Click "Download Excel" to export as CSV</span>
            </div>
        </div>
    </div>
</div>

<div class="max-w-[1560px] mx-auto py-8 px-5 sm:px-8 lg:px-12">

    <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-6 mb-8">
        <div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.35em] mb-3">Admin / Conference Operations</p>
            <h1 class="text-4xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight">Conference Readiness</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Registered participants, abstract presenters, payment coverage, uploads and scheduling.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 xl:min-w-[720px]">
            @php
                $topline = [
                    ['label' => 'All Participants', 'value' => $allParticipantCount, 'tone' => 'text-sky-500', 'scope' => 'all', 'filter' => 'participants'],
                    ['label' => 'With Abstracts', 'value' => $abstractParticipantCount, 'tone' => 'text-indigo-500', 'scope' => 'abstracts', 'filter' => 'participants'],
                    ['label' => 'Accepted Abstracts', 'value' => $acceptedCount, 'tone' => 'text-emerald-500', 'scope' => 'all', 'filter' => 'accepted'],
                    ['label' => 'Checked In Today', 'value' => $todayAttendance, 'tone' => 'text-amber-500'],
                ];
            @endphp

            @foreach($topline as $item)
            @if(isset($item['scope']))
            <button
                type="button"
                class="bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl px-4 py-3 text-left hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700 transition-all cursor-pointer group"
                @click="$dispatch('open-participant-modal', { scope: '{{ $item['scope'] }}', filter: '{{ $item['filter'] }}', label: '{{ $item['label'] }}', sectionTitle: 'Dashboard' })"
            >
                <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ $item['value'] }}</p>
                <p class="text-[10px] font-black uppercase tracking-widest {{ $item['tone'] }} mt-1 flex items-center gap-1">{{ $item['label'] }} <svg class="w-2.5 h-2.5 opacity-0 group-hover:opacity-60 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg></p>
            </button>
            @else
            <div class="bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl px-4 py-3">
                <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ $item['value'] }}</p>
                <p class="text-[10px] font-black uppercase tracking-widest {{ $item['tone'] }} mt-1">{{ $item['label'] }}</p>
            </div>
            @endif
            @endforeach
        </div>
    </div>

    @php
        $cardDefinitions = [
            ['key' => 'participants', 'label' => 'Registered', 'denominator' => null, 'good' => true, 'tone' => 'text-slate-700 dark:text-slate-200', 'accent' => 'bg-slate-500'],
            ['key' => 'paid', 'label' => 'Paid / Covered', 'denominator' => 'participants', 'good' => true, 'tone' => 'text-emerald-500', 'accent' => 'bg-emerald-400'],
            ['key' => 'unpaid', 'label' => 'Not Paid', 'denominator' => 'participants', 'good' => false, 'tone' => 'text-red-500', 'accent' => 'bg-red-400'],
            ['key' => 'with_abstracts', 'label' => 'With Abstracts', 'denominator' => 'participants', 'good' => true, 'tone' => 'text-indigo-500', 'accent' => 'bg-indigo-400'],
            ['key' => 'paid_with_abstracts', 'label' => 'Paid + Has Abstract', 'denominator' => 'with_abstracts', 'good' => true, 'tone' => 'text-emerald-500', 'accent' => 'bg-emerald-400'],
            ['key' => 'paid_without_abstracts', 'label' => 'Paid + No Abstract', 'denominator' => 'without_abstracts', 'good' => true, 'tone' => 'text-cyan-500', 'accent' => 'bg-cyan-400'],
            ['key' => 'unpaid_with_abstracts', 'label' => 'Not Paid + Has Abstract', 'denominator' => 'with_abstracts', 'good' => false, 'tone' => 'text-red-500', 'accent' => 'bg-red-400'],
            ['key' => 'unpaid_without_abstracts', 'label' => 'Not Paid + No Abstract', 'denominator' => 'without_abstracts', 'good' => false, 'tone' => 'text-rose-500', 'accent' => 'bg-rose-400'],
            ['key' => 'without_abstracts', 'label' => 'Without Abstracts', 'denominator' => 'participants', 'good' => null, 'tone' => 'text-cyan-500', 'accent' => 'bg-cyan-400'],
            ['key' => 'abstracts', 'label' => 'Abstracts', 'denominator' => null, 'good' => true, 'tone' => 'text-slate-700 dark:text-slate-200', 'accent' => 'bg-slate-500'],
            ['key' => 'accepted', 'label' => 'Accepted', 'denominator' => 'abstracts', 'good' => true, 'tone' => 'text-emerald-500', 'accent' => 'bg-emerald-400'],
            ['key' => 'not_accepted', 'label' => 'Not Accepted', 'denominator' => 'abstracts', 'good' => false, 'tone' => 'text-rose-500', 'accent' => 'bg-rose-400'],
            ['key' => 'uploaded', 'label' => 'Uploaded', 'denominator' => 'accepted', 'good' => true, 'tone' => 'text-teal-500', 'accent' => 'bg-teal-400'],
            ['key' => 'not_uploaded', 'label' => 'Not Uploaded', 'denominator' => 'accepted', 'good' => false, 'tone' => 'text-amber-500', 'accent' => 'bg-amber-400'],
            ['key' => 'scheduled', 'label' => 'Scheduled', 'denominator' => 'accepted', 'good' => true, 'tone' => 'text-lime-600', 'accent' => 'bg-lime-400'],
            ['key' => 'unscheduled', 'label' => 'Not Scheduled', 'denominator' => 'accepted', 'good' => false, 'tone' => 'text-red-500', 'accent' => 'bg-red-400'],
            ['key' => 'oral', 'label' => 'Oral', 'denominator' => 'accepted', 'good' => null, 'tone' => 'text-violet-500', 'accent' => 'bg-violet-400'],
            ['key' => 'poster', 'label' => 'Poster', 'denominator' => 'accepted', 'good' => null, 'tone' => 'text-fuchsia-500', 'accent' => 'bg-fuchsia-400'],
        ];
    @endphp

    <div class="space-y-10">
        @foreach($scopeSections as $section)
        @php
            $stats = $section['stats'];
            $paidPct = $stats['participants'] > 0 ? round(($stats['paid'] / $stats['participants']) * 100) : 0;
            $acceptedPct = $stats['abstracts'] > 0 ? round(($stats['accepted'] / $stats['abstracts']) * 100) : 0;
            $uploadPct = $stats['accepted'] > 0 ? round(($stats['uploaded'] / $stats['accepted']) * 100) : 0;
            $schedulePct = $stats['accepted'] > 0 ? round(($stats['scheduled'] / $stats['accepted']) * 100) : 0;
        @endphp

        <section>
            <div class="flex items-center gap-4 mb-4">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-3 h-3 rounded-full {{ $section['accent'] }} flex-shrink-0"></span>
                    <div>
                        <h2 class="text-[11px] font-black text-slate-500 dark:text-slate-300 uppercase tracking-[0.32em] whitespace-nowrap">{{ $section['title'] }}</h2>
                        <p class="text-xs text-slate-400 mt-1">{{ $section['note'] }}</p>
                    </div>
                </div>
                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                @foreach([
                    ['label' => 'Payment', 'value' => $paidPct, 'tone' => $stats['unpaid'] > 0 ? 'text-red-500' : 'text-emerald-500', 'accent' => $stats['unpaid'] > 0 ? 'bg-red-400' : 'bg-emerald-400'],
                    ['label' => 'Acceptance', 'value' => $acceptedPct, 'tone' => 'text-emerald-500', 'accent' => 'bg-emerald-400'],
                    ['label' => 'Upload', 'value' => $uploadPct, 'tone' => $stats['not_uploaded'] > 0 ? 'text-amber-500' : 'text-teal-500', 'accent' => $stats['not_uploaded'] > 0 ? 'bg-amber-400' : 'bg-teal-400'],
                    ['label' => 'Schedule', 'value' => $schedulePct, 'tone' => $stats['unscheduled'] > 0 ? 'text-red-500' : 'text-lime-600', 'accent' => $stats['unscheduled'] > 0 ? 'bg-red-400' : 'bg-lime-400'],
                ] as $meter)
                <div class="bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[10px] font-black uppercase tracking-widest {{ $meter['tone'] }}">{{ $meter['label'] }}</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white tabular-nums">{{ $meter['value'] }}%</p>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-1.5 rounded-full {{ $meter['accent'] }}" style="width: {{ $meter['value'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
                @foreach($cardDefinitions as $card)
                @php
                    $value = $stats[$card['key']] ?? 0;
                    $denominatorValue = $card['denominator'] ? ($stats[$card['denominator']] ?? 0) : null;
                    $pct = $denominatorValue && $denominatorValue > 0 ? round(($value / $denominatorValue) * 100) : null;
                    $isAttention = $card['good'] === false && $value > 0;
                    $border = $isAttention ? 'border-red-200 dark:border-red-900/50' : 'border-slate-200 dark:border-slate-800';
                @endphp

                <button
                    type="button"
                    class="bg-white dark:bg-slate-900/60 border {{ $border }} rounded-2xl p-4 min-h-[128px] text-left w-full hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700 transition-all cursor-pointer group"
                    @click="$dispatch('open-participant-modal', { scope: '{{ $section['scope'] }}', filter: '{{ $card['key'] }}', label: '{{ $card['label'] }}', sectionTitle: '{{ $section['title'] }}' })"
                >
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <p class="text-3xl font-black text-slate-900 dark:text-white tabular-nums leading-none">
                            {{ $value }}
                            @if($denominatorValue !== null)
                                <span class="text-sm font-semibold text-slate-400">/ {{ $denominatorValue }}</span>
                            @endif
                        </p>
                        <span class="w-2.5 h-2.5 rounded-full {{ $card['accent'] }} mt-1.5 flex-shrink-0"></span>
                    </div>

                    @if($pct !== null)
                    <p class="text-xs font-bold text-slate-400 tabular-nums mb-2">{{ $pct }}%</p>
                    <div class="w-full h-1 rounded-full bg-slate-100 dark:bg-slate-800 mb-3">
                        <div class="h-1 rounded-full {{ $card['accent'] }}" style="width: {{ $pct }}%"></div>
                    </div>
                    @else
                    <div class="h-6"></div>
                    @endif

                    <p class="text-[10px] font-black uppercase tracking-widest {{ $card['tone'] }} leading-snug flex items-center gap-1">
                        {{ $card['label'] }}
                        <svg class="w-2.5 h-2.5 opacity-0 group-hover:opacity-60 transition-opacity flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </p>
                </button>
                @endforeach
            </div>
        </section>
        @endforeach
    </div>

    @if($orphanedAcceptedCount > 0)
    <p class="text-xs text-red-500 mt-6">{{ $orphanedAcceptedCount }} accepted {{ Str::plural('abstract', $orphanedAcceptedCount) }} {{ $orphanedAcceptedCount === 1 ? 'has' : 'have' }} no linked user record.</p>
    @endif

</div>

@push('scripts')
<script>
function participantModal() {
    return {
        open: false,
        loading: false,
        title: '',
        subtitle: '',
        exportUrl: '',
        rows: [],
        isAbstractFilter: false,
        abstractFilters: ['abstracts', 'accepted', 'not_accepted', 'uploaded', 'not_uploaded', 'scheduled', 'unscheduled', 'oral', 'poster'],

        init() {
            window.addEventListener('open-participant-modal', (e) => {
                const { scope, filter, label, sectionTitle } = e.detail;
                this.title = sectionTitle + ' — ' + label;
                this.subtitle = 'Loading...';
                this.isAbstractFilter = this.abstractFilters.includes(filter);
                this.exportUrl = '{{ route("admin.dashboard.participants.export") }}?scope=' + scope + '&filter=' + filter;
                this.open = true;
                this.loading = true;
                this.rows = [];

                fetch('{{ route("admin.dashboard.participants") }}?scope=' + scope + '&filter=' + filter, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    this.rows = data.rows || [];
                    this.subtitle = this.rows.length + ' record' + (this.rows.length !== 1 ? 's' : '');
                    this.loading = false;
                })
                .catch(() => {
                    this.subtitle = 'Failed to load.';
                    this.loading = false;
                });
            });
        },

        close() {
            this.open = false;
        }
    };
}
</script>
@endpush
@endsection
