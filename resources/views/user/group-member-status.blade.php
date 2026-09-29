@extends('layouts.app')

@section('title', 'Membership Status')

@section('content')
<div class="min-h-screen bg-[#f8fafc] dark:bg-[#0a0a0b] font-sans pb-24">
    <!-- Radiant Header -->
    <div class="relative min-h-[300px] flex items-center overflow-hidden bg-gradient-to-br from-slate-900 to-slate-800 rounded-b-[4rem] shadow-2xl">
        <div class="max-w-7xl mx-auto px-8 w-full relative z-10 py-12 text-center md:text-left">
            <h1 class="text-4xl md:text-6xl font-black text-white leading-tight tracking-tight">
                Profile <span class="text-slate-500">Overview.</span>
            </h1>
            <p class="text-slate-400 text-lg font-medium mt-4 max-w-2xl">
                You are registered as a participant through group membership. Your profile and attendance are managed by your group representative.
            </p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-8 -mt-20 relative z-20">
        <div class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-2xl border border-slate-200 dark:border-gray-800 overflow-hidden">
            <div class="p-12">
                <div class="flex flex-col md:flex-row gap-10 items-center">
                    <!-- Identity Card -->
                    <div class="w-full md:w-1/3">
                        <div class="bg-slate-50 dark:bg-black rounded-[2.5rem] p-8 text-center border border-slate-100 dark:border-gray-800">
                            <div class="w-24 h-24 bg-white dark:bg-gray-800 rounded-3xl flex items-center justify-center text-3xl font-black text-slate-900 dark:text-white mx-auto mb-6 shadow-sm">
                                {{ $member->initials }}
                            </div>
                            <h2 class="text-xl font-black text-slate-900 dark:text-white truncate">{{ $member->full_name }}</h2>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">{{ $member->category_label }}</p>
                        </div>
                    </div>

                    <!-- Status Info -->
                    <div class="w-full md:w-2/3 space-y-6" id="member-status-container">
                        <div>
                            <h3 class="text-sm font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Registration Details</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-slate-50 dark:bg-gray-800/50 rounded-2xl border border-slate-100 dark:border-gray-700/50">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Affiliation</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $member->institution }}</p>
                                </div>
                                <div class="p-4 bg-slate-50 dark:bg-gray-800/50 rounded-2xl border border-slate-100 dark:border-gray-700/50">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Group Affiliation</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $group->group_name ?: 'Group ID: ' . $group->id }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-slate-900 dark:bg-white text-white dark:text-black rounded-3xl">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black uppercase tracking-widest opacity-60">Status</span>
                                @if($group->isVerified() || $group->payment_status === 'waived')
                                    <span class="px-3 py-1 bg-emerald-500 text-white rounded-full text-[10px] font-black uppercase tracking-widest">{{ $group->payment_status === 'waived' ? 'Exempted' : 'Active' }}</span>
                                @else
                                    <span class="px-3 py-1 bg-amber-500 text-white rounded-full text-[10px] font-black uppercase tracking-widest">Pending Payment</span>
                                @endif
                            </div>

                            <div class="mt-8">
                                @if($group->isVerified() || $group->payment_status === 'waived')
                                    <p class="text-xl font-black leading-tight text-white dark:text-black">Registration Confirmed!</p>
                                    <p class="text-sm opacity-70 mt-2 font-medium">Your registration through <strong>{{ $group->group_name }}</strong> is active. You can now access your digital badge below.</p>
                                    
                                    <div class="mt-10 group/badge relative">
                                        @include('components.badge-preview', ['user' => Auth::user(), 'qrToken' => Auth::user()->getOrCreateQrToken()])

                                    </div>
                                @else
                                    <p class="text-lg font-bold leading-tight">Awaiting group activation...</p>
                                    <p class="text-sm opacity-60 mt-2">Your registration is part of a group that is currently awaiting payment verification by the Finance Office.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Notice -->
                <div class="mt-12 pt-8 border-t border-slate-50 dark:border-gray-800">
                    <div class="flex gap-4 items-start bg-amber-50 dark:bg-amber-900/10 p-6 rounded-2xl border border-amber-100 dark:border-amber-900/20">
                        <svg class="w-6 h-6 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="text-xs text-amber-900 dark:text-amber-400 font-medium leading-relaxed">
                            <strong>Note:</strong> Individual group members do not have access to abstract submission or individual payment management. If you are a presenter, please ensure you have an individual registration or contact the support desk.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
