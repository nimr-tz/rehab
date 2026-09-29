@extends('layouts.admin')

@section('title', 'Attendance Report')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Attendance Report</h1>
                <p class="text-slate-500 dark:text-slate-400">View and export conference attendance data</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.attendance.scanner') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Scanner
                </a>
            </div>
        </div>

        <!-- Export Panel -->
        @php $totalDays = (int) config('conference.total_days', 3); @endphp
        <form method="GET" action="{{ route('admin.attendance.export') }}"
              class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 flex flex-wrap items-end gap-4 mb-8">
            <div class="mr-2">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">Export attendance</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Full roster across all days, or a single day.</p>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Scope</label>
                <select name="day" class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 text-sm px-3 py-2">
                    <option value="">Full report — all days</option>
                    @for($d = 1; $d <= $totalDays; $d++)
                        <option value="{{ $d }}">Day {{ $d }} only</option>
                    @endfor
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Filter</label>
                <select name="status" class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 text-sm px-3 py-2">
                    <option value="all">Everyone</option>
                    <option value="present">Attended only</option>
                    <option value="absent">Did not attend</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium text-sm transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download CSV
            </button>
        </form>

        <!-- Stats Overview -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <!-- Day Stats -->
            @foreach($stats as $d => $count)
            <a href="{{ route('admin.attendance.report', ['day' => $d]) }}" 
               class="bg-white dark:bg-slate-800 rounded-xl p-6 border-2 transition-all hover:shadow-lg {{ $day == $d ? 'border-blue-500' : 'border-transparent' }}">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Day {{ $d }}</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1">{{ $count }}</p>
                <p class="text-xs text-slate-400 mt-1">
                    {{ $totalRegistered > 0 ? round(($count / $totalRegistered) * 100) : 0 }}% attendance
                </p>
            </a>
            @endforeach

            <!-- Total Registered -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                <p class="text-sm font-medium text-blue-100">Total Registered</p>
                <p class="text-3xl font-bold mt-1">{{ $totalRegistered }}</p>
                <p class="text-xs text-blue-200 mt-1">Verified payments</p>
            </div>

            <!-- Full Attendance -->
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-6 text-white">
                <p class="text-sm font-medium text-emerald-100">Full Attendance</p>
                <p class="text-3xl font-bold mt-1">{{ $fullAttendance }}</p>
                <p class="text-xs text-emerald-200 mt-1">All 3 days</p>
            </div>
        </div>

        <!-- Filter Pills -->
        <div class="flex flex-wrap gap-2 mb-6">
            <a href="{{ route('admin.attendance.report') }}" 
               class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ !$day ? 'bg-blue-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600' }}">
                All Days
            </a>
            @for($d = 1; $d <= 3; $d++)
            <a href="{{ route('admin.attendance.report', ['day' => $d]) }}" 
               class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $day == $d ? 'bg-blue-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600' }}">
                Day {{ $d }}
            </a>
            @endfor
        </div>

        <!-- Attendance Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Attendee</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Day</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Check-in Time</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Checked By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse($attendances as $attendance)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm">
                                        {{ $attendance->attendee_initials }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-medium text-slate-900 dark:text-white">{{ $attendance->attendee_name }}</p>
                                        </div>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $attendance->attendee_email ?? $attendance->attendee_affiliation }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                    {{ $attendance->day == 1 ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : '' }}
                                    {{ $attendance->day == 2 ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300' : '' }}
                                    {{ $attendance->day == 3 ? 'bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-300' : '' }}
                                ">
                                    Day {{ $attendance->day }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-slate-900 dark:text-white">{{ $attendance->checked_in_at->format('g:i A') }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $attendance->checked_in_at->format('M d, Y') }}</p>
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                {{ $attendance->checkedInByUser?->full_name ?? 'System' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m12-3.803a4 4 0 11-5 0"/>
                                </svg>
                                <p>No attendance records found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($attendances->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700">
                {{ $attendances->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
