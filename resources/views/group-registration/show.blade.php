@extends('layouts.app')

@section('title', 'Group Registration Details')

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 relative font-sans">
    <!-- Header -->
    <div class="relative bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-900 py-10 md:py-12 lg:py-16 rounded-b-[2.5rem] md:rounded-b-[4rem] shadow-2xl overflow-hidden mb-6 md:mb-8">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.05]"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-black/20 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="space-y-2 md:space-y-3">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('group-registration.index') }}" class="text-teal-200 hover:text-white transition-colors flex items-center gap-1 text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Back to Groups
                        </a>
                    </div>
                    <h1 class="text-2xl md:text-3xl lg:text-4xl font-black text-white leading-tight tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        {{ $groupRegistration->group_name ?: 'Group #' . $groupRegistration->id }}
                    </h1>
                    <div class="flex items-center gap-4 text-teal-200 text-sm font-medium">
                        @if($groupRegistration->organization)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                {{ $groupRegistration->organization }}
                            </span>
                        @endif
                        <span class="opacity-40">•</span>
                        <span>{{ $groupRegistration->member_count }} members</span>
                    </div>
                </div>

                <div class="flex flex-col items-start md:items-end gap-2">
                    <p class="text-[10px] font-bold text-teal-300 uppercase tracking-[0.2em]">Payment Status</p>
                    <div class="scale-110 origin-left md:origin-right">
                        {!! $groupRegistration->getStatusBadge() !!}
                    </div>
                </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Members List -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Group Members ({{ $groupRegistration->member_count }})
                        </h2>
                        @if($groupRegistration->isPending())
                            <a href="{{ route('group-registration.edit', $groupRegistration) }}" class="inline-flex items-center gap-1 text-sm text-teal-600 hover:text-teal-700 font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit Members
                            </a>
                        @endif
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-gray-700">
                        @foreach($groupRegistration->members as $member)
                            <div class="p-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-gray-700/50 transition-colors">
                                <div class="w-10 h-10 {{ $member->checked_in ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 dark:bg-gray-700 text-slate-500' }} rounded-xl flex items-center justify-center font-bold text-sm">
                                    {{ $member->initials }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-900 dark:text-white truncate">{{ $member->full_name }}</p>
                                        {!! $member->getStatusBadge() !!}
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        <span>{{ $member->category_label }}</span>
                                        @if($member->institution)
                                            <span class="opacity-40">•</span>
                                            <span class="truncate">{{ $member->institution }}</span>
                                        @endif
                                        @if($member->email)
                                            <span class="opacity-40">•</span>
                                            <span class="truncate">{{ $member->email }}</span>
                                        @endif
                                        @if($member->student_id_path)
                                            <span class="opacity-40">•</span>
                                            <a href="{{ Storage::url($member->student_id_path) }}" target="_blank" class="text-teal-600 hover:text-teal-700 font-medium flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Student ID
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-right">
                                        <p class="font-bold text-slate-900 dark:text-white">{{ $member->formatted_fee }}</p>
                                        @if($groupRegistration->isVerified())
                                            <p class="text-xs text-slate-400 font-mono">{{ $member->qr_token }}</p>
                                        @endif
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <a href="{{ route('group-registration.member-invitation', [$groupRegistration, $member]) }}" class="w-8 h-8 bg-teal-50 hover:bg-teal-100 text-teal-700 rounded-lg flex items-center justify-center transition-colors" title="Download Invitation Letter">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </a>
                                        @if($groupRegistration->isVerified())
                                            <a href="{{ route('group-registration.print-badge', [$groupRegistration, $member]) }}" class="w-8 h-8 bg-slate-900 dark:bg-white text-white dark:text-black rounded-lg flex items-center justify-center hover:scale-110 transition-transform" title="Print Badge">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            </a>
                                            <button onclick="openEditMemberModal({{ json_encode($member) }})" class="w-8 h-8 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg flex items-center justify-center transition-colors" title="Edit Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-base md:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Group Oversight Panel
                        </h3>
                        @if($groupRegistration->isVerified())
                            <a href="{{ route('group-registration.bulk-print', $groupRegistration) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print All Badges
                            </a>
                        @endif
                    </div>

                    <div class="p-8">
                        @if($groupRegistration->isVerified())
                            <div class="flex flex-col md:flex-row items-center gap-6 p-6 bg-emerald-50 dark:bg-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-900/20">
                                <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center text-emerald-600">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="flex-1 text-center md:text-left">
                                    <h4 class="text-xl font-black text-slate-900 dark:text-white mb-1">Group Activated Successfully</h4>
                                    <p class="text-slate-600 dark:text-gray-400 text-sm">
                                        All members are now registered. As the group leader, you are responsible for managing their attendance and providing their identification badges.
                                    </p>
                                </div>
                            </div>

                            <!-- Important Advice -->
                            <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-100 dark:border-amber-900/20 flex gap-3">
                                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-xs text-amber-800 dark:text-amber-400 font-medium leading-relaxed">
                                    <strong>Notice:</strong> Group members do not have individual dashboards. All benefits including badge generation and attendance tracking are managed via this page by the group representative.
                                </p>
                            </div>
                        @elseif($groupRegistration->payment_status === 'submitted')
                            <div class="text-center py-10">
                                <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Payment Under Review</h4>
                                <p class="text-slate-500 dark:text-gray-400 max-w-sm mx-auto">
                                    Your group payment details have been sent to the finance team. All members will be activated once it is verified.
                                </p>
                                <a href="{{ route('payment.show') }}" class="mt-8 inline-flex px-8 py-3 bg-slate-100 dark:bg-gray-700 hover:bg-slate-200 dark:hover:bg-gray-600 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                                    View Payment
                                </a>
                            </div>
                        @else
                            @php
                                $user = auth()->user();
                                $isStudentPending = $user->student_status === 'yes' && $user->student_verification_status !== 'verified';
                                $rejectionNotes = $user->student_verification_notes;
                            @endphp

                            @if($isStudentPending)
                                <div class="text-center py-10 px-6">
                                    <div class="w-16 h-16 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center mx-auto mb-6 relative">
                                        <div class="absolute inset-0 rounded-2xl border-4 border-amber-500/20 border-t-amber-500 animate-[spin_3s_linear_infinite]"></div>
                                        <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </div>

                                    @if($user->student_verification_status === 'rejected')
                                        <h4 class="text-lg font-bold text-rose-600 mb-2 uppercase tracking-tight">Student ID Rejected</h4>
                                        <p class="text-slate-500 dark:text-gray-400 text-sm max-w-sm mx-auto mb-6">
                                            Your Student ID was rejected. Please update your profile to proceed with group payment.
                                        </p>
                                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 px-6 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all shadow-lg shadow-rose-200 dark:shadow-none">
                                            Update Profile
                                        </a>
                                    @else
                                        <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-2 uppercase tracking-tight">Verification Required</h4>
                                        <p class="text-slate-500 dark:text-gray-400 text-sm max-w-sm mx-auto">
                                            Your Student ID is currently being reviewed. Payment for your group will be enabled once your status is verified.
                                        </p>
                                        <div class="mt-8 inline-flex items-center gap-2 px-6 py-2 bg-slate-100 dark:bg-gray-700 rounded-xl text-slate-400 font-bold text-[10px] uppercase tracking-widest border border-slate-200 dark:border-gray-600">
                                            <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Review Pending
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="text-center py-10">
                                    <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center mx-auto mb-6 text-blue-600">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-2 text-sans uppercase tracking-[0.2em]">Pay for Your Group</h4>
                                    <p class="text-slate-500 dark:text-gray-400 max-w-sm mx-auto mb-8 font-sans">
                                        Pay {{ $groupRegistration->formatted_total }} by bank transfer or mobile money, then submit the transaction details for verification.
                                    </p>
                                    <a href="{{ route('payment.show') }}" class="inline-flex items-center gap-3 px-10 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-xl shadow-blue-500/20">
                                        Go to Payment
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <div class="lg:sticky lg:top-8 space-y-6">
                    <!-- Payment Summary Card -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white overflow-hidden relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-teal-600/20 to-transparent"></div>
                        <div class="relative z-10 space-y-4">
                            <p class="text-xs font-bold text-teal-300 uppercase tracking-wider">Total Amount</p>
                            <p class="text-3xl font-black tracking-tight" style="font-family: 'Outfit', sans-serif;">
                                {{ $groupRegistration->formatted_total }}
                            </p>
                            <div class="h-px bg-white/20"></div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-400">Members</span>
                                <span class="font-bold">{{ $groupRegistration->member_count }}</span>
                            </div>
                            @if($groupRegistration->isVerified())
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-400">Checked In</span>
                                    <span class="font-bold text-emerald-400">{{ $groupRegistration->checked_in_count }} / {{ $groupRegistration->member_count }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    @if($groupRegistration->isPending())
                        <div class="space-y-3">
                            <a href="{{ route('group-registration.edit', $groupRegistration) }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-gray-700 text-slate-700 dark:text-slate-300 rounded-xl text-sm font-bold transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit Group
                            </a>
                            <form action="{{ route('group-registration.destroy', $groupRegistration) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this group registration?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-400 rounded-xl text-sm font-bold transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete Group
                                </button>
                            </form>
                        </div>
                    @endif

                    <!-- Help -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-900/30">
                        <p class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wide mb-1">Need Help?</p>
                        <p class="text-sm text-blue-600 dark:text-blue-300">{{ config('conference.contact_email') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div id="editMemberModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeEditMemberModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form id="editMemberForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Edit Member Details</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="full_name" id="edit_full_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm p-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="edit_email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm p-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" name="phone" id="edit_phone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm p-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Institution</label>
                            <input type="text" name="institution" id="edit_institution" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm p-2 border">
                        </div>
                    </div>
                    <p class="text-xs text-amber-600 mt-4">Note: Financial details (Category & Fee) cannot be edited after payment.</p>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Changes
                    </button>
                    <button type="button" onclick="closeEditMemberModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openEditMemberModal(member) {
        document.getElementById('edit_full_name').value = member.full_name;
        document.getElementById('edit_email').value = member.email;
        document.getElementById('edit_phone').value = member.phone || '';
        document.getElementById('edit_institution').value = member.institution;

        const form = document.getElementById('editMemberForm');
        // Construct the URL: /group-registration/{group}/members/{member}
        // Since we are on the 'show' page, route('group-registration.update-member') needs parameters.
        // It's easier to build it if we have the base path.
        // Let's assume the current path is /group-registration/{id}.
        // The update path is /group-registration/{id}/members/{member_id}
        const currentPath = window.location.pathname;
        const actionUrl = `${currentPath}/members/${member.id}`;
        form.action = actionUrl;

        document.getElementById('editMemberModal').classList.remove('hidden');
    }

    function closeEditMemberModal() {
        document.getElementById('editMemberModal').classList.add('hidden');
    }

    // Optional: Submit via AJAX to avoid reload or handle errors nicely
    document.getElementById('editMemberForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        // Include _method for Laravel
        // formData.append('_method', 'PATCH');

        fetch(form.action, {
            method: 'POST', // POST with _method=PATCH
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success
                alert('Member updated successfully');
                window.location.reload();
            } else {
                alert('Error updating member: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An unexpected error occurred.');
        });
    });
</script>
@endsection
