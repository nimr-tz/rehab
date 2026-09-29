@extends('layouts.app')

@section('title', 'Group Registrations Management')

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 relative font-sans">
    <!-- Header -->
    <div class="relative bg-gradient-to-br from-slate-800 via-slate-900 to-gray-900 py-12 rounded-b-[3rem] shadow-2xl overflow-hidden mb-8">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.03]"></div>
        
        <div class="max-w-7xl mx-auto px-6 sm:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h1 class="text-3xl font-black text-white" style="font-family: 'Outfit', sans-serif;">
                        Group Registrations
                    </h1>
                    <p class="text-slate-400 text-sm mt-1">Manage group conference registrations and payments</p>
                </div>
                
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.group-registrations.export') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg text-sm font-medium transition-all" title="Export CSV">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        CSV
                    </a>
                    <a href="{{ route('admin.group-registrations.export-excel') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600/80 hover:bg-emerald-600 text-white rounded-lg text-sm font-medium transition-all" title="Export Excel">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Excel
                    </a>
                    <a href="{{ route('admin.group-registrations.export-pdf') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600/80 hover:bg-rose-600 text-white rounded-lg text-sm font-medium transition-all" title="Export PDF">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-8 pb-12">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
                <p class="text-xs text-slate-500">Total Groups</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700">
                <p class="text-2xl font-bold text-slate-600">{{ $stats['pending'] }}</p>
                <p class="text-xs text-slate-500">Pending</p>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
                <p class="text-2xl font-bold text-amber-600">{{ $stats['submitted'] }}</p>
                <p class="text-xs text-amber-600">Awaiting Review</p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
                <p class="text-2xl font-bold text-emerald-600">{{ $stats['verified'] }}</p>
                <p class="text-xs text-emerald-600">Verified</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_members'] }}</p>
                <p class="text-xs text-slate-500">Total Members</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700">
                <p class="text-2xl font-bold text-emerald-600">{{ $stats['verified_members'] }}</p>
                <p class="text-xs text-slate-500">Verified Members</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-slate-200 dark:border-gray-700 mb-6">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search groups, leaders, members..." class="w-full px-4 py-2 bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500">
                </div>
                <select name="status" class="px-4 py-2 bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 rounded-lg text-sm">
                    <option value="all">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Payment</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Awaiting Review</option>
                    <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors">
                    Filter
                </button>
                @if(request('search') || request('status'))
                    <a href="{{ route('admin.group-registrations.index') }}" class="px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4">
                <p class="text-emerald-700 dark:text-emerald-300 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Groups Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-gray-700/50 border-b border-slate-200 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Group</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Leader</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Members</th>

                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Payment Date</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                        @forelse($groupRegistrations as $group)
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $group->group_name ?: 'Group #' . $group->id }}</p>
                                        @if($group->organization)
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $group->organization }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $group->leader->full_name ?? 'N/A' }}</p>
                                        <p class="text-xs text-slate-500">{{ $group->leader->email ?? '' }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-slate-100 dark:bg-gray-700 rounded-lg text-sm font-bold text-slate-700 dark:text-slate-300">
                                        {{ $group->members_count }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {!! $group->getStatusBadge() !!}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($group->payment_verified_at && in_array($group->payment_status, ['verified', 'waived']))
                                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200">
                                            {{ $group->payment_verified_at->format('M d, Y') }}
                                        </div>
                                        <div class="text-xs mt-0.5 {{ $group->payment_status === 'waived' ? 'text-indigo-500' : 'text-emerald-600' }}">
                                            {{ $group->payment_status === 'waived' ? 'Waived' : 'Paid' }}
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-sm">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ $group->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.group-registrations.show', $group) }}" class="px-3 py-1.5 bg-slate-100 dark:bg-gray-700 hover:bg-slate-200 dark:hover:bg-gray-600 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-medium transition-colors">
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                    No group registrations found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($groupRegistrations->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 dark:border-gray-700">
                    {{ $groupRegistrations->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
