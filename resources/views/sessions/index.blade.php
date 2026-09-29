@extends('layouts.app')

@section('title', 'My Scientific Sessions')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    {{-- Header --}}
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-2">My Scientific Sessions</h1>
            <p class="text-slate-500 dark:text-gray-400">Manage and oversee the sessions you have been assigned to for {{ config('conference.short_name') }} {{ config('conference.year') }}.</p>
        </div>
        <div class="flex gap-3">
            <span class="px-4 py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-xl text-sm font-bold border border-indigo-100 dark:border-indigo-800">
                Total Assigned: {{ $sessions->count() }}
            </span>
        </div>
    </div>

    @if($sessions->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center shadow-xl border border-slate-100 dark:border-gray-700">
            <div class="w-20 h-20 bg-slate-50 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No sessions assigned</h3>
            <p class="text-slate-500 dark:text-gray-400 max-w-sm mx-auto">You haven't been assigned as a Chair or Rapporteur to any sessions yet. Once assigned, they will appear here.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($sessions as $session)
                @php
                    $isChair = $session->session_chair_id === auth()->id();
                    $isRapporteur = $session->session_rapporteur_id === auth()->id();
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-lg border border-slate-100 dark:border-gray-700 hover:shadow-2xl transition-all duration-300 group">
                    {{-- Card Header --}}
                    <div class="p-6 bg-gradient-to-br {{ $isChair ? 'from-indigo-600 to-purple-700' : 'from-emerald-600 to-teal-700' }} text-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                        </div>
                        
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-lg text-[10px] font-black uppercase tracking-widest border border-white/30">
                                {{ $session->session_type }}
                            </span>
                            <span class="px-3 py-1 bg-white rounded-lg text-slate-900 text-[10px] font-black uppercase tracking-widest shadow-sm">
                                {{ $isChair ? 'Chairperson' : 'Rapporteur' }}
                            </span>
                        </div>
                        
                        <h3 class="text-xl font-bold mb-1 relative z-10 leading-tight">{{ $session->name }}</h3>
                        <p class="text-white/80 text-xs font-medium relative z-10 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $session->room_location ?? 'Room Unset' }}
                        </p>
                    </div>

                    {{-- Card Body --}}
                    <div class="p-6">
                        <div class="space-y-4 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-gray-700 flex items-center justify-center text-slate-500 shadow-sm border border-slate-100 dark:border-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-tighter">Schedule</p>
                                    <p class="text-sm font-bold text-slate-700 dark:text-gray-200">
                                        {{ $session->getPrimaryDayLabel() }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-gray-700 flex items-center justify-center text-slate-500 shadow-sm border border-slate-100 dark:border-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-tighter">Time Range</p>
                                    <p class="text-sm font-bold text-slate-700 dark:text-gray-200">
                                        {{ $session->start_time ? $session->start_time->format('g:i A') : '--:--' }} - 
                                        {{ $session->end_time ? $session->end_time->format('g:i A') : '--:--' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-gray-700 flex items-center justify-center text-slate-500 shadow-sm border border-slate-100 dark:border-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-tighter">Presentations</p>
                                    <p class="text-sm font-bold text-slate-700 dark:text-gray-200">
                                        {{ $session->abstracts->count() }} Papers Assigned
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-100 dark:border-gray-700">
                            <a href="{{ route('sessions.show', $session) }}" class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 border border-slate-900 text-white rounded-2xl font-bold hover:bg-slate-800 transition-all shadow-lg hover:shadow-indigo-500/20 active:scale-95 group">
                                Manage Session
                                <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
