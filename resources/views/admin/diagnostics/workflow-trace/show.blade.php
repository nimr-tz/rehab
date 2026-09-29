@extends('layouts.app')

@section('title', 'Workflow Trace #' . $abstract->id)

@php
    $cardBase = 'rounded-3xl border border-slate-200/70 dark:border-slate-800/70 bg-white dark:bg-slate-900 shadow-soft p-6';
    $pathTone = [
        'emerald' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'amber' => 'bg-amber-100 text-amber-700 border-amber-200',
        'sky' => 'bg-sky-100 text-sky-700 border-sky-200',
        'slate' => 'bg-slate-100 text-slate-700 border-slate-200',
    ][$path['tone']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
@endphp

@section('content')
<div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">Trace #{{ $abstract->id }}</span>
                <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] {{ $pathTone }}">{{ $path['label'] }}</span>
            </div>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-900 dark:text-white">{{ $abstract->title }}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $abstract->author_name }} · {{ $abstract->user?->email ?? 'No account email' }}</p>
            <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">{{ $path['detail'] }}</p>
        </div>
        <div class="flex flex-col gap-2 xl:w-56">
            <a href="{{ route('admin.diagnostics.trace.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-sm font-black text-slate-600 dark:text-slate-300">
                Back to Trace List
            </a>
            <a href="{{ route('admin.abstracts.view', $abstract) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 dark:bg-white px-4 py-3 text-sm font-black text-white dark:text-slate-900">
                Open Abstract
            </a>
        </div>
    </div>

    @if(collect($quickFixes)->contains(true))
        <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-700">Quick Fix Available</p>
                    <p class="mt-2 text-sm font-semibold text-emerald-800">This trace already has enough signal for a direct action, so you don’t have to leave this page to repair it.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @if($quickFixes['repair_revision_path'])
                        <form action="{{ route('admin.diagnostics.workflow.repair-revision-path', $abstract) }}" method="POST" onsubmit="return confirm('Restore this abstract to its previous valid revision path now?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-3 text-sm font-black text-white hover:bg-amber-700">
                                Restore Previous Path
                            </button>
                        </form>
                    @endif

                    @if($quickFixes['resync_status'])
                        <form action="{{ route('admin.diagnostics.workflow.repair', $abstract) }}" method="POST" onsubmit="return confirm('Re-sync this abstract status based on the current completed reviews?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-fuchsia-600 px-4 py-3 text-sm font-black text-white hover:bg-fuchsia-700">
                                Re-sync Status
                            </button>
                        </form>
                    @endif

                    @if($quickFixes['clean_assignment_drift'])
                        <form action="{{ route('admin.diagnostics.workflow.repair-assignment-status', $abstract) }}" method="POST" onsubmit="return confirm('Clean the assignment/status drift for this abstract now?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-sky-600 px-4 py-3 text-sm font-black text-white hover:bg-sky-700">
                                Clean Assignment Drift
                            </button>
                        </form>
                    @endif

                    @if($quickFixes['accept_without_reemail'])
                        <form action="{{ route('admin.diagnostics.workflow.email-drift.accept', $abstract) }}" method="POST" onsubmit="return confirm('Accept this abstract to match the acceptance email already sent, without sending another acceptance email?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-teal-600 px-4 py-3 text-sm font-black text-white hover:bg-teal-700">
                                Accept Without Re-email
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if(!empty($warnings))
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-6">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-amber-700">Needs Attention</p>
            <div class="mt-3 space-y-2 text-sm font-semibold text-amber-800">
                @foreach($warnings as $warning)
                    <p>{{ $warning }}</p>
                @endforeach
            </div>
        </section>
    @endif

    <section class="grid grid-cols-1 xl:grid-cols-5 gap-4">
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Current Status</p>
            <p class="mt-4 text-3xl font-black text-slate-900 dark:text-white">{{ $statusLabel }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Revision round {{ (int) ($abstract->revision_round ?? 0) }}</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Reviews In Now</p>
            <p class="mt-4 text-3xl font-black text-slate-900 dark:text-white">{{ $progress['completed_count'] }}/{{ $progress['required_count'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $progress['assigned_count'] }} assigned reviewer slots</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Revision Stage</p>
            <p class="mt-4 text-lg font-black text-slate-900 dark:text-white">{{ $abstract->revision_requested_at ? 'Revision requested' : 'No revision yet' }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ $abstract->revision_submitted_at ? 'Author re-submitted on ' . $abstract->revision_submitted_at->toDateTimeString() : 'No author re-submission recorded yet' }}
            </p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Current Reviewers</p>
            <p class="mt-4 text-sm font-black text-slate-900 dark:text-white">{{ $abstract->reviewer1?->email ?? 'No reviewer in slot 1' }}</p>
            <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">{{ $abstract->reviewer2?->email ?? 'No reviewer in slot 2' }}</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Emails Sent</p>
            <p class="mt-4 text-3xl font-black text-slate-900 dark:text-white">{{ $emailLogs->count() }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Review and revision communications</p>
        </div>
    </section>

    <section class="{{ $cardBase }}">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">Review Rounds</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">This keeps the stored reviews visible round by round, so we can still spot mismatches when needed.</p>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($rounds as $round)
                <div class="rounded-3xl border border-slate-200/70 dark:border-slate-800/70 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ $round['label'] }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $round['submitted_count'] }} submitted · {{ $round['draft_count'] }} drafts</p>
                        </div>
                        @if($round['is_current_effective'])
                            <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-sky-700">Counts as current round</span>
                        @endif
                    </div>
                    <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @foreach($round['reviews'] as $review)
                            <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/70 p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-700 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-700 dark:text-slate-200">Reviewer {{ $review->reviewer_number }}</span>
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700">{{ $review->status }}</span>
                                    @if($review->recommendation)
                                        <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-indigo-700">{{ ucwords(str_replace('_', ' ', $review->recommendation)) }}</span>
                                    @endif
                                </div>
                                <div class="mt-3 text-sm text-slate-600 dark:text-slate-300 space-y-1">
                                    <p><span class="font-bold text-slate-900 dark:text-white">Reviewer:</span> {{ trim(($review->reviewer?->first_name ?? '') . ' ' . ($review->reviewer?->last_name ?? '')) ?: 'Unknown reviewer' }} ({{ $review->reviewer?->email ?? 'n/a' }})</p>
                                    <p><span class="font-bold text-slate-900 dark:text-white">Submitted:</span> {{ $review->submitted_at?->toDateTimeString() ?? 'Not submitted' }}</p>
                                    <p><span class="font-bold text-slate-900 dark:text-white">Score:</span> {{ $review->score ?? 'n/a' }}</p>
                                </div>
                                @if($review->comments)
                                    <div class="mt-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-3 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $review->comments }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="{{ $cardBase }}">
        <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">Plain-Language Timeline</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">This now shows the actual workflow actions first: assignments, reviews, revisions, and status changes. Email notifications are kept separately below.</p>

        <div class="mt-6 space-y-4">
            @foreach($timeline as $event)
                <div class="rounded-2xl border border-slate-200/70 dark:border-slate-800/70 px-4 py-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">{{ $event['type'] }}</span>
                                <span class="text-sm font-black text-slate-900 dark:text-white">{{ $event['label'] }}</span>
                            </div>
                            @if($event['detail'])
                                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $event['detail'] }}</p>
                            @endif
                            @if(!empty($event['meta']))
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($event['meta'] as $key => $value)
                                        @if(!is_null($value) && $value !== '')
                                            <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ $event['at']->toDateTimeString() }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="{{ $cardBase }}">
        <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">Communication Log</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">These are the automatic emails and notices that were sent because of the workflow actions above.</p>

        @if($communicationLog->isEmpty())
            <div class="mt-6 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 px-6 py-10 text-center">
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No email or notification history was found for this abstract.</p>
            </div>
        @else
            <div class="mt-6 space-y-4">
                @foreach($communicationLog as $event)
                    <div class="rounded-2xl border border-slate-200/70 dark:border-slate-800/70 px-4 py-4">
                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">email</span>
                                    <span class="text-sm font-black text-slate-900 dark:text-white">{{ $event['label'] }}</span>
                                </div>
                                @if($event['detail'])
                                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $event['detail'] }}</p>
                                @endif
                                @if(!empty($event['meta']))
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($event['meta'] as $key => $value)
                                            @if(!is_null($value) && $value !== '')
                                                <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ $key }}: {{ $value }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ $event['at']->toDateTimeString() }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
