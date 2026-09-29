@extends('layouts.app')

@section('title', 'Analytics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900">
    <div class="relative max-w-[1600px] mx-auto px-6 py-10 text-slate-900 dark:text-white">

        {{-- Executive Header System --}}
        <div class="relative overflow-hidden mb-12">
            {{-- Abstract Background Elements --}}
            <div class="absolute inset-0 opacity-40 pointer-events-none">
                <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-200 dark:bg-blue-500/20 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -bottom-24 left-1/4 w-80 h-80 bg-purple-200 dark:bg-purple-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-8 z-10">
                <div class="flex items-center gap-6">
                    <div class="p-5 bg-indigo-600 dark:bg-white rounded-[2rem] shadow-2xl shadow-indigo-200/50 dark:shadow-none transition-all group">
                        <svg class="w-10 h-10 text-white dark:text-indigo-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">Conference Analytics</h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase rounded-md border border-indigo-500/20">Operational Analytics</span>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium tracking-wide">Real-time telemetry and decision support for {{ config('conference.short_name') }} {{ config('conference.year') }}.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="px-6 py-4 bg-white/50 dark:bg-slate-800/50 backdrop-blur-xl border border-white dark:border-slate-700 rounded-2xl flex items-center gap-4 shadow-sm">
                        <div class="flex flex-col items-end">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">System Latency</span>
                            <span id="last-updated" class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ now()->format('H:i:s') }}</span>
                        </div>
                        <div class="w-3 h-3 bg-emerald-500 rounded-full animate-ping shadow-[0_0_10px_#10b981]"></div>
                    </div>
                    <button onclick="window.location.reload()" class="p-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl shadow-xl hover:-translate-y-1 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- TOP EXECUTIVE METRICS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            {{-- Total Registered --}}
            <div class="p-8 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none group transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-500/20 rounded-2xl text-indigo-600 dark:text-indigo-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Engagement</span>
                </div>
                <p class="text-4xl font-black text-slate-900 dark:text-white leading-none tracking-tighter" id="total-registered">{{ number_format($registrationStats['total_registered']) }}</p>
                <p class="text-xs font-bold text-slate-500 uppercase mt-3 tracking-widest">Registered Delegates</p>
            </div>

            {{-- Financial Flux --}}
            <div class="p-8 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none group transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-emerald-100 dark:bg-emerald-500/20 rounded-2xl text-emerald-600 dark:text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Revenue Status</span>
                </div>
                <p class="text-4xl font-black text-slate-900 dark:text-white leading-none tracking-tighter" id="total-paid">{{ number_format($registrationStats['total_paid']) }}</p>
                <p class="text-xs font-bold text-slate-500 uppercase mt-3 tracking-widest">Paid / {{ number_format($registrationStats['pending_payment']) }} Pending</p>
            </div>

            {{-- Content Volume --}}
            <div class="p-8 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none group transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-500/20 rounded-2xl text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Scientific Output</span>
                </div>
                <p class="text-4xl font-black text-slate-900 dark:text-white leading-none tracking-tighter" id="total-submitted-abstracts">{{ number_format($registrationStats['total_submitted_abstracts']) }}</p>
                <p class="text-xs font-bold text-slate-500 uppercase mt-3 tracking-widest">{{ $conversionRates['registration_to_submission'] }}% Conversion Rate</p>
            </div>

            {{-- Acceptance Fidelity --}}
            <div class="p-8 bg-slate-900 rounded-[2.5rem] border border-slate-800 shadow-2xl transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-slate-800 rounded-2xl">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Quality Yield</span>
                </div>
                <p class="text-4xl font-black text-white leading-none tracking-tighter">{{ $conversionRates['submission_to_acceptance'] }}%</p>
                <p class="text-xs font-bold text-slate-600 uppercase mt-3 tracking-widest">{{ $abstractStats['accepted'] }} of {{ $abstractStats['total_abstracts'] }} accepted</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-12 gap-10">

            {{-- Left: Deep Insights & Pipeline --}}
            <div class="lg:col-span-4 space-y-10">

                {{-- Pipeline Health --}}
                <div class="p-10 bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none mb-8">Pipeline forensic</h3>

                    <div class="space-y-4">
                        @foreach([
                            ['label' => 'Pending Review', 'count' => $abstractStats['pending_review'], 'color' => 'amber'],
                            ['label' => 'Under Review', 'count' => $abstractStats['under_review'], 'color' => 'indigo'],
                            ['label' => 'Accepted', 'count' => $abstractStats['accepted'], 'color' => 'emerald'],
                            ['label' => 'Rejected', 'count' => $abstractStats['rejected'], 'color' => 'rose'],
                            ['label' => 'Revision Required', 'count' => $abstractStats['revision'], 'color' => 'orange']
                        ] as $status)
                            <div class="flex items-center justify-between p-5 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-500">{{ $status['label'] }}</span>
                                <span class="px-4 py-1.5 bg-{{ $status['color'] }}-100 dark:bg-{{ $status['color'] }}-500/10 text-{{ $status['color'] }}-600 dark:text-{{ $status['color'] }}-400 text-sm font-black rounded-xl border border-{{ $status['color'] }}-500/20">
                                    {{ $status['count'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Operational Efficiency --}}
                <div class="p-1 text-white bg-gradient-to-br from-indigo-600 to-blue-600 rounded-[3rem] shadow-2xl">
                    <div class="bg-slate-900 h-full rounded-[2.9rem] p-10">
                        <div class="flex items-center gap-4 mb-10">
                            <div class="p-3 bg-blue-500/10 rounded-2xl text-blue-400 border border-blue-500/20">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black uppercase tracking-tighter leading-none">Review Ecosystem</h3>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1">Operational Utilization</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="p-6 bg-slate-800/50 rounded-3xl border border-slate-700 text-center">
                                <p class="text-3xl font-black">{{ $reviewerStats['total_reviewers'] }}</p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Universe</p>
                            </div>
                            <div class="p-6 bg-slate-800/50 rounded-3xl border border-slate-700 text-center">
                                <p class="text-3xl font-black text-blue-400">{{ $reviewerStats['active_reviewers'] }}</p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Active</p>
                            </div>
                        </div>

                        <div class="relative pt-6">
                            @php $utilization = $reviewerStats['total_reviewers'] > 0 ? round(($reviewerStats['active_reviewers'] / $reviewerStats['total_reviewers']) * 100, 1) : 0; @endphp
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Utilization Protocol</span>
                                <span class="text-xl font-black">{{ $utilization }}%</span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-3">
                                <div class="bg-blue-500 h-3 rounded-full shadow-[0_0_10px_#3b82f6]" style="width: {{ $utilization }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Middle/Right: Charts & Visualizations --}}
            <div class="lg:col-span-8 space-y-10">

                {{-- Global Registry Trends --}}
                <div class="p-10 bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl">
                    <div class="flex items-center justify-between mb-10">
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none">Engagement Velocity</h3>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Registry vs Submission (Last 30 Days)</p>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Registry</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Submission</span>
                            </div>
                        </div>
                    </div>

                    <div class="h-80 relative">
                        <canvas id="trendsChart"></canvas>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-10">
                    {{-- Geographic Pulse --}}
                    <div class="p-10 bg-white dark:bg-slate-800 rounded-[3rem] border border-slate-100 dark:border-slate-700 shadow-2xl">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none mb-8">Geographic Pulse</h3>
                        <div class="space-y-6">
                            @forelse($countryDistribution as $country)
                                @php $percentage = $registrationStats['total_registered'] > 0 ? ($country->count / $registrationStats['total_registered']) * 100 : 0; @endphp
                                <div class="group">
                                    <div class="flex justify-between items-end mb-2">
                                        <span class="text-xs font-black uppercase tracking-widest text-slate-700 dark:text-slate-300">{{ $country->country }}</span>
                                        <span class="text-sm font-black text-slate-900 dark:text-white">{{ $country->count }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-900/50 rounded-full h-2 overflow-hidden">
                                        <div class="bg-blue-600 dark:bg-blue-500 h-full rounded-full transition-all duration-1000 group-hover:bg-indigo-500" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm font-bold text-slate-400 text-center py-10 tracking-widest uppercase">No geographic telemetry</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Strategic KPI --}}
                    <div class="p-10 bg-white dark:bg-slate-800 rounded-[3rem] border border-slate-100 dark:border-slate-700 shadow-2xl">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none mb-10">Operational Benchmarks</h3>
                        <div class="space-y-8">
                            @foreach([
                                ['label' => 'Registry Completion', 'current' => $registrationStats['total_registered'], 'target' => 500, 'color' => 'blue'],
                                ['label' => 'Submission Yield', 'current' => $abstractStats['total_abstracts'], 'target' => 200, 'color' => 'emerald'],
                                ['label' => 'Review Progress', 'current' => ($abstractStats['accepted'] + $abstractStats['rejected']), 'target' => $abstractStats['total_abstracts'], 'color' => 'purple']
                            ] as $kpi)
                                @php $progress = $kpi['target'] > 0 ? min(($kpi['current'] / $kpi['target']) * 100, 100) : 0; @endphp
                                <div>
                                    <div class="flex justify-between items-end mb-4">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $kpi['label'] }}</p>
                                            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($kpi['current']) }} <span class="text-sm text-slate-400">/ {{ number_format($kpi['target']) }}</span></p>
                                        </div>
                                        <span class="text-sm font-black text-{{ $kpi['color'] }}-600">{{ round($progress) }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-900/50 rounded-full h-4 overflow-hidden p-1 border border-white dark:border-slate-800">
                                        <div class="bg-{{ $kpi['color'] }}-600 dark:bg-{{ $kpi['color'] }}-500 h-full rounded-full transition-all duration-1000 shadow-lg" style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-10">
                    <div class="p-10 bg-white dark:bg-slate-800 rounded-[3rem] border border-slate-100 dark:border-slate-700 shadow-2xl">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none">Presentation Mix</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Accepted abstracts by confirmed format</p>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Current</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-[2rem] border border-blue-100 dark:border-blue-500/20 bg-blue-50 dark:bg-blue-500/10 p-6">
                                <p class="text-[10px] font-black uppercase tracking-widest text-blue-500">Oral</p>
                                <p class="mt-3 text-4xl font-black text-slate-900 dark:text-white">{{ number_format($abstractStats['accepted_oral']) }}</p>
                                <p class="mt-2 text-xs font-bold uppercase tracking-widest text-slate-500">Accepted oral presentations</p>
                            </div>
                            <div class="rounded-[2rem] border border-emerald-100 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-500/10 p-6">
                                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500">Poster</p>
                                <p class="mt-3 text-4xl font-black text-slate-900 dark:text-white">{{ number_format($abstractStats['accepted_poster']) }}</p>
                                <p class="mt-2 text-xs font-bold uppercase tracking-widest text-slate-500">Poster + audio poster</p>
                            </div>
                        </div>

                        @if(($abstractStats['accepted_audio_poster'] ?? 0) > 0)
                            <p class="mt-5 text-xs font-bold text-slate-500 uppercase tracking-widest">
                                Audio posters included in poster tally: {{ number_format($abstractStats['accepted_audio_poster']) }}
                            </p>
                        @endif
                    </div>

                    <div class="p-10 bg-white dark:bg-slate-800 rounded-[3rem] border border-slate-100 dark:border-slate-700 shadow-2xl">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none">Institution Pulse</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Normalized affiliation tally for dashboard reporting</p>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Grouped aliases</span>
                        </div>

                        <div class="space-y-5">
                            @forelse($affiliationDistribution as $affiliation)
                                @php $percentage = $registrationStats['total_registered'] > 0 ? ($affiliation->count / $registrationStats['total_registered']) * 100 : 0; @endphp
                                <div class="group">
                                    <div class="flex justify-between items-end mb-2 gap-4">
                                        <span class="text-xs font-black uppercase tracking-widest text-slate-700 dark:text-slate-300">{{ $affiliation->affiliation }}</span>
                                        <span class="text-sm font-black text-slate-900 dark:text-white shrink-0">{{ $affiliation->count }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-900/50 rounded-full h-2 overflow-hidden">
                                        <div class="bg-fuchsia-600 dark:bg-fuchsia-500 h-full rounded-full transition-all duration-1000 group-hover:bg-indigo-500" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm font-bold text-slate-400 text-center py-10 tracking-widest uppercase">No institution telemetry</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Recent Transmissions Section --}}
                <div class="p-10 bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none mb-8">Recent Personnel Activity</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50 dark:border-slate-700">
                                    <th class="px-6 py-4 text-left">Entity</th>
                                    <th class="px-6 py-4 text-left">Unit Title</th>
                                    <th class="px-6 py-4 text-left">Status</th>
                                    <th class="px-6 py-4 text-right">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                                @forelse($recentActivity as $activity)
                                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-all">
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="h-8 w-8 rounded-lg bg-blue-100 dark:bg-blue-500/10 text-blue-600 flex items-center justify-center font-black text-xs">{{ $activity->user->initials }}</div>
                                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $activity->user->first_name }} {{ $activity->user->last_name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-sm font-medium text-slate-600 dark:text-slate-400">{{ Str::limit($activity->title, 30) }}</td>
                                        <td class="px-6 py-5">
                                            <span class="px-3 py-1 bg-slate-100 dark:bg-slate-900 text-slate-500 text-[10px] font-black uppercase tracking-widest rounded-full border border-slate-200 dark:border-slate-700">
                                                {{ $activity->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 text-right text-[10px] font-black text-slate-400">{{ $activity->created_at->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-10 text-center text-sm font-bold text-slate-400 uppercase tracking-widest">No activity detected</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Charts Protocol --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');

    // Gradient Setup
    const blueGradient = trendsCtx.createLinearGradient(0, 0, 0, 400);
    blueGradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
    blueGradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

    const emeraldGradient = trendsCtx.createLinearGradient(0, 0, 0, 400);
    emeraldGradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
    emeraldGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

    const trendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($registrationTrends->pluck('date')->map(function($date) { return \Carbon\Carbon::parse($date)->format('M d'); })) !!},
            datasets: [{
                label: 'Registry',
                data: {!! json_encode($registrationTrends->pluck('count')) !!},
                borderColor: '#3b82f6',
                borderWidth: 4,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                fill: true,
                backgroundColor: blueGradient,
                tension: 0.4
            }, {
                label: 'Submission',
                data: {!! json_encode($submissionTrends->pluck('count')) !!},
                borderColor: '#10b981',
                borderWidth: 4,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                fill: true,
                backgroundColor: emeraldGradient,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 10, weight: 'bold', family: 'Outfit' },
                    bodyFont: { size: 12, weight: 'bold', family: 'Outfit' },
                    padding: 12,
                    displayColors: false,
                    borderRadius: 12
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(226, 232, 240, 0.05)', drawBorder: false },
                    ticks: { color: '#94a3b8', font: { size: 10, weight: '900', family: 'Outfit' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 10, weight: '900', family: 'Outfit' } }
                }
            }
        }
    });

    // Strategy: Real-time Data Synchronization
    function updateRealtimeStats() {
        fetch('{{ route("admin.analytics.realtime") }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('total-registered').textContent = data.total_registered.toLocaleString();
                document.getElementById('total-paid').textContent = data.total_paid.toLocaleString();
                document.getElementById('total-submitted-abstracts').textContent = data.total_submitted_abstracts.toLocaleString();
                document.getElementById('last-updated').textContent = data.last_updated;
            })
            .catch(error => console.error('Intelligence Sync Failed:', error));
    }
    setInterval(updateRealtimeStats, 30000);
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap');
    :root { font-family: 'Outfit', sans-serif; }
</style>
@endsection
