@extends('layouts.app')

@section('title', 'Conference Program - Final View')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="mb-8 no-print">
        <div class="bg-gradient-to-r from-green-600 to-blue-600 rounded-xl shadow-lg p-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold mb-2">
                        📋 Conference Program
                    </h1>
                    <p class="text-xl text-green-100">
                        Complete schedule organized by time slots
                    </p>
                </div>
                <div class="text-right">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-white/20 rounded-lg p-3">
                            <div class="text-2xl font-bold">{{ $stats['total_days'] }}</div>
                            <div class="text-sm text-green-100">Days</div>
                        </div>
                        <div class="bg-white/20 rounded-lg p-3">
                            <div class="text-2xl font-bold">{{ $stats['total_sessions'] }}</div>
                            <div class="text-sm text-green-100">Sessions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Actions -->
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 no-print">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Export Options</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Download the program in various formats</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.conference-program.export-pdf') }}" 
                   class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-lg font-medium transition-all shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Program PDF
                </a>
                <a href="{{ route('admin.conference-program.view-pdf') }}"
                   class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-lg font-medium transition-all shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Preview Programme
                </a>
                <a href="{{ route('admin.conference-program.generate-abstract-book') }}" 
                   class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-lg font-medium transition-all shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Abstract Book
                </a>
                <a href="{{ route('admin.conference-program.export') }}" 
                   class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-lg font-medium transition-all shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    CSV Export
                </a>
                <button onclick="sendNotifications()" id="notifyBtn"
                   class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-lg font-medium transition-all shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    📧 Notify
                </button>
            </div>
        </div>
    </div>

    <!-- Program Content -->
    <div class="space-y-12">
        @forelse($programByDay as $date => $timeSlots)
            @php
                $dateValue = trim((string) $date);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                    try {
                        $dayName = \Carbon\Carbon::createFromFormat('Y-m-d', $dateValue)->format('l, F j, Y');
                    } catch (\Exception $e) {
                        $dayName = $dateValue;
                    }
                } else {
                    $dayName = $dateValue !== '' ? $dateValue : 'Unassigned';
                }
                $dayNumber = $loop->iteration;
            @endphp
            
            <div class="space-y-6">
                <!-- Day Header -->
                <div class="sticky top-0 z-10 bg-white dark:bg-gray-900 py-4 border-b-2 border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <span class="bg-blue-100 text-blue-800 text-lg font-semibold mr-3 px-3 py-1 rounded dark:bg-blue-900 dark:text-blue-300">
                            DAY {{ $dayNumber }}
                        </span>
                        {{ strtoupper($dayName) }}
                    </h2>
                </div>

                <!-- Time Slots -->
                <div class="space-y-6">
                    @foreach($timeSlots as $timeRange => $sessions)
                        @php
                            // timeRange is now "HH:MM-HH:MM" or "TBD-TBD"
                            $parts = explode('-', $timeRange, 2);
                            $start = trim($parts[0] ?? 'TBD');
                            $end = trim($parts[1] ?? 'TBD');
                            
                            // Safely format times (they might already be HH:MM format)
                            if ($start !== 'TBD' && strpos($start, ':') !== false) {
                                try {
                                    $startTime = \Carbon\Carbon::parse($start)->format('H:i');
                                } catch (\Exception $e) {
                                    $startTime = $start;
                                }
                            } else {
                                $startTime = $start;
                            }
                            
                            if ($end !== 'TBD' && strpos($end, ':') !== false) {
                                try {
                                    $endTime = \Carbon\Carbon::parse($end)->format('H:i');
                                } catch (\Exception $e) {
                                    $endTime = $end;
                                }
                            } else {
                                $endTime = $end;
                            }
                            
                            $isParallel = $sessions->count() > 1;

                            // Poster sessions share one coordinator for the whole day (not a chair per screen).
                            $hasPoster = $sessions->contains(fn($s) => strtolower((string)$s->session_type) === 'poster');
                            $posterCoordinator = $hasPoster
                                ? \App\Support\PosterCoordinator::forDayNumber($dayNumber)
                                : null;
                        @endphp

                        @if($posterCoordinator)
                            <div class="flex gap-4">
                                <div class="w-20 md:w-24 flex-shrink-0"></div>
                                <div class="flex-1 text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>Poster Coordinator: {{ $posterCoordinator }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="flex gap-4">
                            <!-- Time Column -->
                            <div class="w-20 md:w-24 flex-shrink-0 text-right pr-4 border-r-2 border-blue-200 dark:border-blue-800">
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $startTime }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">to {{ $endTime }}</div>
                            </div>

                            <!-- Sessions Grid -->
                            <div class="flex-1 {{ $isParallel ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4' : '' }}">
                                @foreach($sessions as $session)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg border {{ $session->type_color_class ?? 'border-gray-200 dark:border-gray-700' }} shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
                                        <!-- Session Header -->
                                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                                                    {{ $session->room_location ?? 'TBA' }}
                                                </span>
                                                @if($session->session_type)
                                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ ucfirst($session->session_type) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <h3 class="font-bold text-gray-900 dark:text-white leading-tight">
                                                {{ $session->name }}
                                            </h3>
                                            @if($session->session_chair && strtolower((string)$session->session_type) !== 'poster')
                                                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                    <span class="truncate">Chair: {{ $session->session_chair }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Abstracts (Concise) -->
                                        @if($session->abstracts->count() > 0)
                                            <div class="p-3 flex-1 bg-white dark:bg-gray-800">
                                                <div class="space-y-2">
                                                    @foreach($session->abstracts as $abstract)
                                                        <div class="flex items-center text-sm">
                                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 font-mono font-bold rounded text-xs">
                                                                {{ $abstract->conference_code }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">No Sessions Scheduled</h3>
                <p class="text-gray-600 dark:text-gray-400">Go to the Program Builder to start scheduling.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
function sendNotifications() {
    if (!confirm('This will send session assignment emails to ALL participants with scheduled presentations. Are you sure?')) {
        return;
    }
    
    const btn = document.getElementById('notifyBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Sending...';
    
    fetch('{{ route("admin.conference-program.send-notifications") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
        } else {
            alert('⚠️ Error: ' + (data.message || 'Failed to send notifications'));
        }
    })
    .catch(error => {
        alert('❌ Network error. Please try again.');
        console.error(error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg> 📧 Send All Notifications';
    });
}
</script>
@endpush
