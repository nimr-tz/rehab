@extends('layouts.app')

@section('title', 'Attendance Scanner')

@section('content')
<div class="min-h-screen bg-[#f8fafc] dark:bg-[#0a0a0b] font-sans pb-24 selection:bg-violet-100 dark:selection:bg-violet-900/40">
    <!-- Radiant Violet Header -->
    <div class="relative min-h-[320px] flex items-center overflow-hidden bg-gradient-to-br from-[#7c3aed] via-[#6d28d9] to-[#5b21b6] rounded-b-[4rem] shadow-[0_20px_50px_rgba(124,58,237,0.2)] mb-8">
        <!-- Ambient Light Particles -->
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[60%] bg-[#7c3aed]/30 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[60%] bg-[#7c3aed]/10 rounded-full blur-[120px]"></div>
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-[0.06]"></div>

        <div class="max-w-6xl mx-auto px-8 w-full relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8 mb-8">
                <div class="flex items-center gap-6">
                    <a href="{{ route('registration.dashboard') }}" class="w-14 h-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center text-white hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </a>
                    <div class="space-y-1">
                        <h1 class="text-3xl md:text-4xl font-black text-white leading-tight" style="font-family: 'Outfit', sans-serif;">
                            Attendance <span class="text-violet-100/80">Tracker.</span>
                        </h1>
                        <p class="text-white/60 font-medium">Monitoring real-time conference arrival stats</p>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-4">
                @for($d = 1; $d <= 3; $d++)
                @php
                    $dayCount = \App\Models\Attendance::where('day', $d)->count();
                    $isToday = $d == $today;
                @endphp
                <div 
                    class="relative bg-white/10 backdrop-blur-md rounded-[2rem] p-5 border-2 {{ $isToday ? 'border-white/50' : 'border-white/10' }} group overflow-hidden">
                    @if($isToday)
                    <div class="absolute top-2 right-2 w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                    @endif
                    <div class="text-3xl font-black text-white mb-1">{{ $dayCount }}</div>
                    <div class="text-xs font-bold text-white/50 uppercase tracking-[0.1em]">Day {{ $d }}</div>
                </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-8">
        <div class="flex flex-col gap-8">
            <!-- Mobile App Notice -->
            <div class="bg-gradient-to-r from-violet-600 to-indigo-700 rounded-[3rem] p-8 shadow-2xl relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-32 -mt-32"></div>
                <div class="flex flex-col md:flex-row items-center gap-8 relative z-10">
                    <div class="w-20 h-20 bg-white/10 backdrop-blur-md rounded-3xl flex items-center justify-center shrink-0">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-2xl font-black text-white mb-2">QR Scanning moved to Mobile</h4>
                        <p class="text-violet-100 font-medium max-w-xl">Use the official {{ config('conference.short_name') }} App for high-speed badge scanning. Web scanning has been disabled to prioritize mobile check-ins.</p>
                    </div>
                </div>
            </div>

            <!-- Recent Check-ins -->
            <div class="bg-white dark:bg-[#111827] rounded-[3rem] shadow-[0_30px_60px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800 overflow-hidden">
                <div class="p-8 border-b border-slate-50 dark:border-gray-800/50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white">Recent Arrivals</h3>
                                <p class="text-sm text-slate-400">Latest check-ins today</p>
                            </div>
                        </div>
                        <a href="{{ route('registration.attendance.report') }}" class="px-4 py-2 bg-slate-100 dark:bg-gray-800 hover:bg-slate-200 dark:hover:bg-gray-700 rounded-xl text-sm font-bold text-slate-600 dark:text-white transition-colors">
                            View Report
                        </a>
                    </div>
                </div>
                
                <div class="divide-y divide-slate-50 dark:divide-gray-800" id="recent-list">
                    @forelse($recentCheckIns as $checkin)
                    <div class="p-6 hover:bg-slate-50/50 dark:hover:bg-gray-800/30 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                                {{ $checkin->attendee_initials }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-bold text-slate-900 dark:text-white truncate">{{ $checkin->attendee_name }}</p>
                                    @if($checkin->attendee_type === 'group_member')
                                        <span class="text-[10px] font-black uppercase tracking-widest text-rose-600">Group</span>
                                    @endif
                                </div>
                                <p class="text-sm text-slate-400 truncate">{{ $checkin->attendee_affiliation }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-emerald-600">{{ $checkin->checked_in_at->format('g:i A') }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Day {{ $checkin->day }}</p>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-slate-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <p class="text-slate-400 font-medium">No check-ins yet today</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mt-8 flex justify-center gap-4">
            <a href="{{ route('registration.dashboard') }}" class="px-8 py-4 bg-white dark:bg-gray-800 hover:bg-slate-50 dark:hover:bg-gray-700 text-slate-900 dark:text-white font-bold rounded-2xl transition-colors shadow-lg border border-slate-100 dark:border-gray-700 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search Dashboard
            </a>
            <a href="{{ route('registration.attendance.report') }}" class="px-8 py-4 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-2xl transition-colors shadow-lg flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Full Report
            </a>
        </div>
    </div>
</div>

@endsection
