@extends('layouts.app')

@section('title', 'Email Manager')

@section('content')
@php $isFullAdmin = auth()->user()->hasRole('admin'); @endphp
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900">
    {{-- Hero Header System --}}
    <div class="relative overflow-hidden">
        {{-- Subtle Background Pattern --}}
        <div class="absolute inset-0 opacity-30 dark:opacity-20">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-200 dark:bg-blue-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-1/4 w-[500px] h-[500px] bg-purple-200 dark:bg-purple-500/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 w-72 h-72 bg-emerald-100 dark:bg-emerald-500/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-[1600px] mx-auto px-6 py-10">
            {{-- Purpose: what this page is for --}}
            <div class="mb-6 p-4 bg-blue-50/80 dark:bg-slate-800/80 border border-blue-100 dark:border-slate-700 rounded-2xl">
                <p class="text-sm text-slate-700 dark:text-slate-300">
                    <strong>Acceptance, rejection, and revision-request emails</strong> are sent automatically when you make decisions in the Decisions queue. Use this page for <strong>presentation reminders</strong>, <strong>payment reminders</strong>, and your own <strong>group emails</strong>.
                </p>
            </div>

            {{-- Top Navigation Bar --}}
            <div class="flex items-center justify-between mb-12">
                <div class="flex items-center gap-4">
                    <div class="p-4 bg-gradient-to-br from-indigo-600 to-blue-700 rounded-2xl shadow-lg shadow-indigo-500/20">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Email Manager</h1>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-black uppercase rounded-md border border-emerald-500/20">System Active</span>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Send reminders, announcements, and bulk updates.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <form method="GET" action="{{ route('admin.emails.activity-log') }}" class="hidden lg:flex items-center gap-2 px-3 py-2.5 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" placeholder="Search email logs, subject, recipient, abstract..." class="w-80 bg-transparent border-0 text-sm font-semibold text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-0 focus:outline-none">
                        <button type="submit" class="px-3 py-1.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-lg">Search</button>
                    </form>
                    <button onclick="openBroadcastModal()"
                       class="px-5 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black rounded-xl shadow-xl hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        Compose Group Email
                    </button>
                    <button onclick="refreshQueueStats()" class="p-3 bg-white dark:bg-slate-800 text-slate-400 hover:text-blue-600 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 transition-all group">
                        <svg class="w-6 h-6 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </div>
            </div>

            {{-- Strategic Metrics --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                {{-- Outgoing Emails --}}
                <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none transition-all hover:shadow-blue-500/5 group relative overflow-hidden">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-blue-100 dark:bg-blue-500/20 rounded-xl group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </div>
                        <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase">Outgoing</span>
                    </div>
                    <p class="text-3xl font-black text-slate-900 dark:text-white leading-none">{{ $emailStats['display_sent'] ?? 0 }}</p>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mt-2">{{ ($emailStats['total_sent_today'] ?? 0) > 0 ? 'Emails Sent Today' : 'Emails Sent In Last 7 Days' }}</p>
                </div>

                {{-- Failed Emails --}}
                <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none transition-all hover:shadow-rose-500/5 group text-rose-600 dark:text-rose-400">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-rose-100 dark:bg-rose-500/20 rounded-xl group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01m-9-4a9 9 0 1118 0 9 9 0 01-18 0z"/></svg>
                        </div>
                        <span class="text-[10px] font-black uppercase">Failures</span>
                    </div>
                    <p class="text-3xl font-black leading-none">{{ ($stats['emails_failed_today'] ?? 0) > 0 ? $stats['emails_failed_today'] : ($stats['emails_failed_week'] ?? 0) }}</p>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mt-2">{{ ($stats['emails_failed_today'] ?? 0) > 0 ? 'Failed Today' : 'Failed In Last 7 Days' }}</p>
                </div>

                {{-- Presentation Reminders --}}
                <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none transition-all hover:shadow-violet-500/5 group text-violet-600 dark:text-violet-400">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-violet-100 dark:bg-violet-500/20 rounded-xl group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0020 6.83V5a1 1 0 00-1-1h-1.17a1 1 0 00-.894.553L15 8m0 2v10m0-10H5a2 2 0 00-2 2v6a2 2 0 002 2h10m0-10V8a2 2 0 00-2-2H5a2 2 0 00-2 2v2"/></svg>
                        </div>
                        <span class="text-[10px] font-black uppercase">Presentations</span>
                    </div>
                    <p class="text-3xl font-black leading-none">{{ $stats['presentation_due'] ?? 0 }}</p>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mt-2">Accepted Abstracts Awaiting Upload</p>
                </div>

                {{-- Payment Reminders --}}
                <div class="p-6 bg-slate-900 rounded-[2rem] shadow-xl transition-all group border border-slate-800">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-slate-800 rounded-xl group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <span class="text-[10px] font-black text-emerald-500 uppercase">Payments</span>
                    </div>
                    <p class="text-3xl font-black text-white leading-none">{{ $stats['payment_due'] ?? 0 }}</p>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-2">Registrations Still Unpaid</p>
                </div>
            </div>

            <div class="grid lg:grid-cols-12 gap-8">
                {{-- Left: Main Operation Deck --}}
                <div class="lg:col-span-8 space-y-8">

                    {{-- Strategic Intelligence Panel (NEW) --}}
                    @if($actionableItems->isNotEmpty())
                        <div class="p-1 text-white bg-gradient-to-r from-red-600 via-orange-500 to-indigo-600 rounded-[2.5rem] shadow-2xl animate-in slide-in-from-top duration-700">
                            <div class="bg-slate-900 rounded-[2.3rem] p-8">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse shadow-[0_0_10px_#ef4444]"></div>
                                        <h3 class="text-lg font-black uppercase tracking-tighter">Action Needed</h3>
                                    </div>
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Suggested Actions</span>
                                </div>

                                <div class="space-y-4">
                                    @foreach($actionableItems as $item)
                                        @php
                                            $actionPayload = [
                                                'key' => $item['operation_key'],
                                                'route' => route($item['action_route']),
                                                'title' => $item['title'],
                                                'button_label' => $item['action_label'],
                                                'confirm' => $item['confirm'] ?? null,
                                            ];
                                        @endphp
                                        <div class="group flex items-center justify-between p-5 bg-slate-800/40 border border-slate-700/50 rounded-2xl hover:bg-slate-800/80 transition-all">
                                            <div class="flex items-start gap-4">
                                                <div class="mt-1 p-2 rounded-lg {{ $item['type'] === 'urgent' ? 'bg-red-500/10 text-red-500' : ($item['type'] === 'warning' ? 'bg-amber-500/10 text-amber-500' : 'bg-blue-500/10 text-blue-500') }}">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        @if($item['type'] === 'urgent')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                        @else
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        @endif
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-white leading-none mb-1">{{ $item['title'] }}</h4>
                                                    <p class="text-sm text-slate-400">{{ $item['message'] }}</p>
                                                </div>
                                            </div>
                                            <button type="button"
                                                onclick='confirmOperationSend(@json($actionPayload))'
                                                class="px-5 py-2.5 bg-white text-slate-900 text-xs font-black rounded-xl hover:scale-105 active:scale-95 transition-all shadow-lg">
                                                {{ $item['action_label'] }}
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Operations Hub --}}
                    <div class="grid md:grid-cols-2 gap-6">
                        @if($isFullAdmin)
                            <div class="p-8 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] border border-emerald-100 dark:border-emerald-500/20 shadow-2xl shadow-emerald-100/50 dark:shadow-none group overflow-hidden relative">
                                <div class="absolute top-0 right-0 p-8 text-emerald-600 opacity-[0.04] group-hover:scale-125 transition-transform duration-700">
                                    <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="relative z-10">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="p-3 rounded-2xl bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m4 6H5a2 2 0 01-2-2V7a2 2 0 012-2h3l2-2h4l2 2h3a2 2 0 012 2v10a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none tracking-tight">CPD Requests</h3>
                                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400 mt-1">Post-conference export</p>
                                        </div>
                                    </div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3 leading-relaxed">
                                        <span class="text-slate-900 dark:text-white font-black">{{ $cpdStats['total'] }}</span>
                                        participants entered CPD details.
                                    </p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-6">
                                        {{ $cpdStats['complete'] }} complete · {{ $cpdStats['incomplete'] }} incomplete. The CSV includes board, registration number, contact details, and recorded attendance days.
                                    </p>
                                    <a href="{{ route('admin.emails.export-cpd-requests') }}"
                                       class="flex items-center justify-center gap-2 w-full py-4 text-white font-black rounded-2xl bg-emerald-600 shadow-xl shadow-emerald-500/20 hover:-translate-y-1 hover:shadow-emerald-500/40 transition-all {{ $cpdStats['total'] === 0 ? 'pointer-events-none opacity-50' : '' }}"
                                       @if($cpdStats['total'] === 0) aria-disabled="true" @endif>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M5 20h14"/>
                                        </svg>
                                        Download CPD Requests CSV
                                    </a>
                                </div>
                            </div>
                        @endif

                        @php
                            $themeClasses = [
                                'blue' => ['soft' => 'bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400', 'button' => 'bg-blue-600 shadow-blue-500/20 hover:shadow-blue-500/40'],
                                'rose' => ['soft' => 'bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400', 'button' => 'bg-rose-600 shadow-rose-500/20 hover:shadow-rose-500/40'],
                                'amber' => ['soft' => 'bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400', 'button' => 'bg-amber-600 shadow-amber-500/20 hover:shadow-amber-500/40'],
                                'cyan' => ['soft' => 'bg-cyan-100 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-400', 'button' => 'bg-cyan-600 shadow-cyan-500/20 hover:shadow-cyan-500/40'],
                                'indigo' => ['soft' => 'bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400', 'button' => 'bg-indigo-600 shadow-indigo-500/20 hover:shadow-indigo-500/40'],
                                'orange' => ['soft' => 'bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400', 'button' => 'bg-orange-600 shadow-orange-500/20 hover:shadow-orange-500/40'],
                                'violet' => ['soft' => 'bg-violet-100 dark:bg-violet-500/20 text-violet-600 dark:text-violet-400', 'button' => 'bg-violet-600 shadow-violet-500/20 hover:shadow-violet-500/40'],
                                'sky' => ['soft' => 'bg-sky-100 dark:bg-sky-500/20 text-sky-600 dark:text-sky-400', 'button' => 'bg-sky-600 shadow-sky-500/20 hover:shadow-sky-500/40'],
                                'emerald' => ['soft' => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400', 'button' => 'bg-emerald-600 shadow-emerald-500/20 hover:shadow-emerald-500/40'],
                            ];
                        @endphp

                        @foreach($operations as $operation)
                            @continue(($operation['admin_only'] ?? false) && !$isFullAdmin)
                            @php
                                $theme = $themeClasses[$operation['theme']] ?? $themeClasses['blue'];
                                $operationPayload = [
                                    'key' => $operation['key'],
                                    'route' => route($operation['route']),
                                    'title' => $operation['title'],
                                    'button_label' => $operation['button_label'],
                                    'confirm' => $operation['confirm'] ?? null,
                                ];
                            @endphp
                            <div class="p-8 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] border border-white dark:border-slate-700 shadow-2xl shadow-slate-200/50 dark:shadow-none group overflow-hidden relative">
                                <div class="absolute top-0 right-0 p-8 opacity-[0.03] group-hover:scale-125 transition-transform duration-700">
                                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3a9 9 0 100 18 9 9 0 000-18zm0 4a1 1 0 110 2 1 1 0 010-2zm2 9h-4v-1h1.25v-3H10v-1h3.25v4H14v1z"/></svg>
                                </div>
                                <div class="relative z-10">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="p-3 rounded-2xl {{ $theme['soft'] }}">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h10"/></svg>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none tracking-tight">{{ $operation['title'] }}</h3>
                                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mt-1">{{ $operation['eyebrow'] }}</p>
                                        </div>
                                    </div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3 leading-relaxed">
                                        <span class="text-slate-900 dark:text-white font-black">{{ $operation['count'] }}</span>
                                        {{ $operation['description'] }}
                                    </p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-6">{{ $operation['detail'] }}</p>
                                    <div class="flex gap-3">
                                        <button type="button"
                                            onclick="previewOperationEmail('{{ $operation['key'] }}')"
                                            class="px-5 py-4 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-black rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-all text-sm shrink-0">
                                            Preview
                                        </button>
                                        <button type="button"
                                            onclick='confirmOperationSend(@json($operationPayload))'
                                            class="flex-1 py-4 text-white font-black rounded-2xl shadow-xl hover:-translate-y-1 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 {{ $theme['button'] }}"
                                            {{ $operation['count'] === 0 ? 'disabled' : '' }}>
                                            {{ $operation['button_label'] }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Conference Programme Card --}}
                        <div class="p-8 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] border border-white dark:border-slate-700 shadow-2xl shadow-slate-200/50 dark:shadow-none group overflow-hidden relative">
                            <div class="absolute top-0 right-0 p-8 opacity-[0.03] group-hover:scale-125 transition-transform duration-700">
                                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/></svg>
                            </div>
                            <div class="relative z-10">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="p-3 rounded-2xl bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none tracking-tight">Conference Programme</h3>
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mt-1">{{ config('conference.host_short') }} + Paid Participants</p>
                                    </div>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-3 leading-relaxed">
                                    Send the official conference programme PDF to all attending participants — all paid or waived registrations.
                                </p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mb-6">Attach the programme PDF below, then send. Subject and message are pre-filled.</p>

                                <form action="{{ route('admin.emails.send-broadcast') }}" method="POST" enctype="multipart/form-data" id="programmeSendForm">
                                    @csrf
                                    <input type="hidden" name="target_group" value="attending">
                                    <input type="hidden" name="subject" value="{{ config('conference.short_name') }} {{ config('conference.year') }} — Conference Programme">
                                    <input type="hidden" name="message" value="Please find the official {{ config('conference.short_name') }} {{ config('conference.year') }} Conference Programme attached to this email. It contains the full schedule, session details, venues, and speaker information.&#10;&#10;We look forward to welcoming you to the conference.&#10;&#10;Warm regards,&#10;{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee">

                                    <div class="mb-5 p-4 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-100 dark:border-slate-700">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Attach Programme PDF</label>
                                        <input type="file" name="attachments[]" accept=".pdf" required
                                               class="block w-full text-xs font-bold text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-white file:px-5 file:py-3 file:text-xs file:font-black file:uppercase file:tracking-widest file:text-slate-900 hover:file:bg-slate-100 dark:file:bg-slate-800 dark:file:text-white cursor-pointer">
                                    </div>

                                    <div class="flex gap-3">
                                        <button type="button" onclick="previewProgrammeEmail()"
                                                class="px-5 py-4 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-black rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-all text-sm shrink-0">
                                            Preview
                                        </button>
                                        <button type="submit"
                                                onclick="return confirm('Send the conference programme to all {{ config('conference.host_short') }} staff and paid participants?')"
                                                class="flex-1 py-4 bg-blue-600 text-white font-black rounded-2xl shadow-xl shadow-blue-500/20 hover:shadow-blue-500/40 hover:-translate-y-1 transition-all">
                                            Send Programme Email
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Custom Group Email Card --}}
                        <div class="p-8 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] border border-white dark:border-slate-700 shadow-2xl shadow-slate-200/50 dark:shadow-none group overflow-hidden relative">
                            <div class="absolute top-0 right-0 p-8 opacity-[0.03] group-hover:scale-125 transition-transform duration-700">
                                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/></svg>
                            </div>
                            <div class="relative z-10">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none tracking-tight">Custom Group Email</h3>
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mt-1">Write Your Own Message</p>
                                    </div>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-3 leading-relaxed">
                                    Write your own email and send it to a selected group such as reviewers, accepted authors, authors pending revision, participants, authors, or unpaid delegates.
                                </p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mb-6">Use this when you want full control over the subject and message instead of a fixed one-click template.</p>
                                <button type="button" onclick="openBroadcastModal()" class="w-full py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black rounded-2xl shadow-xl hover:-translate-y-1 transition-all">
                                    Open Composer
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Dynamic Queue Console (admin only) --}}
                    @if($isFullAdmin)
                    <div class="bg-slate-900 rounded-[2.5rem] p-10 border border-slate-800 shadow-2xl relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-transparent opacity-50"></div>

                        <div class="relative z-10 flex flex-col xl:flex-row items-start justify-between gap-10">
                            <div class="flex-1 space-y-6">
                                <div class="flex items-center gap-4">
                                    <div class="px-4 py-1.5 bg-emerald-500/10 border border-emerald-500/30 rounded-full flex items-center gap-2">
                                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                        <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Queue Status</span>
                                    </div>
                                    <h4 class="text-2xl font-black text-white leading-none tracking-tight">Email Queue</h4>
                                </div>
                                <p class="text-slate-400 font-medium leading-relaxed max-w-lg">
                                    Background email jobs waiting or rejected by the queue. Recently queued: <span class="text-white font-bold">{{ $queueStats['recent_processed'] }}</span> in the last hour.
                                </p>

                                @if(($stats['emails_failed_today'] ?? 0) > 0 || ($stats['emails_failed_week'] ?? 0) > 0)
                                    <a href="{{ route('admin.emails.activity-log', ['status' => 'failed']) }}" class="block p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl text-sm font-semibold text-amber-200 hover:bg-amber-500/15 transition-colors">
                                        Email delivery failures are tracked in the Activity Log. Queue failed jobs are separate from failed email attempts.
                                    </a>
                                @endif

                                {{-- Failure Analysis (NEW) --}}
                                @if(isset($queueStats['top_failures']) && $queueStats['top_failures']->count() > 0)
                                    <div class="p-5 bg-red-500/5 border border-red-500/20 rounded-2xl space-y-3">
                                        <h5 class="text-[10px] font-black text-red-500 uppercase tracking-widest flex items-center gap-2">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            Top Failures
                                        </h5>
                                        @foreach($queueStats['top_failures'] as $failure)
                                            <div class="flex items-center justify-between group/fail">
                                                <p class="text-xs text-slate-400 font-medium truncate max-w-[300px]">{{ $failure->reason }}</p>
                                                <span class="text-[10px] font-black text-slate-600 bg-slate-800 px-2 py-0.5 rounded">{{ $failure->count }} occurrences</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-4 pt-4">
                                    @if(($queueStats['total_pending'] ?? 0) > 0)
                                    <button onclick="processQueueNow()" class="px-6 py-3 bg-white text-slate-900 font-black rounded-xl shadow-lg hover:-translate-y-1 transition-all flex items-center gap-2 text-xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-4.586-2.674A1 1 0 009 9.358v5.284a1 1 0 001.166.986l4.586-2.674a1 1 0 000-1.732z"/></svg>
                                        Process Queue Now
                                    </button>
                                    @endif
                                    <button onclick="restartQueueWorkers()" class="px-6 py-3 bg-slate-800 border border-slate-700 text-white font-black rounded-xl shadow-lg hover:-translate-y-1 transition-all flex items-center gap-2 text-xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Restart + Process
                                    </button>
                                    @if(($queueStats['total_failed'] ?? 0) > 0)
                                    <button onclick="retryFailedJobs()" class="px-6 py-3 bg-amber-500/10 border border-amber-500/20 text-amber-400 font-black rounded-xl hover:bg-amber-500/20 transition-all flex items-center gap-2 text-xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Retry Queue Jobs ({{ $queueStats['total_failed'] }})
                                    </button>
                                    @endif
                                    <button onclick="clearFailedJobs()" class="px-6 py-3 bg-red-600/10 border border-red-600/20 text-red-500 font-black rounded-xl hover:bg-red-600/20 transition-all text-xs">
                                        Clear Failed Queue Jobs
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-1 gap-4 shrink-0 w-full xl:w-48">
                                <div class="p-6 bg-slate-800/50 rounded-3xl border border-slate-700 text-center flex flex-col justify-center">
                                    <p class="text-3xl font-black text-white leading-none">{{ $queueStats['total_pending'] ?? 0 }}</p>
                                    <p class="text-[10px] font-black uppercase text-slate-500 tracking-[0.2em] mt-2">Queue Jobs</p>
                                </div>
                                <div class="p-6 bg-slate-800/50 rounded-3xl border border-slate-700 text-center flex flex-col justify-center">
                                    <p class="text-3xl font-black text-red-500 leading-none">{{ $queueStats['total_failed'] ?? 0 }}</p>
                                    <p class="text-[10px] font-black uppercase text-slate-500 tracking-[0.2em] mt-2">Failed Jobs</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Right: Activity & Diagnostics --}}
                <div class="lg:col-span-4 space-y-8">

                    {{-- Recent Campaigns --}}
                    <div class="p-8 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] border border-white dark:border-slate-700 shadow-2xl shadow-slate-200/50 dark:shadow-none">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-xl font-black text-slate-900 dark:text-white">Recent Campaigns</h3>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Manual Sends</span>
                        </div>
                        <div class="space-y-5">
                            @forelse($recentCampaigns as $campaign)
                                <div class="rounded-2xl border border-slate-100 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/50 p-5">
                                    <div class="flex items-center justify-between gap-4 mb-2">
                                        <p class="text-sm font-black text-slate-900 dark:text-white truncate">{{ $campaign->subject }}</p>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $campaign->sent_at ? $campaign->sent_at->diffForHumans() : $campaign->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">{{ $campaign->audience_label }}</p>
                                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                        <span>{{ $campaign->recipient_count }} recipient{{ $campaign->recipient_count === 1 ? '' : 's' }}</span>
                                        <span class="font-bold uppercase">{{ $campaign->status }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="py-10 text-center">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">No Campaigns Yet</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- SMTP Heartbeat (Modular) --}}
                    <div class="p-8 bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none">Diagnostic Center</h3>
                            <div class="w-3 h-3 bg-blue-500 rounded-full animate-ping"></div>
                        </div>

                        <div class="space-y-4">
                            <button onclick="document.getElementById('testEmailModal').classList.remove('hidden')" class="w-full p-5 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-700 group hover:border-blue-500/30 transition-all text-left">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 bg-blue-100 dark:bg-blue-500/10 text-blue-600 rounded-xl group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 dark:text-white text-sm">SMTP Health Check</h4>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Test Gateway Latency</p>
                                    </div>
                                </div>
                            </button>

                            <button onclick="openExportModal()" class="w-full p-5 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-700 group hover:border-purple-500/30 transition-all text-left">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 bg-purple-100 dark:bg-purple-500/10 text-purple-600 rounded-xl group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 dark:text-white text-sm">Data Extraction</h4>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Export Delivery Logs</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Activity Stream --}}
                    <div class="p-8 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] border border-white dark:border-slate-700 shadow-2xl shadow-slate-200/50 dark:shadow-none">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white">Recent Emails</h3>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Activity Log Summary</span>
                            </div>
                            <a href="{{ route('admin.emails.activity-log') }}" class="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:-translate-y-0.5 transition-all">
                                View Full Log
                            </a>
                        </div>
                        <div class="space-y-6">
                            @if(isset($emailStats['recent_emails']) && $emailStats['recent_emails']->count() > 0)
                                @foreach($emailStats['recent_emails'] as $email)
                                    <div class="flex gap-4 group">
                                        <div class="mt-1.5 flex-shrink-0">
                                            <div class="w-2 h-2 rounded-full {{ $email->status === 'sent' ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : ($email->status === 'pending' ? 'bg-amber-500 animate-pulse' : 'bg-red-500 animate-pulse') }}"></div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-0.5">
                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                                    {{ str_replace('_', ' ', $email->email_type) }} · {{ $email->status }}
                                                </p>
                                                <span class="text-[8px] font-bold text-slate-400 uppercase">{{ $email->sent_at ? $email->sent_at->diffForHumans() : $email->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ $email->recipient_email }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="py-12 text-center">
                                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100 dark:border-slate-800">
                                        <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">No Emails Yet</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Test Email Modal --}}
<div id="testEmailModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-6 hidden">
    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] w-full max-w-md overflow-hidden shadow-2xl animate-in zoom-in duration-300 border border-white dark:border-slate-700">
        <div class="p-10 space-y-8">
            <div class="flex items-center gap-4">
                <div class="p-4 bg-slate-100 dark:bg-slate-900 rounded-2xl text-slate-900 dark:text-white shadow-inner">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Test Email</h3>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Verify SMTP Integrity</p>
                </div>
            </div>

            <form id="testEmailForm" class="space-y-6">
                @csrf
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Test Email Address</label>
                    <input type="email" name="test_email" required class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-4 focus:ring-blue-500/10 outline-none transition-all" placeholder="you@example.com">
                </div>
                <div id="testEmailResult" class="hidden p-5 rounded-2xl text-xs font-bold"></div>

                <div class="flex items-center justify-between pt-6 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" onclick="document.getElementById('testEmailModal').classList.add('hidden')" class="text-xs font-black uppercase text-slate-400 tracking-widest">Abort</button>
                    <button type="submit" class="px-10 py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black rounded-2xl shadow-xl hover:-translate-y-1 transition-all">
                        Send Test Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function closeManagedModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.remove();
    }

    function showMessageModal(title, message) {
        closeManagedModal('messageModal');
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-slate-900/70 backdrop-blur-md flex items-center justify-center z-[140] p-6';
        modal.id = 'messageModal';
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] w-full max-w-lg shadow-2xl border border-white dark:border-slate-700 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white">${escapeHtml(title)}</h3>
                </div>
                <div class="px-8 py-8">
                    <p class="text-sm font-semibold text-slate-600 dark:text-slate-300 leading-relaxed">${escapeHtml(message)}</p>
                </div>
                <div class="px-8 py-6 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                    <button type="button" onclick="closeManagedModal('messageModal')" class="px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl text-xs font-black uppercase tracking-widest">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    function showConfirmModal({ id = 'confirmModal', title, message, confirmLabel = 'Continue', onConfirm }) {
        closeManagedModal(id);
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-slate-900/70 backdrop-blur-md flex items-center justify-center z-[140] p-6';
        modal.id = id;
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] w-full max-w-xl shadow-2xl border border-white dark:border-slate-700 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white">${escapeHtml(title)}</h3>
                </div>
                <div class="px-8 py-8">
                    <p class="text-sm font-semibold text-slate-600 dark:text-slate-300 leading-relaxed">${escapeHtml(message)}</p>
                </div>
                <div class="px-8 py-6 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between gap-4">
                    <button type="button" onclick="closeManagedModal('${id}')" class="text-xs font-black uppercase tracking-widest text-slate-400">Cancel</button>
                    <button type="button" data-confirm-button class="px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl text-xs font-black uppercase tracking-widest">${escapeHtml(confirmLabel)}</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        modal.querySelector('[data-confirm-button]').addEventListener('click', () => {
            closeManagedModal(id);
            onConfirm();
        });
    }

    function confirmOperationSend(operation) {
        showConfirmModal({
            id: 'operationConfirmModal',
            title: operation.title,
            message: operation.confirm || `Send this email action now?`,
            confirmLabel: operation.button_label,
            onConfirm: () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = operation.route;
                form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function openBroadcastModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-slate-900/60 backdrop-blur-md flex items-center justify-center z-[100] p-6';
        modal.id = 'broadcastModal';
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-800 rounded-[3rem] shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto animate-in zoom-in duration-300 relative border border-white dark:border-slate-700">
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-indigo-600 via-blue-600 to-indigo-600"></div>
                <div class="px-10 py-10 flex items-center justify-between border-b border-slate-50 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                    <div class="flex items-center gap-4">
                        <div class="p-4 bg-white dark:bg-slate-900 rounded-2xl text-indigo-600 shadow-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Compose Group Email</h3>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-[0.3em] mt-1">Write Once, Send To A Selected Group</p>
                        </div>
                    </div>
                </div>

                <form id="broadcastEmailForm" action="{{ route('admin.emails.send-broadcast') }}" method="POST" enctype="multipart/form-data" class="p-10 space-y-8">
                    @csrf
                    <div class="grid md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Recipient Group</label>
                            <select name="target_group" class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-bold text-slate-900 dark:text-white appearance-none cursor-pointer">
                                <option value="all">All Delegates</option>
                                <option value="attending">Attending Participants ({{ config('conference.host_short') }} + Paid)</option>
                                <option value="participants">Participants</option>
                                <option value="paid">Paid Registrations</option>
                                <option value="authors">Authors</option>
                                <option value="accepted_authors">Accepted Authors</option>
                                <option value="revision_pending_authors">Authors Awaiting Revision</option>
                                <option value="reviewers">Reviewers</option>
                                <option value="unpaid">Unpaid Registrations</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Email Subject</label>
                            <input type="text" name="subject" required placeholder="e.g., Programme Published"
                                   class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-900 dark:text-white font-bold">
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-100 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/60 p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Recipient Count</p>
                                <p id="broadcastAudienceSummary" class="text-lg font-black text-slate-900 dark:text-white mt-2">Choose a group to load recipients</p>
                            </div>
                            <button type="button" onclick="loadBroadcastAudienceInfo()" class="px-5 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-200 dark:border-slate-700 font-black rounded-2xl text-xs uppercase tracking-widest">
                                Refresh Count
                            </button>
                        </div>
                        <div id="broadcastAudienceWarning" class="hidden mt-4 p-4 rounded-2xl bg-amber-50 text-amber-700 text-sm font-semibold border border-amber-200"></div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Message</label>
                        <textarea name="message" required rows="6" placeholder="Write your announcement..."
                                  class="w-full px-8 py-7 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-[2.5rem] focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-900 dark:text-white font-medium resize-none shadow-inner"></textarea>
                    </div>

                    <div class="p-6 bg-slate-50/80 dark:bg-slate-900/60 rounded-[2rem] border border-slate-100 dark:border-slate-700">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Attachments</label>
                                <p class="text-xs font-semibold text-slate-400 mt-1">Optional. Up to 5 files, 10MB each. PDF, Word, Excel, PowerPoint, CSV, text, JPG, or PNG.</p>
                            </div>
                            <input type="file"
                                   name="attachments[]"
                                   multiple
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.jpg,.jpeg,.png"
                                   class="block w-full md:w-auto text-xs font-bold text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-white file:px-5 file:py-3 file:text-xs file:font-black file:uppercase file:tracking-widest file:text-slate-900 hover:file:bg-slate-100 dark:file:bg-slate-800 dark:file:text-white">
                        </div>
                    </div>

                    <div class="p-8 bg-blue-50/50 dark:bg-blue-500/5 rounded-[2rem] border border-blue-100 dark:border-blue-500/20">
                        <h4 class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-[0.25em] mb-6 flex items-center gap-2 font-black">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Optional Call To Action
                        </h4>
                        <div class="grid md:grid-cols-2 gap-6">
                            <input type="text" name="action_text" placeholder="Button text (e.g., View Programme)"
                                   class="w-full px-6 py-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold placeholder:text-slate-300">
                            <input type="url" name="action_url" placeholder="Link URL"
                                   class="w-full px-6 py-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold placeholder:text-slate-300">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-8 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" onclick="closeBroadcastModal()"
                                class="text-xs font-black uppercase text-slate-400 hover:text-slate-600 transition-colors tracking-widest leading-none">
                            Cancel
                        </button>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="previewBroadcastEmail()" class="px-8 py-5 bg-white dark:bg-slate-900 text-slate-900 dark:text-white border border-slate-200 dark:border-slate-700 rounded-[2rem] font-black text-xs uppercase tracking-widest">
                                Preview
                            </button>
                            <button type="button" onclick="confirmBroadcastSend()"
                                    class="px-12 py-6 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-[2rem] shadow-2xl shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-1 transition-all font-black text-sm uppercase tracking-widest">
                                Send Group Email
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        modal.querySelector('select[name="target_group"]').addEventListener('change', loadBroadcastAudienceInfo);
        modal.querySelector('input[name="subject"]').addEventListener('blur', loadBroadcastAudienceInfo);
        modal.querySelector('#broadcastEmailForm').addEventListener('submit', handleBroadcastSubmit);
        loadBroadcastAudienceInfo();
    }

    function closeBroadcastModal() {
        const modal = document.getElementById('broadcastModal');
        if (modal) {
            modal.querySelector('div').classList.add('animate-out', 'fade-out', 'zoom-out', 'duration-200');
            modal.classList.add('animate-out', 'fade-out', 'duration-200');
            setTimeout(() => modal.remove(), 200);
        }
    }

    function getBroadcastForm() {
        return document.getElementById('broadcastEmailForm');
    }

    async function loadBroadcastAudienceInfo() {
        const form = getBroadcastForm();
        if (!form) return;

        const formData = new FormData();
        formData.append('target_group', form.querySelector('[name="target_group"]').value);
        formData.append('subject', form.querySelector('[name="subject"]').value);

        const response = await fetch('{{ route("admin.emails.broadcast-audience-info") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });

        const result = await response.json();
        const summary = document.getElementById('broadcastAudienceSummary');
        const warning = document.getElementById('broadcastAudienceWarning');

        summary.textContent = `${result.count} recipient${result.count === 1 ? '' : 's'} in ${result.audience_label}`;

        if (result.warning) {
            warning.textContent = result.warning.message;
            warning.classList.remove('hidden');
        } else {
            warning.textContent = '';
            warning.classList.add('hidden');
        }
    }

    async function previewProgrammeEmail() {
        try {
            const response = await fetch('{{ route("admin.emails.preview-broadcast") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    target_group: 'attending',
                    subject: '{{ config('conference.short_name') }} {{ config('conference.year') }} - Conference Programme',
                    message: 'Please find the official {{ config('conference.short_name') }} {{ config('conference.year') }} Conference Programme attached to this email. It contains the full schedule, session details, venues, and speaker information.\n\nWe look forward to welcoming you to the conference.\n\nWarm regards,\n{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee',
                }),
            });

            const result = await response.json();

            if (!response.ok) {
                showMessageModal('Preview Unavailable', result.message || JSON.stringify(result));
                return;
            }

            const previewModal = document.createElement('div');
            previewModal.className = 'fixed inset-0 bg-slate-900/70 backdrop-blur-md flex items-center justify-center z-[120] p-6';
            previewModal.id = 'programmePreviewModal';
            previewModal.innerHTML = `
                <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] w-full max-w-6xl h-[90vh] overflow-hidden shadow-2xl border border-white dark:border-slate-700 flex flex-col">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white">Programme Email Preview</h3>
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mt-1">${result.count} recipient${result.count === 1 ? '' : 's'} &mdash; ${result.audience_label}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('programmePreviewModal').remove()" class="px-5 py-3 bg-slate-100 dark:bg-slate-900 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-500">Close</button>
                    </div>
                    <div class="flex-1 overflow-auto bg-slate-100 dark:bg-slate-900 p-4">
                        <iframe title="Email Preview" class="w-full h-full bg-white rounded-2xl border border-slate-200" srcdoc="${result.html.replace(/"/g, '&quot;')}"></iframe>
                    </div>
                </div>
            `;
            document.body.appendChild(previewModal);
        } catch (e) {
            showMessageModal('Preview Error', e.message);
        }
    }

    async function previewBroadcastEmail() {
        try {
            const form = getBroadcastForm();
            if (!form) return;

            const formData = new FormData(form);

            const response = await fetch('{{ route("admin.emails.preview-broadcast") }}', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });

            if (!response.ok) {
                let msg = 'Please complete the group, subject, and message before previewing.';
                try { const err = await response.json(); if (err.message) msg = err.message; } catch {}
                showMessageModal('Preview Unavailable', msg);
                return;
            }

            const result = await response.json();

            const previewModal = document.createElement('div');
            previewModal.className = 'fixed inset-0 bg-slate-900/70 backdrop-blur-md flex items-center justify-center z-[120] p-6';
            previewModal.id = 'broadcastPreviewModal';
            previewModal.innerHTML = `
                <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] w-full max-w-6xl h-[90vh] overflow-hidden shadow-2xl border border-white dark:border-slate-700 flex flex-col">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white">Email Preview</h3>
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mt-1">${result.count} recipient${result.count === 1 ? '' : 's'} in ${result.audience_label}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('broadcastPreviewModal').remove()" class="px-5 py-3 bg-slate-100 dark:bg-slate-900 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-500">Close</button>
                    </div>
                    <div class="flex-1 overflow-auto bg-slate-100 dark:bg-slate-900 p-4">
                        ${result.warning ? '<div class="max-w-5xl mx-auto mb-4 p-4 rounded-2xl bg-amber-50 text-amber-700 border border-amber-200 text-sm font-semibold">' + result.warning.message + '</div>' : ''}
                        <iframe title="Email Preview" class="w-full h-full bg-white rounded-2xl border border-slate-200" srcdoc="${result.html.replace(/"/g, '&quot;')}"></iframe>
                    </div>
                </div>
            `;
            document.body.appendChild(previewModal);
        } catch (e) {
            showMessageModal('Preview Error', e.message);
        }
    }

    function handleBroadcastSubmit(e) {
        e.preventDefault();
        confirmBroadcastSend();
    }

    function confirmBroadcastSend() {
        const form = getBroadcastForm();
        if (!form) return;

        const warning = document.getElementById('broadcastAudienceWarning');
        const message = warning && !warning.classList.contains('hidden')
            ? `${warning.textContent} Please confirm that you want to continue.`
            : 'Send this group email now?';

        showConfirmModal({
            id: 'broadcastConfirmModal',
            title: 'Confirm Group Email',
            message,
            confirmLabel: 'Send Email',
            onConfirm: () => form.submit(),
        });
    }

    document.getElementById('testEmailForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        const resultDiv = document.getElementById('testEmailResult');

        btn.textContent = 'TRANSMITTING...';
        btn.disabled = true;

        try {
            const formData = new FormData(this);
            const response = await fetch('{{ route("admin.emails.test-email") }}', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const result = await response.json();

            resultDiv.className = `p-6 rounded-[1.5rem] text-xs font-black uppercase tracking-widest ${result.success ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600' : 'bg-red-50 dark:bg-red-500/10 text-red-600'}`;
            resultDiv.textContent = result.message;
            resultDiv.classList.remove('hidden');
        } catch (error) {
            resultDiv.className = 'p-6 rounded-[1.5rem] bg-red-50 dark:bg-red-500/10 text-red-600 text-xs font-black uppercase tracking-widest';
            resultDiv.textContent = 'Send failed. Please check your mail settings.';
            resultDiv.classList.remove('hidden');
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    });

    async function previewOperationEmail(operationKey) {
        const res = await fetch('{{ route("admin.emails.preview-operation") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ operation: operationKey }),
        });

        if (!res.ok) {
            showMessageModal('Preview Unavailable', 'Could not load the email preview. Please try again.');
            return;
        }

        const data = await res.json();

        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-slate-900/70 backdrop-blur-md flex items-center justify-center z-[120] p-6';
        modal.id = 'operationPreviewModal';
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] w-full max-w-4xl h-[90vh] overflow-hidden shadow-2xl border border-white dark:border-slate-700 flex flex-col">
                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between shrink-0">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">${escapeHtml(data.eyebrow)}</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">${escapeHtml(data.title)}</h3>
                        <p class="text-xs text-slate-500 mt-1">Subject: <span class="font-bold text-slate-700 dark:text-slate-300">${escapeHtml(data.subject)}</span> &nbsp;·&nbsp; ${data.count} recipient${data.count === 1 ? '' : 's'}</p>
                    </div>
                    <button type="button" onclick="closeManagedModal('operationPreviewModal')"
                        class="px-5 py-3 bg-slate-100 dark:bg-slate-900 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-500">
                        Close
                    </button>
                </div>
                <div class="flex-1 overflow-auto bg-slate-100 dark:bg-slate-900 p-4">
                    <iframe title="Email Preview" class="w-full h-full bg-white rounded-2xl border border-slate-200"
                        srcdoc="${data.html.replace(/"/g, '&quot;')}"></iframe>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    function refreshQueueStats() { window.location.reload(); }

    function processQueueNow() {
        showConfirmModal({
            id: 'processQueueModal',
            title: 'Process Email Queue',
            message: 'Start processing pending email queue jobs now?',
            confirmLabel: 'Process Queue',
            onConfirm: () => {
                fetch('{{ route("admin.queue.start") }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
                    .then(r => r.json())
                    .then(d => showMessageModal('Email Queue', d.message || 'Queue processing started.'));
            }
        });
    }

    function restartQueueWorkers() {
        showConfirmModal({
            id: 'restartWorkersModal',
            title: 'Restart and Process Queue',
            message: 'Send a restart signal and start processing any pending queue jobs?',
            confirmLabel: 'Restart + Process',
            onConfirm: () => {
                fetch('{{ route("admin.queue.restart") }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
                    .then(r => r.json())
                    .then(d => showMessageModal('Queue Workers', d.message || 'Worker sequence rebooted.'));
            }
        });
    }

    function retryFailedJobs() {
        showConfirmModal({
            id: 'retryFailedJobsModal',
            title: 'Retry Failed Queue Jobs',
            message: 'Re-queue failed background jobs for processing now? This does not resend failed email-log attempts.',
            confirmLabel: 'Retry Queue Jobs',
            onConfirm: () => {
                fetch('{{ route("admin.queue.retry-failed") }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
                    .then(r => r.json())
                    .then(d => { showMessageModal('Retry Queued', d.message || 'Failed jobs re-queued.'); refreshQueueStats(); });
            }
        });
    }

    function clearFailedJobs() {
        showConfirmModal({
            id: 'clearFailedJobsModal',
            title: 'Clear Failed Queue Jobs',
            message: 'Clear failed background queue jobs? This does not remove failed email-log records.',
            confirmLabel: 'Clear Queue Jobs',
            onConfirm: () => {
                fetch('{{ route("admin.queue.clear-failed") }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
                    .then(r => r.json())
                    .then(d => { showMessageModal('Failed Jobs Cleared', d.message || 'Log space cleared.'); refreshQueueStats(); });
            }
        });
    }

    function openExportModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-slate-900/60 backdrop-blur-md flex items-center justify-center z-[100] p-6';
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-12 w-full max-w-md shadow-2xl border border-white dark:border-slate-700 animate-in zoom-in duration-300">
                <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Export Email Logs</h3>
                <p class="text-[10px] font-black text-slate-400 uppercase mb-10 tracking-[0.3em]">Download Reports</p>
                <form action="{{ route('admin.emails.export-report') }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-500 ml-1">Start Date</label>
                            <input type="date" name="start_date" required class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl text-xs font-bold font-black">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-500 ml-1">End Date</label>
                            <input type="date" name="end_date" required class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl text-xs font-bold font-black">
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-500 ml-1">Data Encoding</label>
                        <select name="format" class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl text-xs font-black appearance-none cursor-pointer">
                            <option value="csv">Standard CSV Table Structure</option>
                            <option value="json">Raw JSON Object Schema</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-6 pt-10">
                        <button type="button" onclick="this.closest('.fixed').remove()" class="text-xs font-black uppercase text-slate-400 tracking-widest px-4">Abort</button>
                        <button type="submit" class="px-10 py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black rounded-2xl shadow-xl hover:-translate-y-1 transition-all text-xs uppercase tracking-widest">Execute Extraction</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        const ed = new Date().toISOString().split('T')[0];
        const sd = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        modal.querySelector('input[name="start_date"]').value = sd;
        modal.querySelector('input[name="end_date"]').value = ed;
    }
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap');
    :root { font-family: 'Outfit', sans-serif; }
</style>
@endsection
