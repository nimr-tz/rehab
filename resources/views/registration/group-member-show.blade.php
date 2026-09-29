@extends('layouts.app')

@section('title', $attendee['name'] . ' - Group Member Profile')

@section('content')
<div class="min-h-screen bg-[#f8fafc] dark:bg-[#0a0a0b] font-sans pb-24">
    <!-- Radiant Header -->
    <div class="relative min-h-[340px] flex items-center overflow-hidden bg-gradient-to-br from-[#710000] via-[#c40000] to-[#8a0000] rounded-b-[4rem] shadow-[0_20px_50px_rgba(184,0,0,0.2)]">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[60%] bg-[#ff0000]/20 rounded-full blur-[120px] animate-pulse"></div>
        
        <div class="max-w-7xl mx-auto px-8 w-full relative z-10 py-12">
            <div class="flex flex-col lg:flex-row gap-10 items-start lg:items-center">
                <!-- Back Button -->
                <a href="{{ route('registration.dashboard') }}" class="absolute top-8 left-8 lg:static lg:w-14 lg:h-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center text-white hover:bg-white/20 transition-colors shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>

                <!-- Profile Info -->
                <div class="flex-1 flex flex-col md:flex-row gap-8 items-center md:items-start text-center md:text-left pt-12 lg:pt-0">
                    <!-- Avatar -->
                    <div class="w-32 h-32 rounded-[2.5rem] bg-white text-rose-600 flex items-center justify-center text-4xl font-black shadow-2xl shrink-0 rotate-3 transform hover:rotate-0 transition-all duration-300">
                        {{ $attendee['initials'] }}
                    </div>

                    <!-- Details -->
                    <div class="space-y-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-3 justify-center md:justify-start mb-2">
                                <span class="px-3 py-1 bg-white/20 backdrop-blur border border-white/10 text-white rounded-lg text-xs font-bold uppercase tracking-widest">
                                    Group Member
                                </span>
                                <span class="px-3 py-1 bg-white/10 backdrop-blur text-white rounded-lg text-xs font-medium">
                                    {{ $attendee['group_name'] }}
                                </span>
                                @if($attendee['is_paid'])
                                    <span class="px-3 py-1 bg-emerald-400/20 backdrop-blur border border-emerald-400/30 text-emerald-100 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span>
                                        Payment Verified
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-amber-400/20 backdrop-blur border border-amber-400/30 text-amber-100 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 bg-amber-400 rounded-full animate-pulse"></span>
                                        Group Payment Pending
                                    </span>
                                @endif
                            </div>
                            <h1 class="text-4xl md:text-5xl font-black text-white leading-tight tracking-tight">
                                {{ $attendee['name'] }}
                            </h1>
                            <p class="text-white/70 text-lg font-medium mt-1">{{ $attendee['affiliation'] }}</p>
                        </div>

                        <!-- Contact -->
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="max-w-7xl mx-auto px-8 -mt-12 relative z-20 pb-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <!-- Daily Attendance -->
                <div class="bg-white dark:bg-[#111827] rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-gray-800 overflow-hidden">
                    <div class="p-8 border-b border-slate-50 dark:border-gray-800">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Daily Attendance</h3>
                        <p class="text-sm text-slate-400 mt-1">Track presence for this group member</p>
                    </div>
                    
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                            @for($day = 1; $day <= 3; $day++)
                                @php
                                    $dayData = $attendee['attendance'][$day] ?? ['attended' => false, 'checked_in_at' => null];
                                    $checkedInAt = $dayData['checked_in_at'] ?? null;
                                    $isToday = $day == $attendee['current_day'];
                                @endphp
                                <div class="p-6 rounded-2xl text-center {{ $checkedInAt ? 'bg-emerald-50 dark:bg-emerald-900/20 border-2 border-emerald-200 dark:border-emerald-800' : ($isToday ? 'bg-rose-50 dark:bg-rose-900/20 border-2 border-rose-200 dark:border-rose-800' : 'bg-slate-50 dark:bg-gray-800 border-2 border-slate-100 dark:border-gray-700') }}">
                                    <p class="text-xs font-black uppercase tracking-widest {{ $checkedInAt ? 'text-emerald-600' : ($isToday ? 'text-rose-600' : 'text-slate-400') }} mb-2">Day {{ $day }}</p>
                                    @if($checkedInAt)
                                        <div class="flex items-center justify-center gap-2 text-emerald-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span class="font-bold text-sm">{{ $checkedInAt }}</span>
                                        </div>
                                    @else
                                        <p class="font-bold text-sm {{ $isToday ? 'text-rose-600' : 'text-slate-400' }}">{{ $isToday ? 'Today' : 'Pending' }}</p>
                                    @endif
                                </div>
                            @endfor
                        </div>

                        @if(!$attendee['is_paid'])
                            <div class="p-6 bg-amber-50 dark:bg-amber-900/20 rounded-2xl text-center">
                                <p class="font-black text-amber-700 mb-1 tracking-tight">Group Payment Required</p>
                                <p class="text-sm text-amber-600">This member belongs to group <strong>{{ $attendee['group_name'] }}</strong> which has not cleared payment.</p>
                            </div>
                        @elseif(($attendee['attendance'][$attendee['current_day']]['attended'] ?? false))
                            <div class="p-6 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl text-center">
                                <p class="font-black text-emerald-700 mb-1 tracking-tight">Already Present</p>
                                <p class="text-sm text-emerald-600">Day {{ $attendee['current_day'] }} attendance recorded.</p>
                            </div>
                        @else
                            <form id="attendance-form" onsubmit="return markAttendance(event)">
                                <button type="submit" id="mark-btn" class="w-full py-5 bg-emerald-600 hover:bg-emerald-700 text-white font-black uppercase tracking-widest rounded-2xl transition-colors shadow-xl flex items-center justify-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Mark Present for Day {{ $attendee['current_day'] }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column: Badge Panel -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-[#111827] rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-gray-800 p-6">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-6">Credential Management</h3>
                    
                    @if($attendee['is_paid'])
                        <a href="{{ route('registration.group-member.print-badge', $member->id) }}" target="_blank" class="w-full py-5 bg-slate-900 dark:bg-white text-white dark:text-black font-black uppercase tracking-widest rounded-2xl transition-all hover:scale-[1.02] shadow-xl flex items-center justify-center gap-3 mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Print Member Badge
                        </a>
                        
                        <div class="p-6 bg-slate-50 dark:bg-gray-800 rounded-2xl text-center border-2 border-dashed border-slate-200 dark:border-gray-700">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">QR Identifier</p>
                            <p class="font-mono text-lg font-bold text-slate-900 dark:text-white">{{ $attendee['qr_token'] }}</p>
                        </div>

                        @if(in_array($attendee['category'], ['student_local', 'student_international'], true) && !empty($attendee['student_id_path']))
                            <a href="{{ Storage::url($attendee['student_id_path']) }}" target="_blank" class="mt-4 w-full py-4 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-black uppercase tracking-widest rounded-2xl transition-all hover:bg-blue-100 dark:hover:bg-blue-900/30 flex items-center justify-center gap-3 border border-blue-100 dark:border-blue-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                View Student ID
                            </a>
                        @endif
                    @else
                        <div class="p-8 bg-slate-50 dark:bg-gray-800 rounded-3xl text-center border border-slate-100 dark:border-gray-800">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <h4 class="font-bold text-slate-900 dark:text-white mb-2">Badge Locked</h4>
                            <p class="text-xs text-slate-400">Payment must be verified for the entire group before badges can be issued.</p>
                        </div>
                    @endif
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
        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Attendance Recorded.</h3>
        <p class="text-sm text-slate-400 font-medium mb-10" id="success-message">Successfully marked present</p>
        <button onclick="location.reload()" class="w-full py-4 bg-slate-900 dark:bg-gray-700 text-white font-black text-[10px] uppercase tracking-widest rounded-xl transition-all hover:bg-slate-800 shadow-lg">
            Done
        </button>
    </div>
</div>

@push('scripts')
<script>
function markAttendance(event) {
    event.preventDefault();
    
    const btn = document.getElementById('mark-btn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-5 h-5 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>';
    
    fetch('{{ route("registration.group-member.record-attendance", $member->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ day: {{ $attendee['current_day'] }} })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const modal = document.getElementById('success-modal');
            const content = document.getElementById('success-modal-content');
            document.getElementById('success-message').textContent = data.message;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => content.classList.remove('scale-95', 'opacity-0'), 10);
        } else {
            alert(data.message || 'Failed to record attendance');
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Mark Present for Day {{ $attendee['current_day'] }}';
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
