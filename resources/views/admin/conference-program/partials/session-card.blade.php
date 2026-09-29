@php
    $scheduleDays = $session->schedule_days;
    if (is_string($scheduleDays)) {
        $scheduleDays = json_decode($scheduleDays, true);
    }
    if (!is_array($scheduleDays)) {
        $scheduleDays = [];
    }

    try {
        $sessionStart = $session->start_time ? \Carbon\Carbon::parse($session->start_time) : null;
        $sessionEnd = $session->end_time ? \Carbon\Carbon::parse($session->end_time) : null;
    } catch (\Exception $e) {
        $sessionStart = null;
        $sessionEnd = null;
    }

    $scheduledMinutes = ($sessionStart && $sessionEnd) ? max(0, $sessionStart->diffInMinutes($sessionEnd, false)) : 0;
    $scheduledHours = floor($scheduledMinutes / 60);
    $scheduledRemainder = $scheduledMinutes % 60;
    $scheduledDurationText = $scheduledHours > 0
        ? trim($scheduledHours . 'h ' . ($scheduledRemainder ? $scheduledRemainder . 'm' : ''))
        : $scheduledMinutes . 'm';

    $abstractCount = $session->abstracts->count();
    $isAbstractSession = in_array($session->session_type, ['presentation', 'poster'], true);
    $isOralPresentationSession = $session->session_type === 'presentation' && $session->presentation_type === 'oral';
    $minutesPerTalk = $isOralPresentationSession ? 10 : 5;
    $expectedSlots = $isOralPresentationSession && $scheduledMinutes > 0
        ? (int) floor($scheduledMinutes / $minutesPerTalk)
        : ($isAbstractSession ? (int) $session->max_abstracts : null);
    $capacityState = $isAbstractSession && $expectedSlots !== null
        ? ($abstractCount > $expectedSlots ? 'over' : ($abstractCount === $expectedSlots ? 'full' : 'open'))
        : 'event';
    $capacityClass = match ($capacityState) {
        'over' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'full' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
        'open' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300',
        default => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300',
    };
    $sessionTypeLabel = ucwords(str_replace('_', ' ', (string) $session->session_type));
@endphp

<div class="session-card bg-white dark:bg-gray-800 rounded-xl border-2 border-gray-200 dark:border-gray-700 shadow-sm mb-3 overflow-hidden"
     data-session-id="{{ $session->id }}"
     data-session-type="{{ $session->session_type }}"
     data-minutes-per-talk="{{ $minutesPerTalk }}"
     data-start-time="{{ $session->start_time ? \Carbon\Carbon::parse($session->start_time)->format('H:i') : '' }}"
     data-end-time="{{ $session->end_time ? \Carbon\Carbon::parse($session->end_time)->format('H:i') : '' }}">
    
    <!-- Collapsible Header -->
    <div class="session-header cursor-pointer p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 hover:from-indigo-100 hover:to-purple-100 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30 transition-colors"
         onclick="toggleSession({{ $session->id }})">
        
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 flex-1">
                <!-- Expand/Collapse Icon -->
                <svg class="collapse-icon w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                
                <!-- Session Name -->
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $session->name }}
                </h3>
                
                <!-- Session Type Badge -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                    {{ $sessionTypeLabel }}
                </span>

                @if($isAbstractSession)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $session->presentation_type === 'oral' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' }}">
                        {{ $session->presentation_type === 'oral' ? 'Oral' : 'Poster' }}
                    </span>

                    <!-- Abstract Count Badge with Expected Capacity -->
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $capacityClass }} capacity-info">
                        <span class="font-medium">{{ $abstractCount }}/{{ $expectedSlots }}</span>
                        <span class="ml-1">{{ $session->session_type === 'poster' ? 'posters' : 'talks' }}</span>
                        @if($isOralPresentationSession)
                            <span class="ml-1 opacity-75">({{ $minutesPerTalk }}m)</span>
                        @endif
                    </span>
                    @if($isOralPresentationSession && $scheduledMinutes > 0 && ($abstractCount * $minutesPerTalk) > $scheduledMinutes)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300" title="Abstracts exceed session time by {{ ($abstractCount * $minutesPerTalk) - $scheduledMinutes }} minutes">
                            ⚠ +{{ ($abstractCount * $minutesPerTalk) - $scheduledMinutes }}m over
                        </span>
                    @endif
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $capacityClass }}">
                        Program event
                    </span>
                @endif

                <!-- Scheduled Duration Badge -->
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                    {{ $scheduledDurationText }}
                </span>
            </div>
            
            <!-- Session Stats & Actions Move to Right -->
            <div class="flex items-center gap-6 text-sm text-gray-600 dark:text-gray-400">
                @if(count($scheduleDays) > 0)
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    @php
                        try {
                            $dayDisplay = \Carbon\Carbon::parse($scheduleDays[0])->format('M j');
                        } catch (\Exception $e) {
                            $dayDisplay = $scheduleDays[0];
                        }
                    @endphp
                    {{ $dayDisplay }}
                </div>
                @endif
                
                @if($session->start_time && $session->end_time)
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ \Carbon\Carbon::parse($session->start_time)->format('g:i A') }}
                </div>
                @endif
                
                @if($session->room_location)
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    {{ $session->room_location }}
                </div>
                @endif
                
                <!-- Action Group -->
                <div class="flex items-center gap-1 ml-4 border-l border-gray-200 dark:border-gray-700 pl-4">
                    <!-- Notify Leads -->
                    @if($session->session_chair_id || $session->session_rapporteur_id)
                    <button onclick="event.stopPropagation(); notifySessionLeads({{ $session->id }})" 
                            class="p-2 {{ ($session->chair_notified_at || $session->rapporteur_notified_at) ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20' }} rounded-lg transition-colors group relative"
                            title="{{ ($session->chair_notified_at || $session->rapporteur_notified_at) ? 'Notified: ' . ($session->chair_notified_at ?? $session->rapporteur_notified_at)->format('M j, g:i A') : 'Notify Chairperson & Rapporteur' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        @if($session->chair_notified_at || $session->rapporteur_notified_at)
                            <span class="absolute top-1 right-1 w-2 h-2 bg-green-500 rounded-full border border-white"></span>
                        @endif
                    </button>
                    @endif

                    <!-- Download Presentations -->
                    <a href="{{ route('admin.conference-program.download-session-presentations', $session) }}" 
                       onclick="event.stopPropagation()"
                       class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors group"
                       title="Download All Presentations in this Session">
                        <svg class="w-5 h-5 transition-transform group-hover:translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>

                    <!-- Edit Button -->
                    <button onclick="event.stopPropagation(); editSession({{ $session->id }})" 
                            class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                            title="Edit Session Settings">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>

                    <!-- Delete Button -->
                    <button onclick="event.stopPropagation(); deleteSession({{ $session->id }})" 
                            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                            title="Delete Session">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Additional Info Row (Chair & Rapporteur) -->
        @if($session->session_chair || $session->session_rapporteur)
        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 mt-2 ml-8">
            @if($session->session_chair)
            <div>
                <span class="font-medium">Chair:</span> {{ $session->session_chair }}
            </div>
            @endif
            @if($session->session_rapporteur)
            <div>
                <span class="font-medium">Rapporteur:</span> {{ $session->session_rapporteur }}
            </div>
            @endif
        </div>
        @endif
    </div>
    
    <!-- Collapsible Content -->
    <div class="session-content" style="display: none;">
        @if($isAbstractSession)
        <!-- Drop Zone for Abstracts (Sortable) -->
        <div class="sortable-abstracts p-4 min-h-[100px] bg-gray-50 dark:bg-gray-900/50"
             data-session-id="{{ $session->id }}"
             ondrop="drop(event, {{ $session->id }})"
             ondragover="allowDrop(event)">
            
            @if($session->abstracts && $session->abstracts->count() > 0)
                <!-- Sortable Abstracts List -->
                <div class="space-y-2" id="abstracts-session-{{ $session->id }}">
                    @php
                        try {
                            $startTime = $session->start_time ? \Carbon\Carbon::parse($session->start_time) : null;
                        } catch (\Exception $e) {
                            $startTime = null;
                        }
                    @endphp
                    
                    @foreach($session->abstracts as $index => $abstract)
                    @php
                        if ($startTime) {
                            $presentationTime = $startTime->copy()->addMinutes($index * 10);
                        }
                    @endphp
                    <div class="abstract-in-session flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 cursor-move transition-all"
                         draggable="true"
                         ondragstart="dragWithinSession(event)"
                         ondragover="allowDropWithinSession(event)"
                         ondrop="dropWithinSession(event)"
                         data-abstract-id="{{ $abstract->id }}"
                         data-session-id="{{ $session->id }}">
                        
                        <!-- Drag Handle -->
                        <div class="drag-handle text-gray-400 dark:text-gray-500">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
                            </svg>
                        </div>
                        
                        <!-- Time Badge (Editable) -->
                        @if($startTime)
                        <div class="flex-shrink-0">
                            <div class="time-display text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors"
                                 onclick="editTime(this, {{ $abstract->id }}, {{ $session->id }})"
                                 title="Click to edit time">
                                {{ $presentationTime->format('g:i A') }}
                            </div>
                            <input type="time" 
                                   class="time-input hidden w-20 text-xs px-2 py-1 border border-indigo-300 dark:border-indigo-600 rounded focus:ring-2 focus:ring-indigo-500"
                                   value="{{ $presentationTime->format('H:i') }}"
                                   onblur="saveTime(this, {{ $abstract->id }}, {{ $session->id }})"
                                   onkeydown="if(event.key === 'Enter') this.blur()">
                            <div class="duration-input flex items-center gap-1 mt-0.5">
                                <input type="number" 
                                       class="w-12 text-[10px] text-center border border-gray-300 dark:border-gray-600 rounded px-1 focus:ring-1 focus:ring-indigo-500"
                                       value="10"
                                       min="5"
                                       max="60"
                                       step="5"
                                       onchange="updateDuration(this, {{ $abstract->id }}, {{ $session->id }})"
                                       title="Duration in minutes">
                                <span class="text-[10px] text-gray-500 dark:text-gray-400">min</span>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Order Number -->
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center text-sm font-bold">
                            {{ $index + 1 }}
                        </div>
                        
                        <!-- Code -->
                        <div class="font-mono text-sm font-bold text-blue-600 dark:text-blue-400 flex-shrink-0">
                            {{ $abstract->conference_code }}
                        </div>
                        
                        <!-- Title -->
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $abstract->title }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ \App\Support\TitleFormatter::personName($abstract->author_name) }}
                            </div>
                        </div>
                        
                        <!-- Subtheme Badge -->
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                {{ $abstract->subtheme }}
                            </span>
                        </div>
                        
                        <!-- Remove Button -->
                        <button onclick="removeFromSession({{ $abstract->id }}, {{ $session->id }})" 
                                class="flex-shrink-0 p-1.5 text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                
                <!-- Session Summary -->
                <div class="mt-4 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-700">
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-4">
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Total Presentations:</span>
                                <span class="font-bold text-gray-900 dark:text-white ml-1">{{ $session->abstracts->count() }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Total Duration:</span>
                                <span class="font-bold text-amber-600 dark:text-amber-400 ml-1">{{ $scheduledDurationText }}</span>
                            </div>
                            @if($startTime && $session->abstracts->count() > 0)
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Session End:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 ml-1">
                                    {{ $sessionEnd?->format('g:i A') }}
                                </span>
                            </div>
                            @endif
                        </div>
                        @if($expectedSlots !== null)
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            @if($abstractCount > $expectedSlots)
                                {{ $abstractCount - $expectedSlots }} over scheduled capacity
                            @else
                                {{ $expectedSlots - $abstractCount }} slots remaining
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">No abstracts assigned yet</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Drag abstracts from the left panel to add them</p>
                </div>
            @endif
        </div>
        @else
        <div class="p-4 bg-gray-50 dark:bg-gray-900/50">
            <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-gray-800 p-4">
                <div class="text-sm text-slate-600 dark:text-slate-300">
                    This is a program event. Abstract assignment is disabled for this item.
                </div>
                @if($session->description || $session->speaker)
                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    @if($session->speaker)
                    <div>
                        <span class="font-medium text-slate-700 dark:text-slate-200">Speaker:</span>
                        <span class="text-slate-600 dark:text-slate-300">{{ $session->speaker }}</span>
                    </div>
                    @endif
                    @if($session->description)
                    <div>
                        <span class="font-medium text-slate-700 dark:text-slate-200">Description:</span>
                        <span class="text-slate-600 dark:text-slate-300">{{ $session->description }}</span>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@once
<script>
// Toggle session expand/collapse
function toggleSession(sessionId) {
    const card = document.querySelector(`.session-card[data-session-id="${sessionId}"]`);
    const content = card.querySelector('.session-content');
    const icon = card.querySelector('.collapse-icon');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// Edit presentation time
function editTime(displayElement, abstractId, sessionId) {
    const parent = displayElement.parentElement;
    const timeInput = parent.querySelector('.time-input');
    
    displayElement.classList.add('hidden');
    timeInput.classList.remove('hidden');
    timeInput.focus();
}

// Save presentation time
function saveTime(inputElement, abstractId, sessionId) {
    const parent = inputElement.parentElement;
    const displayElement = parent.querySelector('.time-display');
    const timeValue = inputElement.value;
    
    if (timeValue) {
        // Convert to 12-hour format for display
        const [hours, minutes] = timeValue.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        const displayTime = `${displayHour}:${minutes} ${ampm}`;
        
        displayElement.textContent = displayTime;
        
        // TODO: Save to database via AJAX
        console.log(`Saving time for abstract ${abstractId} in session ${sessionId}: ${timeValue}`);
        
        // Show success notification
        if (typeof window.showNotification === 'function') {
            window.showNotification('⏱️ Time updated!', 'success');
        }
    }
    
    inputElement.classList.add('hidden');
    displayElement.classList.remove('hidden');
}

// Update presentation duration
function updateDuration(inputElement, abstractId, sessionId) {
    const duration = parseInt(inputElement.value);
    
    if (duration >= 5 && duration <= 60) {
        // TODO: Save to database via AJAX
        console.log(`Updating duration for abstract ${abstractId} in session ${sessionId}: ${duration} minutes`);
        
        // Recalculate subsequent times in the session
        recalculateSessionTimes(sessionId);
        
        // Show success notification
        if (typeof window.showNotification === 'function') {
            window.showNotification('⏱️ Duration updated!', 'success');
        }
    } else {
        inputElement.value = 10; // Reset to default
    }
}

// Recalculate all times in a session based on durations
function recalculateSessionTimes(sessionId) {
    const container = document.getElementById(`abstracts-session-${sessionId}`);
    if (!container) return;
    
    const items = container.querySelectorAll('.abstract-in-session');
    let cumulativeMinutes = 0;
    
    // Get session start time (would need to be passed or fetched)
    // For now, we'll keep the first item's time as reference
    const firstItem = items[0];
    if (!firstItem) return;
    
    const firstTimeInput = firstItem.querySelector('.time-input');
    if (!firstTimeInput) return;
    
    const [startHours, startMinutes] = firstTimeInput.value.split(':').map(Number);
    const startTimeInMinutes = startHours * 60 + startMinutes;
    
    items.forEach((item, index) => {
        if (index === 0) {
            // First item keeps its time
            const durationInput = item.querySelector('.duration-input input[type="number"]');
            if (durationInput) {
                cumulativeMinutes += parseInt(durationInput.value);
            }
            return;
        }
        
        // Calculate new time based on cumulative duration
        const newTimeInMinutes = startTimeInMinutes + cumulativeMinutes;
        const newHours = Math.floor(newTimeInMinutes / 60) % 24;
        const newMinutes = newTimeInMinutes % 60;
        
        // Update time input
        const timeInput = item.querySelector('.time-input');
        const timeDisplay = item.querySelector('.time-display');
        
        if (timeInput && timeDisplay) {
            const formattedTime = `${String(newHours).padStart(2, '0')}:${String(newMinutes).padStart(2, '0')}`;
            timeInput.value = formattedTime;
            
            // Update display
            const hour = newHours % 12 || 12;
            const ampm = newHours >= 12 ? 'PM' : 'AM';
            timeDisplay.textContent = `${hour}:${String(newMinutes).padStart(2, '0')} ${ampm}`;
        }
        
        // Add this item's duration to cumulative
        const durationInput = item.querySelector('.duration-input input[type="number"]');
        if (durationInput) {
            cumulativeMinutes += parseInt(durationInput.value);
        }
    });
    
    // Update session summary
    updateSessionSummary(sessionId, cumulativeMinutes);
}

// Update session summary with new total duration
function updateSessionSummary(sessionId, totalMinutes) {
    // This would update the session summary footer
    // Implementation depends on your needs
    console.log(`Session ${sessionId} total duration: ${totalMinutes} minutes`);
}

// Make functions globally accessible
if (typeof window.toggleSession === 'undefined') {
    window.toggleSession = toggleSession;
}
if (typeof window.editTime === 'undefined') {
    window.editTime = editTime;
}
if (typeof window.saveTime === 'undefined') {
    window.saveTime = saveTime;
}
if (typeof window.updateDuration === 'undefined') {
    window.updateDuration = updateDuration;
}
if (typeof window.recalculateSessionTimes === 'undefined') {
    window.recalculateSessionTimes = recalculateSessionTimes;
}
if (typeof window.updateSessionSummary === 'undefined') {
    window.updateSessionSummary = updateSessionSummary;
}

// Drag and drop within session (reordering)
// Declare draggedElement only once globally
if (typeof window.draggedElement === 'undefined') {
    window.draggedElement = null;
}

function dragWithinSession(e) {
    window.draggedElement = e.target;
    e.dataTransfer.effectAllowed = 'move';
    e.target.style.opacity = '0.4';
}

function allowDropWithinSession(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function dropWithinSession(e) {
    // Only handle internal reordering if we have a dragged element from within the session
    if (window.draggedElement) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        if (window.draggedElement !== e.currentTarget) {
            const parent = e.currentTarget.parentNode;
            const allItems = [...parent.children];
            const draggedIndex = allItems.indexOf(window.draggedElement);
            const targetIndex = allItems.indexOf(e.currentTarget);
            
            if (draggedIndex < targetIndex) {
                parent.insertBefore(window.draggedElement, e.currentTarget.nextSibling);
            } else {
                parent.insertBefore(window.draggedElement, e.currentTarget);
            }
            
            // Update order numbers and times
            updateSessionOrder(window.draggedElement.dataset.sessionId);
        }
        
        return false;
    }
    
    // If no draggedElement, it's likely an external drag. 
    // Let it bubble up to the container's ondrop handler.
    return true;
}

// Update order numbers and times after reordering
function updateSessionOrder(sessionId) {
    const container = document.getElementById(`abstracts-session-${sessionId}`);
    if (!container) return;
    
    const items = container.querySelectorAll('.abstract-in-session');
    let startTime = '09:00'; // Default start time
    
    // Try to get the session start time from the first item
    if (items.length > 0) {
        const firstTimeDisplay = items[0].querySelector('.time-display');
        if (firstTimeDisplay) {
            const timeText = firstTimeDisplay.textContent;
            const timeMatch = timeText.match(/(\d+):(\d+) (AM|PM)/);
            if (timeMatch) {
                let hours = parseInt(timeMatch[1]);
                const minutes = timeMatch[2];
                const ampm = timeMatch[3];
                
                if (ampm === 'PM' && hours !== 12) hours += 12;
                if (ampm === 'AM' && hours === 12) hours = 0;
                
                startTime = `${String(hours).padStart(2, '0')}:${minutes}`;
            }
        }
    }
    
    items.forEach((item, index) => {
        // Update order number
        const orderBadge = item.querySelector('.rounded-full.bg-gradient-to-br');
        if (orderBadge) {
            orderBadge.textContent = index + 1;
        }
        
        // Update time based on new order
        const timeDisplay = item.querySelector('.time-display');
        const timeInput = item.querySelector('.time-input');
        
        if (timeDisplay && timeInput) {
            // Calculate new time based on index and start time
            const [startHours, startMinutes] = startTime.split(':').map(Number);
            const totalMinutes = startHours * 60 + startMinutes + (index * 10);
            const abstractHours = Math.floor(totalMinutes / 60);
            const abstractMinutes = totalMinutes % 60;
            const displayHours = abstractHours % 12 || 12;
            const ampm = abstractHours >= 12 ? 'PM' : 'AM';
            const timeString = `${displayHours}:${String(abstractMinutes).padStart(2, '0')} ${ampm}`;
            const timeInputValue = `${String(abstractHours).padStart(2, '0')}:${String(abstractMinutes).padStart(2, '0')}`;
            
            timeDisplay.textContent = timeString;
            timeInput.value = timeInputValue;
        }
    });
    
    // TODO: Save new order to database via AJAX
    console.log('Session order updated for session:', sessionId);
}

// Reset opacity after drag
document.addEventListener('dragend', function(e) {
    if (e.target.classList.contains('abstract-in-session')) {
        e.target.style.opacity = '1';
        window.draggedElement = null;
    }
});

function removeFromSession(abstractId, sessionId) {
    if (typeof window.showConfirmationModal === 'function') {
        window.showConfirmationModal(
            'Remove Abstract',
            'Are you sure you want to remove this abstract from the session?',
            function() {
                performRemoveFromSession(abstractId, sessionId);
            }
        );
    } else {
        if (!confirm('Remove this abstract from the session?')) {
            return;
        }
        performRemoveFromSession(abstractId, sessionId);
    }
}

function performRemoveFromSession(abstractId, sessionId) {
    // Optimistically update the UI first
    const abstractElement = document.querySelector(`[data-abstract-id="${abstractId}"]`);
    if (abstractElement) {
        abstractElement.style.transition = 'all 0.3s ease';
        abstractElement.style.transform = 'translateX(-100%)';
        abstractElement.style.opacity = '0';
    }
    
    fetch('{{ route('admin.conference-program.remove-from-session') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            abstract_id: abstractId,
            session_id: sessionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success - remove element completely and add to sidebar
            if (abstractElement) {
                // Get abstract data before removing
                const codeElement = abstractElement.querySelector('.font-mono');
                const titleElement = abstractElement.querySelector('.text-sm.font-medium');
                const authorElement = abstractElement.querySelector('.text-xs.text-gray-500');
                const subthemeElement = abstractElement.querySelector('.inline-flex.items-center');
                
                const abstractData = {
                    id: abstractId,
                    conference_code: codeElement ? codeElement.textContent : 'N/A',
                    title: titleElement ? titleElement.textContent : 'No Title',
                    author_name: authorElement ? authorElement.textContent : 'Unknown Author',
                    subtheme: subthemeElement ? subthemeElement.textContent : 'General'
                };
                
                setTimeout(() => {
                    abstractElement.remove();
                    
                    // Add abstract back to sidebar
                    addAbstractToSidebar(abstractData);
                }, 300);
            }
            
            // Update session capacity display
            const sessionCard = document.querySelector(`[data-session-id="${sessionId}"]`);
            const capacityDisplay = sessionCard?.querySelector('.capacity-info');
            if (capacityDisplay) {
                const currentText = capacityDisplay.textContent;
                const match = currentText.match(/(\d+)\/(\d+)/);
                if (match) {
                    const current = parseInt(match[1]) - 1;
                    const max = parseInt(match[2]);
                    capacityDisplay.innerHTML = `<span class="text-blue-600 font-medium">${current}/${max}</span> abstracts`;
                }
            }
            
            // Show subtle success feedback
            if (typeof window.showSubtleNotification === 'function') {
                window.showSubtleNotification('✅ Abstract removed from session', 'success');
            }
        } else {
            // Error - revert optimistic changes
            if (abstractElement) {
                abstractElement.style.transform = 'translateX(0)';
                abstractElement.style.opacity = '1';
            }
            
            if (typeof window.showNotificationModal === 'function') {
                window.showNotificationModal('Error', data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Revert optimistic changes on error
        if (abstractElement) {
            abstractElement.style.transform = 'translateX(0)';
            abstractElement.style.opacity = '1';
        }
        
        if (typeof window.showNotificationModal === 'function') {
            window.showNotificationModal('Error', 'An error occurred', 'error');
        }
    });
}

// Add abstract back to sidebar
function addAbstractToSidebar(abstractData) {
    const sidebar = document.querySelector('#abstractsList');
    if (!sidebar) return;
    
    // Create abstract card element
    const abstractCard = document.createElement('div');
    abstractCard.className = 'abstract-card bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-3 cursor-move hover:shadow-md transition-all';
    abstractCard.setAttribute('draggable', 'true');
    abstractCard.setAttribute('ondragstart', 'drag(event)');
    abstractCard.setAttribute('data-id', abstractData.id);
    abstractCard.setAttribute('data-code', abstractData.conference_code);
    
    abstractCard.innerHTML = `
        <div class="flex items-start justify-between mb-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                    ${abstractData.conference_code}
                </span>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    ${abstractData.subtheme}
                </span>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Drag to session
            </div>
        </div>
        
        <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1 line-clamp-2">
            ${abstractData.title}
        </h4>
        
        <p class="text-xs text-gray-600 dark:text-gray-400">
            ${abstractData.author_name}
        </p>
    `;
    
    // Add to sidebar with smooth animation
    abstractCard.style.opacity = '0';
    abstractCard.style.transform = 'translateX(-100%)';
    sidebar.insertBefore(abstractCard, sidebar.firstChild);
    
    // Animate in
    setTimeout(() => {
        abstractCard.style.transition = 'all 0.3s ease';
        abstractCard.style.opacity = '1';
        abstractCard.style.transform = 'translateX(0)';
    }, 100);
}

const editSessionFieldIds = [
    'editSessionModal',
    'editSessionId',
    'editSessionName',
    'editSessionType',
    'editPresentationType',
    'editSessionDate',
    'editStartTime',
    'editEndTime',
    'editRoomLocation',
    'editMaxAbstracts',
    'editStatus',
    'editSessionChairId',
    'editSessionChair',
    'editSessionChairEmail',
    'editSessionRapporteurId',
    'editSessionRapporteur',
    'editSessionRapporteurEmail',
    'editPanelists',
    'editDescription'
];

function waitForEditSessionFields(timeoutMs = 3000) {
    const startedAt = Date.now();

    return new Promise((resolve, reject) => {
        function checkFields() {
            const elements = {};
            const missing = [];

            editSessionFieldIds.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    elements[id] = element;
                } else {
                    missing.push(id);
                }
            });

            if (missing.length === 0) {
                resolve(elements);
                return;
            }

            if (Date.now() - startedAt >= timeoutMs) {
                reject(new Error('The edit form is still loading. Please wait a moment and try again.'));
                return;
            }

            window.requestAnimationFrame(checkFields);
        }

        checkFields();
    });
}

async function editSession(sessionId) {
    try {
        const response = await fetch(`/admin/conference-program/session/${sessionId}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Unable to load session');
        }

        const fields = await waitForEditSessionFields();
        const session = data.session;
        const scheduleDays = Array.isArray(session.schedule_days) ? session.schedule_days : [];
        const startTime = session.start_time ? String(session.start_time).slice(0, 5) : '';
        const endTime = session.end_time ? String(session.end_time).slice(0, 5) : '';

        fields.editSessionId.value = session.id;
        fields.editSessionName.value = session.name || '';
        fields.editSessionType.value = session.session_type || 'presentation';
        fields.editPresentationType.value = session.presentation_type || 'oral';
        fields.editSessionDate.value = scheduleDays[0] || '';
        fields.editStartTime.value = startTime;
        fields.editEndTime.value = endTime;
        fields.editRoomLocation.value = session.room_location || '';
        fields.editMaxAbstracts.value = session.max_abstracts ?? 0;
        fields.editStatus.value = session.status || 'draft';
        fields.editSessionChairId.value = session.session_chair_id || '';
        fields.editSessionChair.value = session.session_chair || '';
        fields.editSessionChairEmail.value = session.session_chair_email || '';
        fields.editSessionRapporteurId.value = session.session_rapporteur_id || '';
        fields.editSessionRapporteur.value = session.session_rapporteur || '';
        fields.editSessionRapporteurEmail.value = session.session_rapporteur_email || '';

        // Clear the linked user ID if the admin changes the name/email (they're overriding manually)
        fields.editSessionChair.addEventListener('input', () => { fields.editSessionChairId.value = ''; }, { once: true });
        fields.editSessionChairEmail.addEventListener('input', () => { fields.editSessionChairId.value = ''; }, { once: true });
        fields.editSessionRapporteur.addEventListener('input', () => { fields.editSessionRapporteurId.value = ''; }, { once: true });
        fields.editSessionRapporteurEmail.addEventListener('input', () => { fields.editSessionRapporteurId.value = ''; }, { once: true });
        fields.editPanelists.value = session.panelists || '';
        fields.editDescription.value = session.description || '';
        updateEditSessionTypeFields();
        fields.editSessionModal.classList.remove('hidden');
    } catch (error) {
        if (typeof window.showNotificationModal === 'function') {
            window.showNotificationModal('Error', error.message, 'error');
        } else {
            alert(error.message);
        }
    }
}

// deleteSession function is handled by main builder.blade.php

// Make functions globally accessible
if (typeof window.removeFromSession === 'undefined') {
    window.removeFromSession = removeFromSession;
}
if (typeof window.editSession === 'undefined') {
    window.editSession = editSession;
}
// deleteSession is handled by main builder.blade.php
if (typeof window.dragWithinSession === 'undefined') {
    window.dragWithinSession = dragWithinSession;
}
if (typeof window.allowDropWithinSession === 'undefined') {
    window.allowDropWithinSession = allowDropWithinSession;
}
if (typeof window.dropWithinSession === 'undefined') {
    window.dropWithinSession = dropWithinSession;
}
if (typeof window.addAbstractToSidebar === 'undefined') {
    window.addAbstractToSidebar = addAbstractToSidebar;
}
</script>
@endonce
