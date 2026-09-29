@extends('layouts.app')

@section('title', 'Conference Program Builder')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-700 shadow-lg">
        <div class="max-w-[98%] mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">🎯 Conference Program Builder</h1>
                    <p class="text-purple-100">Drag & drop abstracts to create your conference schedule</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="saveProgram()" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium shadow-lg transition-colors">
                        💾 Save Program
                    </button>

                    <button onclick="notifyAllLeads()" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow-lg transition-colors flex items-center gap-2">
                        ✉️ Notify All Leads
                    </button>
                    
                    <!-- PDF Exports Dropdown -->
                    <div class="relative inline-block text-left" x-data="{ open: false }">
                        <button @click="open = !open" type="button" class="px-4 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm transition-colors flex items-center gap-2">
                            📄 PDF Exports
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" 
                             class="absolute right-0 mt-2 w-56 rounded-xl shadow-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 z-[70] overflow-hidden"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100">
                            <div class="py-1">
                                <a href="{{ route('admin.conference-program.export-pdf') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors border-b border-gray-100 dark:border-gray-700">
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <span>Conference Program</span>
                                </a>
                                <a href="{{ route('admin.conference-program.generate-abstract-book') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-emerald-900/40 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    <span>Abstract Book</span>
                                </a>
                                <a href="{{ route('admin.presentations.download-all', ['mode' => 'all']) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-emerald-900/40 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <span>All Presentations ZIP</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('admin.conference-program.view') }}" class="px-4 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm transition-colors flex items-center gap-2">
                        👁️ View
                    </a>
                    <a href="{{ route('admin.conference-program.index') }}" class="px-4 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm transition-colors">
                        ← Exit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-[98%] mx-auto px-4 py-6">
        <div class="grid grid-cols-12 gap-6">
            
            <!-- LEFT PANEL: Available Abstracts -->
            <div class="col-span-3">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 sticky top-6">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">📋 Available Abstracts</h2>
                            <button onclick="showQuickAddGuestModal()" class="px-3 py-1.5 text-xs bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-lg font-medium transition-colors shadow-sm" title="Add Distinguished Guest Presentation">
                                ⭐ Quick Add Guest
                            </button>
                        </div>
                        
                        <!-- Filters -->
                        <div class="space-y-2">
                            <select id="typeFilter" onchange="filterAbstracts()" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                                <option value="">All Types</option>
                                <option value="Oral">🎤 Oral Presentations</option>
                                <option value="Poster">📋 Poster Presentations</option>
                            </select>
                            
                            <select id="subthemeFilter" onchange="filterAbstracts()" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                                <option value="">All Subthemes</option>
                                @foreach($subthemes as $subtheme)
                                    <option value="{{ $subtheme }}">{{ $subtheme }}</option>
                                @endforeach
                            </select>
                            
                            <input type="text" id="searchAbstracts" onkeyup="filterAbstracts()" placeholder="🔍 Search..." 
                                   class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                        </div>
                        
                        <div class="mt-3 text-xs text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Showing:</span> <span id="totalCount">{{ $abstracts->count() }}</span> abstracts
                            <span class="ml-1 text-gray-400">(paid only)</span>
                        </div>
                        <div class="mt-1.5 flex gap-2 text-xs flex-wrap">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">Paid</span>
                        </div>
                    </div>
                    
                    <!-- Abstracts List -->
                    <div class="p-3 space-y-2 overflow-y-auto" style="max-height: calc(100vh - 320px);" id="abstractsList">
                        @foreach($abstracts as $abstract)
                        <div class="abstract-card p-3 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-700 dark:to-gray-750 rounded-lg border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md transition-all"
                             draggable="true"
                             data-id="{{ $abstract->id }}"
                             data-code="{{ $abstract->conference_code }}"
                             data-type="{{ $abstract->presentation_mode }}"
                             data-subtheme="{{ $abstract->subtheme }}"
                             data-title="{{ strtolower($abstract->title) }}"
                             ondragstart="drag(event)">

                            <div class="flex items-start gap-2">
                                <div class="flex-shrink-0 w-6 h-6 bg-purple-100 dark:bg-purple-900/30 rounded flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <div class="font-mono text-xs font-bold text-purple-600 dark:text-purple-400">
                                            {{ $abstract->conference_code }}
                                        </div>
                                        @if($abstract->is_invited)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gradient-to-r from-orange-500 to-red-500 text-white" title="Invited/Guest Presentation">
                                            ⭐ Invited
                                        </span>
                                        @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300" title="Paid Participant">
                                            Paid
                                        </span>
                                        @endif
                                    </div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 mb-1">
                                        {{ $abstract->title }}
                                    </div>
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                            {{ $abstract->presentation_mode === 'Oral' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' }}">
                                            {{ $abstract->presentation_mode }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ Str::limit(\App\Support\TitleFormatter::personName($abstract->author_name), 15) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: Program Schedule -->
            <div class="col-span-9">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    
                    <!-- Create Session Button -->
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">📅 Conference Schedule</h2>
                        <div class="flex gap-2">
                            <button onclick="showCreateSessionModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                                ➕ Create Session
                            </button>
                            <button onclick="fixSessionCapacities()" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors">
                                🔧 Fix Capacities
                            </button>
                        </div>
                    </div>

                    <!-- Sessions Container -->
                    <div id="sessionsContainer" class="space-y-4">
                        @if($sessions->count() === 0)
                        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-lg font-medium mb-2">No sessions created yet</p>
                            <p class="text-sm">Click "Create Session" to start building your conference program</p>
                        </div>
                        @else
                            @php $currentDay = null; @endphp
                            @foreach($sessions as $session)
                                @php
                                    $days = is_array($session->schedule_days)
                                        ? $session->schedule_days
                                        : json_decode((string) $session->schedule_days, true);
                                    $sessionDay = !empty($days) ? $days[0] : null;
                                    $dayLabel = $sessionDay
                                        ? \Carbon\Carbon::parse($sessionDay)->format('l, F j, Y')
                                        : 'Unscheduled';
                                @endphp
                                @if($sessionDay !== $currentDay)
                                    @php $currentDay = $sessionDay; @endphp
                                    <div class="flex items-center gap-4 mt-6 mb-2 first:mt-0">
                                        <div class="flex-1 h-px bg-indigo-200 dark:bg-indigo-800"></div>
                                        <div class="flex items-center gap-2 px-4 py-1.5 bg-indigo-600 dark:bg-indigo-700 text-white rounded-full text-sm font-bold whitespace-nowrap shadow">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ $dayLabel }}
                                        </div>
                                        <div class="flex-1 h-px bg-indigo-200 dark:bg-indigo-800"></div>
                                    </div>
                                @endif
                                @include('admin.conference-program.partials.session-card', ['session' => $session])
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Session Modal -->
<div id="createSessionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create New Session</h3>
            
            <form id="createSessionForm" class="space-y-4">
                @csrf
                
                <!-- Session Type Selection -->
                <div class="col-span-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Session Type *</label>
                    <select name="session_type" id="sessionType" required onchange="updateSessionTypeFields()"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg font-medium">
                        <optgroup label="📊 SESSIONS WITH ABSTRACTS">
                            <option value="presentation">📊 Parallel Presentation Session</option>
                            <option value="poster">Poster Viewing Session</option>
                        </optgroup>
                        <optgroup label="🎤 KEYNOTE & DISCUSSIONS">
                            <option value="plenary">🎤 Plenary Session (Keynote)</option>
                            <option value="panel">💬 Panel Discussion</option>
                            <option value="discussion">🗨️ Discussion Period</option>
                        </optgroup>
                        <optgroup label="☕ BREAKS & MEALS">
                            <option value="break">☕ Tea/Coffee Break</option>
                            <option value="lunch">🍽️ Lunch Break</option>
                        </optgroup>
                        <optgroup label="🎉 CEREMONIES & EVENTS">
                            <option value="opening">🎉 Opening Ceremony</option>
                            <option value="closing">🏁 Closing Ceremony</option>
                            <option value="networking">🤝 Networking/Reception Event</option>
                            <option value="meeting">📅 Meeting (AGM, etc.)</option>
                        </optgroup>
                        <optgroup label="📌 OTHER">
                            <option value="other">📌 Other Event</option>
                        </optgroup>
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        💡 <strong>Presentation</strong> and <strong>Poster</strong> sessions can accept abstracts. Others are standalone events.
                    </p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Session Name *</label>
                        <input type="text" name="name" required
                               placeholder="e.g., Morning Session - AI & Machine Learning"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>
                    
                    <!-- Presentation Type (Only for presentation sessions) -->
                    <div id="presentationTypeField">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presentation Type *</label>
                        <select name="presentation_type" id="presentationType"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                            <option value="oral">🎤 Oral</option>
                            <option value="poster">📋 Poster</option>
                        </select>
                    </div>
                    
                    <!-- Speaker/Presenter Field (for non-presentation sessions) -->
                    <div id="speakerField" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Speaker/Presenter</label>
                        <input type="text" name="speaker"
                               placeholder="e.g., Prof. John Doe"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <div id="panelistsField" class="col-span-2 hidden">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Panelists / Speakers and Identities</label>
                        <textarea name="panelists" rows="5"
                                  placeholder="One panelist or identity per line, e.g.:&#10;Moderator: Dr. Jane Doe (MoH)&#10;Prof. John Smith (MUHAS)&#10;TMDA representative"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"></textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">These lines appear in the third column of the printed programme.</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date *</label>
                        <input type="date" name="date" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time *</label>
                        <input type="time" name="start_time" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time *</label>
                        <input type="time" name="end_time" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Room/Location</label>
                        <input type="text" name="room_location"
                               placeholder="e.g., Main Hall, Conference Room A"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <!-- Description (for non-presentation sessions) -->
                    <div id="descriptionField" class="col-span-2 hidden">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea name="description" rows="2"
                                  placeholder="Brief description of the event..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"></textarea>
                    </div>
                    
                    <div id="chairField">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Session Chair</label>
                        <select name="session_chair_id" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                            <option value="">Select Session Chair</option>
                            @foreach($potentialChairs as $chair)
                                <option value="{{ $chair->id }}">{{ $chair->first_name }} {{ $chair->last_name }} ({{ $chair->email }})</option>
                            @endforeach
                        </select>
                        <input type="text" name="session_chair_name" 
                               placeholder="Or enter custom name"
                               class="w-full px-3 py-2 mt-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>
                    
                    <div id="rapporteurField">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rapporteur</label>
                        <select name="session_rapporteur_id" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                            <option value="">Select Rapporteur</option>
                            @foreach($potentialChairs as $rapporteur)
                                <option value="{{ $rapporteur->id }}">{{ $rapporteur->first_name }} {{ $rapporteur->last_name }} ({{ $rapporteur->email }})</option>
                            @endforeach
                        </select>
                        <input type="text" name="session_rapporteur_name" 
                               placeholder="Or enter custom name"
                               class="w-full px-3 py-2 mt-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeCreateSessionModal()" 
                            class="flex-1 px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg font-medium transition-colors shadow-lg">
                        ➕ Create Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Edit Session Modal -->
<div id="editSessionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit Session</h3>

            <form id="editSessionForm" class="space-y-4">
                @csrf
                <input type="hidden" name="session_id" id="editSessionId">
                <input type="hidden" name="session_chair_id" id="editSessionChairId">
                <input type="hidden" name="session_rapporteur_id" id="editSessionRapporteurId">

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Session Name *</label>
                        <input type="text" name="name" id="editSessionName" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Session Type *</label>
                        <select name="session_type" id="editSessionType" required onchange="updateEditSessionTypeFields()"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                            @foreach(['presentation', 'poster', 'plenary', 'panel', 'discussion', 'break', 'lunch', 'opening', 'closing', 'networking', 'meeting', 'other'] as $type)
                                <option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="editPresentationTypeField">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presentation Type</label>
                        <select name="presentation_type" id="editPresentationType"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                            <option value="oral">Oral</option>
                            <option value="poster">Poster</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date *</label>
                        <input type="date" name="date" id="editSessionDate" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time *</label>
                        <input type="time" name="start_time" id="editStartTime" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time *</label>
                        <input type="time" name="end_time" id="editEndTime" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Room/Location</label>
                        <input type="text" name="room_location" id="editRoomLocation"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Capacity</label>
                        <input type="number" name="max_abstracts" id="editMaxAbstracts" min="0"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Capacity is auto-calculated from session duration ÷ minutes per talk.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select name="status" id="editStatus"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                            @foreach(['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chair</label>
                        <input type="text" name="session_chair" id="editSessionChair"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chair Email</label>
                        <input type="email" name="session_chair_email" id="editSessionChairEmail"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rapporteur</label>
                        <input type="text" name="session_rapporteur" id="editSessionRapporteur"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rapporteur Email</label>
                        <input type="email" name="session_rapporteur_email" id="editSessionRapporteurEmail"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Panelists / Speakers and Identities</label>
                        <textarea name="panelists" id="editPanelists" rows="5"
                                  placeholder="One panelist or identity per line, e.g.:&#10;Moderator: Dr. Jane Doe (MoH)&#10;Prof. John Smith (MUHAS)&#10;TMDA representative"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Enter each speaker/panelist or identity line separately. Shown as the third column in the printed programme.</p>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea name="description" id="editDescription" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"></textarea>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeEditSessionModal()"
                            class="flex-1 px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Professional Notification Modal -->
<div id="notificationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4" onclick="closeNotificationModal()">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full" onclick="event.stopPropagation()">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div id="notificationIcon" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center mr-4">
                    <!-- Icon will be inserted here -->
                </div>
                <h3 id="notificationTitle" class="text-xl font-bold text-gray-900 dark:text-white"></h3>
            </div>
            <p id="notificationMessage" class="text-gray-600 dark:text-gray-300 mb-6"></p>
            <div class="flex justify-end gap-3">
                <button id="notificationOkBtn" onclick="closeNotificationModal()" 
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-medium transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 id="confirmationTitle" class="text-xl font-bold text-gray-900 dark:text-white">Confirm Action</h3>
            </div>
            <p id="confirmationMessage" class="text-gray-600 dark:text-gray-300 mb-6"></p>
            <div class="flex gap-3 justify-end">
                <button onclick="closeConfirmationModal()" 
                        class="px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button id="confirmActionBtn" 
                        class="px-6 py-2.5 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-lg font-medium transition-colors">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Guest Presentation Modal -->
<div id="quickAddGuestModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">⭐ Quick Add Guest Presentation</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Add a distinguished guest or invited speaker who doesn't need to go through the normal submission process.</p>
            
            <form id="quickAddGuestForm" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presenter Name *</label>
                        <input type="text" name="author_name" required
                               placeholder="e.g., Dr. Jane Smith"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Institution/Affiliation</label>
                        <input type="text" name="author_institute"
                               placeholder="e.g., Harvard University"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presentation Title *</label>
                    <input type="text" name="title" required
                           placeholder="e.g., Keynote: Future of Medical Research"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description/Abstract</label>
                    <textarea name="description" rows="3"
                              placeholder="Brief description of the presentation..."
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presentation Mode *</label>
                        <select name="presentation_mode" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                            <option value="Oral">Oral Presentation</option>
                            <option value="Poster">Poster Presentation</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Conference Code *</label>
                        <input type="text" name="conference_code" required
                               placeholder="e.g., INV-001"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg font-mono">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Unique code for this presentation</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subtheme</label>
                    <select name="subtheme"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                        <option value="">General</option>
                        @foreach($subthemes as $subtheme)
                            <option value="{{ $subtheme }}">{{ $subtheme }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign to Session (Optional)</label>
                    <select name="session_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                        <option value="">Leave unassigned for now</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }} ({{ $session->current_abstracts }}/{{ $session->max_abstracts }})</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="committee_selected" id="committee_selected" value="1"
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="committee_selected" class="text-sm text-gray-700 dark:text-gray-300">Mark as Committee Selected (highlighted)</label>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeQuickAddGuestModal()" 
                            class="flex-1 px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2.5 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-lg font-medium transition-colors">
                        ⭐ Add Guest Presentation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize Sortable for each session container
    document.querySelectorAll('.session-abstracts-container').forEach(container => {
        new Sortable(container, {
            group: 'abstracts', // Allow dragging between sessions if needed, or just reordering
            animation: 150,
            ghostClass: 'bg-indigo-100',
            onEnd: function (evt) {
                const sessionId = evt.to.dataset.sessionId;
                const abstractIds = Array.from(evt.to.children).map(el => el.dataset.id);
                
                // Call reorder API
                fetch('{{ route('admin.conference-program.reorder-session') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        abstract_ids: abstractIds
                    })
                });
            }
        });
    });

// Drag and Drop Functions
function drag(ev) {
    ev.dataTransfer.setData("abstractId", ev.target.dataset.id);
    ev.dataTransfer.setData("abstractCode", ev.target.dataset.code);
    ev.target.style.opacity = "0.5";
}

function allowDrop(ev) {
    ev.preventDefault();
}

function drop(ev, sessionId) {
    ev.preventDefault();
    const abstractId = ev.dataTransfer.getData("abstractId");
    
    if (!abstractId) return;
    
    // Check for conflicts before adding
    checkConflicts(abstractId, sessionId).then(hasConflict => {
        if (hasConflict) {
            // If user confirms (handled in checkConflicts), add it
            // For now, checkConflicts handles the confirmation UI
        } else {
            addAbstractToSession(abstractId, sessionId);
        }
    });
}

// Check conflicts
function checkConflicts(abstractId, sessionId) {
    return fetch('{{ route('admin.conference-program.check-conflicts') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            abstract_id: abstractId,
            session_id: sessionId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.has_conflicts) {
            // Show conflict modal
            const conflictsList = data.conflicts.map(c => `<li>⚠️ ${c.message}</li>`).join('');
            
            document.getElementById('confirmationTitle').textContent = 'Scheduling Conflict Detected';
            document.getElementById('confirmationMessage').innerHTML = `
                <div class="mb-4 text-red-600 font-medium">This author has potential conflicts:</div>
                <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700 dark:text-gray-300 mb-4">${conflictsList}</ul>
                <p>Do you want to proceed anyway?</p>
            `;
            
            const modal = document.getElementById('confirmationModal');
            modal.classList.remove('hidden');
            
            return new Promise(resolve => {
                document.getElementById('confirmActionBtn').onclick = function() {
                    modal.classList.add('hidden');
                    addAbstractToSession(abstractId, sessionId);
                    resolve(true);
                };
                
                document.querySelector('#confirmationModal button:first-child').onclick = function() {
                    modal.classList.add('hidden');
                    // Reset opacity
                    const card = document.querySelector(`.abstract-card[data-id="${abstractId}"]`);
                    if(card) card.style.opacity = '1';
                    resolve(true); // Resolved but not added
                };
            });
        }
        return false;
    });
}

// ... rest of existing scripts ...


// Make functions globally accessible
window.drag = drag;
window.allowDrop = allowDrop;
window.drop = drop;

// Filter abstracts
function filterAbstracts() {
    const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
    const subthemeFilter = document.getElementById('subthemeFilter').value.toLowerCase();
    const searchTerm = document.getElementById('searchAbstracts').value.toLowerCase();
    
    const cards = document.querySelectorAll('.abstract-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const type = card.dataset.type.toLowerCase();
        const subtheme = card.dataset.subtheme.toLowerCase();
        const title = card.dataset.title;
        
        const matchesType = !typeFilter || type === typeFilter;
        const matchesSubtheme = !subthemeFilter || subtheme === subthemeFilter;
        const matchesSearch = !searchTerm || title.includes(searchTerm);
        
        if (matchesType && matchesSubtheme && matchesSearch) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    document.getElementById('totalCount').textContent = visibleCount;
}

window.filterAbstracts = filterAbstracts;

// Session Modal Functions
function showCreateSessionModal() {
    document.getElementById('createSessionModal').classList.remove('hidden');
}

function closeCreateSessionModal() {
    document.getElementById('createSessionModal').classList.add('hidden');
    document.getElementById('createSessionForm').reset();
}

// Update form fields based on session type
function updateSessionTypeFields() {
    const sessionType = document.getElementById('sessionType').value;
    const presentationTypeField = document.getElementById('presentationTypeField');
    const presentationType = document.getElementById('presentationType');
    const speakerField = document.getElementById('speakerField');
    const panelistsField = document.getElementById('panelistsField');
    const descriptionField = document.getElementById('descriptionField');
    const chairField = document.getElementById('chairField');
    const rapporteurField = document.getElementById('rapporteurField');
    
    // Reset all fields first
    presentationTypeField.classList.add('hidden');
    presentationType.required = false;
    speakerField.classList.add('hidden');
    panelistsField.classList.add('hidden');
    descriptionField.classList.add('hidden');
    chairField.classList.add('hidden');
    rapporteurField.classList.add('hidden');
    
    // Session types that accept abstracts
    if (sessionType === 'presentation' || sessionType === 'poster') {
        presentationTypeField.classList.remove('hidden');
        presentationType.required = true;
        if (sessionType === 'poster') {
            presentationType.value = 'poster';
        }
        chairField.classList.remove('hidden');
        rapporteurField.classList.remove('hidden');
    }
    // Plenary, Panel, Discussion - need speakers, chair, and rapporteur
    else if (sessionType === 'plenary' || sessionType === 'panel' || sessionType === 'discussion') {
        speakerField.classList.remove('hidden');
        panelistsField.classList.remove('hidden');
        descriptionField.classList.remove('hidden');
        chairField.classList.remove('hidden');
        rapporteurField.classList.remove('hidden');
    }
    // Breaks and Lunch - minimal fields
    else if (sessionType === 'break' || sessionType === 'lunch') {
        descriptionField.classList.remove('hidden');
    }
    // Ceremonies and networking - speaker and description
    else if (sessionType === 'opening' || sessionType === 'closing' || sessionType === 'networking' || sessionType === 'meeting') {
        speakerField.classList.remove('hidden');
        panelistsField.classList.remove('hidden');
        descriptionField.classList.remove('hidden');
    }
    // Other - show all
    else {
        speakerField.classList.remove('hidden');
        panelistsField.classList.remove('hidden');
        descriptionField.classList.remove('hidden');
        chairField.classList.remove('hidden');
    }
}

window.showCreateSessionModal = showCreateSessionModal;
window.closeCreateSessionModal = closeCreateSessionModal;
window.updateSessionTypeFields = updateSessionTypeFields;

function closeEditSessionModal() {
    document.getElementById('editSessionModal').classList.add('hidden');
    document.getElementById('editSessionForm').reset();
}

function updateEditSessionTypeFields() {
    const sessionType = document.getElementById('editSessionType').value;
    const presentationTypeField = document.getElementById('editPresentationTypeField');
    const presentationType = document.getElementById('editPresentationType');
    const capacityInput = document.getElementById('editMaxAbstracts');
    const acceptsAbstracts = sessionType === 'presentation' || sessionType === 'poster';

    presentationTypeField.classList.toggle('hidden', !acceptsAbstracts);
    presentationType.required = acceptsAbstracts;
    if (sessionType === 'poster') {
        presentationType.value = 'poster';
    }
    capacityInput.disabled = !acceptsAbstracts || (sessionType === 'presentation' && presentationType.value === 'oral');

    if (!acceptsAbstracts) {
        capacityInput.value = 0;
    }
}

document.getElementById('editPresentationType').addEventListener('change', updateEditSessionTypeFields);

document.getElementById('editSessionForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const sessionId = document.getElementById('editSessionId').value;
    const formData = new FormData(this);
    if (document.getElementById('editMaxAbstracts').disabled) {
        formData.delete('max_abstracts');
    }
    const data = Object.fromEntries(formData);

    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Saving...';

    fetch(`/admin/conference-program/session/${sessionId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw err; });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotificationModal('Success', 'Session updated successfully.', 'success');
            closeEditSessionModal();
            setTimeout(() => window.location.reload(), 700);
        } else {
            throw new Error(data.message || 'Failed to update session');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        const message = error.errors ? Object.values(error.errors).flat().join(', ') : (error.message || 'An error occurred while updating session');
        showNotificationModal('Error', message, 'error');
    });
});

window.closeEditSessionModal = closeEditSessionModal;
window.updateEditSessionTypeFields = updateEditSessionTypeFields;

// Quick Add Guest Modal Functions
function showQuickAddGuestModal() {
    document.getElementById('quickAddGuestModal').classList.remove('hidden');
}

function closeQuickAddGuestModal() {
    document.getElementById('quickAddGuestModal').classList.add('hidden');
    document.getElementById('quickAddGuestForm').reset();
}

window.showQuickAddGuestModal = showQuickAddGuestModal;
window.closeQuickAddGuestModal = closeQuickAddGuestModal;

// Create Session
document.getElementById('createSessionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Creating...';
    
    fetch('{{ route('admin.conference-program.create-session') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw err;
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotificationModal('Success', 'Session created successfully!', 'success');
            closeCreateSessionModal();
            // Reload page to show new session
            setTimeout(() => window.location.reload(), 1000);
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            showNotificationModal('Error', data.message || 'Failed to create session', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        // Handle validation errors
        if (error.errors) {
            const errorMessages = Object.values(error.errors).flat().join(', ');
            showNotificationModal('Validation Error', errorMessages, 'error');
        } else if (error.message) {
            showNotificationModal('Error', error.message, 'error');
        } else {
            showNotificationModal('Error', 'An error occurred while creating session', 'error');
        }
    });
});

// Quick Add Guest Presentation Form
document.getElementById('quickAddGuestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Disable button during submission
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Adding...';
    
    fetch('{{ route("admin.conference-program.quick-add-guest") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotificationModal('Success', data.message || 'Invited presentation added successfully!', 'success');
            closeQuickAddGuestModal();
            // Reload page to show new presentation
            setTimeout(() => window.location.reload(), 1000);
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            showNotificationModal('Error', data.message || 'Failed to add guest presentation', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        if (error.errors) {
            const errorMessages = Object.values(error.errors).flat().join(', ');
            showNotificationModal('Validation Error', errorMessages, 'error');
        } else {
            showNotificationModal('Error', 'An error occurred while adding guest presentation', 'error');
        }
    });
});

// Add abstract to session
function addAbstractToSession(abstractId, sessionId) {
    // Optimistically update the UI first for instant feedback
    const abstractCard = document.querySelector(`[data-id="${abstractId}"]`);
    const sessionCard = document.querySelector(`[data-session-id="${sessionId}"]`);
    
    if (abstractCard && sessionCard) {
        // Hide the abstract card immediately
        abstractCard.style.opacity = '0.3';
        abstractCard.style.transform = 'scale(0.95)';
        abstractCard.style.transition = 'all 0.2s ease';
        
        // Update session capacity display optimistically
        const capacityDisplay = sessionCard.querySelector('.capacity-info');
        if (capacityDisplay) {
            const currentText = capacityDisplay.textContent;
            const match = currentText.match(/(\d+)\/(\d+)/);
            if (match) {
                const current = parseInt(match[1]) + 1;
                const max = parseInt(match[2]);
                capacityDisplay.innerHTML = `<span class="text-blue-600 font-medium">${current}/${max}</span> abstracts`;
            }
        }
    }
    
    fetch('{{ route('admin.conference-program.add-to-session') }}', {
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
            // If we moved a poster that was already assigned to another session,
            // reload so both the old and new session cards update correctly.
            const wasAssigned = abstractCard && abstractCard.dataset.currentSessionId;
            if (wasAssigned) {
                showSubtleNotification('✅ Poster moved — reloading…', 'success');
                setTimeout(() => window.location.reload(), 800);
                return;
            }

            // Success - remove abstract from available list smoothly
            if (abstractCard) {
                abstractCard.style.transition = 'all 0.3s ease';
                abstractCard.style.transform = 'translateX(100%)';
                abstractCard.style.opacity = '0';
                setTimeout(() => abstractCard.remove(), 300);
            }

            // Add abstract to session display
            addAbstractToSessionDisplay(data.abstract, sessionId);

            // Show subtle success feedback (no modal)
            showSubtleNotification('✅ Abstract added to session', 'success');
        } else {
            // Error - revert optimistic changes
            if (abstractCard) {
                abstractCard.style.opacity = '1';
                abstractCard.style.transform = 'scale(1)';
            }
            
            // Show error notification
            showNotificationModal('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Revert optimistic changes on error
        if (abstractCard) {
            abstractCard.style.opacity = '1';
            abstractCard.style.transform = 'scale(1)';
        }
        
        showNotificationModal('Error', 'An error occurred', 'error');
    });
}

// Add abstract to session display
function addAbstractToSessionDisplay(abstract, sessionId) {
    const sessionCard = document.querySelector(`[data-session-id="${sessionId}"]`);
    if (!sessionCard) return;
    
    const abstractsContainer = sessionCard.querySelector(`#abstracts-session-${sessionId}`);
    const emptyState = sessionCard.querySelector('.text-center.py-8.border-2.border-dashed');
    
    // Remove empty state if it exists
    if (emptyState) {
        emptyState.remove();
    }
    
    // Create abstracts container if it doesn't exist
    if (!abstractsContainer) {
        const sortableContainer = sessionCard.querySelector('.sortable-abstracts');
        const newContainer = document.createElement('div');
        newContainer.className = 'space-y-2';
        newContainer.id = `abstracts-session-${sessionId}`;
        sortableContainer.appendChild(newContainer);
        newContainer.appendChild(createAbstractElement(abstract, sessionId, 1));
    } else {
        // Get current count for order number
        const currentCount = abstractsContainer.children.length;
        abstractsContainer.appendChild(createAbstractElement(abstract, sessionId, currentCount + 1));
    }
}

// Create abstract element for session display
function createAbstractElement(abstract, sessionId, orderNumber) {
    const div = document.createElement('div');
    div.className = 'abstract-in-session flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 cursor-move transition-all';
    div.setAttribute('draggable', 'true');
    div.setAttribute('ondragstart', 'dragWithinSession(event)');
    div.setAttribute('ondragover', 'allowDropWithinSession(event)');
    div.setAttribute('ondrop', 'dropWithinSession(event)');
    div.setAttribute('data-abstract-id', abstract.id);
    div.setAttribute('data-session-id', sessionId);
    
    // Calculate time based on order using session's configured minutes-per-talk.
    // Poster sessions show no per-abstract time — only the session-level time range.
    const sessionCard = document.querySelector(`[data-session-id="${sessionId}"]`);
    let timeDisplay = '';
    let timeInput = '';
    let durationInput = '';

    if (sessionCard) {
        const isPoster = sessionCard.dataset.sessionType === 'poster';
        const minutesPerTalk = parseInt(sessionCard.dataset.minutesPerTalk || '10', 10);

        if (!isPoster) {
            // Oral: calculate per-abstract start time from session start
            let startTime = sessionCard.dataset.startTime || '';
            if (!startTime) {
                const sessionTimeElement = sessionCard.querySelector('.text-gray-600.dark\\:text-gray-400');
                if (sessionTimeElement && sessionTimeElement.textContent.includes(':')) {
                    const timeMatch = sessionTimeElement.textContent.match(/(\d+):(\d+)/);
                    if (timeMatch) {
                        startTime = `${timeMatch[1].padStart(2, '0')}:${timeMatch[2]}`;
                    }
                }
            }
            if (!startTime) {
                const existingAbstracts = sessionCard.querySelectorAll('.abstract-in-session');
                if (existingAbstracts.length > 0) {
                    const firstTimeDisplay = existingAbstracts[0].querySelector('.time-display');
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
            }
            if (!startTime) startTime = '09:00';

            const [startHours, startMinutes] = startTime.split(':').map(Number);
            const totalMinutes = startHours * 60 + startMinutes + ((orderNumber - 1) * minutesPerTalk);
            const abstractHours = Math.floor(totalMinutes / 60);
            const abstractMinutes = totalMinutes % 60;
            const displayHours = abstractHours % 12 || 12;
            const ampm = abstractHours >= 12 ? 'PM' : 'AM';
            const timeString = `${displayHours}:${String(abstractMinutes).padStart(2, '0')} ${ampm}`;
            const timeInputValue = `${String(abstractHours).padStart(2, '0')}:${String(abstractMinutes).padStart(2, '0')}`;

            timeDisplay = `
                <div class="flex-shrink-0">
                    <div class="time-display text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors"
                         onclick="editTime(this, ${abstract.id}, ${sessionId})"
                         title="Click to edit time">
                        ${timeString}
                    </div>
                    <input type="time"
                           class="time-input hidden w-20 text-xs px-2 py-1 border border-indigo-300 dark:border-indigo-600 rounded focus:ring-2 focus:ring-indigo-500"
                           value="${timeInputValue}"
                           onblur="saveTime(this, ${abstract.id}, ${sessionId})"
                           onkeydown="if(event.key === 'Enter') this.blur()">
                    <div class="duration-input flex items-center gap-1 mt-0.5">
                        <input type="number"
                               class="w-12 text-[10px] text-center border border-gray-300 dark:border-gray-600 rounded px-1 focus:ring-1 focus:ring-indigo-500"
                               value="${minutesPerTalk}"
                               min="5"
                               max="60"
                               step="5"
                               onchange="updateDuration(this, ${abstract.id}, ${sessionId})"
                               title="Duration in minutes">
                        <span class="text-[10px] text-gray-500 dark:text-gray-400">min</span>
                    </div>
                </div>
            `;
        }
        // Poster: no per-abstract time — the session banner already shows the time range
    }
    
    div.innerHTML = `
        <!-- Drag Handle -->
        <div class="drag-handle text-gray-400 dark:text-gray-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
            </svg>
        </div>
        
        ${timeDisplay}
        
        <!-- Order Number -->
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center text-sm font-bold">
            ${orderNumber}
        </div>
        
        <!-- Code -->
        <div class="font-mono text-sm font-bold text-blue-600 dark:text-blue-400 flex-shrink-0">
            ${abstract.conference_code || 'N/A'}
        </div>
        
        <!-- Title -->
        <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                ${abstract.title || 'No Title'}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                ${abstract.author_name || 'Unknown Author'}
            </div>
        </div>
        
        <!-- Subtheme Badge -->
        <div class="flex-shrink-0">
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                ${abstract.subtheme || 'General'}
            </span>
        </div>
        
        <!-- Remove Button -->
        <button onclick="removeFromSession(${abstract.id}, ${sessionId})" 
                class="flex-shrink-0 p-1.5 text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;
    
    return div;
}

window.addAbstractToSession = addAbstractToSession;
window.addAbstractToSessionDisplay = addAbstractToSessionDisplay;

// Show notification
function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 3000);
}

// Show subtle notification (non-intrusive)
function showSubtleNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-100 text-green-800 border border-green-200',
        error: 'bg-red-100 text-red-800 border border-red-200',
        warning: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
        info: 'bg-blue-100 text-blue-800 border border-blue-200'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed top-20 right-4 z-40 px-4 py-2 rounded-lg shadow-md transition-all duration-300 transform translate-x-full text-sm font-medium ${colors[type]}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 2 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 2000);
}

window.showNotification = showNotification;
window.showSubtleNotification = showSubtleNotification;

// Reset drag opacity
document.addEventListener('dragend', function(e) {
    if (e.target.classList.contains('abstract-card')) {
        e.target.style.opacity = "1";
    }
});


// Professional Modal Functions
function showNotificationModal(title, message, type = 'info') {
    const modal = document.getElementById('notificationModal');
    const icon = document.getElementById('notificationIcon');
    const titleEl = document.getElementById('notificationTitle');
    const messageEl = document.getElementById('notificationMessage');
    
    // Set content
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    // Set icon and colors based on type
    icon.className = 'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center mr-4';
    
    switch(type) {
        case 'success':
            icon.classList.add('bg-green-100', 'dark:bg-green-900/30');
            icon.innerHTML = '<svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            break;
        case 'error':
            icon.classList.add('bg-red-100', 'dark:bg-red-900/30');
            icon.innerHTML = '<svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
            break;
        case 'warning':
            icon.classList.add('bg-yellow-100', 'dark:bg-yellow-900/30');
            icon.innerHTML = '<svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
            break;
        default: // info
            icon.classList.add('bg-blue-100', 'dark:bg-blue-900/30');
            icon.innerHTML = '<svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeNotificationModal() {
    try {
        const modal = document.getElementById('notificationModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        } else {
            console.error('Notification modal not found');
        }
    } catch (error) {
        console.error('Error closing notification modal:', error);
    }
}

// Ensure modal close functionality works - add event listener as backup
document.addEventListener('DOMContentLoaded', function() {
    const okBtn = document.getElementById('notificationOkBtn');
    if (okBtn) {
        okBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeNotificationModal();
        });
    }
    
    // Also add escape key listener
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const notificationModal = document.getElementById('notificationModal');
            if (notificationModal && !notificationModal.classList.contains('hidden')) {
                closeNotificationModal();
            }
        }
    });
});

function showConfirmationModal(title, message, onConfirm) {
    const modal = document.getElementById('confirmationModal');
    const titleEl = document.getElementById('confirmationTitle');
    const messageEl = document.getElementById('confirmationMessage');
    const confirmBtn = document.getElementById('confirmActionBtn');
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    // Remove existing event listeners
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    // Add new event listener
    newConfirmBtn.addEventListener('click', function() {
        closeConfirmationModal();
        if (onConfirm) onConfirm();
    });
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function deleteSession(sessionId) {
    showConfirmationModal(
        'Delete Session',
        'Are you sure you want to delete this session? All abstracts in this session will be unassigned and you will need to reassign them.',
        function() {
            fetch(`/admin/conference-program/session/${sessionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Delete session response status:', response.status);
                console.log('Delete session response:', response);
                return response.json();
            })
            .then(data => {
                console.log('Delete session response data:', data);
                if (data.success) {
                    // Remove session from UI immediately
                    let sessionCard = document.querySelector(`[data-session-id="${sessionId}"]`);
                    
                    // Try alternative selectors if the main one fails
                    if (!sessionCard) {
                        sessionCard = document.querySelector(`.session-card[data-session-id="${sessionId}"]`);
                    }
                    if (!sessionCard) {
                        sessionCard = document.getElementById(`session-${sessionId}`);
                    }
                    
                    console.log('Looking for session card with ID:', sessionId);
                    console.log('Found session card:', sessionCard);
                    console.log('All session cards:', document.querySelectorAll('.session-card'));
                    
                    if (sessionCard) {
                        // Add removal animation
                        sessionCard.style.transition = 'all 0.3s ease-out';
                        sessionCard.style.opacity = '0';
                        sessionCard.style.transform = 'translateX(-100%)';
                        
                        // Remove after animation
                        setTimeout(() => {
                            sessionCard.remove();
                            console.log('Session card removed from DOM');
                        }, 300);
                        
                        showNotificationModal('Success', 'Session deleted successfully!', 'success');
                    } else {
                        console.error('Session card not found for ID:', sessionId);
                        // Still show success but refresh page as fallback
                        showNotificationModal('Success', 'Session deleted successfully! Please refresh to see changes.', 'success');
                    }
                } else {
                    showNotificationModal('Error', data.message || 'Failed to delete session', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotificationModal('Error', 'An error occurred while deleting the session', 'error');
            });
        }
    );
}


// Fix Session Capacities
function fixSessionCapacities() {
    showConfirmationModal(
        'Fix Session Capacities',
        'This will update the capacity limits for all existing sessions based on their type. This fixes the issue where sessions were created with incorrect capacity limits.',
        function() {
            fetch('{{ route('admin.conference-program.fix-capacities') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotificationModal('Success', data.message, 'success');
                    // Reload page to show updated capacities
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotificationModal('Error', data.message || 'Failed to fix session capacities', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotificationModal('Error', 'An error occurred while fixing session capacities', 'error');
            });
        }
    );
}

// Make functions globally available
window.editSession = editSession;
window.deleteSession = deleteSession;
window.fixSessionCapacities = fixSessionCapacities;
window.showNotificationModal = showNotificationModal;
window.closeNotificationModal = closeNotificationModal;
window.showConfirmationModal = showConfirmationModal;
window.closeConfirmationModal = closeConfirmationModal;

function exportProgram() {
    window.location.href = '{{ route("admin.conference-program.view") }}';
}
window.exportProgram = exportProgram;

// Notification Functions for Session Leads
function notifyAllLeads() {
    showConfirmationModal(
        'Notify All Session Leads',
        'This will send personalized assignment emails (as Chairperson or Rapporteur) and include the latest PDF Conference Program as an attachment. This should only be done once the schedule is final.',
        function() {
            fetch('{{ route('admin.conference-program.notify-all-leads') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotificationModal('Notifications Sent', data.message, 'success');
                } else {
                    showNotificationModal('Error', data.message || 'Failed to send notifications', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotificationModal('Error', 'An error occurred while sending notifications', 'error');
            });
        }
    );
}
window.notifyAllLeads = notifyAllLeads;

// Save Program — all changes are persisted instantly on each drag/drop action,
// so this button just confirms the current state and lets the user know.
function saveProgram() {
    fetch('{{ route('admin.conference-program.stats') }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        const sessions = data.total_sessions ?? '?';
        const abstracts = data.total_abstracts ?? '?';
        showNotificationModal(
            '✅ Programme Saved',
            `All changes are auto-saved. Current state: ${sessions} sessions, ${abstracts} abstracts assigned.`,
            'success'
        );
    })
    .catch(() => {
        showNotificationModal('✅ Programme Saved', 'All changes are auto-saved as you go — nothing extra needed.', 'success');
    });
}
window.saveProgram = saveProgram;

function notifySessionLeads(sessionId) {
    const url = '{{ route('admin.conference-program.notify-leads', ':id') }}'.replace(':id', sessionId);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotificationModal('Notification Sent', data.message, 'success');
            // Optionally update the UI to show notifed status
        } else {
            showNotificationModal('Error', data.message || 'Failed to send notification', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotificationModal('Error', 'An error occurred while sending notification', 'error');
    });
}
window.notifySessionLeads = notifySessionLeads;

}); // End DOMContentLoaded
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endpush
@endsection
