@extends('layouts.app')

@section('title', 'Speaker Intelligence Hub')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900">
    <div class="relative max-w-[1600px] mx-auto px-6 py-10 text-slate-900 dark:text-white">

        {{-- Executive Header System --}}
        <div class="relative overflow-hidden mb-12">
            {{-- Abstract Background Elements --}}
            <div class="absolute inset-0 opacity-40 pointer-events-none">
                <div class="absolute top-0 right-1/4 w-96 h-96 bg-purple-200 dark:bg-purple-500/20 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -bottom-24 left-1/4 w-80 h-80 bg-indigo-200 dark:bg-indigo-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-8 z-10">
                <div class="flex items-center gap-6">
                    <div class="p-5 bg-purple-600 dark:bg-white rounded-[2rem] shadow-2xl shadow-purple-200/50 dark:shadow-none transition-all group">
                        <svg class="w-10 h-10 text-white dark:text-purple-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">Speaker Intelligence</h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 text-[10px] font-black uppercase rounded-md border border-purple-500/20">Mobile App Nexus</span>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium tracking-wide">Managing the strategic voices and keynote influencers for {{ config('conference.short_name') }} {{ config('conference.year') }}.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.speakers.create') }}" class="px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl shadow-xl hover:-translate-y-1 transition-all font-black text-sm tracking-tight flex items-center gap-3 group">
                        <div class="p-1 bg-white/20 dark:bg-slate-900/10 rounded-lg group-hover:rotate-90 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        Enlist Speaker
                    </a>
                </div>
            </div>
        </div>

        {{-- STRATEGIC METRICS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            @php
                $metrics = [
                    ['label' => 'Total Enrolled', 'count' => $speakers->count(), 'sub' => 'All Speaker Entities', 'color' => 'indigo', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                    ['label' => 'Keynote Alpha', 'count' => $speakers->where('type', 'keynote')->count(), 'sub' => 'Primary Influencers', 'color' => 'amber', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                    ['label' => 'Plenary Strategists', 'count' => $speakers->where('type', 'plenary')->count(), 'sub' => 'Core Content Streams', 'color' => 'blue', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['label' => 'Operational Delta', 'count' => $speakers->where('is_active', true)->count(), 'sub' => 'Active Terminals', 'color' => 'emerald', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z']
                ];
            @endphp

            @foreach($metrics as $m)
                <div class="p-8 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none group transition-all hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-{{ $m['color'] }}-100 dark:bg-{{ $m['color'] }}-500/20 rounded-2xl text-{{ $m['color'] }}-600 dark:text-{{ $m['color'] }}-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $m['icon'] }}"/></svg>
                        </div>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Analytics</span>
                    </div>
                    <p class="text-4xl font-black text-slate-900 dark:text-white leading-none tracking-tighter">{{ $m['count'] }}</p>
                    <p class="text-xs font-bold text-slate-500 uppercase mt-3 tracking-widest">{{ $m['label'] }}</p>
                    <p class="text-[10px] font-medium text-slate-400 mt-1 italic">{{ $m['sub'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- FILTER CONSOLE --}}
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl p-10 mb-12">
            <form action="{{ route('admin.speakers.index') }}" method="GET" class="flex flex-col lg:flex-row gap-8">
                <div class="flex-1 space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Entity Identifier</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none text-slate-400 group-focus-within:text-purple-500 transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Detecting speaker via name, intelligence or affiliation..."
                            class="block w-full pl-14 pr-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 transition-all font-medium">
                    </div>
                </div>

                <div class="flex flex-wrap lg:flex-nowrap gap-6 items-end">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Classification</label>
                        <select name="type" class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-purple-500 font-bold min-w-[180px]">
                            <option value="all">All Echelons</option>
                            <option value="keynote" {{ request('type') === 'keynote' ? 'selected' : '' }}>Keynote</option>
                            <option value="plenary" {{ request('type') === 'plenary' ? 'selected' : '' }}>Plenary</option>
                            <option value="invited" {{ request('type') === 'invited' ? 'selected' : '' }}>Invited</option>
                            <option value="panelist" {{ request('type') === 'panelist' ? 'selected' : '' }}>Panelist</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Terminal Status</label>
                        <select name="status" class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-purple-500 font-bold min-w-[180px]">
                            <option value="all">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Operational</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Offline</option>
                        </select>
                    </div>

                    <button type="submit" class="px-8 py-4 bg-purple-600 dark:bg-white text-white dark:text-slate-900 font-black rounded-2xl hover:bg-purple-700 dark:hover:bg-slate-100 transition-all shadow-xl shadow-purple-200/50 dark:shadow-none uppercase text-xs tracking-widest">
                        Apply Protocol
                    </button>

                    @if(request()->anyFilled(['search', 'type', 'status']))
                        <a href="{{ route('admin.speakers.index') }}" class="px-8 py-4 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-black rounded-2xl hover:bg-slate-300 dark:hover:bg-slate-600 transition-all uppercase text-xs tracking-widest border border-slate-300 dark:border-slate-600">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- PERSONNEL GRID --}}
        @if($speakers->isEmpty())
            <div class="p-20 text-center bg-white/50 dark:bg-slate-800/50 backdrop-blur-xl rounded-[4rem] border border-dashed border-slate-200 dark:border-slate-700 shadow-inner">
                <div class="w-24 h-24 bg-purple-100 dark:bg-purple-900/30 rounded-[2rem] flex items-center justify-center mx-auto mb-8 animate-bounce">
                    <svg class="w-10 h-10 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                </div>
                <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-4 tracking-tight">Zero Personnel Detected</h3>
                <p class="text-slate-500 dark:text-slate-400 font-medium max-w-md mx-auto mb-10 text-lg">The speaker intelligence database is currently empty. Enlist the first strategic voice to populate the mobile nexus.</p>
                <a href="{{ route('admin.speakers.create') }}" class="inline-flex items-center px-10 py-5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-black rounded-2xl shadow-2xl hover:-translate-y-1 transition-all uppercase text-sm tracking-widest">
                    Initialize Enrollment
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10" x-data="{ bioModal: false, activeBio: '', activeName: '' }">
                @foreach($speakers as $speaker)
                    <div class="group relative bg-white dark:bg-slate-800 rounded-[3.5rem] shadow-2xl shadow-slate-200/50 dark:shadow-none border border-white dark:border-slate-700 overflow-hidden transition-all duration-500 hover:scale-[1.02] flex flex-col">

                        {{-- Visual Terminal --}}
                        <div class="relative h-72 overflow-hidden bg-slate-900">
                            <img src="{{ $speaker->photo_url }}" alt="{{ $speaker->name }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 opacity-90 group-hover:opacity-100">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/20 to-transparent"></div>

                            {{-- Status Badges --}}
                            <div class="absolute top-6 right-6 flex flex-col gap-2 items-end">
                                @if(!$speaker->is_active)
                                    <span class="px-3 py-1 bg-rose-500/90 backdrop-blur-md text-white text-[9px] font-black uppercase tracking-[0.2em] rounded-full border border-rose-400/30">Offline</span>
                                @else
                                    <div class="flex items-center gap-2 px-3 py-1 bg-emerald-500/90 backdrop-blur-md text-white text-[9px] font-black uppercase tracking-[0.2em] rounded-full border border-emerald-400/30">
                                        <div class="w-1.5 h-1.5 bg-white rounded-full animate-pulse shadow-[0_0_5px_white]"></div>
                                        Operational
                                    </div>
                                @endif
                            </div>

                            <div class="absolute top-6 left-6">
                                @php
                                    $typeColors = [
                                        'keynote' => 'bg-amber-500/90 text-amber-50 border-amber-400/30',
                                        'plenary' => 'bg-indigo-500/90 text-indigo-50 border-indigo-400/30',
                                        'invited' => 'bg-purple-500/90 text-purple-50 border-purple-400/30',
                                        'panelist' => 'bg-emerald-500/90 text-emerald-50 border-emerald-400/30',
                                    ];
                                    $style = $typeColors[$speaker->type] ?? 'bg-slate-500/90 text-slate-50 border-slate-400/30';
                                @endphp
                                <span class="{{ $style }} backdrop-blur-md text-[9px] font-black px-4 py-1 rounded-full uppercase tracking-[0.2em] border shadow-lg">{{ $speaker->type }}</span>
                            </div>

                            <div class="absolute bottom-6 left-6 right-6">
                                <h3 class="text-2xl font-black text-white leading-tight mb-1">{{ $speaker->name }}</h3>
                                <p class="text-xs font-bold text-purple-400 uppercase tracking-widest">{{ $speaker->position }}</p>
                            </div>
                        </div>

                        {{-- Metadata Terminal --}}
                        <div class="p-10 flex-1 flex flex-col">
                            @if($speaker->affiliation)
                                <div class="flex items-center gap-3 mb-6 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                                    <div class="p-2 bg-white dark:bg-slate-800 rounded-lg text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-400">{{ $speaker->affiliation }}</span>
                                </div>
                            @endif

                            <div class="flex-1">
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-3 mb-6">
                                    {{ $speaker->bio }}
                                </p>

                                <div class="flex items-center justify-between mb-8">
                                    <div class="flex gap-2">
                                        @if($speaker->social_links)
                                            @foreach(['twitter' => 'M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z', 'linkedin' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452z'] as $platform => $svg)
                                                @if(isset($speaker->social_links[$platform]))
                                                    <a href="{{ $platform === 'twitter' ? 'https://twitter.com/'.$speaker->social_links[$platform] : $speaker->social_links[$platform] }}" target="_blank" class="w-10 h-10 bg-slate-50 dark:bg-slate-900 rounded-xl flex items-center justify-center text-slate-400 hover:text-purple-600 transition-colors border border-slate-100 dark:border-slate-700">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="{{ $svg }}"/></svg>
                                                    </a>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                    <div class="px-4 py-2 bg-purple-50 dark:bg-purple-900/30 rounded-2xl border border-purple-100 dark:border-purple-800">
                                        <span class="text-[10px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-widest">{{ $speaker->sessions_count }} Assignments</span>
                                    </div>
                                </div>
                            </div>

                            {{-- High-End Actions --}}
                            <div class="flex gap-4 pt-10 border-t border-slate-50 dark:border-slate-700">
                                <a href="{{ route('admin.speakers.edit', $speaker) }}" class="flex-1 flex items-center justify-center gap-3 px-6 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black rounded-2xl transition-all hover:shadow-xl hover:-translate-y-1 uppercase text-[10px] tracking-widest">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Reconfigure
                                </a>
                                <form action="{{ route('admin.speakers.destroy', $speaker) }}" method="POST" onsubmit="return confirm('Initiate decommission protocol for this speaker?');" class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-14 h-14 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-2xl flex items-center justify-center border border-rose-100 dark:border-rose-900/30 transition-all hover:bg-rose-600 hover:text-white">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap');
    :root { font-family: 'Outfit', sans-serif; }
</style>
@endsection
