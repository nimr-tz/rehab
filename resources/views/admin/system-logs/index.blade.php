@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap');
    .logs-body { font-family: 'Outfit', sans-serif; }
    .mono { font-family: 'JetBrains Mono', monospace; }
</style>

<div class="min-h-screen bg-[#f1f5f9] dark:bg-[#020617] pb-32 logs-body">

    {{-- ═══ Header ═══ --}}
    <div class="relative bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 py-12 md:py-16 rounded-b-[3rem] md:rounded-b-[5rem] shadow-2xl overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.03]"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-black/20 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-end gap-10">
                <div class="space-y-3">
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-slate-400/50 hover:text-slate-300 transition-colors text-xs font-bold uppercase tracking-widest mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Admin Panel
                    </a>
                    <h1 class="text-3xl md:text-5xl font-black text-white leading-none tracking-tight">
                        System <span class="text-rose-400">Logs</span>.
                    </h1>
                    <p class="text-base text-slate-400 font-medium">Monitor errors and system issues in real-time.</p>
                </div>

                {{-- Stats Row --}}
                <div class="flex items-center gap-6 lg:gap-8 bg-white/5 backdrop-blur-md px-6 lg:px-8 py-4 rounded-2xl border border-white/10 shadow-2xl">
                    <div class="text-center">
                        <p class="text-[9px] font-black text-slate-400/60 uppercase tracking-[0.3em] mb-1">Total</p>
                        <p class="text-2xl font-black text-white leading-none">{{ $stats['total'] }}</p>
                    </div>
                    <div class="h-8 w-[1px] bg-white/10"></div>
                    <div class="text-center">
                        <p class="text-[9px] font-black text-rose-300/60 uppercase tracking-[0.3em] mb-1">Unresolved</p>
                        <p class="text-2xl font-black text-rose-400 leading-none">{{ $stats['unresolved'] }}</p>
                    </div>
                    <div class="h-8 w-[1px] bg-white/10"></div>
                    <div class="text-center">
                        <p class="text-[9px] font-black text-amber-300/60 uppercase tracking-[0.3em] mb-1">Today</p>
                        <p class="text-2xl font-black text-amber-400 leading-none">{{ $stats['errors_today'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Main Content ═══ --}}
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 mt-10">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-500/30 rounded-2xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <p class="text-sm font-bold text-emerald-700">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Filters --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-800 mb-6">
            <form method="GET" action="{{ route('admin.system-logs.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search errors..." class="w-full h-11 px-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none transition-all text-slate-700 dark:text-white">
                </div>
                <div class="w-36">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Level</label>
                    <select name="level" class="w-full h-11 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold appearance-none cursor-pointer outline-none text-slate-700 dark:text-white">
                        <option value="">All</option>
                        <option value="error" {{ request('level') === 'error' ? 'selected' : '' }}>Error</option>
                        <option value="warning" {{ request('level') === 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="info" {{ request('level') === 'info' ? 'selected' : '' }}>Info</option>
                    </select>
                </div>
                <div class="w-36">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Channel</label>
                    <select name="channel" class="w-full h-11 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold appearance-none cursor-pointer outline-none text-slate-700 dark:text-white">
                        <option value="">All</option>
                        <option value="auth" {{ request('channel') === 'auth' ? 'selected' : '' }}>Auth</option>
                        <option value="system" {{ request('channel') === 'system' ? 'selected' : '' }}>System</option>
                    </select>
                </div>
                <div class="w-36">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Status</label>
                    <select name="status" class="w-full h-11 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold appearance-none cursor-pointer outline-none text-slate-700 dark:text-white">
                        <option value="">All</option>
                        <option value="unresolved" {{ request('status') === 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    </select>
                </div>
                <button type="submit" class="h-11 px-6 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black text-xs uppercase tracking-wider rounded-xl hover:opacity-90 transition-all">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'level', 'channel', 'status']))
                    <a href="{{ route('admin.system-logs.index') }}" class="h-11 px-4 flex items-center text-slate-400 hover:text-slate-600 text-xs font-bold uppercase tracking-wider">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        {{-- Logs Table --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
            @if($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Time</th>
                                <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Level</th>
                                <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Channel</th>
                                <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Message</th>
                                <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">User</th>
                                <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="text-right px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            @foreach($logs as $log)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group {{ !$log->resolved ? 'border-l-4 border-l-rose-400' : 'border-l-4 border-l-transparent' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="mono text-xs text-slate-500 dark:text-slate-400">{{ $log->created_at->format('M d, H:i:s') }}</span>
                                        <span class="block text-[10px] text-slate-400">{{ $log->created_at->diffForHumans() }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {!! $log->level_badge !!}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {!! $log->channel_badge !!}
                                    </td>
                                    <td class="px-6 py-4 max-w-md">
                                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate" title="{{ $log->message }}">{{ Str::limit($log->message, 80) }}</p>
                                        @if($log->source)
                                            <span class="mono text-[10px] text-slate-400">{{ $log->source }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($log->user)
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500">
                                                    {{ $log->user->initials ?? 'U' }}
                                                </div>
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ $log->user->email ?? 'ID: ' . $log->user_id }}</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($log->resolved)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Resolved
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 uppercase tracking-wider">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                                Open
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.system-logs.show', $log) }}" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-all" title="View Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            @if(!$log->resolved)
                                                <form method="POST" action="{{ route('admin.system-logs.resolve', $log) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg transition-all" title="Mark Resolved">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $logs->links() }}
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-20">
                    <div class="w-20 h-20 rounded-[2rem] bg-emerald-50 dark:bg-emerald-900/10 flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-700 dark:text-white mb-2">All Clear</h3>
                    <p class="text-sm text-slate-400">No log entries match your filters.</p>
                </div>
            @endif
        </div>

        {{-- Action Bar --}}
        @if($stats['total'] > 0)
            <div class="mt-6 flex items-center justify-between">
                <p class="text-xs text-slate-400">Showing {{ $logs->firstItem() ?? 0 }} – {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries</p>
                @if($stats['resolved'] > 0)
                    <form method="POST" action="{{ route('admin.system-logs.clear-resolved') }}" onsubmit="return confirm('Are you sure you want to permanently delete all resolved logs?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-bold text-rose-400 hover:text-rose-600 transition-colors uppercase tracking-wider">
                            Clear {{ $stats['resolved'] }} Resolved Logs
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
