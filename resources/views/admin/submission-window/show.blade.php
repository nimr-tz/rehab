@extends('layouts.app')

@section('title', 'Submission Window')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-950 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Submission Control</p>
                    <h1 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">Submission Window</h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300 max-w-3xl">
                        The system closes submissions automatically after the deadline. Use a timed override here when you need to reopen the window briefly.
                    </p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                    Back to Dashboard
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <div class="text-xs font-black uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Deadline</div>
                <div class="mt-3 text-2xl font-black text-slate-900 dark:text-white">{{ $status['deadline']->format('M d, Y') }}</div>
                <div class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $status['deadline']->format('H:i') }} EAT</div>
            </div>
            <div class="rounded-3xl border {{ $status['is_open'] ? 'border-emerald-200 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/20' : 'border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/20' }} p-5">
                <div class="text-xs font-black uppercase tracking-[0.22em] {{ $status['is_open'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">Current Status</div>
                <div class="mt-3 text-2xl font-black {{ $status['is_open'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                    {{ $status['is_open'] ? 'Open' : 'Closed' }}
                </div>
                <div class="mt-1 text-sm {{ $status['is_open'] ? 'text-emerald-700/80 dark:text-emerald-300/80' : 'text-rose-700/80 dark:text-rose-300/80' }}">
                    {{ $status['override_active'] ? 'Temporary admin override is active.' : 'Following the normal deadline rule.' }}
                </div>
            </div>
            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <div class="text-xs font-black uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Override Until</div>
                <div class="mt-3 text-2xl font-black text-slate-900 dark:text-white">
                    {{ $status['override_until'] ? $status['override_until']->format('M d, Y') : 'None' }}
                </div>
                <div class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    {{ $status['override_until'] ? $status['override_until']->format('H:i') . ' EAT' : 'No active override window' }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6">
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Open Temporary Window</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Reopen submissions briefly for late authors without changing the main deadline.
                </p>

                @if(!$status['supports_overrides'])
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                        Override storage is not available until the latest database migration is run.
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.submission-window.open') }}" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="duration_unit" value="days">
                        <div>
                            <label for="duration_value" class="block text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400 mb-2">Open For (Days)</label>
                            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_140px] gap-3">
                                <input id="duration_value" name="duration_value" type="number" min="1" max="3" value="{{ old('duration_value', 1) }}" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                <div class="inline-flex items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-sm font-black text-slate-600 dark:text-slate-300">
                                    Days
                                </div>
                            </div>
                            @error('duration_value')
                                <p class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Use a day-based override only. The window can be opened for 1 to 3 days.</p>
                        </div>
                        <div class="flex gap-3 flex-wrap">
                            <button type="submit" class="inline-flex items-center px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow-sm">
                                Open Timed Window
                            </button>
                            <button type="submit" name="preset" value="1:days" class="inline-flex items-center px-4 py-3 rounded-2xl border border-emerald-200 bg-emerald-50 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-300">
                                1 Day
                            </button>
                            <button type="submit" name="preset" value="2:days" class="inline-flex items-center px-4 py-3 rounded-2xl border border-emerald-200 bg-emerald-50 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-300">
                                2 Days
                            </button>
                            <button type="submit" name="preset" value="3:days" class="inline-flex items-center px-4 py-3 rounded-2xl border border-emerald-200 bg-emerald-50 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-300">
                                3 Days
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6">
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Close Override</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    End the temporary window immediately and return the system to the normal deadline state.
                </p>

                <form method="POST" action="{{ route('admin.submission-window.close') }}" class="mt-5">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-5 py-3 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-semibold shadow-sm">
                        Close Override Now
                    </button>
                </form>

                <div class="mt-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 p-4 text-sm text-slate-600 dark:text-slate-300">
                    Applies to:
                    <div class="mt-2 font-medium text-slate-900 dark:text-white">Abstract submissions.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
