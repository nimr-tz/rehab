@extends('layouts.app')

@section('title', 'Session: ' . $session->name)

@section('content')
<div class="h-full flex flex-col overflow-hidden" x-data="{ activeTab: 'schedule' }">
    {{-- Top Action Bar --}}
    <div class="bg-white dark:bg-gray-800 border-b border-slate-200 dark:border-gray-700 px-8 py-4 flex flex-wrap justify-between items-center gap-4 sticky top-0 z-10">
        <div class="flex items-center gap-4">
            <a href="{{ route('sessions.my') }}" class="p-2 hover:bg-slate-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1">{{ $session->name }}</h1>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                        {{ ucfirst($session->session_type) }}
                    </span>
                    <p class="text-slate-500 dark:text-gray-400 text-xs font-medium">{{ $session->getPrimaryDayLabel() }} • {{ $session->start_time ? $session->start_time->format('g:i A') : '--' }} - {{ $session->end_time ? $session->end_time->format('g:i A') : '--' }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($isChair || auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.conference-program.download-session-presentations', $session) }}" class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-emerald-500/20 active:scale-95 text-sm group">
                <svg class="w-5 h-5 transition-transform group-hover:translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download All Presentations (ZIP)
            </a>
            @endif
            <div class="h-10 w-[1px] bg-slate-200 dark:bg-gray-700 mx-2"></div>
            <div class="bg-slate-50 dark:bg-gray-900 p-1 rounded-xl border border-slate-100 dark:border-gray-700 flex gap-1">
                <button @click="activeTab = 'schedule'" :class="activeTab === 'schedule' ? 'bg-white dark:bg-gray-800 shadow-sm text-slate-900 dark:text-white' : 'text-slate-500'" class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all">Schedule</button>
                <button @click="activeTab = 'report'" :class="activeTab === 'report' ? 'bg-white dark:bg-gray-800 shadow-sm text-slate-900 dark:text-white' : 'text-slate-500'" class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all">Session Report</button>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-auto bg-slate-50 dark:bg-gray-900 px-8 py-8">
        {{-- Schedule Tab --}}
        <div x-show="activeTab === 'schedule'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="max-w-4xl mx-auto space-y-4">
            @forelse($session->abstracts as $index => $abstract)
                @php
                    $minutes = $index * 10;
                    $time = $session->start_time ? $session->start_time->copy()->addMinutes($minutes) : null;
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-gray-700 flex flex-wrap md:flex-nowrap gap-6 items-center hover:shadow-md transition-shadow group">
                    {{-- Time & Order --}}
                    <div class="flex-shrink-0 flex flex-col items-center justify-center w-24">
                        <p class="text-xs font-black text-indigo-600 dark:text-indigo-400 mb-1 uppercase tracking-tighter">{{ $time ? $time->format('g:i A') : '--:--' }}</p>
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-gray-700 flex items-center justify-center text-lg font-black text-slate-900 dark:text-white border border-slate-100 dark:border-gray-600 shadow-sm">
                            {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                        </div>
                    </div>

                    {{-- Abstract Details --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-gray-400 font-mono text-[10px] font-bold rounded">
                                {{ $abstract->conference_code }}
                            </span>
                            @if($abstract->presentation_mode)
                                <span class="px-2 py-0.5 {{ $abstract->presentation_mode === 'Oral' ? 'bg-green-100 text-green-700' : 'bg-purple-100 text-purple-700' }} text-[10px] font-bold rounded uppercase">
                                    {{ $abstract->presentation_mode }}
                                </span>
                            @endif
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1 group-hover:text-indigo-600 transition-colors leading-tight">{{ $abstract->title }}</h3>
                        <p class="text-slate-500 dark:text-gray-400 text-sm font-medium">{{ $abstract->author_name }}</p>
                        <p class="text-slate-400 text-[10px] uppercase font-bold tracking-tight mt-1">{{ $abstract->author_institute }}</p>
                    </div>

                    {{-- Action --}}
                    <div class="flex-shrink-0 flex flex-col gap-2">
                        @php $presentationCount = 0; @endphp
                        @if($abstract->oral_presentation_file)
                            @php $presentationCount++; @endphp
                        @endif
                        @if($abstract->poster_presentation_file)
                            @php $presentationCount++; @endphp
                        @endif

                        @if($presentationCount > 0)
                            <div class="flex gap-2">
                                @if($abstract->oral_presentation_file)
                                <a href="{{ route('admin.abstracts.download-presentation', [$abstract, 'oral']) }}" class="p-3 bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 rounded-2xl transition-all shadow-sm group/btn" title="Download Presentation">
                                    <svg class="w-5 h-5 transition-transform group-hover/btn:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </a>
                                @endif
                                @if($abstract->poster_presentation_file)
                                <a href="{{ route('admin.abstracts.download-presentation', [$abstract, 'poster']) }}" class="p-3 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 rounded-2xl transition-all shadow-sm group/btn" title="Download Poster Video">
                                    <svg class="w-5 h-5 transition-transform group-hover/btn:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </a>
                                @endif
                            </div>
                        @else
                            <span class="text-[10px] font-black uppercase text-slate-400 bg-slate-100 dark:bg-gray-700 px-3 py-1 rounded-lg">No Files</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center shadow-sm border border-slate-100 dark:border-gray-700">
                    <p class="text-slate-500 font-bold">No abstracts assigned to this session yet.</p>
                </div>
            @endforelse
        </div>

        {{-- Report Tab --}}
        <div x-show="activeTab === 'report'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="max-w-4xl mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-xl border border-slate-100 dark:border-gray-700">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Session Report & Notes</h2>
                        <p class="text-slate-500 text-xs font-medium">As a rapporteur, please document the proceedings, discussion highlights, and any recommendations.</p>
                    </div>
                </div>

                <form action="{{ route('sessions.save-report', $session) }}" method="POST">
                    @csrf
                    <div class="mb-6">
                        <textarea name="rapporteur_notes" rows="15" 
                                  class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-900 border-2 border-slate-100 dark:border-gray-700 rounded-3xl focus:border-indigo-500 focus:ring-0 transition-all font-medium text-slate-700 dark:text-gray-200 placeholder-slate-400 shadow-inner"
                                  placeholder="Type your session report here... Include key discussion points, questions asked, and session highlights."
                                  {{ !$isRapporteur && !auth()->user()->hasRole('admin') ? 'disabled' : '' }}>{{ $session->rapporteur_notes }}</textarea>
                    </div>

                    @if($isRapporteur || auth()->user()->hasRole('admin'))
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <label class="text-sm font-bold text-slate-700 dark:text-gray-300">Session Status:</label>
                            <select name="status" class="bg-white dark:bg-gray-800 border-2 border-slate-100 dark:border-gray-700 rounded-xl px-4 py-2 text-sm font-bold text-slate-700 dark:text-gray-200">
                                <option value="scheduled" {{ $session->status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="ongoing" {{ $session->status === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                                <option value="completed" {{ $session->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $session->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" class="flex items-center gap-2 px-8 py-4 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl font-black uppercase tracking-widest text-xs transition-all shadow-xl active:scale-95 group">
                            Submit Report
                            <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    @else
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 rounded-2xl border border-amber-100 dark:border-amber-800 text-sm font-bold flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        View only mode. Only the assigned rapporteur or administrators can edit session notes.
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
