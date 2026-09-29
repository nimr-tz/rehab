@extends('layouts.app')

@section('title', 'Session Role Application')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-gray-950 pb-20">
    <div class="bg-white dark:bg-gray-900 border-b border-slate-200 dark:border-gray-800 py-12">
        <div class="max-w-4xl mx-auto px-6 sm:px-8">
            <a href="{{ route('user.dashboard') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white mb-5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Dashboard
            </a>
            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-indigo-600 dark:text-indigo-300 mb-3">{{ config('conference.short_name') }} {{ config('conference.year') }}</p>
            <h1 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight" style="font-family: 'Outfit', sans-serif;">Session Chair and Rapporteur Application</h1>
            <p class="text-slate-600 dark:text-slate-300 mt-4 max-w-2xl leading-relaxed">
                Apply to support one conference session as a Session Chair or Rapporteur. Applicants should be sure they can attend the conference.
            </p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-6 sm:px-8 mt-8">
        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-900/20 p-5">
                <ul class="text-sm text-rose-700 dark:text-rose-300 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>- {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!$isOpen)
            <div class="rounded-3xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-8 text-amber-800 dark:text-amber-200">
                <h2 class="text-xl font-black mb-2">Applications Closed</h2>
                <p class="text-sm font-medium">Applications closed on {{ $deadline->format('F d, Y') }}.</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <form action="{{ route('session-role-applications.store') }}" method="POST" class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-3xl border border-slate-200 dark:border-gray-800 shadow-sm p-8 space-y-8">
                    @csrf

                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-3">Preferred Role</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($roles as $value => $label)
                                <label class="cursor-pointer rounded-2xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-950 p-4 flex items-center gap-3">
                                    <input type="radio" name="role_requested" value="{{ $value }}" required class="text-indigo-600 focus:ring-indigo-500" {{ old('role_requested', $application->role_requested ?? '') === $value ? 'checked' : '' }}>
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="preferred_theme" class="block text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-3">Preferred Session Theme</label>
                        <select id="preferred_theme" name="preferred_theme" required class="w-full rounded-2xl border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-950 text-slate-900 dark:text-white px-5 py-4 text-sm font-bold">
                            <option value="">Select a theme...</option>
                            @foreach($themes as $theme)
                                <option value="{{ $theme }}" {{ old('preferred_theme', $application->preferred_theme ?? '') === $theme ? 'selected' : '' }}>{{ $theme }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="block text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-3">Optional Note</label>
                        <textarea id="notes" name="notes" rows="4" maxlength="1000" class="w-full rounded-2xl border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-950 text-slate-900 dark:text-white px-5 py-4 text-sm" placeholder="Share relevant experience, availability, or language preference.">{{ old('notes', $application->notes ?? '') }}</textarea>
                    </div>

                    <label class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-5 flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="attendance_confirmed" value="1" required class="mt-1 rounded border-amber-300 text-indigo-600 focus:ring-indigo-500" {{ old('attendance_confirmed', $application->attendance_confirmed ?? false) ? 'checked' : '' }}>
                        <span class="text-sm font-bold text-amber-800 dark:text-amber-200">I confirm that I am sure I can attend {{ config('conference.short_name') }} {{ config('conference.year') }} if shortlisted.</span>
                    </label>

                    <button type="submit" class="w-full rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-4 text-xs font-black uppercase tracking-widest transition-colors shadow-lg shadow-indigo-600/20">
                        {{ $application ? 'Update Application' : 'Submit Application' }}
                    </button>
                </form>

                <aside class="space-y-5">
                    <div class="rounded-3xl bg-slate-900 text-white p-7">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-indigo-200 mb-3">Deadline</p>
                        <p class="text-3xl font-black">{{ $deadline->format('M d') }}</p>
                        <p class="text-sm text-slate-300 mt-2">{{ $deadline->format('Y') }}</p>
                    </div>
                    <div class="rounded-3xl bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-800 p-7">
                        <h2 class="text-sm font-black uppercase tracking-widest text-slate-900 dark:text-white mb-4">Training</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Shortlisted applicants will attend a virtual training three days before the conference.</p>
                    </div>
                    @if($application)
                        <div class="rounded-3xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-7">
                            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-300 mb-2">Current Status</p>
                            <p class="text-lg font-black text-emerald-900 dark:text-emerald-100">{{ ucfirst(str_replace('_', ' ', $application->status)) }}</p>
                        </div>
                    @endif
                </aside>
            </div>
        @endif
    </div>
</div>
@endsection
