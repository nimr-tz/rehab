@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-100">
            {{-- Status Icon --}}
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 backdrop-blur-md rounded-2xl mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-white uppercase tracking-tight">Assignment Confirmed</h2>
            </div>

            <div class="p-8 text-center space-y-6">
                <div class="space-y-2">
                    <p class="text-slate-600 font-medium">
                        {{ $message }}
                    </p>
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Session</p>
                        <p class="text-sm font-bold text-slate-900">{{ $session->name }}</p>
                        @if($session->start_time)
                            <p class="text-[10px] text-indigo-600 font-bold mt-1">
                                {{ $session->getPrimaryDayLabel() }} • {{ $session->start_time->format('H:i') }} - {{ $session->end_time->format('H:i') }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-50">
                    <p class="text-xs text-slate-500 mb-6">
                        You can view all the abstracts for this session and prepare ahead of time by visiting your dashboard.
                    </p>
                    <a href="{{ route('sessions.my') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-slate-900 hover:bg-black text-white font-bold rounded-2xl transition-all shadow-xl shadow-slate-200 group">
                        Go to My Sessions
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bg-slate-50 px-8 py-4 text-center">
                <p class="text-[10px] text-slate-400 font-medium uppercase tracking-tighter">{{ config('conference.short_name') }} {{ config('conference.year') }} • Scientific Committee</p>
            </div>
        </div>
    </div>
</div>
@endsection
