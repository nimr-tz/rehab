@extends('layouts.admin')

@section('title', 'Attendance Scanner')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-white mb-2">Attendance Scanner</h1>
            <p class="text-slate-400">Scan QR codes to record daily attendance</p>
        </div>

        <!-- Day Selector & Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <!-- Day Selector -->
            <div class="lg:col-span-1 bg-slate-800/50 backdrop-blur-xl rounded-2xl p-6 border border-slate-700">
                <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Select Day</h3>
                <div class="space-y-2">
                    @for($d = 1; $d <= 3; $d++)
                    <button 
                        onclick="selectDay({{ $d }})"
                        id="day-btn-{{ $d }}"
                        class="w-full py-3 px-4 rounded-xl font-semibold transition-all {{ $d == $today ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}"
                    >
                        Day {{ $d }}
                    </button>
                    @endfor
                </div>
                <input type="hidden" id="selected-day" value="{{ $today }}">
            </div>

            <!-- Stats Cards -->
            <div class="lg:col-span-3 grid grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-6 text-white">
                    <p class="text-blue-200 text-sm font-medium">Today's Check-ins</p>
                    <p class="text-4xl font-bold mt-2" id="today-count">{{ $todayCount }}</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-2xl p-6 text-white">
                    <p class="text-emerald-200 text-sm font-medium">Total Registered</p>
                    <p class="text-4xl font-bold mt-2">{{ $totalRegistered }}</p>
                </div>
                <div class="bg-gradient-to-br from-violet-600 to-violet-700 rounded-2xl p-6 text-white">
                    <p class="text-violet-200 text-sm font-medium">Attendance Rate</p>
                    <p class="text-4xl font-bold mt-2">{{ $totalRegistered > 0 ? round(($todayCount / $totalRegistered) * 100) : 0 }}%</p>
                </div>
            </div>
        </div>

        <!-- Scanner Area -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- QR Input -->
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl p-8 border border-slate-700">
                <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Scan QR Code
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">QR Code / Token</label>
                        <input 
                            type="text" 
                            id="qr-input" 
                            placeholder="Scan or enter QR code token..."
                            class="w-full px-4 py-4 bg-slate-900 border border-slate-600 rounded-xl text-white text-lg font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            autofocus
                            autocomplete="off"
                        >
                    </div>
                    
                    <button 
                        onclick="processQR()"
                        id="scan-btn"
                        class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Record Attendance
                    </button>
                </div>

                <!-- Result Display -->
                <div id="result-container" class="mt-6 hidden">
                    <div id="result-content" class="rounded-xl p-6"></div>
                </div>
            </div>

            <!-- Recent Check-ins -->
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl p-8 border border-slate-700">
                <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Recent Check-ins
                </h3>
                
                <div class="space-y-3" id="recent-list">
                    @forelse($recentCheckIns as $checkin)
                    <div class="flex items-center gap-4 p-3 bg-slate-900/50 rounded-xl">
                        <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                            {{ substr($checkin->user->first_name, 0, 1) }}{{ substr($checkin->user->last_name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-medium truncate">{{ $checkin->user->full_name }}</p>
                            <p class="text-slate-400 text-sm">{{ $checkin->user->affiliation }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-emerald-400 text-sm font-medium">{{ $checkin->checked_in_at->format('g:i A') }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-slate-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m12-3.803a4 4 0 11-5 0"/>
                        </svg>
                        <p>No check-ins yet today</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 flex justify-center gap-4">
            <a href="{{ route('admin.attendance.report') }}" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-medium transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                View Full Report
            </a>
        </div>
    </div>
</div>

<script>
let selectedDay = {{ $today }};

function selectDay(day) {
    selectedDay = day;
    document.getElementById('selected-day').value = day;
    
    // Update button styles
    for (let d = 1; d <= 3; d++) {
        const btn = document.getElementById('day-btn-' + d);
        if (d === day) {
            btn.className = 'w-full py-3 px-4 rounded-xl font-semibold transition-all bg-blue-600 text-white';
        } else {
            btn.className = 'w-full py-3 px-4 rounded-xl font-semibold transition-all bg-slate-700 text-slate-300 hover:bg-slate-600';
        }
    }
    
    // Clear input and result
    document.getElementById('qr-input').value = '';
    document.getElementById('result-container').classList.add('hidden');
    document.getElementById('qr-input').focus();
}

function processQR() {
    const qrToken = document.getElementById('qr-input').value.trim();
    const day = selectedDay;
    
    if (!qrToken) {
        showResult('error', 'Please scan or enter a QR code token');
        return;
    }
    
    const btn = document.getElementById('scan-btn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Processing...';
    
    fetch('{{ route("admin.attendance.scan") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            qr_token: qrToken,
            day: day
        })
    })
    .then(response => response.json().then(data => ({ ok: response.ok, data })))
    .then(({ ok, data }) => {
        if (ok && data.success) {
            showResult('success', data.message, data.user, data.attendance);
            updateRecentList(data.user, data.attendance);
            incrementCount();
        } else {
            showResult('error', data.message, data.user, data.attendance);
        }
    })
    .catch(error => {
        showResult('error', 'Network error. Please try again.');
        console.error(error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Record Attendance';
        document.getElementById('qr-input').value = '';
        document.getElementById('qr-input').focus();
    });
}

function showResult(type, message, user = null, attendance = null) {
    const container = document.getElementById('result-container');
    const content = document.getElementById('result-content');
    
    container.classList.remove('hidden');
    
    if (type === 'success') {
        content.className = 'rounded-xl p-6 bg-emerald-900/50 border border-emerald-600';
        content.innerHTML = `
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-emerald-600 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-emerald-400 font-bold text-lg">${message}</p>
                    <p class="text-emerald-200 text-sm">${attendance ? attendance.day_label + ' • ' + attendance.checked_in_at : ''}</p>
                </div>
            </div>
            ${user ? `
            <div class="bg-slate-900/50 rounded-xl p-4">
                <p class="text-white font-semibold text-lg">${user.name}</p>
                <p class="text-slate-400">${user.affiliation || ''}</p>
                ${user.is_presenter ? '<span class="inline-block mt-2 px-3 py-1 bg-violet-600 text-white text-xs font-bold rounded-full">PRESENTER</span>' : ''}
            </div>
            ` : ''}
        `;
    } else {
        content.className = 'rounded-xl p-6 bg-red-900/50 border border-red-600';
        content.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div>
                    <p class="text-red-400 font-bold text-lg">${message}</p>
                    ${user ? `<p class="text-red-200 text-sm">${user.name} • ${user.email}</p>` : ''}
                    ${attendance ? `<p class="text-red-200 text-sm">Checked in at ${attendance.checked_in_at}</p>` : ''}
                </div>
            </div>
        `;
    }
}

function updateRecentList(user, attendance) {
    const list = document.getElementById('recent-list');
    const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    
    const newItem = document.createElement('div');
    newItem.className = 'flex items-center gap-4 p-3 bg-emerald-900/30 rounded-xl border border-emerald-600 animate-pulse';
    newItem.innerHTML = `
        <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
            ${initials}
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-white font-medium truncate">${user.name}</p>
            <p class="text-slate-400 text-sm">${user.affiliation || ''}</p>
        </div>
        <div class="text-right">
            <p class="text-emerald-400 text-sm font-medium">${attendance.checked_in_at}</p>
        </div>
    `;
    
    list.insertBefore(newItem, list.firstChild);
    
    // Remove animation after 2 seconds
    setTimeout(() => {
        newItem.classList.remove('animate-pulse', 'bg-emerald-900/30', 'border', 'border-emerald-600');
        newItem.classList.add('bg-slate-900/50');
    }, 2000);
    
    // Remove old items if more than 10
    while (list.children.length > 10) {
        list.removeChild(list.lastChild);
    }
}

function incrementCount() {
    const countEl = document.getElementById('today-count');
    countEl.textContent = parseInt(countEl.textContent) + 1;
}

// Auto-submit on scanner input (most barcode scanners add Enter key)
document.getElementById('qr-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        processQR();
    }
});
</script>
@endsection
