@extends('layouts.app')

@section('title', 'Workflow Trace')

@php
    $cardBase = 'rounded-3xl border border-slate-200/70 dark:border-slate-800/70 bg-white dark:bg-slate-900 shadow-soft p-6';
@endphp

@section('content')
<div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.25em] text-slate-400">Workflow Trace</p>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Abstract Route Inspector</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Read-only trace view for non-final abstracts. This version is meant to explain the path in plain language, not just dump raw workflow data.
            </p>
        </div>
        <a href="{{ route('admin.diagnostics.workflow') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-300">
            Back to Diagnostics
        </a>
    </div>

    <section class="{{ $cardBase }}">
        <form method="GET" action="{{ route('admin.diagnostics.trace.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-7">
                <label class="block text-xs font-black uppercase tracking-[0.16em] text-slate-400 mb-2">Search</label>
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Abstract ID, title, author, email, or conference code"
                    class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 px-4 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400"
                >
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-black uppercase tracking-[0.16em] text-slate-400 mb-2">Status Scope</label>
                <select name="status" class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 px-4 py-3 text-sm text-slate-900 dark:text-white">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex items-end gap-3">
                <button type="submit" class="w-full inline-flex items-center justify-center rounded-2xl bg-slate-900 dark:bg-white px-4 py-3 text-sm font-black text-white dark:text-slate-900">
                    Trace
                </button>
            </div>
        </form>
    </section>

    <section class="{{ $cardBase }}">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">Matched Abstracts</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Showing {{ $abstracts->total() }} abstracts in this trace view.</p>
            </div>
            <div class="text-sm font-bold text-slate-500 dark:text-slate-400">{{ $abstracts->firstItem() ?? 0 }}-{{ $abstracts->lastItem() ?? 0 }}</div>
        </div>

        @if($abstracts->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 px-6 py-10 text-center">
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No abstracts matched this trace filter.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($abstracts as $row)
                    @php
                        $abstract = $row['abstract'];
                        $progress = $row['progress'];
                        $path = $row['path'];
                        $toneMap = [
                            'emerald' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'amber' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'sky' => 'bg-sky-100 text-sky-700 border-sky-200',
                            'slate' => 'bg-slate-100 text-slate-700 border-slate-200',
                        ];
                        $toneClass = $toneMap[$path['tone']] ?? $toneMap['slate'];
                    @endphp
                    <div class="rounded-3xl border border-slate-200/70 dark:border-slate-800/70 p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">#{{ $abstract->id }}</span>
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] {{ $toneClass }}">{{ $path['label'] }}</span>
                                </div>
                                <h3 class="mt-3 text-lg font-black text-slate-900 dark:text-white">{{ $abstract->title }}</h3>
                                <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                    {{ $abstract->author_name }} · {{ $abstract->user?->email ?? 'No account email' }}
                                </div>
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 text-sm">
                                    <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/70 px-4 py-3">
                                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Current status</p>
                                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ $row['status_label'] }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/70 px-4 py-3">
                                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Reviews in now</p>
                                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ $progress['completed_count'] }}/{{ $progress['required_count'] }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/70 px-4 py-3">
                                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Latest activity</p>
                                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ $row['latest_activity']['label'] ?? 'No activity yet' }}</p>
                                        @if($row['latest_activity'])
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $row['latest_activity']['at'] }}</p>
                                        @endif
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/70 px-4 py-3">
                                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">History summary</p>
                                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ $row['review_count'] }} reviews · {{ $row['revision_count'] }} revisions · {{ $row['email_count'] }} emails</p>
                                    </div>
                                </div>

                                @if(!empty($row['warnings']))
                                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                                        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-700">Needs Attention</p>
                                        <div class="mt-2 space-y-1 text-sm font-semibold text-amber-800">
                                            @foreach($row['warnings'] as $warning)
                                                <p>{{ $warning }}</p>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ $path['detail'] }}</p>
                            </div>

                            <div class="flex flex-col gap-2 xl:w-56">
                                <a href="{{ route('admin.diagnostics.trace.show', $abstract) }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-black text-white">
                                    Open Full Trace
                                </a>
                                <a href="{{ route('admin.abstracts.view', $abstract) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-sm font-black text-slate-600 dark:text-slate-300">
                                    Open Abstract
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $abstracts->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
