@extends('layouts.app')

@section('title', 'Payment Ledger')

@section('content')
<div class="min-h-screen bg-[#F8F9FC] dark:bg-[#0B1120] font-sans pb-24">
    <!-- Top Navigation / Header (Consistent with Dashboard) -->
    <div class="bg-white dark:bg-[#151C2C] border-b border-slate-200/60 dark:border-slate-800/60 sticky top-0 z-30 backdrop-blur-xl bg-white/80 dark:bg-[#151C2C]/80">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-4">
                    <a href="{{ route('finance.dashboard') }}" class="group flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Payment Ledger</h1>
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

        <!-- Metrics Bar (Condensed) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-[#151C2C] p-4 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Settled (Paid)</p>
                    <p class="text-2xl font-black text-emerald-600">{{ $stats['verified_count'] }}</p>
                </div>
                <div class="h-8 w-8 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <div class="bg-white dark:bg-[#151C2C] p-4 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Awaiting Review</p>
                    <p class="text-2xl font-black text-amber-500">{{ $stats['pending_count'] }}</p> <!-- Previously 'submitted' -->
                </div> 
                <div class="h-8 w-8 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
             <div class="bg-white dark:bg-[#151C2C] p-4 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Unpaid (Draft)</p>
                    <p class="text-2xl font-black text-slate-500">{{ $stats['not_started_count'] }}</p>
                </div>
                <div class="h-8 w-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </div>
            </div>
             <!-- Total, just text -->
             <div class="flex flex-col justify-center px-4">
                 <p class="text-xs text-slate-400 font-medium">Total Registered</p>
                 <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $users->total() }} <span class="text-xs font-normal text-slate-400">records</span></p>
             </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('finance.groups') }}" class="bg-white dark:bg-[#151C2C] p-5 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm hover:border-indigo-300 transition-colors">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Group Payments</p>
                <p class="mt-2 text-lg font-black text-slate-900 dark:text-white">Review group invoices and verification</p>
            </a>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white dark:bg-[#151C2C] rounded-2xl p-4 border border-slate-100 dark:border-slate-800 shadow-sm">
            <form method="GET" action="{{ route('finance.payments') }}" class="flex flex-col lg:flex-row gap-4">
                
                <!-- Status Tabs (Desktop) -->
                <div class="hidden lg:flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl overflow-x-auto">
                    @php
                        $tabBase = "px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide whitespace-nowrap transition-all";
                        $tabActive = "bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm";
                        $tabInactive = "text-slate-500 hover:text-slate-700 dark:hover:text-slate-300";
                    @endphp
                    <a href="{{ route('finance.payments', array_merge(request()->except('status', 'method'), ['status' => 'all'])) }}" class="{{ $status === 'all' && request('method') !== 'bank_transfer' ? $tabActive : $tabInactive }} {{ $tabBase }}">All</a>
                    <a href="{{ route('finance.payments', array_merge(request()->except('status', 'method'), ['status' => 'verified'])) }}" class="{{ $status === 'verified' ? $tabActive : $tabInactive }} {{ $tabBase }}">Settled</a>
                    <a href="{{ route('finance.payments', array_merge(request()->except('status', 'method'), ['status' => 'submitted'])) }}" class="{{ $status === 'submitted' && request('method') !== 'bank_transfer' ? $tabActive : $tabInactive }} {{ $tabBase }}">Awaiting Review</a>
                    <a href="{{ route('finance.payments', array_merge(request()->except('status', 'method'), ['status' => 'submitted', 'method' => 'bank_transfer'])) }}" class="{{ request('method') === 'bank_transfer' ? $tabActive . ' text-blue-600' : $tabInactive }} {{ $tabBase }}">Bank Transfers</a>
                    <a href="{{ route('finance.payments', array_merge(request()->except('status', 'method'), ['status' => 'pending'])) }}" class="{{ $status === 'pending' ? $tabActive : $tabInactive }} {{ $tabBase }}">Unpaid (Draft)</a>
                    <a href="{{ route('finance.payments', array_merge(request()->except('status', 'method'), ['status' => 'waived'])) }}" class="{{ $status === 'waived' ? $tabActive : $tabInactive }} {{ $tabBase }}">Waived</a>
                </div>

                <!-- Search -->
                <div class="flex-1 relative">
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name or email..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                 <div class="w-full lg:w-48">
                     <select name="category" onchange="this.form.submit()" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                        <option value="all" {{ ($category ?? 'all') === 'all' ? 'selected' : '' }}>All Categories</option>
                        <option value="professional_local" {{ ($category ?? '') === 'professional_local' ? 'selected' : '' }}>Local Participant</option>
                        <option value="professional_international" {{ ($category ?? '') === 'professional_international' ? 'selected' : '' }}>Intl. Participant</option>
                        <option value="student_local" {{ ($category ?? '') === 'student_local' ? 'selected' : '' }}>Local Student</option>
                        <option value="student_international" {{ ($category ?? '') === 'student_international' ? 'selected' : '' }}>Intl. Student</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Ledger Table -->
        <div class="bg-white dark:bg-[#151C2C] rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
             <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Participant</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Reference / Method</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-right">Fee</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse($users as $user)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 font-bold text-sm">
                                        {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $user->full_name }}</div>
                                        <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->payment_reference)
                                    <span class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">{{ $user->payment_reference }}</span>
                                @else
                                    <span class="text-xs text-slate-400 italic">No reference yet</span>
                                @endif
                                @if($user->payment_method)
                                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                        {{ config('payments.methods.'.$user->payment_method.'.label', $user->payment_method) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($user->payment_status === 'verified')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Settled
                                    </span>
                                @elseif($user->payment_status === 'submitted')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
                                        Awaiting Review
                                    </span>
                                @elseif($user->payment_status === 'waived')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400">
                                        Waived
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        Draft / Unpaid
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @php $fee = $user->getRegistrationFee(); @endphp
                                <div class="text-xs font-bold text-slate-900 dark:text-white">{{ $fee['currency'] }} {{ number_format($fee['amount']) }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('finance.show', $user) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 hover:text-indigo-600 hover:border-indigo-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="mx-auto w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                </div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">No records found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
             @if($users->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    {{ $users->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
