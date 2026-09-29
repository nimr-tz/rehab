@extends('layouts.app')

@section('title', 'Group Registration Details')

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 relative font-sans">
    <!-- Header -->
    <div class="relative bg-gradient-to-br from-slate-800 via-slate-900 to-gray-900 py-12 rounded-b-[3rem] shadow-2xl overflow-hidden mb-8">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.03]"></div>
        
        <div class="max-w-7xl mx-auto px-6 sm:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('admin.group-registrations.index') }}" class="text-slate-400 hover:text-white transition-colors flex items-center gap-1 text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Back to Groups
                        </a>
                    </div>
                    <h1 class="text-3xl font-black text-white" style="font-family: 'Outfit', sans-serif;">
                        {{ $groupRegistration->group_name ?: 'Group #' . $groupRegistration->id }}
                    </h1>
                    <div class="flex items-center gap-4 text-slate-400 text-sm mt-2">
                        @if($groupRegistration->organization)
                            <span>{{ $groupRegistration->organization }}</span>
                            <span class="opacity-40">•</span>
                        @endif
                        <span>{{ $groupRegistration->member_count }} members</span>
                    </div>
                </div>
                
                <div class="scale-125">
                    {!! $groupRegistration->getStatusBadge() !!}
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-8 pb-12">
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4">
                <p class="text-emerald-700 dark:text-emerald-300 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                <p class="text-blue-700 dark:text-blue-300 font-medium">{{ session('info') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Leader Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 p-6">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-4">Group Leader</h3>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-teal-100 dark:bg-teal-900/40 rounded-xl flex items-center justify-center text-teal-600 font-bold text-lg">
                            {{ $groupRegistration->leader->initials ?? '??' }}
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $groupRegistration->leader->full_name ?? 'Unknown' }}</p>
                            <p class="text-sm text-slate-500">{{ $groupRegistration->leader->email ?? '' }}</p>
                            <p class="text-xs text-slate-400">{{ $groupRegistration->leader->phone ?? '' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Members List -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-gray-700">
                        <h3 class="font-bold text-slate-900 dark:text-white">Group Members ({{ $groupRegistration->member_count }})</h3>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-gray-700">
                        @foreach($groupRegistration->members as $member)
                            <div class="p-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-gray-700/50 transition-colors">
                                <div class="w-10 h-10 {{ $member->checked_in ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 dark:bg-gray-700 text-slate-500' }} rounded-lg flex items-center justify-center font-bold text-sm">
                                    {{ $member->initials }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $member->full_name }}</p>
                                        {!! $member->getStatusBadge() !!}
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        <span>{{ $member->category_label }}</span>
                                        @if($member->email)
                                            <span class="opacity-40">•</span>
                                            <span>{{ $member->email }}</span>
                                        @endif
                                        @if($member->institution)
                                            <span class="opacity-40">•</span>
                                            <span>{{ $member->institution }}</span>
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
                                <div class="text-right">
                                    <p class="text-xs text-slate-400 font-mono">{{ $member->qr_token }}</p>
                                </div>
                                @if($groupRegistration->isVerified() && !$member->checked_in)
                                    <form action="{{ route('admin.group-registrations.check-in-member', $member) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-medium transition-colors">
                                            Check In
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>


            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Summary Card -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white">
                    <p class="text-xs font-bold text-teal-300 uppercase tracking-wider mb-2">Group Logistics</p>
                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Total Members</span>
                            <span class="text-3xl font-black">{{ $groupRegistration->member_count }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-white/20 pt-4">
                            <span class="text-slate-400">Checked In</span>
                            <span class="text-2xl font-bold text-emerald-400">{{ $groupRegistration->checked_in_count }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                @if($groupRegistration->payment_status === 'submitted')
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-blue-800 dark:text-blue-200 text-sm">Review Required</p>
                                <p class="text-xs text-blue-600 dark:text-blue-400">The Finance Officer is responsible for verifying this payment.</p>
                            </div>
                        </div>
                    </div>
                @elseif($groupRegistration->isVerified())
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800 p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center text-emerald-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-emerald-800 dark:text-emerald-200">Payment Verified</p>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ $groupRegistration->payment_verified_at?->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                        @if($groupRegistration->verifier)
                            <p class="text-xs text-emerald-600 dark:text-emerald-400">Verified by: {{ $groupRegistration->verifier->full_name }}</p>
                        @endif
                    </div>
                @endif

                <!-- Timeline -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 p-6">
                    <h4 class="font-bold text-slate-900 dark:text-white mb-4">Timeline</h4>
                    <div class="space-y-4 text-sm">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mt-0.5">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">Group Created</p>
                                <p class="text-xs text-slate-400">{{ $groupRegistration->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                        
                        @if($groupRegistration->payment_submitted_at)
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mt-0.5">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">Payment Proof Submitted</p>
                                    <p class="text-xs text-slate-400">{{ $groupRegistration->payment_submitted_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                        @endif
                        
                        @if($groupRegistration->payment_verified_at)
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mt-0.5">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">Payment Verified</p>
                                    <p class="text-xs text-slate-400">{{ $groupRegistration->payment_verified_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
