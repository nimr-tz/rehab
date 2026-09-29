@extends('layouts.app')

@section('title', 'Finance Dashboard')

@php
    $totalTracked = ($stats['verified_count'] ?? 0) + ($stats['pending_count'] ?? 0) + ($stats['not_started_count'] ?? 0) + ($stats['waived_count'] ?? 0);
    $collectionRateTZS = ($revenue['projected']['TZS'] ?? 0) > 0 ? round((($revenue['realized']['TZS'] ?? 0) / $revenue['projected']['TZS']) * 100) : 0;
    $collectionRateUSD = ($revenue['projected']['USD'] ?? 0) > 0 ? round((($revenue['realized']['USD'] ?? 0) / $revenue['projected']['USD']) * 100) : 0;
    $pendingReviewCount = collect($pendingPayments ?? [])->count();
@endphp

@push('styles')
<style>
    .finance-shell {
        background:
            radial-gradient(circle at top left, rgba(20, 184, 166, 0.16), transparent 30%),
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.12), transparent 28%),
            linear-gradient(180deg, #f8fafc 0%, #ecfeff 48%, #f8fafc 100%);
    }
    .dark .finance-shell {
        background:
            radial-gradient(circle at top left, rgba(20, 184, 166, 0.18), transparent 30%),
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.14), transparent 28%),
            linear-gradient(180deg, #020617 0%, #0f172a 50%, #020617 100%);
    }
    .finance-card {
        background: rgba(255,255,255,0.84);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(148,163,184,0.14);
        box-shadow: 0 22px 60px -34px rgba(15,23,42,0.35);
    }
    .dark .finance-card {
        background: rgba(15,23,42,0.82);
        border-color: rgba(148,163,184,0.1);
        box-shadow: 0 22px 60px -34px rgba(0,0,0,0.72);
    }
    .finance-hero {
        background-image:
            linear-gradient(135deg, rgba(15,23,42,0.96), rgba(15,118,110,0.92)),
            linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px);
        background-size: auto, 28px 28px, 28px 28px;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen finance-shell pb-16">
    <div class="max-w-[1600px] mx-auto px-6 py-8">
        <div class="finance-card overflow-hidden rounded-[2rem] mb-8">
            <div class="finance-hero px-8 py-8 md:px-10 md:py-10 text-white">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div class="max-w-4xl">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[11px] font-black uppercase tracking-[0.24em] text-teal-100">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            Finance Control
                        </div>
                        <h1 class="mt-4 text-3xl font-black tracking-tight md:text-5xl">Finance Operations Desk</h1>
                        <p class="mt-3 max-w-3xl text-sm text-slate-200 md:text-base">
                            Monitor settlement performance, outstanding participant obligations, fee waivers, and reporting from one clean finance view.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[520px]">
                        <a href="{{ route('finance.export', ['type' => 'summary']) }}" class="rounded-2xl bg-white/10 px-5 py-4 transition hover:bg-white/20">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-300">Summary Report</p>
                            <p class="mt-2 text-xl font-bold">Download CSV</p>
                        </a>
                        <a href="{{ route('finance.export', ['type' => 'individuals']) }}" class="rounded-2xl bg-white/10 px-5 py-4 transition hover:bg-white/20">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-300">Individual Payments</p>
                            <p class="mt-2 text-xl font-bold">Export Participant Report</p>
                        </a>
                        <a href="{{ route('finance.export', ['type' => 'groups']) }}" class="rounded-2xl bg-white/10 px-5 py-4 transition hover:bg-white/20">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-300">Group Payments</p>
                            <p class="mt-2 text-xl font-bold">Export Group Report</p>
                        </a>
                        <a href="{{ route('finance.sponsors') }}" class="rounded-2xl bg-white/10 px-5 py-4 transition hover:bg-white/20">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-300">Sponsor Payments</p>
                            <p class="mt-2 text-xl font-bold">{{ number_format($sponsorStats['pending_count'] ?? 0) }} awaiting payment</p>
                        </a>
                        <a href="{{ route('finance.payments', ['status' => 'submitted']) }}" class="rounded-2xl bg-white/10 px-5 py-4 transition hover:bg-white/20">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-300">Pending Queue</p>
                            <p class="mt-2 text-xl font-bold">{{ number_format($stats['all_pending_count']) }} awaiting action</p>
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 bg-white/75 px-6 py-6 dark:bg-slate-950/30 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('finance.payments', ['status' => 'verified']) }}" class="rounded-2xl border border-emerald-200/80 bg-emerald-50 px-5 py-5 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-400">Settled</p>
                    <p class="mt-3 text-4xl font-black text-emerald-900 dark:text-emerald-300">{{ number_format($stats['verified_count'] ?? 0) }}</p>
                    <p class="mt-2 text-sm text-emerald-800/80 dark:text-emerald-300/80">Paid participants confirmed.</p>
                </a>
                <a href="{{ route('finance.payments', ['status' => 'submitted']) }}" class="rounded-2xl border border-amber-200/80 bg-amber-50 px-5 py-5 dark:border-amber-900/40 dark:bg-amber-950/30">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-amber-700 dark:text-amber-400">Awaiting Review</p>
                    <p class="mt-3 text-4xl font-black text-amber-900 dark:text-amber-300">{{ number_format($stats['pending_count']) }}</p>
                    <p class="mt-2 text-sm text-amber-800/80 dark:text-amber-300/80">Payment details submitted, waiting for verification.</p>
                </a>
                <a href="{{ route('finance.payments', ['status' => 'pending']) }}" class="rounded-2xl border border-slate-200/80 bg-slate-50 px-5 py-5 dark:border-slate-800 dark:bg-slate-900/80">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-600 dark:text-slate-300">Not Started</p>
                    <p class="mt-3 text-4xl font-black text-slate-900 dark:text-white">{{ number_format($stats['not_started_count']) }}</p>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">No settlement yet or no bill progress.</p>
                </a>
                <a href="{{ route('finance.payments', ['status' => 'waived']) }}" class="rounded-2xl border border-indigo-200/80 bg-indigo-50 px-5 py-5 dark:border-indigo-900/40 dark:bg-indigo-950/30">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-indigo-700 dark:text-indigo-400">Waived</p>
                    <p class="mt-3 text-4xl font-black text-indigo-900 dark:text-indigo-300">{{ number_format($stats['waived_count']) }}</p>
                    <p class="mt-2 text-sm text-indigo-800/80 dark:text-indigo-300/80">Fee exemptions granted.</p>
                </a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.25fr_0.9fr]">
            <div class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-2">
                    <section class="finance-card rounded-[1.75rem] p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">TZS Revenue</p>
                                <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Local Collection</h2>
                            </div>
                            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                {{ $collectionRateTZS }}% collected
                            </div>
                        </div>

                        <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-teal-500" style="width: {{ $collectionRateTZS }}%"></div>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-4">
                            <div class="rounded-2xl bg-emerald-50 px-5 py-5 dark:bg-emerald-950/30">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-400">Realized</p>
                                <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-300">TSh {{ number_format($revenue['realized']['TZS']) }}</p>
                            </div>
                            <div class="rounded-2xl bg-amber-50 px-5 py-5 dark:bg-amber-950/30">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-amber-700 dark:text-amber-400">Outstanding</p>
                                <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-300">TSh {{ number_format($revenue['pending']['TZS']) }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-100 px-5 py-5 dark:bg-slate-800/80">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">Waived</p>
                                <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">TSh {{ number_format($revenue['waived']['TZS']) }}</p>
                            </div>
                            <div class="rounded-2xl bg-sky-50 px-5 py-5 dark:bg-sky-950/30">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700 dark:text-sky-400">Projected</p>
                                <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-300">TSh {{ number_format($revenue['projected']['TZS']) }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="finance-card rounded-[1.75rem] p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">USD Revenue</p>
                                <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">International Collection</h2>
                            </div>
                            <div class="rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300">
                                {{ $collectionRateUSD }}% collected
                            </div>
                        </div>

                        <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-400 to-sky-500" style="width: {{ $collectionRateUSD }}%"></div>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-4">
                            <div class="rounded-2xl bg-emerald-50 px-5 py-5 dark:bg-emerald-950/30">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-400">Realized</p>
                                <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-300">${{ number_format($revenue['realized']['USD'], 2) }}</p>
                            </div>
                            <div class="rounded-2xl bg-amber-50 px-5 py-5 dark:bg-amber-950/30">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-amber-700 dark:text-amber-400">Outstanding</p>
                                <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-300">${{ number_format($revenue['pending']['USD'], 2) }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-100 px-5 py-5 dark:bg-slate-800/80">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">Waived</p>
                                <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">${{ number_format($revenue['waived']['USD'], 2) }}</p>
                            </div>
                            <div class="rounded-2xl bg-sky-50 px-5 py-5 dark:bg-sky-950/30">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700 dark:text-sky-400">Projected</p>
                                <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-300">${{ number_format($revenue['projected']['USD'], 2) }}</p>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="finance-card rounded-[1.75rem] p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Payment Streams</p>
                            <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Special Billing Lanes</h2>
                        </div>
                        <div class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            Sponsor payments are tracked alongside registrations
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <a href="{{ route('finance.sponsors') }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-5 transition hover:border-cyan-500 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/80">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Sponsors</p>
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">{{ number_format($sponsorStats['pending_count'] ?? 0) }} pending</span>
                            </div>
                            <p class="mt-3 text-xl font-black text-slate-900 dark:text-white">{{ number_format($sponsorStats['verified_count'] ?? 0) }} verified</p>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Create sponsor invoices and record sponsor payments.</p>
                        </a>
                    </div>
                </section>

                <section class="finance-card rounded-[1.75rem] p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Report Center</p>
                            <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Generate Reports</h2>
                        </div>
                        <div class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ number_format($totalTracked) }} tracked payment records
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <a href="{{ route('finance.export', ['type' => 'summary']) }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-5 transition hover:border-teal-500 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/80">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Summary</p>
                            <p class="mt-3 text-xl font-black text-slate-900 dark:text-white">Finance Summary Report</p>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Snapshot of counts, realized revenue, projected revenue, and category totals.</p>
                        </a>
                        <a href="{{ route('finance.export', ['type' => 'individuals']) }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-5 transition hover:border-teal-500 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/80">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Individuals</p>
                            <p class="mt-3 text-xl font-black text-slate-900 dark:text-white">Participant Payment Report</p>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Detailed participant payment export with category, amount, status, and notes.</p>
                        </a>
                        <a href="{{ route('finance.export', ['type' => 'groups']) }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-5 transition hover:border-teal-500 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/80">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Groups</p>
                            <p class="mt-3 text-xl font-black text-slate-900 dark:text-white">Group Payment Report</p>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Group name, leader, organization, member count, total amount, and payment status.</p>
                        </a>
                        <a href="{{ route('finance.export', ['type' => 'sponsors']) }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-5 transition hover:border-teal-500 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/80">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Sponsors</p>
                            <p class="mt-3 text-xl font-black text-slate-900 dark:text-white">Sponsor Payment Report</p>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Sponsor invoices with amount, payment reference and verification details.</p>
                        </a>
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="finance-card rounded-[1.75rem] p-6">
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Today</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Daily Pulse</h2>
                    <div class="mt-6 grid gap-4">
                        <div class="rounded-2xl bg-teal-950 px-5 py-5 text-white">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-teal-200">Verified Today</p>
                            <p class="mt-3 text-5xl font-black">{{ number_format($todayVerifications) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-100 px-5 py-5 dark:bg-slate-800/80">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Today's Revenue</p>
                            <div class="mt-4 space-y-2 text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <div class="flex items-center justify-between"><span>TZS</span><span>TSh {{ number_format($todayRevenue['TZS']) }}</span></div>
                                <div class="flex items-center justify-between"><span>USD</span><span>${{ number_format($todayRevenue['USD'], 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="finance-card rounded-[1.75rem] p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Action Queue</p>
                            <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Pending Reviews</h2>
                        </div>
                        <span class="rounded-2xl bg-amber-50 px-3 py-2 text-xs font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">{{ $pendingReviewCount }} participant proofs</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse($pendingPayments as $user)
                            <a href="{{ route('finance.show', $user) }}" class="block rounded-2xl border border-slate-200 bg-white px-4 py-4 transition hover:border-amber-500 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/80">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ $user->full_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                                        <p class="mt-2 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ $user->registration_category ?? 'Category not set' }}</p>
                                    </div>
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                                        Submitted
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 px-5 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                No recent pending payment submissions.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="finance-card rounded-[1.75rem] p-6">
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Category Breakdown</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Registrant Mix</h2>
                    <div class="mt-5 space-y-3">
                        @forelse($categoryStats as $category => $total)
                            <div class="flex items-center justify-between rounded-2xl bg-slate-100 px-4 py-3 dark:bg-slate-800/70">
                                <span class="text-sm font-semibold text-slate-700 capitalize dark:text-slate-300">{{ str_replace('_', ' ', $category) }}</span>
                                <span class="text-lg font-black text-slate-900 dark:text-white">{{ number_format($total) }}</span>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 px-5 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                No category data available.
                            </div>
                        @endforelse
                    </div>
                </section>

            </aside>
        </div>
    </div>
</div>
@endsection
