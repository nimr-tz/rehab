@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-gray-900 pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-10">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                    Invitation <span class="text-blue-600">Requests.</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Manage and approve visa invitation letters.</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-slate-200/60 dark:border-gray-700/50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-gray-900/50 border-b border-slate-100 dark:border-gray-700">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">User</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Letter Name</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Institute</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Request Date</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Downloaded</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                        @forelse($invitations as $invitation)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 font-bold text-xs">
                                        {{ $invitation->user?->initials ?? '?' }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $invitation->user?->full_name ?? 'Unknown User' }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $invitation->user?->email ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-slate-700 dark:text-gray-300">{{ $invitation->title }} {{ $invitation->passport_name }}</p>
                                <p class="text-[10px] text-slate-400 font-bold">{{ $invitation->nationality }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-slate-700 dark:text-gray-300">{{ $invitation->institute }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if($invitation->status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        Approved
                                    </span>
                                @elseif($invitation->status === 'rejected')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                                        Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                        Pending
                                    </span>
                                @endif

                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ $invitation->created_at->format('M d, Y') }}</p>
                                <p class="text-[10px] text-slate-400">{{ $invitation->created_at->format('h:i A') }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if($invitation->download_count > 0)
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Yes ({{ $invitation->download_count }})
                                        </span>
                                        @if($invitation->downloaded_at)
                                            <p class="text-[10px] text-slate-400 mt-1 font-medium">Last: {{ $invitation->downloaded_at->diffForHumans() }}</p>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs font-bold text-slate-400">No</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="w-16 h-16 bg-slate-100 dark:bg-gray-900/50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <p class="text-slate-500 font-medium">No invitation requests found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($invitations->hasPages())
                <div class="px-6 py-4 bg-slate-50 dark:bg-gray-900/50 border-t border-slate-100 dark:border-gray-700">
                    {{ $invitations->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
