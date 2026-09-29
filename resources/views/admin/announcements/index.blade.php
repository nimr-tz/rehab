@extends('layouts.app')

@section('title', 'Announcements')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900">
    {{-- Hero Header System --}}
    <div class="relative overflow-hidden">
        {{-- Subtle Background Pattern --}}
        <div class="absolute inset-0 opacity-30 dark:opacity-20">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-200 dark:bg-blue-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-1/4 w-[500px] h-[500px] bg-purple-200 dark:bg-purple-500/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 w-72 h-72 bg-emerald-100 dark:bg-emerald-500/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-[1600px] mx-auto px-6 py-10">
            {{-- Top Navigation Bar --}}
            <div class="flex items-center justify-between mb-12">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-lg shadow-blue-500/20">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.684A4.022 4.022 0 017 6h3.832c4.514 0 9.226-4.995 9.226-4.995V17s-4.27 4-9.226 4H7a4.022 4.022 0 01-1.564-.316 4 4.022 0 01-2 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">App Announcements</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm">Publish updates on the website and in participants' notification feed.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.announcements.create') }}"
                       class="px-5 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold rounded-xl shadow-xl hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Announcement
                    </a>
                </div>
            </div>

            {{-- Metrics Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                @php
                    $totalCount = \App\Models\Announcement::count();
                    $urgentCount = \App\Models\Announcement::where('is_urgent', true)->count();
                    $recentCount = \App\Models\Announcement::where('created_at', '>=', now()->subDays(7))->count();
                @endphp

                {{-- Total --}}
                <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-3xl border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none transition-all hover:shadow-blue-500/5 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-blue-100 dark:bg-blue-500/20 rounded-xl group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $totalCount }}</p>
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Total Posts</p>
                </div>

                {{-- Urgent --}}
                <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-3xl border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none transition-all hover:shadow-red-500/5 group text-red-600 dark:text-red-400">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-red-100 dark:bg-red-500/20 rounded-xl group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black">{{ $urgentCount }}</p>
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Urgent Alerts</p>
                </div>

                {{-- Recent --}}
                <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-3xl border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none transition-all hover:shadow-emerald-500/5 group text-emerald-600 dark:text-emerald-400">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-500/20 rounded-xl group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black">{{ $recentCount }}</p>
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">This Week</p>
                </div>

                {{-- Active --}}
                <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-3xl border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none transition-all hover:shadow-purple-500/5 group text-purple-600 dark:text-purple-400">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-purple-100 dark:bg-purple-500/20 rounded-xl group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black">{{ $announcements->total() }}</p>
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Active Feed</p>
                </div>
            </div>

            {{-- Announcements Registry --}}
            <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] border border-white dark:border-slate-700 shadow-2xl shadow-slate-200/50 dark:shadow-none overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 dark:bg-blue-500/20 rounded-2xl text-blue-600 dark:text-blue-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Announcement Stream</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Comprehensive list of all broadcasted messages.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black uppercase tracking-tighter text-slate-400 bg-slate-100 dark:bg-slate-900 px-3 py-1 rounded-full">Server Sync: OK</span>
                    </div>
                </div>

                @if($announcements->isEmpty())
                    <div class="p-20 text-center">
                        <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center border border-slate-100 dark:border-slate-800">
                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.684A4.022 4.022 0 017 6h3.832c4.514 0 9.226-4.995 9.226-4.995V17s-4.27 4-9.226 4H7a4.022 4.022 0 01-1.564-.316 4 4.022 0 01-2 0z" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No active announcements</h3>
                        <p class="text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-8">Ready to share news with the delegates? Create your first broadcast now.</p>
                        <a href="{{ route('admin.announcements.create') }}" class="px-8 py-4 bg-blue-600 text-white font-black rounded-2xl shadow-xl shadow-blue-500/20 hover:shadow-blue-500/40 hover:-translate-y-1 transition-all">
                            Initialize First Post
                        </a>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($announcements as $announcement)
                            <div class="p-8 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-all duration-300 group relative">
                                <div class="flex items-start justify-between gap-6">
                                    <div class="flex items-start gap-6 flex-1">
                                        {{-- Visual Cue --}}
                                        <div class="flex-shrink-0 mt-1">
                                            @if($announcement->is_urgent)
                                                <div class="w-14 h-14 rounded-2xl bg-red-100 dark:bg-red-500/20 flex items-center justify-center text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                </div>
                                            @else
                                                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-800 transition-colors group-hover:bg-blue-50 group-hover:text-blue-600 group-hover:border-blue-100 dark:group-hover:bg-blue-500/10">
                                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.684A4.022 4.022 0 017 6h3.832c4.514 0 9.226-4.995 9.226-4.995V17s-4.27 4-9.226 4H7a4.022 4.022 0 01-1.564-.316 4 4.022 0 01-2 0z" /></svg>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex-1 space-y-3">
                                            <div class="flex items-center gap-3">
                                                <h4 class="text-xl font-black text-slate-900 dark:text-white tracking-tight leading-none">
                                                    {{ $announcement->title }}
                                                </h4>
                                                @if($announcement->is_urgent)
                                                    <span class="px-2 py-1 bg-red-600 text-white text-[10px] font-black rounded-lg uppercase tracking-tighter">Emergency</span>
                                                @endif
                                            </div>

                                            <p class="text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed font-medium">
                                                {{ $announcement->content }}
                                            </p>

                                            <div class="flex items-center gap-6">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest
                                                    @if($announcement->category === 'General') bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400
                                                    @elseif($announcement->category === 'Social') bg-pink-100 text-pink-700 dark:bg-pink-500/20 dark:text-pink-400
                                                    @elseif($announcement->category === 'Urgent') bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400
                                                    @elseif($announcement->category === 'Info') bg-cyan-100 text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-400
                                                    @elseif($announcement->category === 'Update') bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400
                                                    @elseif($announcement->category === 'Schedule') bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400
                                                    @else bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300
                                                    @endif
                                                ">
                                                    {{ $announcement->category }}
                                                </span>
                                                <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500 text-xs font-bold uppercase tracking-widest">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    {{ $announcement->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Hover Actions --}}
                                    <div class="flex items-center gap-3 opacity-0 group-hover:opacity-100 transition-all transform translate-x-4 group-hover:translate-x-0">
                                        <a href="{{ route('admin.announcements.edit', $announcement->id) }}"
                                           class="p-3 bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-2xl shadow-lg border border-slate-100 dark:border-slate-600 hover:text-blue-600 dark:hover:text-blue-400 transition-all">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <form action="{{ route('admin.announcements.destroy', $announcement->id) }}" method="POST" class="inline" onsubmit="return confirm('Archive this message permanently?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-3 bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-2xl shadow-lg border border-slate-100 dark:border-slate-600 hover:text-red-600 dark:hover:text-red-400 transition-all">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($announcements->hasPages())
                        <div class="p-8 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                            {{ $announcements->links() }}
                        </div>
                    @endif
                @endif
            </div>

            {{-- Policy/Help Disclaimer --}}
            <div class="mt-8 p-6 bg-gradient-to-r from-blue-50/50 to-indigo-50/50 dark:from-blue-900/10 dark:to-indigo-900/10 rounded-3xl border border-blue-100/50 dark:border-blue-900/30">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-white dark:bg-slate-800 rounded-2xl shadow-sm text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                        <strong>Deployment Note:</strong> Broadcasts are synchronized across all connected mobile devices.
                        <span class="text-blue-600 dark:text-blue-400">Urgent</span> communications bypass standard polling and appear immediately as push notifications.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
