@extends('layouts.app')

@section('title', 'Management Dashboard')

@php
    $phaseLabels = [
        'pre_conference' => 'Pre-Conference',
        'final_preparations' => 'Final Preparations',
        'during_conference' => 'Conference Live',
        'post_conference' => 'Post-Conference',
    ];

    $m = $metrics['management'];
    $f = $metrics['finance'];

    $moneyProgress = 0;
    if (($f['expected_if_all_paid_tzs'] ?? 0) > 0) {
        $moneyProgress = max($moneyProgress, round((($f['received_tzs'] ?? 0) / $f['expected_if_all_paid_tzs']) * 100));
    }
    if (($f['expected_if_all_paid_usd'] ?? 0) > 0) {
        $moneyProgress = max($moneyProgress, round((($f['received_usd'] ?? 0) / $f['expected_if_all_paid_usd']) * 100));
    }
@endphp

@push('styles')
<style>
    .exec-shell {
        background:
            radial-gradient(circle at top left, rgba(14, 165, 233, 0.16), transparent 34%),
            radial-gradient(circle at top right, rgba(249, 115, 22, 0.14), transparent 30%),
            linear-gradient(180deg, #f8fafc 0%, #eef2ff 52%, #f8fafc 100%);
    }
    .dark .exec-shell {
        background:
            radial-gradient(circle at top left, rgba(14, 165, 233, 0.18), transparent 30%),
            radial-gradient(circle at top right, rgba(249, 115, 22, 0.16), transparent 26%),
            linear-gradient(180deg, #020617 0%, #0f172a 55%, #020617 100%);
    }
    .exec-card {
        background: rgba(255, 255, 255, 0.84);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(148, 163, 184, 0.16);
        box-shadow: 0 20px 50px -28px rgba(15, 23, 42, 0.35);
    }
    .dark .exec-card {
        background: rgba(15, 23, 42, 0.78);
        border-color: rgba(148, 163, 184, 0.12);
        box-shadow: 0 20px 50px -28px rgba(0, 0, 0, 0.7);
    }
    .metric-band {
        background-color: #0f172a;
        background-image:
            linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.92)),
            linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
    }
    .soft-grid {
        background-size: 28px 28px;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen exec-shell pb-16">
    <div class="max-w-[1600px] mx-auto px-6 py-8">
        <div class="exec-card rounded-[2rem] overflow-hidden mb-8">
            <div class="metric-band soft-grid px-8 py-8 md:px-10 md:py-10 text-white">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div class="max-w-4xl">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[11px] font-black uppercase tracking-[0.24em] text-sky-100">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            Management Dashboard
                        </div>
                        <h1 class="mt-4 text-3xl font-black tracking-tight md:text-5xl">{{ $conferenceName }}</h1>
                        <p class="mt-3 max-w-3xl text-sm text-slate-200 md:text-base">
                            Leadership dashboard for registrations, abstracts, student mix, country and institute spread, and payment performance.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:min-w-[560px]">
                        <div class="rounded-2xl bg-white/10 px-5 py-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-300">Conference Phase</p>
                            <p class="mt-2 text-xl font-bold">{{ $phaseLabels[$phase] ?? ucfirst($phase) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 px-5 py-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-300">Submission Deadline</p>
                            <p class="mt-2 text-xl font-bold">{{ $submissionDeadline->format('M d, Y') }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 px-5 py-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-300">Conference Starts</p>
                            <p class="mt-2 text-xl font-bold">{{ $conferenceStartDate->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 bg-white/80 px-6 py-6 dark:bg-slate-950/40 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/70 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900/80">
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">All Registered</p>
                    <p class="mt-3 text-4xl font-black text-slate-900 dark:text-white">{{ number_format($m['all_registered']) }}</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Individuals and group members combined.</p>
                </div>
                <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50 px-5 py-5 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-400">Registered And Paid</p>
                    <p class="mt-3 text-4xl font-black text-emerald-900 dark:text-emerald-300">{{ number_format($m['all_registered_paid']) }}</p>
                    <p class="mt-2 text-sm text-emerald-700/80 dark:text-emerald-300/80">{{ $m['paid_conversion_percent'] }}% of all registered.</p>
                </div>
                <div class="rounded-2xl border border-sky-200/80 bg-sky-50 px-5 py-5 dark:border-sky-900/40 dark:bg-sky-950/30">
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-sky-700 dark:text-sky-400">Registered With Abstracts</p>
                    <p class="mt-3 text-4xl font-black text-sky-900 dark:text-sky-300">{{ number_format($m['registered_with_abstracts']) }}</p>
                    <p class="mt-2 text-sm text-sky-700/80 dark:text-sky-300/80">{{ $m['abstract_conversion_percent'] }}% of all registered.</p>
                </div>
                <div class="rounded-2xl border border-violet-200/80 bg-violet-50 px-5 py-5 dark:border-violet-900/40 dark:bg-violet-950/30">
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-violet-700 dark:text-violet-400">All Abstracts</p>
                    <p class="mt-3 text-4xl font-black text-violet-900 dark:text-violet-300">{{ number_format($m['total_abstracts']) }}</p>
                    <p class="mt-2 text-sm text-violet-700/80 dark:text-violet-300/80">Non-draft abstracts currently in the system.</p>
                </div>
            </div>
        </div>

        @if(count($metrics['alerts']) > 0)
            <div class="mb-8 grid gap-3">
                @foreach($metrics['alerts'] as $alert)
                    <div class="exec-card flex flex-col justify-between gap-4 rounded-2xl border px-5 py-4 md:flex-row md:items-center">
                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $alert['title'] }}</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $alert['message'] }}</p>
                        </div>
                        <a href="{{ $alert['action_url'] }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                            {{ $alert['action_label'] }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1.35fr_0.95fr]">
            <div class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-2">
                    <section class="exec-card rounded-[1.75rem] p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Registration Mix</p>
                                <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Students vs Normal</h2>
                            </div>
                            <div class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                {{ $m['registration_student_percent'] }}% students
                            </div>
                        </div>
                        <div class="mt-6 grid grid-cols-2 gap-4">
                            <div class="rounded-2xl bg-amber-50 px-5 py-5 dark:bg-amber-950/30">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-amber-700 dark:text-amber-400">Students</p>
                                <p class="mt-3 text-4xl font-black text-amber-900 dark:text-amber-300">{{ number_format($m['registration_students']) }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-100 px-5 py-5 dark:bg-slate-800/80">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">Normal</p>
                                <p class="mt-3 text-4xl font-black text-slate-900 dark:text-white">{{ number_format($m['registration_normal']) }}</p>
                            </div>
                        </div>
                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500" style="width: {{ $m['registration_student_percent'] }}%"></div>
                        </div>
                    </section>

                    <section class="exec-card rounded-[1.75rem] p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Abstract Mix</p>
                                <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Student Authors vs Normal</h2>
                            </div>
                            <div class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                {{ $m['abstract_student_percent'] }}% students
                            </div>
                        </div>
                        <div class="mt-6 grid grid-cols-2 gap-4">
                            <div class="rounded-2xl bg-sky-50 px-5 py-5 dark:bg-sky-950/30">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700 dark:text-sky-400">Students</p>
                                <p class="mt-3 text-4xl font-black text-sky-900 dark:text-sky-300">{{ number_format($m['abstract_students']) }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-100 px-5 py-5 dark:bg-slate-800/80">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">Normal</p>
                                <p class="mt-3 text-4xl font-black text-slate-900 dark:text-white">{{ number_format($m['abstract_normal']) }}</p>
                            </div>
                        </div>
                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-indigo-500" style="width: {{ $m['abstract_student_percent'] }}%"></div>
                        </div>
                    </section>
                </div>

                <section class="exec-card rounded-[1.75rem] p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Finance Snapshot</p>
                            <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Paid, Received, And Expected</h2>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                            Revenue progress {{ $moneyProgress }}%
                        </div>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl bg-emerald-50 px-5 py-5 dark:bg-emerald-950/30">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-400">Cash Paid</p>
                            <p class="mt-3 text-4xl font-black text-emerald-900 dark:text-emerald-300">{{ number_format($f['paid_count']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-blue-50 px-5 py-5 dark:bg-blue-950/30">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-700 dark:text-blue-400">Waived</p>
                            <p class="mt-3 text-4xl font-black text-blue-900 dark:text-blue-300">{{ number_format($f['waived_count']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-amber-50 px-5 py-5 dark:bg-amber-950/30">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-amber-700 dark:text-amber-400">Awaiting Payment</p>
                            <p class="mt-3 text-4xl font-black text-amber-900 dark:text-amber-300">{{ number_format($f['unpaid_count']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-100 px-5 py-5 dark:bg-slate-800/80">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">Paid Coverage</p>
                            <p class="mt-3 text-4xl font-black text-slate-900 dark:text-white">{{ $m['paid_conversion_percent'] }}%</p>
                        </div>
                    </div>

                    <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 via-teal-500 to-sky-500" style="width: {{ $moneyProgress }}%"></div>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900/80">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Amount Received</p>
                            <div class="mt-4 space-y-2 text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <div class="flex items-center justify-between"><span>TZS</span><span>TSh {{ number_format($f['received_tzs']) }}</span></div>
                                <div class="flex items-center justify-between"><span>USD</span><span>${{ number_format($f['received_usd'], 2) }}</span></div>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900/80">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Expected If All Pay</p>
                            <div class="mt-4 space-y-2 text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <div class="flex items-center justify-between"><span>TZS</span><span>TSh {{ number_format($f['expected_if_all_paid_tzs']) }}</span></div>
                                <div class="flex items-center justify-between"><span>USD</span><span>${{ number_format($f['expected_if_all_paid_usd'], 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="grid gap-6 xl:grid-cols-2">
                    <section class="exec-card rounded-[1.75rem] p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Registration Geography</p>
                                <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Registrations By Country</h2>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">Top 20</span>
                        </div>
                        <div class="mt-5 max-h-[420px] overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-white dark:bg-slate-900">
                                    <tr class="border-b border-slate-200 dark:border-slate-800">
                                        <th class="py-3 text-left font-black uppercase tracking-[0.16em] text-slate-400">Country</th>
                                        <th class="py-3 text-right font-black uppercase tracking-[0.16em] text-slate-400">Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($metrics['registrations_by_country'] as $row)
                                        <tr class="border-b border-slate-100 dark:border-slate-800/60">
                                            <td class="py-3 pr-3 font-semibold text-slate-800 dark:text-slate-200">{{ $row->name }}</td>
                                            <td class="py-3 text-right font-black text-slate-900 dark:text-white">{{ number_format($row->count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="py-10 text-center text-slate-400">No data available.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="exec-card rounded-[1.75rem] p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Submission Geography</p>
                                <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Abstracts By Country</h2>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">Top 20</span>
                        </div>
                        <div class="mt-5 max-h-[420px] overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-white dark:bg-slate-900">
                                    <tr class="border-b border-slate-200 dark:border-slate-800">
                                        <th class="py-3 text-left font-black uppercase tracking-[0.16em] text-slate-400">Country</th>
                                        <th class="py-3 text-right font-black uppercase tracking-[0.16em] text-slate-400">Abstracts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($metrics['submissions_by_country'] as $row)
                                        <tr class="border-b border-slate-100 dark:border-slate-800/60">
                                            <td class="py-3 pr-3 font-semibold text-slate-800 dark:text-slate-200">{{ $row->name }}</td>
                                            <td class="py-3 text-right font-black text-slate-900 dark:text-white">{{ number_format($row->count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="py-10 text-center text-slate-400">No data available.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <aside class="space-y-6">
                <section class="exec-card rounded-[1.75rem] p-6">
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Management Notes</p>
                    <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">What leadership should watch</h2>
                    <div class="mt-5 space-y-4">
                        <div class="rounded-2xl bg-slate-100 px-4 py-4 dark:bg-slate-800/70">
                            <p class="text-sm font-black text-slate-900 dark:text-white">Payment conversion</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $m['all_registered_paid'] }} of {{ $m['all_registered'] }} registered delegates are financially cleared.</p>
                        </div>
                        <div class="rounded-2xl bg-slate-100 px-4 py-4 dark:bg-slate-800/70">
                            <p class="text-sm font-black text-slate-900 dark:text-white">Abstract participation</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $m['registered_with_abstracts'] }} registered delegates have at least one non-draft abstract.</p>
                        </div>
                        <div class="rounded-2xl bg-slate-100 px-4 py-4 dark:bg-slate-800/70">
                            <p class="text-sm font-black text-slate-900 dark:text-white">Student representation</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Students represent {{ $m['registration_student_percent'] }}% of registrations and {{ $m['abstract_student_percent'] }}% of abstract authors.</p>
                        </div>
                    </div>
                </section>

                <section class="exec-card rounded-[1.75rem] p-6">
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Reviewer Capacity</p>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-violet-50 px-4 py-4 dark:bg-violet-950/30"><p class="text-xs font-black uppercase tracking-[0.16em] text-violet-700 dark:text-violet-400">Active</p><p class="mt-2 text-3xl font-black text-violet-900 dark:text-violet-300">{{ $metrics['reviewers']['active'] }}</p></div>
                        <div class="rounded-2xl bg-sky-50 px-4 py-4 dark:bg-sky-950/30"><p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700 dark:text-sky-400">Assigned</p><p class="mt-2 text-3xl font-black text-sky-900 dark:text-sky-300">{{ $metrics['reviewers']['assigned_to_conference'] ?? 0 }}</p></div>
                        <div class="rounded-2xl bg-rose-50 px-4 py-4 dark:bg-rose-950/30"><p class="text-xs font-black uppercase tracking-[0.16em] text-rose-700 dark:text-rose-400">Overloaded</p><p class="mt-2 text-3xl font-black text-rose-900 dark:text-rose-300">{{ $metrics['reviewers']['workload_distribution']['overloaded'] }}</p></div>
                        <div class="rounded-2xl bg-emerald-50 px-4 py-4 dark:bg-emerald-950/30"><p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-400">Light</p><p class="mt-2 text-3xl font-black text-emerald-900 dark:text-emerald-300">{{ $metrics['reviewers']['workload_distribution']['light'] }}</p></div>
                    </div>
                </section>

                <section class="exec-card rounded-[1.75rem] p-6">
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Program Position</p>
                    <div class="mt-5 space-y-3">
                        <div class="flex items-center justify-between rounded-2xl bg-slate-100 px-4 py-3 dark:bg-slate-800/70"><span class="text-sm text-slate-600 dark:text-slate-400">Accepted abstracts</span><span class="text-lg font-black text-slate-900 dark:text-white">{{ $metrics['conference']['accepted_abstracts'] }}</span></div>
                        <div class="flex items-center justify-between rounded-2xl bg-slate-100 px-4 py-3 dark:bg-slate-800/70"><span class="text-sm text-slate-600 dark:text-slate-400">Sessions planned</span><span class="text-lg font-black text-slate-900 dark:text-white">{{ $metrics['conference']['sessions_planned'] }}</span></div>
                        <div class="flex items-center justify-between rounded-2xl bg-slate-100 px-4 py-3 dark:bg-slate-800/70"><span class="text-sm text-slate-600 dark:text-slate-400">Submission deadline</span><span class="text-lg font-black text-slate-900 dark:text-white">@if($metrics['timeline']['submission_deadline_passed']) Closed @else {{ $metrics['timeline']['days_to_submission_deadline'] }} days @endif</span></div>
                        <div class="flex items-center justify-between rounded-2xl bg-slate-100 px-4 py-3 dark:bg-slate-800/70"><span class="text-sm text-slate-600 dark:text-slate-400">Conference starts</span><span class="text-lg font-black text-slate-900 dark:text-white">@if($metrics['timeline']['conference_started']) Started @else {{ $metrics['timeline']['days_to_conference'] }} days @endif</span></div>
                    </div>
                </section>

                <section class="exec-card rounded-[1.75rem] p-6">
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Suggestions</p>
                    <ul class="mt-5 space-y-3 text-sm text-slate-600 dark:text-slate-400">
                        <li>Daily trend lines for registrations, payments, and abstracts across the last 30 days.</li>
                        <li>Local vs international split for registrations and abstract authors.</li>
                        <li>Payment verification queue for submitted proofs still waiting on approval.</li>
                        <li>Accepted abstracts still missing session placement.</li>
                        <li>Institute normalization report to merge duplicate spellings and abbreviations.</li>
                    </ul>
                </section>
            </aside>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section class="exec-card rounded-[1.75rem] p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Institution Footprint</p>
                        <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Registrations By Institute</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">Top 15</span>
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Free-text institutions may have spelling or abbreviation differences.</p>
                <div class="mt-5 max-h-[420px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-white dark:bg-slate-900">
                            <tr class="border-b border-slate-200 dark:border-slate-800">
                                <th class="py-3 text-left font-black uppercase tracking-[0.16em] text-slate-400">Institute</th>
                                <th class="py-3 text-right font-black uppercase tracking-[0.16em] text-slate-400">Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($metrics['registrations_by_institute'] as $row)
                                <tr class="border-b border-slate-100 dark:border-slate-800/60">
                                    <td class="py-3 pr-3 font-semibold text-slate-800 dark:text-slate-200">{{ \Illuminate\Support\Str::limit($row->name, 54) }}</td>
                                    <td class="py-3 text-right font-black text-slate-900 dark:text-white">{{ number_format($row->count) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-10 text-center text-slate-400">No data available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="exec-card rounded-[1.75rem] p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Institution Footprint</p>
                        <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Abstracts By Institute</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">Top 15</span>
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Free-text institutions may have spelling or abbreviation differences.</p>
                <div class="mt-5 max-h-[420px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-white dark:bg-slate-900">
                            <tr class="border-b border-slate-200 dark:border-slate-800">
                                <th class="py-3 text-left font-black uppercase tracking-[0.16em] text-slate-400">Institute</th>
                                <th class="py-3 text-right font-black uppercase tracking-[0.16em] text-slate-400">Abstracts</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($metrics['submissions_by_institute'] as $row)
                                <tr class="border-b border-slate-100 dark:border-slate-800/60">
                                    <td class="py-3 pr-3 font-semibold text-slate-800 dark:text-slate-200">{{ \Illuminate\Support\Str::limit($row->name, 54) }}</td>
                                    <td class="py-3 text-right font-black text-slate-900 dark:text-white">{{ number_format($row->count) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-10 text-center text-slate-400">No data available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
