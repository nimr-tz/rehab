@extends('layouts.app')

@section('title', 'Workflow Diagnostics')

@php
    $cardBase = 'rounded-3xl border border-slate-200/70 dark:border-slate-800/70 bg-white dark:bg-slate-900 shadow-soft p-6';
@endphp

@section('content')
<div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    @if(session('success') || session('error') || session('info'))
        <div class="rounded-2xl border px-5 py-4 text-sm font-semibold
            @if(session('success')) border-emerald-200 bg-emerald-50 text-emerald-800
            @elseif(session('error')) border-rose-200 bg-rose-50 text-rose-800
            @else border-sky-200 bg-sky-50 text-sky-800 @endif">
            {{ session('success') ?? session('error') ?? session('info') }}
        </div>
    @endif

    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.25em] text-slate-400">Workflow Diagnostics</p>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Review Status Drift</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Read-only checks for abstracts whose reviewer outcomes, stored status, and acceptance email trail may be out of sync.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('admin.diagnostics.workflow.email-drift.bulk-accept') }}" method="POST" onsubmit="return confirm('This will mark all abstracts with an acceptance email but non-accepted status as accepted, without sending a second acceptance email. Continue?');">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-bold text-teal-700 hover:bg-teal-100">
                    Bulk Accept Email Drift
                </button>
            </form>
            <form action="{{ route('admin.diagnostics.workflow.stale.bulk-accept') }}" method="POST" onsubmit="return confirm('This will accept stale recycled abstracts whose best available score is 70 or above. Continue?');">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 hover:bg-emerald-100">
                    Bulk Accept Stale 70+
                </button>
            </form>
            <form action="{{ route('admin.diagnostics.workflow.bulk-repair') }}" method="POST" onsubmit="return confirm('Bulk repair will move unanimous current-round accepts to Accepted and other fully reviewed stale records to Decision Required. Continue?');">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-fuchsia-200 bg-fuchsia-50 px-4 py-2.5 text-sm font-bold text-fuchsia-700 hover:bg-fuchsia-100">
                    Bulk Repair Stale Statuses
                </button>
            </form>
            <form action="{{ route('admin.diagnostics.workflow.bulk-repair-revision-path') }}" method="POST" onsubmit="return confirm('Bulk repair will restore legacy revision-drift records into the revision workflow and recreate the needed re-reviewer seats. Continue?');">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-700 hover:bg-amber-100">
                    Bulk Repair Revision Drift
                </button>
            </form>
            <form action="{{ route('admin.diagnostics.workflow.bulk-repair-assignment-status') }}" method="POST" onsubmit="return confirm('Bulk cleanup will downgrade records whose stored status no longer matches their current reviewer assignment shape. Continue?');">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-bold text-sky-700 hover:bg-sky-100">
                    Bulk Clean Assignment Drift
                </button>
            </form>
            <a href="{{ route('admin.diagnostics.trace.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-300">
                Open Workflow Trace
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Scanned</p>
            <p class="mt-4 text-4xl font-black text-slate-900 dark:text-white">{{ $summary['scanned_abstracts'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Assigned abstracts checked</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-indigo-500">Initial Peer Review</p>
            <p class="mt-4 text-4xl font-black text-indigo-600 dark:text-indigo-400">{{ $summary['under_review_round0'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Round 0 still in `under_review`</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-500">Unanimous Accepts</p>
            <p class="mt-4 text-4xl font-black text-emerald-600 dark:text-emerald-400">{{ $summary['unanimous_current_round_accepts'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Current-round accept/accept</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-fuchsia-500">Fully Reviewed Under Review</p>
            <p class="mt-4 text-4xl font-black text-fuchsia-600 dark:text-fuchsia-400">{{ $summary['fully_reviewed_under_review'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Both assigned reviews are in, status still stale</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-rose-500">Stuck Accepts</p>
            <p class="mt-4 text-4xl font-black text-rose-600 dark:text-rose-400">{{ $summary['stuck_unanimous_accepts'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Unanimous accepts but not `accepted`</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-amber-500">Revision Drift</p>
            <p class="mt-4 text-4xl font-black text-amber-600 dark:text-amber-400">{{ $summary['revision_workflow_drift'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Revision markers on generic peer-review statuses</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-sky-500">Assignment Drift</p>
            <p class="mt-4 text-4xl font-black text-sky-600 dark:text-sky-400">{{ $summary['assignment_status_drift'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Stored status no longer matches active reviewer seats</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-amber-500">Email Drift</p>
            <p class="mt-4 text-4xl font-black text-amber-600 dark:text-amber-400">{{ $summary['email_status_drift'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Acceptance email exists, status does not</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-sky-500">Accepted Missing Email</p>
            <p class="mt-4 text-4xl font-black text-sky-600 dark:text-sky-400">{{ $summary['accepted_without_email'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Accepted with no acceptance log</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-500">Stale Accept 70+</p>
            <p class="mt-4 text-4xl font-black text-emerald-600 dark:text-emerald-400">{{ $summary['stale_bulk_accept_eligible'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Recycled cases already strong enough to accept</p>
        </div>
        <div class="{{ $cardBase }}">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-violet-500">Stale Quick Decisions</p>
            <p class="mt-4 text-4xl font-black text-violet-600 dark:text-violet-400">{{ $summary['stale_quick_decision'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Recycled cases that still need an admin call</p>
        </div>
    </div>

    <section class="{{ $cardBase }}">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between mb-6">
            <div>
                <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">Stale Queue: Ready to Accept (70+)</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">These abstracts already have enough review evidence, but workflow recycling left them looking incomplete again.</p>
            </div>
            <div class="text-sm font-bold text-slate-500 dark:text-slate-400">{{ $staleBulkAcceptEligible->count() }} ready</div>
        </div>

        @if($staleBulkAcceptEligible->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 px-6 py-10 text-center">
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No stale abstracts are currently eligible for immediate 70+ acceptance.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($staleBulkAcceptEligible as $row)
                    <div class="rounded-3xl border border-emerald-100 bg-emerald-50/40 dark:border-emerald-900/40 dark:bg-emerald-900/10 p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-3 flex-1">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full bg-white dark:bg-slate-900 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">#{{ $row['id'] }}</span>
                                        @if($row['conference_code'])
                                            <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ $row['conference_code'] }}</span>
                                        @endif
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700">Best score {{ number_format($row['best_avg_score'], 1) }}</span>
                                        <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ $row['status_label'] }}</span>
                                        @if($row['reviewer_changed'])
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700">Reviewer changed after revision</span>
                                        @endif
                                    </div>
                                    <h3 class="mt-3 text-lg font-black tracking-tight text-slate-900 dark:text-white">{{ $row['title'] }}</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $row['author'] }} · {{ $row['author_email'] }}</p>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-2xl bg-white dark:bg-slate-900 px-4 py-3 border border-slate-200/70 dark:border-slate-800/70">
                                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">What Happened</p>
                                        <p class="mt-2 font-semibold text-slate-700 dark:text-slate-200">{{ $row['plain_explanation'] }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-white dark:bg-slate-900 px-4 py-3 border border-slate-200/70 dark:border-slate-800/70">
                                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Recommended Action</p>
                                        <div class="mt-2 space-y-1">
                                            @foreach($row['recommendation_summary'] as $recommendation)
                                                <p class="font-semibold text-emerald-700 dark:text-emerald-300">{{ $recommendation }}</p>
                                            @endforeach
                                        </div>
                                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Current round {{ $row['current_round'] }} · {{ $row['current_completed_count'] }}/{{ $row['current_assigned_count'] }} active reviews completed · Best evidence from {{ $row['best_round_label'] }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                                    @foreach($row['best_reviews'] as $review)
                                        <div class="rounded-2xl bg-white dark:bg-slate-900 px-4 py-3 border border-slate-200/70 dark:border-slate-800/70">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">{{ $review['reviewer'] }}</span>
                                                <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-700">{{ $review['recommendation'] }}</span>
                                                @if(!is_null($review['score']))
                                                    <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-bold text-slate-600 dark:text-slate-300">Score {{ number_format($review['score'], 1) }}</span>
                                                @endif
                                            </div>
                                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $review['submitted_at'] ?? 'Submitted date unavailable' }} · Round {{ $review['review_round'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="w-full xl:w-72 flex-shrink-0">
                                <div class="rounded-3xl border border-emerald-200 bg-white dark:bg-slate-900 p-4 space-y-3">
                                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-emerald-600">Quick Actions</p>
                                    <form action="{{ route('admin.diagnostics.workflow.stale.accept', $row['id']) }}" method="POST" onsubmit="return confirm('Accept this stale abstract using the best available review evidence?');">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white hover:bg-emerald-700">
                                            Accept Now (70+)
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.abstracts.view', $row['id']) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                                        Open Decision Workspace
                                    </a>
                                    <a href="{{ route('admin.diagnostics.trace.show', $row['id']) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                                        Open Full Trace
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="{{ $cardBase }}">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between mb-6">
            <div>
                <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">Stale Queue: Needs Quick Admin Decision</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">These are recycled cases below 70. The trace is still available, but this list brings the decision context forward.</p>
            </div>
            <div class="text-sm font-bold text-slate-500 dark:text-slate-400">{{ $staleQuickDecision->count() }} flagged</div>
        </div>

        @if($staleQuickDecision->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 px-6 py-10 text-center">
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No recycled abstracts currently need a quick admin decision.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($staleQuickDecision as $row)
                    <div class="rounded-3xl border border-violet-100 bg-violet-50/30 dark:border-violet-900/40 dark:bg-violet-900/10 p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-3 flex-1">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full bg-white dark:bg-slate-900 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">#{{ $row['id'] }}</span>
                                        @if($row['conference_code'])
                                            <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ $row['conference_code'] }}</span>
                                        @endif
                                        <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-bold text-violet-700">Best score {{ number_format($row['best_avg_score'], 1) }}</span>
                                        <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ $row['status_label'] }}</span>
                                        @if($row['reviewer_changed'])
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700">Reviewer changed after revision</span>
                                        @endif
                                    </div>
                                    <h3 class="mt-3 text-lg font-black tracking-tight text-slate-900 dark:text-white">{{ $row['title'] }}</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $row['author'] }} · {{ $row['author_email'] }}</p>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-2xl bg-white dark:bg-slate-900 px-4 py-3 border border-slate-200/70 dark:border-slate-800/70">
                                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">What Happened</p>
                                        <p class="mt-2 font-semibold text-slate-700 dark:text-slate-200">{{ $row['plain_explanation'] }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-white dark:bg-slate-900 px-4 py-3 border border-slate-200/70 dark:border-slate-800/70">
                                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Decision Context</p>
                                        <div class="mt-2 space-y-1">
                                            @foreach($row['recommendation_summary'] as $recommendation)
                                                <p class="font-semibold text-violet-700 dark:text-violet-300">{{ $recommendation }}</p>
                                            @endforeach
                                        </div>
                                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Current round {{ $row['current_round'] }} · {{ $row['current_completed_count'] }}/{{ $row['current_assigned_count'] }} active reviews completed · Best evidence from {{ $row['best_round_label'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full xl:w-72 flex-shrink-0">
                                <div class="rounded-3xl border border-violet-200 bg-white dark:bg-slate-900 p-4 space-y-3">
                                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-violet-600">Quick Actions</p>
                                    <a href="{{ route('admin.abstracts.view', $row['id']) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 dark:bg-white px-4 py-3 text-sm font-black text-white dark:text-slate-900">
                                        Open Decision Workspace
                                    </a>
                                    <a href="{{ route('admin.diagnostics.trace.show', $row['id']) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                                        Open Full Trace
                                    </a>
                                    <a href="{{ route('admin.abstracts.view', $row['id']) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                                        Open Abstract
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @php
        $sections = [
            [
                'title' => 'Fully Reviewed But Still Under Review',
                'subtitle' => 'Currently assigned reviewers have both submitted effective-current reviews, but the abstract still carries `under_review`.',
                'rows' => $fullyReviewedUnderReview,
                'repairable' => true,
            ],
            [
                'title' => 'Stuck Unanimous Accepts',
                'subtitle' => 'Latest submitted reviews for the current round are all accept-type, but the abstract status is still not accepted.',
                'rows' => $stuckUnanimousAccepts,
                'repairable' => true,
            ],
            [
                'title' => 'Revision Workflow Drift',
                'subtitle' => 'Revision markers exist, but the abstract is still flowing through generic submission/review statuses instead of the dedicated revision workflow.',
                'rows' => $revisionWorkflowDrift,
                'repairable' => true,
                'repair_route' => 'admin.diagnostics.workflow.repair-revision-path',
                'repair_label' => 'Restore Previous Path',
            ],
            [
                'title' => 'Assignment / Status Drift',
                'subtitle' => 'The stored status expects a fuller review state than the current reviewer assignment shape actually supports.',
                'rows' => $assignmentStatusDrift,
                'repairable' => true,
                'repair_route' => 'admin.diagnostics.workflow.repair-assignment-status',
                'repair_label' => 'Clean Assignment Drift',
            ],
            [
                'title' => 'Acceptance Email / Status Drift',
                'subtitle' => 'An acceptance-style email log exists, but the current stored status is still not accepted.',
                'rows' => $emailStatusDrift,
                'repairable' => false,
            ],
            [
                'title' => 'Accepted Without Acceptance Email',
                'subtitle' => 'The abstract is accepted in the database, but there is no acceptance email trail in the log.',
                'rows' => $acceptedWithoutEmail,
                'repairable' => false,
            ],
        ];
    @endphp

    @foreach($sections as $section)
        <section class="{{ $cardBase }}">
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between mb-6">
                <div>
                    <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">{{ $section['title'] }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $section['subtitle'] }}</p>
                </div>
                <div class="text-sm font-bold text-slate-500 dark:text-slate-400">{{ $section['rows']->count() }} flagged</div>
            </div>

            @if($section['rows']->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 px-6 py-10 text-center">
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No records flagged in this check.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-left text-xs uppercase tracking-[0.16em] text-slate-400">
                                <th class="px-3 py-3">Abstract</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Round</th>
                                <th class="px-3 py-3">Latest Reviews</th>
                                <th class="px-3 py-3">Acceptance Email</th>
                                <th class="px-3 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($section['rows'] as $row)
                                <tr class="align-top">
                                    <td class="px-3 py-4">
                                        <div class="font-bold text-slate-900 dark:text-white">#{{ $row['id'] }} {{ $row['title'] }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $row['author'] }}</div>
                                        @if($row['conference_code'])
                                            <div class="mt-2 inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:text-slate-300">
                                                {{ $row['conference_code'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-700 dark:text-slate-200">
                                            {{ $row['status'] }}
                                        </div>
                                        @if($row['status_changed_at'])
                                            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                                Changed: {{ $row['status_changed_at'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 text-slate-700 dark:text-slate-200 font-semibold">
                                        {{ $row['revision_round'] }}
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="mb-3 inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:text-slate-300">
                                            {{ $row['completed_count'] }}/{{ $row['assigned_count'] }} current assigned reviews · Avg {{ $row['avg_score'] ?? 'n/a' }}
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($row['reviews'] as $review)
                                                <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/70 px-3 py-2">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="text-[11px] font-black uppercase tracking-[0.12em] text-slate-400">Reviewer {{ $review['reviewer_id'] }}</span>
                                                        <span class="rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                                                            {{ $review['recommendation'] }}
                                                        </span>
                                                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Round {{ $review['review_round'] }}</span>
                                                        @if($review['is_effective_current'])
                                                            <span class="rounded-full bg-sky-100 dark:bg-sky-900/30 px-2 py-0.5 text-[11px] font-bold text-sky-700 dark:text-sky-300">
                                                                Current
                                                            </span>
                                                        @else
                                                            <span class="rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:text-amber-300">
                                                                Historical
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                                        Submitted {{ $review['submitted_at'] ?? 'n/a' }}
                                                        @if(!is_null($review['score']))
                                                            · Score {{ $review['score'] }}
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        @if($row['latest_acceptance_email'])
                                            <div class="rounded-2xl bg-amber-50 dark:bg-amber-900/20 px-3 py-2">
                                                <div class="text-xs font-black uppercase tracking-[0.12em] text-amber-600 dark:text-amber-300">
                                                    {{ $row['acceptance_email_count'] }} log{{ $row['acceptance_email_count'] === 1 ? '' : 's' }}
                                                </div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                                    {{ $row['latest_acceptance_email']['type'] }}
                                                </div>
                                                <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                                    {{ $row['latest_acceptance_email']['created_at'] ?? 'n/a' }}
                                                </div>
                                                <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                                    {{ $row['latest_acceptance_email']['subject'] }}
                                                </div>
                                            </div>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-500 dark:text-slate-300">
                                                No acceptance email log
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex flex-col gap-2">
                                            <a href="{{ route('admin.abstracts.view', $row['id']) }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 dark:bg-white px-3 py-2 text-xs font-bold text-white dark:text-slate-900">
                                                Open Abstract
                                            </a>
                                            <a href="{{ route('admin.emails.activity-log', ['search' => $row['id']]) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-600 dark:text-slate-300">
                                                Check Email Log
                                            </a>
                                            @if($section['title'] === 'Acceptance Email / Status Drift')
                                                <form action="{{ route('admin.diagnostics.workflow.email-drift.accept', $row['id']) }}" method="POST" onsubmit="return confirm('Accept this abstract to match the acceptance email already sent, without sending another acceptance email?');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-bold text-teal-700 hover:bg-teal-100">
                                                        Accept Without Re-email
                                                    </button>
                                                </form>
                                            @endif
                                            @if($section['repairable'] ?? false)
                                                <form action="{{ route($section['repair_route'] ?? 'admin.diagnostics.workflow.repair', $row['id']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-fuchsia-200 bg-fuchsia-50 px-3 py-2 text-xs font-bold text-fuchsia-700 hover:bg-fuchsia-100">
                                                        {{ $section['repair_label'] ?? 'Re-sync Status' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endforeach
</div>
@endsection
