@extends('layouts.app')

@section('title', 'Feedback Not Available | ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="min-h-[80vh] flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-slate-900">
    <!-- Decorative Background Elements -->
    <div class="fixed top-20 left-10 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl"></div>
    <div class="fixed bottom-20 right-10 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl"></div>

    <div class="max-w-2xl w-full relative z-10 text-center">
        <!-- Icon/Visual -->
        <div class="mb-12 relative inline-flex">
            <div class="w-24 h-24 bg-white dark:bg-slate-800 rounded-3xl shadow-2xl flex items-center justify-center text-5xl transform -rotate-12 animate-float">
                🕒
            </div>
            <div class="absolute -top-4 -right-4 w-12 h-12 bg-indigo-600 rounded-2xl shadow-xl flex items-center justify-center text-white text-xl animate-pulse">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <!-- Content Card -->
        <div class="bg-white dark:bg-slate-800 rounded-[3rem] p-10 md:p-16 shadow-[0_40px_100px_-20px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-slate-700/50">
            <h1 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white mb-6 tracking-tight">
                Not Quite <span class="text-indigo-600">Time Yet.</span>
            </h1>

            <p class="text-lg text-slate-500 dark:text-slate-400 font-medium leading-relaxed mb-10 max-w-lg mx-auto">
                Thank you for your enthusiasm! To ensure you can provide a complete evaluation of {{ config('conference.short_name') }} {{ config('conference.year') }}, the feedback portal opens on the <span class="text-indigo-600 font-bold">last day</span> of the conference.
            </p>

            <div class="inline-flex items-center gap-4 px-6 py-3 bg-slate-50 dark:bg-slate-900 rounded-2xl mb-12 border border-slate-100 dark:border-slate-800">
                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Opening On:</span>
                <span class="text-md font-bold text-slate-900 dark:text-white">{{ $endDate }}</span>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-indigo-200 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Return to Dashboard
                </a>
                <a href="{{ route('home') }}" class="px-8 py-4 bg-white dark:bg-slate-700 text-slate-700 dark:text-white rounded-2xl font-black text-sm uppercase tracking-widest border border-slate-200 dark:border-slate-600 hover:bg-slate-50 transition-all">
                    Public Home
                </a>
            </div>
        </div>

        <p class="mt-12 text-slate-400 dark:text-slate-500 text-sm font-bold uppercase tracking-widest">
            © 2026 {{ config('conference.host_short') }} • {{ config('conference.short_name') }} Portal
        </p>
    </div>
</div>

<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(-12deg); }
        50% { transform: translateY(-20px) rotate(-8deg); }
    }
    .animate-float {
        animation: float 6s ease-in-out infinite;
    }
</style>
@endsection
