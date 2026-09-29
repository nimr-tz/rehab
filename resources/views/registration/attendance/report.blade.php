@extends('layouts.app')

@section('title', 'Attendance Report')

@section('content')
<div class="min-h-screen bg-[#f8fafc] dark:bg-[#0a0a0b] font-sans pb-24 selection:bg-violet-100 selection:text-violet-900">
    <!-- Header -->
    <div class="bg-gradient-to-br from-violet-600 via-violet-700 to-indigo-800 relative overflow-hidden rounded-b-[4rem] sm:rounded-b-[6rem] shadow-2xl">
        <!-- Ambient Light Particles -->
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[60%] bg-violet-400/20 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[10%] w-[30%] h-[50%] bg-indigo-400/20 rounded-full blur-[100px]"></div>
        
        <div class="max-w-7xl mx-auto px-8 py-20 relative z-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">
                <div class="flex items-center gap-6">
                    <a href="{{ route('registration.dashboard') }}" class="w-14 h-14 bg-white/10 backdrop-blur-xl border border-white/20 rounded-[1.5rem] flex items-center justify-center text-white hover:bg-white/20 transition-all shadow-xl group">
                        <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight" style="font-family: 'Outfit', sans-serif;">
                            Attendance <span class="text-violet-200/80">Intelligence.</span>
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Floating Stats Grid -->
    <div class="max-w-7xl mx-auto px-8 relative z-20 -mt-20">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
            @foreach($stats as $d => $count)
            <a href="{{ route('registration.attendance.report', ['day' => $d]) }}" 
               class="group relative bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800 transition-all hover:-translate-y-2 hover:shadow-2xl {{ $day == $d ? 'ring-4 ring-violet-500/20 border-violet-500/50' : '' }}">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 {{ $day == $d ? 'bg-violet-600' : 'bg-slate-50 dark:bg-gray-800' }} rounded-2xl transition-colors">
                        <svg class="w-6 h-6 {{ $day == $d ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    @if($day == $d)
                        <span class="w-2 h-2 bg-violet-600 rounded-full"></span>
                    @endif
                </div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1">Day {{ $d }}</p>
                <h4 class="text-3xl font-black text-slate-900 dark:text-white mb-2">{{ $count }}</h4>
                <div class="w-full bg-slate-100 dark:bg-gray-800 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-violet-600 h-full transition-all duration-1000" style="width: {{ $totalRegistered > 0 ? ($count / $totalRegistered) * 100 : 0 }}%"></div>
                </div>
            </a>
            @endforeach
            
            <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-3xl -mr-16 -mt-16 group-hover:bg-white/20 transition-all"></div>
                <div class="relative z-10 text-white">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60 mb-1 text-center">Total Paid</p>
                    <h4 class="text-4xl font-black text-center mb-2">{{ $totalRegistered }}</h4>
                    <p class="text-[10px] font-bold text-center text-white/50">Verified Delegates</p>
                </div>
            </div>

            <div class="bg-slate-900 dark:bg-black rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden group">
                <div class="relative z-10 text-white text-center">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400 mb-1">Full Pass</p>
                    <h4 class="text-4xl font-black text-white mb-2">{{ $fullAttendance }}</h4>
                    <p class="text-[10px] font-bold text-white/40">Present All 3 Days</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Section -->
    <div class="max-w-7xl mx-auto px-8 mt-16">
        <!-- Export Panel -->
        @php $totalDays = (int) config('conference.total_days', 3); @endphp
        <form method="GET" action="{{ route('registration.export') }}"
              class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 shadow-sm border border-slate-100 dark:border-gray-800 flex flex-wrap items-end gap-5 mb-8">
            <div class="px-2">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-violet-600 mb-1">Export to CSV</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Download the full roster across all days, or pick a single day.</p>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Scope</label>
                <select name="day" class="rounded-xl border-slate-200 dark:border-gray-700 dark:bg-gray-800 text-sm font-bold text-slate-700 dark:text-slate-200 px-4 py-2.5">
                    <option value="">Full report — all days</option>
                    @for($d = 1; $d <= $totalDays; $d++)
                        <option value="{{ $d }}">Day {{ $d }} only</option>
                    @endfor
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Filter</label>
                <select name="status" class="rounded-xl border-slate-200 dark:border-gray-700 dark:bg-gray-800 text-sm font-bold text-slate-700 dark:text-slate-200 px-4 py-2.5">
                    <option value="all">Everyone</option>
                    <option value="present">Attended only</option>
                    <option value="absent">Did not attend</option>
                </select>
            </div>
            <button type="submit"
                    class="px-6 py-3 rounded-2xl bg-violet-600 hover:bg-violet-700 text-white text-[11px] font-black uppercase tracking-widest shadow-lg shadow-violet-600/20 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download CSV
            </button>
        </form>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-4 shadow-sm border border-slate-100 dark:border-gray-800 flex flex-wrap items-center gap-4 mb-12">
            <div class="px-6 py-2 border-r border-slate-100 dark:border-gray-800 mr-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Filter View</span>
            </div>
            <a href="{{ route('registration.attendance.report') }}" 
               class="px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all {{ !$day ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/20' : 'bg-slate-50 dark:bg-gray-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-gray-700' }}">
                All Records
            </a>
            @for($d = 1; $d <= 3; $d++)
            <a href="{{ route('registration.attendance.report', ['day' => $d]) }}" 
               class="px-5 py-2.5 rounded-full text-sm font-bold transition-all {{ $day == $d ? 'bg-violet-600 text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-gray-700 border border-slate-200 dark:border-gray-700' }}">
                Day {{ $d }}
            </a>
            @endfor
        </div>
        
        <!-- Attendance Table -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-slate-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                    @if($day)
                        Day {{ $day }} Attendance
                    @else
                        All Attendance Records
                    @endif
                </h3>
                <span class="text-sm text-slate-500 dark:text-slate-400">
                    {{ $attendances->total() }} records
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Attendee</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Day</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Check-in Time</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Checked By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                        @forelse($attendances as $attendance)
                        <tr class="hover:bg-violet-50/50 dark:hover:bg-violet-900/10 transition-colors">
                            <td class="px-6 py-4">
                                @php
                                    $attendeeRoute = $attendance->attendee_type === 'group_member'
                                        ? route('registration.group-member-details', $attendance->groupMember)
                                        : route('registration.attendee-details', $attendance->user);
                                @endphp
                                <a href="{{ $attendeeRoute }}" class="flex items-center gap-4 group">
                                    <div class="w-11 h-11 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-lg group-hover:scale-105 transition-transform">
                                        {{ $attendance->attendee_initials }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white group-hover:text-violet-600 transition-colors">
                                            {{ $attendance->attendee_name }}
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $attendance->attendee_affiliation }}</p>
                                            @if($attendance->attendee_type === 'group_member')
                                                <span class="text-[10px] font-black uppercase tracking-widest text-rose-600">Group</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold
                                    {{ $attendance->day == 1 ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : '' }}
                                    {{ $attendance->day == 2 ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : '' }}
                                    {{ $attendance->day == 3 ? 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300' : '' }}
                                ">
                                    Day {{ $attendance->day }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-slate-900 dark:text-white">{{ $attendance->checked_in_at->format('g:i A') }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $attendance->checked_in_at->format('M d, Y') }}</p>
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                {{ $attendance->checkedInByUser?->full_name ?? 'Mobile App' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="w-20 h-20 bg-violet-100 dark:bg-violet-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-10 h-10 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m12-3.803a4 4 0 11-5 0"/>
                                    </svg>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 font-medium">No attendance records found</p>
                                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Check-ins will appear here once attendees are marked present</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($attendances->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-gray-700">
                {{ $attendances->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
