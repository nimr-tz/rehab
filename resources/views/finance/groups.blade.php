@extends('layouts.app')

@section('title', 'Group Registrations')

@section('content')
<div class="min-h-screen bg-[#F8F9FC] dark:bg-[#0B1120] font-sans pb-24">
    <!-- Top Navigation / Header -->
    <div class="bg-white dark:bg-[#151C2C] border-b border-slate-200/60 dark:border-slate-800/60 sticky top-0 z-30 backdrop-blur-xl bg-white/80 dark:bg-[#151C2C]/80">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-4">
                    <a href="{{ route('finance.dashboard') }}" class="group flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Group Payments</h1>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">FinanceDesk</p>
                        </div>
                    </a>
                </div>
                
                 <div class="flex items-center gap-4">
                    <div class="hidden md:flex flex-col items-end mr-2">
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ Auth::user()->full_name }}</span>
                        <span class="text-xs text-slate-500">Finance Officer</span>
                    </div>
                     @if(Auth::user()->profile_image)
                        <img src="{{ asset('storage/' . Auth::user()->profile_image) }}" class="h-10 w-10 rounded-full object-cover border-2 border-slate-200 dark:border-slate-700" alt="Profile">
                    @else
                        <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-400 font-bold">
                            {{ Auth::user()->initials }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-10 space-y-8">

        <!-- Metrics Bar -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
             <div class="bg-white dark:bg-[#151C2C] p-4 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Settled Groups</p>
                    <p class="text-2xl font-black text-emerald-600">{{ $stats['verified_count'] }}</p>
                </div>
                <div class="h-8 w-8 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="bg-white dark:bg-[#151C2C] p-4 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Awaiting Review</p>
                    <p class="text-2xl font-black text-amber-500">{{ $stats['pending_count'] }}</p>
                </div>
                <div class="h-8 w-8 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            
             <div class="bg-white dark:bg-[#151C2C] p-4 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Issues / Declined</p>
                    <p class="text-2xl font-black text-rose-500">{{ $stats['rejected_count'] }}</p>
                </div>
                <div class="h-8 w-8 rounded-full bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center text-rose-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            </div>

            <!-- Total Groups -->
             <div class="flex flex-col justify-center px-4">
                 <p class="text-xs text-slate-400 font-medium">Total Groups</p>
                 <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $groups->total() }} <span class="text-xs font-normal text-slate-400">organizations</span></p>
             </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white dark:bg-[#151C2C] rounded-2xl p-4 border border-slate-100 dark:border-slate-800 shadow-sm">
            <form method="GET" class="flex flex-col lg:flex-row gap-4">
                
                <!-- Status Tabs -->
                <div class="hidden lg:flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl overflow-x-auto">
                    @php
                        $tabBase = "px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide whitespace-nowrap transition-all";
                        $tabActive = "bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm";
                        $tabInactive = "text-slate-500 hover:text-slate-700 dark:hover:text-slate-300";
                    @endphp
                    <a href="{{ route('finance.groups', ['status' => 'all']) }}" class="{{ $status === 'all' ? $tabActive : $tabInactive }} {{ $tabBase }}">All</a>
                    <a href="{{ route('finance.groups', ['status' => 'verified']) }}" class="{{ $status === 'verified' ? $tabActive : $tabInactive }} {{ $tabBase }}">Settled</a>
                    <a href="{{ route('finance.groups', ['status' => 'submitted']) }}" class="{{ $status === 'submitted' ? $tabActive : $tabInactive }} {{ $tabBase }}">Pending Settlement</a>
                    <a href="{{ route('finance.groups', ['status' => 'pending']) }}" class="{{ $status === 'pending' ? $tabActive : $tabInactive }} {{ $tabBase }}">Incomplete</a>
                    <a href="{{ route('finance.groups', ['status' => 'rejected']) }}" class="{{ $status === 'rejected' ? $tabActive : $tabInactive }} {{ $tabBase }}">Issues</a>
                </div>

                <!-- Search -->
                <div class="flex-1 relative">
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search groups or leaders..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </form>
        </div>

        <!-- Groups Table -->
        <div class="bg-white dark:bg-[#151C2C] rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
             <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Group</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Size</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-right">Total Amount</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Status</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse($groups as $group)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm">
                                        {{ substr($group->group_name ?: 'G', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $group->group_name }}</div>
                                        <div class="text-xs text-slate-500">{{ $group->organization ?: 'No Organization' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($group->payment_reference)
                                    <span class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">{{ $group->payment_reference }}</span>
                                @else
                                    <span class="text-xs text-slate-400 italic">No reference yet</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300">
                                    {{ $group->member_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-black text-slate-900 dark:text-white">{{ $group->formatted_total ?? '---' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                {!! $group->getStatusBadge() !!}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('finance.group.show', $group) }}" class="inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-600 hover:text-indigo-600 text-slate-500 rounded-xl text-xs font-bold transition-all shadow-sm">
                                    Review
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">No groups found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
