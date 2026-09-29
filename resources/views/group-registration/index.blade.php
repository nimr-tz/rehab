@extends('layouts.app')

@section('title', 'Group Registrations')

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 relative font-sans">
    <!-- Header -->
    <div class="relative bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-900 py-16 rounded-b-[4rem] shadow-2xl overflow-hidden mb-8">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.05]"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-black/20 to-transparent"></div>
        
        <div class="max-w-7xl mx-auto px-6 sm:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="space-y-3">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('dashboard') }}" class="text-teal-200 hover:text-white transition-colors flex items-center gap-1 text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Dashboard
                        </a>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black text-white leading-tight tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        Group Registrations
                    </h1>
                    <p class="text-teal-200 text-sm font-medium">
                        Register multiple attendees with a single payment
                    </p>
                </div>
                
                <a href="{{ route('group-registration.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white rounded-xl font-bold transition-all border border-white/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Group Registration
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-8 pb-12">
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-emerald-700 dark:text-emerald-300 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-rose-100 dark:bg-rose-900/40 rounded-lg flex items-center justify-center text-rose-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <p class="text-rose-700 dark:text-rose-300 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Info Card -->
        <div class="bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-900/20 dark:to-emerald-900/20 rounded-xl p-6 mb-8 border border-teal-100 dark:border-teal-800/50">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/40 rounded-xl flex items-center justify-center text-teal-600 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-teal-900 dark:text-teal-200">Group Registration</h3>
                    <p class="text-sm text-teal-700 dark:text-teal-300 mt-1">
                        Register 3 or more attendees with a single payment. Each member will receive their own conference badge and check-in QR code. 
                        <strong>Note:</strong> Group members cannot submit abstracts. If they need to present, they should register individually.
                    </p>
                </div>
            </div>
        </div>

        @if($groupRegistrations->count() > 0)
            <div class="space-y-4">
                @foreach($groupRegistrations as $group)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border {{ $group->isVerified() ? 'border-emerald-200 dark:border-emerald-800/50' : 'border-slate-200 dark:border-gray-700' }} overflow-hidden hover:shadow-md transition-all">
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start gap-3">
                                        <div class="w-12 h-12 {{ $group->isVerified() ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600' : ($group->payment_status === 'submitted' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-600' : 'bg-slate-100 dark:bg-gray-700 text-slate-500') }} rounded-xl flex items-center justify-center flex-shrink-0">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-3 flex-wrap">
                                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                                    {{ $group->group_name ?: 'Group #' . $group->id }}
                                                </h3>
                                                {!! $group->getStatusBadge() !!}
                                            </div>
                                            
                                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    {{ $group->member_count }} members
                                                </span>
                                                @if($group->organization)
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                        {{ $group->organization }}
                                                    </span>
                                                @endif
                                                <span class="flex items-center gap-1 font-semibold {{ $group->isVerified() ? 'text-emerald-600' : 'text-slate-700 dark:text-slate-300' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    {{ $group->formatted_total }}
                                                </span>
                                                <span class="text-xs text-slate-400">
                                                    Created {{ $group->created_at->format('M d, Y') }}
                                                </span>
                                            </div>

                                            @if($group->isVerified())
                                                <div class="mt-3 flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400 font-medium">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                    Payment verified • {{ $group->checked_in_count }}/{{ $group->member_count }} checked in
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('group-registration.show', $group) }}" class="inline-flex items-center gap-2 px-4 py-2 {{ $group->isVerified() ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-teal-600 hover:bg-teal-700' }} text-white rounded-xl text-sm font-bold transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-12 text-center">
                <div class="w-16 h-16 bg-teal-100 dark:bg-teal-900/30 rounded-2xl flex items-center justify-center text-teal-600 mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Group Registrations Yet</h3>
                <p class="text-slate-500 dark:text-slate-400 mb-6">Create a group registration to register multiple attendees with a single payment.</p>
                <a href="{{ route('group-registration.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-bold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Group Registration
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
