@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap');
    .logs-body { font-family: 'Outfit', sans-serif; }
    .mono { font-family: 'JetBrains Mono', monospace; }
</style>

<div class="min-h-screen bg-[#f1f5f9] dark:bg-[#020617] pb-32 logs-body">

    {{-- ═══ Header ═══ --}}
    <div class="relative bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 py-10 md:py-14 rounded-b-[3rem] shadow-2xl overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.03]"></div>

        <div class="max-w-5xl mx-auto px-6 sm:px-8 lg:px-10 relative z-10">
            <a href="{{ route('admin.system-logs.index') }}" class="inline-flex items-center gap-2 text-slate-400/50 hover:text-slate-300 transition-colors text-xs font-bold uppercase tracking-widest mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Logs
            </a>
            <h1 class="text-2xl md:text-4xl font-black text-white leading-none tracking-tight">
                Log <span class="text-rose-400">#{{ $systemLog->id }}</span>
            </h1>
            <p class="text-sm text-slate-400 mt-2 font-medium">{{ $systemLog->created_at->format('F d, Y \a\t H:i:s') }} · {{ $systemLog->created_at->diffForHumans() }}</p>
        </div>
    </div>

    {{-- ═══ Content ═══ --}}
    <div class="max-w-5xl mx-auto px-6 sm:px-8 lg:px-10 mt-10 space-y-6">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-500/30 rounded-2xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <p class="text-sm font-bold text-emerald-700">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Log Summary Card --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-800 {{ !$systemLog->resolved ? 'border-l-4 border-l-rose-400' : '' }}">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Level</p>
                    {!! $systemLog->level_badge !!}
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Channel</p>
                    {!! $systemLog->channel_badge !!}
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Source</p>
                    <span class="mono text-xs font-bold text-slate-600 dark:text-slate-300">{{ $systemLog->source ?? '—' }}</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Status</p>
                    @if($systemLog->resolved)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 uppercase tracking-wider">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Resolved
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            Open
                        </span>
                    @endif
                </div>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-6">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Error Message</p>
                <div class="bg-rose-50/50 dark:bg-rose-900/10 rounded-xl p-5 border border-rose-100 dark:border-rose-500/10">
                    <p class="text-sm font-bold text-rose-800 dark:text-rose-300 leading-relaxed">{{ $systemLog->message }}</p>
                </div>
            </div>

            @if($systemLog->user)
                <div class="border-t border-slate-100 dark:border-slate-800 pt-6 mt-6">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Related User</p>
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-xs font-black text-indigo-600 dark:text-indigo-400">
                            {{ $systemLog->user->initials ?? 'U' }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-700 dark:text-white">{{ $systemLog->user->full_name ?? 'User #' . $systemLog->user_id }}</p>
                            <p class="text-xs text-slate-400">{{ $systemLog->user->email ?? '' }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @php
                $capturedNotificationEmail = $systemLog->user_notification_email
                    ?? ($systemLog->user->email ?? null)
                    ?? data_get($systemLog->context, 'user.email')
                    ?? data_get($systemLog->context, 'request.input.contact_email')
                    ?? data_get($systemLog->context, 'request.input.notification_email')
                    ?? data_get($systemLog->context, 'request.input.email');
            @endphp

            @if($capturedNotificationEmail)
                <div class="border-t border-slate-100 dark:border-slate-800 pt-6 mt-6">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Captured User Email</p>
                    <span class="mono text-xs text-slate-500">{{ $capturedNotificationEmail }}</span>
                </div>
            @endif

            @if($systemLog->ip_address)
                <div class="border-t border-slate-100 dark:border-slate-800 pt-6 mt-6">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">IP Address</p>
                    <span class="mono text-xs text-slate-500">{{ $systemLog->ip_address }}</span>
                </div>
            @endif
        </div>

        {{-- Context Data --}}
        @if($systemLog->context && count($systemLog->context) > 0)
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-800">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Context / Payload Data</h3>
                <div class="bg-slate-950 rounded-xl p-6 overflow-x-auto">
                    <pre class="mono text-xs text-emerald-400 leading-relaxed whitespace-pre-wrap">{{ json_encode($systemLog->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        @endif

        {{-- Resolution Info --}}
        @if($systemLog->resolved)
            <div class="bg-emerald-50/50 dark:bg-emerald-900/10 rounded-2xl p-8 border border-emerald-200 dark:border-emerald-500/20">
                <h3 class="text-[10px] font-black text-emerald-600/60 uppercase tracking-widest mb-4">Resolution Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-[10px] font-black text-emerald-600/40 uppercase tracking-widest mb-1">Resolved By</p>
                        <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300">{{ $systemLog->resolver->full_name ?? 'Admin' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-emerald-600/40 uppercase tracking-widest mb-1">Resolved At</p>
                        <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300">{{ $systemLog->resolved_at?->format('M d, Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-emerald-600/40 uppercase tracking-widest mb-1">Notes</p>
                        <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ $systemLog->resolution_notes ?? '—' }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                    <div>
                        <p class="text-[10px] font-black text-emerald-600/40 uppercase tracking-widest mb-1">User Notification</p>
                        <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300">{{ ucfirst(str_replace('_', ' ', $systemLog->user_notification_status ?? 'not_requested')) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-emerald-600/40 uppercase tracking-widest mb-1">Notification Email</p>
                        <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ $systemLog->user_notification_email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-emerald-600/40 uppercase tracking-widest mb-1">Action Required</p>
                        <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ $systemLog->user_action_required ? 'Yes' : 'No' }}</p>
                    </div>
                </div>
                @if($systemLog->resolution_summary)
                    <div class="mt-6">
                        <p class="text-[10px] font-black text-emerald-600/40 uppercase tracking-widest mb-1">User-facing Summary</p>
                        <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ $systemLog->resolution_summary }}</p>
                    </div>
                @endif
                @if($systemLog->user_action_details)
                    <div class="mt-4">
                        <p class="text-[10px] font-black text-emerald-600/40 uppercase tracking-widest mb-1">Action Details</p>
                        <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ $systemLog->user_action_details }}</p>
                    </div>
                @endif
                @if($systemLog->user_notification_error)
                    <div class="mt-4">
                        <p class="text-[10px] font-black text-rose-600/50 uppercase tracking-widest mb-1">Notification Error</p>
                        <p class="text-sm text-rose-700 dark:text-rose-300">{{ $systemLog->user_notification_error }}</p>
                    </div>
                @endif
            </div>
        @else
            {{-- Resolve Form --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-800">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Resolve This Issue</h3>
                <form method="POST" action="{{ route('admin.system-logs.resolve', $systemLog) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-4">
                        <textarea name="resolution_notes" rows="3" placeholder="Add resolution notes (optional)..." class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all resize-none text-slate-700 dark:text-white placeholder-slate-400"></textarea>
                    </div>
                    <div class="mb-4">
                        <textarea name="resolution_summary" rows="3" placeholder="Short user-facing resolution summary..." class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all resize-none text-slate-700 dark:text-white placeholder-slate-400"></textarea>
                    </div>
                    <div class="space-y-4 mb-6">
                        <label class="flex items-start gap-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="notify_user" value="1" class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>Notify the affected user after resolution.</span>
                        </label>
                        <input type="email" name="notification_email" value="{{ $capturedNotificationEmail }}" placeholder="User notification email" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all text-slate-700 dark:text-white placeholder-slate-400">
                        <label class="flex items-start gap-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="action_required" value="1" class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>User must take a follow-up action.</span>
                        </label>
                        <textarea name="action_details" rows="2" placeholder="Explain any action the user needs to take..." class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all resize-none text-slate-700 dark:text-white placeholder-slate-400"></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white font-black text-xs uppercase tracking-wider rounded-xl hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Mark as Resolved
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
