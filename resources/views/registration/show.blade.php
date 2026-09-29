@extends('layouts.app')

@section('title', $attendee['name'] . ' - Attendee Profile')

@section('content')
<div class="min-h-screen bg-[#f8fafc] dark:bg-[#0a0a0b] font-sans pb-24">
    <!-- Radiant Header with Contact Info -->
    <div class="relative min-h-[280px] md:min-h-[300px] lg:min-h-[340px] flex items-center overflow-hidden bg-gradient-to-br from-[#7c3aed] via-[#6d28d9] to-[#5b21b6] rounded-b-[2.5rem] md:rounded-b-[4rem] shadow-[0_20px_50px_rgba(124,58,237,0.2)]">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[60%] bg-[#7c3aed]/30 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[60%] bg-[#7c3aed]/10 rounded-full blur-[120px]"></div>

        <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full relative z-10 py-8 md:py-10 lg:py-12">
            <div class="flex flex-col lg:flex-row gap-10 items-start lg:items-center">
                <!-- Back Button -->
                <a href="javascript:history.back()" class="absolute top-8 left-8 lg:static lg:w-14 lg:h-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center text-white hover:bg-white/20 transition-colors shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>

                <!-- Edit Details Button -->
                <button type="button" onclick="openEditModal()" class="absolute top-8 right-8 z-20 px-4 py-3 lg:px-5 bg-white/15 hover:bg-white/25 backdrop-blur-md rounded-2xl flex items-center gap-2 text-white text-sm font-bold transition-colors shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span class="hidden sm:inline">Edit Details</span>
                </button>

                <!-- Profile Info -->
                <div class="flex-1 flex flex-col md:flex-row gap-8 items-center md:items-start text-center md:text-left pt-12 lg:pt-0">
                    <!-- Avatar -->
                    <div class="relative group/avatar">
                        <div class="absolute -inset-1 bg-gradient-to-tr from-white to-violet-300 rounded-[2.2rem] md:rounded-[2.8rem] blur opacity-25 group-hover/avatar:opacity-50 transition duration-500"></div>
                        <div class="relative w-24 h-24 md:w-32 md:h-32 rounded-[2rem] md:rounded-[2.5rem] bg-white text-violet-600 flex items-center justify-center text-3xl md:text-4xl font-black shadow-2xl shrink-0 md:rotate-3 transform md:hover:rotate-0 transition-all duration-300 overflow-hidden">
                            @if($attendee['profile_image'])
                                <img src="{{ asset('storage/' . $attendee['profile_image']) }}" 
                                     class="w-full h-full object-cover" 
                                     alt="{{ $attendee['name'] }}">
                            @else
                                {{ $attendee['initials'] }}
                            @endif
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="space-y-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-3 justify-center md:justify-start mb-2">
                                <span class="px-3 py-1 bg-white/20 backdrop-blur border border-white/10 text-white rounded-lg text-xs font-bold uppercase tracking-widest">
                                    {{ str_replace('_', ' ', $attendee['category']) }}
                                </span>
                                @if($attendee['payment_status'] === 'verified')
                                    <span class="px-3 py-1 bg-emerald-400/20 backdrop-blur border border-emerald-400/30 text-emerald-100 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                                        Verified
                                    </span>
                                @elseif($attendee['payment_status'] === 'waived')
                                    <span class="px-3 py-1 bg-blue-400/20 backdrop-blur border border-blue-400/30 text-blue-100 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 bg-blue-400 rounded-full"></span>
                                        Waived
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-amber-400/20 backdrop-blur border border-amber-400/30 text-amber-100 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 bg-amber-400 rounded-full animate-pulse"></span>
                                        Pending Payment
                                    </span>
                                @endif
                                @if($attendee['is_presenter'])
                                    <span class="px-3 py-1 bg-violet-400/20 backdrop-blur border border-violet-400/30 text-violet-100 rounded-lg text-xs font-bold uppercase tracking-widest">
                                        Presenter
                                    </span>
                                @endif
                            </div>
                            <h1 class="text-3xl md:text-4xl lg:text-5xl font-black text-white leading-tight tracking-tight">
                                {{ $attendee['title'] }} {{ $attendee['name'] }}
                            </h1>
                            <p class="text-white/70 text-base md:text-lg font-medium mt-1">{{ $attendee['affiliation'] }}</p>
                        </div>

                        <!-- Contact Pills -->
                        <div class="flex flex-wrap gap-2 justify-center md:justify-start">
                            <div class="px-4 py-2 bg-black/20 rounded-xl flex items-center gap-3 text-sm font-medium text-white/90">
                                <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                {{ $attendee['email'] }}
                            </div>
                            @if($attendee['phone'])
                            <div class="px-4 py-2 bg-black/20 rounded-xl flex items-center gap-3 text-sm font-medium text-white/90">
                                <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $attendee['phone'] }}
                            </div>
                            @endif
                            @if($attendee['country'])
                            <div class="px-4 py-2 bg-black/20 rounded-xl flex items-center gap-3 text-sm font-medium text-white/90">
                                <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $attendee['country'] }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="max-w-7xl mx-auto px-8 -mt-12 relative z-20 pb-12">
        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 px-6 py-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl text-emerald-700 dark:text-emerald-300 font-bold shadow-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Attendance & Presentations (2/3 width) -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Daily Attendance -->
                <div class="bg-white dark:bg-[#111827] rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-gray-800 overflow-hidden">
                    <div class="p-8 border-b border-slate-50 dark:border-gray-800">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Daily Attendance</h3>
                        <p class="text-sm text-slate-400 mt-1">Track presence for each conference day</p>
                    </div>

                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                                @if($attendee['current_day'] === 0)
                                    @php
                                        $demoData = $attendee['attendance'][0] ?? ['attended' => false, 'checked_in_at' => null];
                                        $canMarkDemo = !$demoData['attended'] && $attendee['is_paid'];
                                    @endphp

                                    @if($canMarkDemo)
                                        <button onclick="markAttendance(event, 0)"
                                            class="p-6 rounded-2xl text-center transition-all group border-2 hover:shadow-lg active:scale-95 bg-violet-50 dark:bg-violet-900/20 border-violet-200 dark:border-violet-800 hover:bg-violet-100">
                                            <p class="text-xs font-black uppercase tracking-widest text-violet-600 mb-2">Demo Day</p>
                                            <div class="flex flex-col items-center gap-1">
                                                <p class="font-bold text-sm text-violet-600">Test Attendance</p>
                                                <svg class="w-4 h-4 text-violet-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            </div>
                                        </button>
                                    @else
                                        <div class="p-6 rounded-2xl text-center border-2 {{ $demoData['attended'] ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' : 'bg-slate-50 dark:bg-gray-800 border-slate-100 dark:border-gray-700 opacity-50' }}">
                                            <p class="text-xs font-black uppercase tracking-widest {{ $demoData['attended'] ? 'text-emerald-600' : 'text-slate-400' }} mb-2">Demo Day</p>
                                            @if($demoData['attended'])
                                                <div class="flex items-center justify-center gap-2 text-emerald-600">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span class="font-bold text-sm">{{ $demoData['checked_in_at'] }}</span>
                                                </div>
                                            @else
                                                <p class="font-bold text-sm text-slate-400">Locked</p>
                                            @endif
                                        </div>
                                    @endif
                                @endif

                                @for($day = 1; $day <= 3; $day++)
                                    @php
                                        $dayData = $attendee['attendance'][$day] ?? ['attended' => false, 'checked_in_at' => null];
                                        $isToday = $day == $attendee['current_day'];
                                        $canMark = $isToday && !$dayData['attended'] && $attendee['is_paid'];
                                    @endphp

                                    @if($canMark)
                                        <button onclick="markAttendance(event, {{ $day }})"
                                            class="p-6 rounded-2xl text-center transition-all group border-2 hover:shadow-lg active:scale-95
                                            {{ $isToday ? 'bg-violet-50 dark:bg-violet-900/20 border-violet-200 dark:border-violet-800 hover:bg-violet-100' : 'bg-slate-50 dark:bg-gray-800 border-slate-100 dark:border-gray-700 hover:bg-slate-100' }}">
                                            <p class="text-xs font-black uppercase tracking-widest {{ $isToday ? 'text-violet-600' : 'text-slate-400 group-hover:text-slate-600' }} mb-2">Day {{ $day }}</p>
                                            <div class="flex flex-col items-center gap-1">
                                                <p class="font-bold text-sm {{ $isToday ? 'text-violet-600' : 'text-slate-400 group-hover:text-slate-600' }}">{{ $isToday ? 'Mark Today' : 'Mark Present' }}</p>
                                                <svg class="w-4 h-4 {{ $isToday ? 'text-violet-400' : 'text-slate-300' }} opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            </div>
                                        </button>
                                    @else
                                        <div class="p-6 rounded-2xl text-center border-2 {{ $dayData['attended'] ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' : 'bg-slate-50 dark:bg-gray-800 border-slate-100 dark:border-gray-700 opacity-50' }}">
                                            <p class="text-xs font-black uppercase tracking-widest {{ $dayData['attended'] ? 'text-emerald-600' : 'text-slate-400' }} mb-2">Day {{ $day }}</p>
                                            @if($dayData['attended'])
                                                <div class="flex items-center justify-center gap-2 text-emerald-600">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span class="font-bold text-sm">{{ $dayData['checked_in_at'] }}</span>
                                                </div>
                                            @else
                                                <p class="font-bold text-sm text-slate-400">Locked</p>
                                            @endif
                                        </div>
                                    @endif
                                @endfor
                        </div>

                        <!-- Action Button -->
                        @if(!$attendee['is_paid'])
                            <div class="p-6 bg-amber-50 dark:bg-amber-900/20 rounded-2xl text-center border-2 border-amber-100 dark:border-amber-800/30">
                                <svg class="w-12 h-12 text-amber-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <p class="font-black text-amber-700 mb-2">Registration Verification Required</p>
                                <p class="text-sm text-amber-600">Please direct this attendee to the Finance desk to verify payment before any attendance can be recorded.</p>
                            </div>
                        @else
                            <div class="p-6 bg-violet-50 dark:bg-violet-900/10 rounded-2xl flex items-center gap-4 border border-violet-100 dark:border-violet-800/50">
                                <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-600 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p class="text-xs font-medium text-violet-800 dark:text-violet-300">
                                    <strong>Officer Tip:</strong> Attendance can only be marked for the current conference day. Before or after the conference, use Demo Day for testing.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <!-- Right Column: Badge Panel (1/3 width, Vertical) -->
            <div class="lg:col-span-1">
                @php
                    $registrationReminders = [];
                    if (empty($attendee['profile_image'])) {
                        $registrationReminders[] = 'Please upload a profile photo in the app or web portal.';
                    }
                    if (blank($attendee['bio'])) {
                        $registrationReminders[] = 'Please add a short biography for the conference app.';
                    }
                    if ($attendee['is_presenter'] && !$attendee['all_uploads_complete']) {
                        $missingCodes = collect($attendee['presentations'])
                            ->filter(fn($presentation) => empty($presentation['has_upload']))
                            ->map(fn($presentation) => $presentation['conference_code'] ?: 'presentation')
                            ->implode(', ');
                        $registrationReminders[] = 'Please upload missing presentation material' . ($missingCodes ? " ({$missingCodes})." : '.');
                    }
                @endphp

                <div class="bg-white dark:bg-[#111827] rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-gray-800 overflow-hidden mb-8">
                    <div class="p-6 border-b border-slate-50 dark:border-gray-800 {{ count($registrationReminders) ? 'bg-amber-50 dark:bg-amber-950/20' : 'bg-emerald-50 dark:bg-emerald-950/20' }} rounded-t-[2.5rem]">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-2xl {{ count($registrationReminders) ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600' }} flex items-center justify-center shrink-0">
                                @if(count($registrationReminders))
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-xl font-black {{ count($registrationReminders) ? 'text-amber-900 dark:text-amber-200' : 'text-emerald-900 dark:text-emerald-200' }}">Registration Reminders</h3>
                                <p class="text-sm {{ count($registrationReminders) ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300' }}">Quick notes to tell the attendee at the desk.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        @if(count($registrationReminders))
                            <ol class="space-y-3">
                                @foreach($registrationReminders as $reminder)
                                    <li class="flex gap-3">
                                        <span class="w-7 h-7 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs font-black shrink-0">{{ $loop->iteration }}</span>
                                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-relaxed">{{ $reminder }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <p class="text-sm font-bold text-emerald-700 dark:text-emerald-300">No missing profile or presentation items to remind this attendee about.</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white dark:bg-[#111827] rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-gray-800 overflow-visible">
                    <!-- Badge Header -->
                    <div class="p-6 border-b border-slate-50 dark:border-gray-800 bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-900/50 dark:to-slate-800/50 rounded-t-[2.5rem]">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white">Conference Badge</h3>
                                <p class="text-sm text-slate-400">Preview & Print</p>
                            </div>
                            @php
                                $statusClass = 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400';
                                $statusLabel = '○ Payment Pending';

                                if ($attendee['badge_printed']) {
                                    $statusClass = 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400';
                                    $statusLabel = '✓ Printed';
                                } elseif ($attendee['payment_status'] === 'waived') {
                                    $statusClass = 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400';
                                    $statusLabel = '○ Waived (Ready)';
                                } elseif ($attendee['payment_status'] === 'verified') {
                                    $statusClass = 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400';
                                    $statusLabel = '○ Ready to Print';
                                }
                            @endphp
                            <span class="px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        @if($attendee['is_paid'] && !empty($attendee['qr_token']))
                        {{-- Premium Badge Preview Component --}}
                        <div class="w-full mb-6">
                            @include('components.badge-preview', ['user' => $user, 'qrToken' => $attendee['qr_token']])
                        </div>

                        {{-- Premium Print Button --}}
                        <button onclick="printBadge()" class="w-full py-5 text-base font-black uppercase tracking-widest text-white bg-gradient-to-r from-[#1e3a8a] via-[#1e40af] to-[#1e3a8a] hover:from-[#1d4ed8] hover:via-[#2563eb] hover:to-[#1d4ed8] rounded-2xl shadow-2xl shadow-blue-900/30 transition-all duration-300 flex items-center justify-center gap-4 group border-2 border-blue-700/20 mb-6">
                            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center group-hover:bg-white/20 transition-all">
                                <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </div>
                            <div class="text-left">
                                <span class="block text-lg">Print Badge</span>
                                <span class="block text-[10px] opacity-70 font-medium normal-case tracking-normal">Generate PDF for printing</span>
                            </div>
                        </button>

                        @if($attendee['badge_printed'])
                            <div class="flex items-center justify-center gap-2 text-sm text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-3 rounded-xl border border-emerald-200 dark:border-emerald-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Last printed: <strong>{{ $attendee['badge_printed_at'] }}</strong></span>
                            </div>
                        @endif
                        @else
                        {{-- Payment Not Verified - No Badge --}}
                        <div class="w-full aspect-[298/420] bg-gradient-to-br from-slate-50 to-slate-100 dark:from-gray-800 dark:to-gray-900 rounded-2xl border-2 border-dashed border-slate-300 dark:border-gray-700 flex flex-col items-center justify-center p-8 text-center mb-6">
                            <div class="w-20 h-20 bg-slate-200 dark:bg-gray-700 rounded-2xl flex items-center justify-center mb-6 shadow-inner">
                                <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white mb-2">Badge Locked</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Payment verification required before badge can be generated</p>
                            <div class="mt-4 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <p class="text-xs text-amber-700 dark:text-amber-400 font-semibold">⚠️ Verify payment first</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="success-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl hidden items-center justify-center z-[100]">
    <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-12 max-w-sm w-full mx-4 shadow-2xl transform scale-95 opacity-0 transition-all text-center" id="success-modal-content">
        <div class="w-20 h-20 mx-auto mb-8 rounded-[2rem] bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/20">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2" id="success-name">Attendance Recorded</h3>
        <p class="text-sm text-slate-400 font-medium mb-10" id="success-message">Successfully marked present</p>
        <button onclick="location.reload()" class="w-full py-4 bg-slate-900 dark:bg-gray-700 text-white font-black text-[10px] uppercase tracking-widest rounded-xl transition-all hover:bg-slate-800 shadow-lg">
            Done
        </button>
    </div>
</div>

<!-- Attendance Confirmation Modal -->
<div id="confirm-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl hidden items-center justify-center z-[100]">
    <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-10 max-w-md w-full mx-4 shadow-2xl transform scale-95 opacity-0 transition-all text-center" id="confirm-modal-content">
        <div class="w-20 h-20 mx-auto mb-6 rounded-[2rem] bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Check-in Different Day?</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-8">
            You are marking attendance for <span id="confirm-day-label" class="text-violet-600 font-bold">Day X</span>, which is different from the current system day (<span class="text-slate-900 dark:text-white font-bold">Day {{ $attendee['current_day'] }}</span>). Proceed?
        </p>
        <div class="flex gap-4">
            <button onclick="closeConfirmModal()" class="flex-1 py-4 bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300 font-black text-[10px] uppercase tracking-widest rounded-xl transition-all hover:bg-slate-200">
                Cancel
            </button>
            <button id="confirm-proceed-btn" class="flex-1 py-4 bg-emerald-600 text-white font-black text-[10px] uppercase tracking-widest rounded-xl transition-all hover:bg-emerald-700 shadow-lg shadow-emerald-600/20">
                Yes, Mark Present
            </button>
        </div>
    </div>
</div>

<!-- Edit Details Modal -->
<div id="edit-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl hidden items-center justify-center z-[100] p-4">
    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] w-full max-w-3xl mx-auto shadow-2xl transform scale-95 opacity-0 transition-all max-h-[90vh] flex flex-col" id="edit-modal-content">
        <div class="flex items-center justify-between p-8 border-b border-slate-100 dark:border-gray-700 shrink-0">
            <div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white">Edit Attendee Details</h3>
                <p class="text-sm text-slate-400 mt-1">Update {{ $attendee['name'] }}'s profile</p>
            </div>
            <button onclick="closeEditModal()" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-700 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('registration.attendee-details.update', $attendee['id']) }}" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            @method('PATCH')

            <div class="p-8 overflow-y-auto space-y-5">
                @if($errors->any())
                    <div class="px-4 py-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-700 dark:text-rose-300 text-sm font-bold">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Title --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Title</label>
                        <select name="title" class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10 appearance-none cursor-pointer">
                            <option value="">N/A</option>
                            @foreach(['Dr.', 'Prof.', 'Mr.', 'Ms.', 'Mrs.'] as $t)
                                <option value="{{ $t }}" {{ old('title', $attendee['title']) === $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Email --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $attendee['email']) }}" required
                               class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10">
                    </div>

                    {{-- First Name --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">First Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name', $attendee['first_name']) }}" required
                               class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10">
                    </div>

                    {{-- Last Name --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Last Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name', $attendee['last_name']) }}" required
                               class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10">
                    </div>

                    {{-- Phone --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $attendee['phone']) }}"
                               class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10">
                    </div>

                    {{-- Country --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Country</label>
                        <input type="text" name="country" value="{{ old('country', $attendee['country']) }}"
                               class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10">
                    </div>

                    {{-- Affiliation --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Affiliation</label>
                        <input type="text" name="affiliation" value="{{ old('affiliation', $user->affiliation) }}"
                               class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10">
                    </div>

                    {{-- Institute --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Institute</label>
                        <input type="text" name="institute" value="{{ old('institute', $user->institute) }}"
                               class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10">
                    </div>

                    {{-- Specialization --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Specialization</label>
                        <input type="text" name="specialization" value="{{ old('specialization', $user->specialization) }}"
                               class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10">
                    </div>

                    {{-- Registration Number --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Registration Number</label>
                        <input type="text" name="registration_number" value="{{ old('registration_number', $user->registration_number) }}"
                               class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10">
                    </div>

                    {{-- Registration Category --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Registration Category</label>
                        <select name="registration_category" class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10 appearance-none cursor-pointer">
                            @php $cat = old('registration_category', $user->registration_category); @endphp
                            <option value="">Select Category...</option>
                            <option value="professional_local" {{ $cat === 'professional_local' ? 'selected' : '' }}>Participant (East Africa)</option>
                            <option value="professional_international" {{ $cat === 'professional_international' ? 'selected' : '' }}>Participant (International)</option>
                            <option value="student_local" {{ $cat === 'student_local' ? 'selected' : '' }}>Student (East Africa)</option>
                            <option value="student_international" {{ $cat === 'student_international' ? 'selected' : '' }}>Student (International)</option>
                        </select>
                    </div>

                    {{-- Payment Status --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Payment Status</label>
                        <select name="payment_status" class="w-full h-12 px-4 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-bold text-sm text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-violet-500/10 appearance-none cursor-pointer">
                            @php $ps = old('payment_status', $attendee['payment_status']); @endphp
                            @foreach(['pending' => 'Pending', 'submitted' => 'Submitted', 'verified' => 'Verified', 'waived' => 'Waived', 'rejected' => 'Rejected'] as $val => $label)
                                <option value="{{ $val }}" {{ $ps === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Bio --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Biography</label>
                    <textarea name="bio" rows="3" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-medium text-sm text-slate-900 dark:text-white resize-none outline-none focus:ring-4 focus:ring-violet-500/10">{{ old('bio', $attendee['bio']) }}</textarea>
                </div>

                {{-- Payment Notes --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest ml-1">Payment Notes (optional)</label>
                    <textarea name="payment_notes" rows="2" placeholder="e.g. Verified at desk, paid cash" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl font-medium text-sm text-slate-900 dark:text-white resize-none outline-none focus:ring-4 focus:ring-violet-500/10">{{ old('payment_notes', $user->payment_notes) }}</textarea>
                </div>
            </div>

            <div class="flex gap-4 p-8 border-t border-slate-100 dark:border-gray-700 shrink-0">
                <button type="button" onclick="closeEditModal()" class="flex-1 py-4 bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300 font-black text-[10px] uppercase tracking-widest rounded-xl transition-all hover:bg-slate-200">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-4 bg-violet-600 text-white font-black text-[10px] uppercase tracking-widest rounded-xl transition-all hover:bg-violet-700 shadow-lg shadow-violet-600/20">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openEditModal() {
    const modal = document.getElementById('edit-modal');
    const content = document.getElementById('edit-modal-content');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => content.classList.remove('scale-95', 'opacity-0'), 10);
}

@if($errors->any())
document.addEventListener('DOMContentLoaded', openEditModal);
@endif

function closeEditModal() {
    const modal = document.getElementById('edit-modal');
    const content = document.getElementById('edit-modal-content');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 200);
}

function printBadge() {
    // Open the print route in a new window/tab
    window.open('{{ route("registration.print-badge", $attendee["id"]) }}', '_blank');
}

function markAttendance(event, day) {
    if (event) event.preventDefault();

    const currentDay = {{ (int) $attendee['current_day'] }};
    const currentDayLabel = currentDay === 0 ? 'Demo Day' : `Day ${currentDay}`;

    if (day !== currentDay) {
        alert(`Attendance is locked to ${currentDayLabel}. Use Demo Day for testing outside conference dates.`);
        return false;
    }

    proceedMarkAttendance(day);
}

function showConfirmModal(day) {
    const modal = document.getElementById('confirm-modal');
    const content = document.getElementById('confirm-modal-content');
    const label = document.getElementById('confirm-day-label');
    const proceedBtn = document.getElementById('confirm-proceed-btn');

    label.textContent = `Day ${day}`;
    proceedBtn.onclick = () => {
        closeConfirmModal();
        proceedMarkAttendance(day);
    };

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => content.classList.remove('scale-95', 'opacity-0'), 10);
}

function closeConfirmModal() {
    const modal = document.getElementById('confirm-modal');
    const content = document.getElementById('confirm-modal-content');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 200);
}

function proceedMarkAttendance(day) {
    fetch('{{ route("registration.record-attendance", $attendee["id"]) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ day: day })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Play success sound
            try {
                const audio = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audio.createOscillator();
                const gain = audio.createGain();
                osc.connect(gain);
                gain.connect(audio.destination);
                osc.frequency.value = 880;
                osc.start();
                setTimeout(() => { osc.stop(); audio.close(); }, 150);
            } catch(e) {}

            // Show success modal
            const modal = document.getElementById('success-modal');
            const content = document.getElementById('success-modal-content');
            document.getElementById('success-message').textContent = data.message;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => content.classList.remove('scale-95', 'opacity-0'), 10);
        } else {
            alert(data.message || 'Failed to record attendance');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error. Please try again.');
    });

    return false;
}

</script>
@endpush
@endsection
