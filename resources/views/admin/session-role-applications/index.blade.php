@extends('layouts.app')

@section('title', 'Session Chair and Rapporteur Applications')

@section('content')
@php
    $statusStyles = [
        'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        'shortlisted' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        'not_selected' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    ];
@endphp

<div class="min-h-screen bg-slate-50 dark:bg-gray-950">
    <div class="max-w-[1600px] mx-auto px-6 py-10">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-10">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-indigo-600 dark:text-indigo-300 mb-3">Scientific Programme Support</p>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight text-slate-900 dark:text-white">Chair and Rapporteur Pool</h1>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400 max-w-3xl">
                    Manage the Excel-approved chair and rapporteur pool, verify each live account once, then assign sessions from the saved pool without uploading again.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.session-role-applications.import-pool') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-indigo-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Manage Imported Pool
                </a>
                <a href="{{ route('admin.session-role-applications.export', request()->query()) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-emerald-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download CSV
                </a>
                <a href="{{ route('admin.conference-program.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950">
                    Scientific Program
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-8 rounded-3xl border border-indigo-200 bg-indigo-50 p-6 dark:border-indigo-900/50 dark:bg-indigo-950/30">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-indigo-700 dark:text-indigo-300">Saved Excel Pool</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($stats['imported_pool']) }} imported people</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        {{ number_format($stats['verified_imported_pool']) }} verified account link(s), {{ number_format($stats['unmatched_imported_pool']) }} still unmatched.
                        @if($latestImport)
                            Last updated {{ $latestImport->updated_at->format('M d, Y H:i') }}.
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.session-role-applications.import-pool') }}"
                       class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-indigo-700">
                        Open Saved Pool
                    </a>
                    <form method="POST" action="{{ route('admin.session-role-applications.import-pool.auto-assign') }}">
                        @csrf
                        <button class="rounded-2xl bg-emerald-600 px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-emerald-700">
                            Auto Assign Current Program
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($stats['total']) }}</p>
                <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-500">Total</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-900/50 dark:bg-slate-900">
                <p class="text-2xl font-black text-amber-600">{{ number_format($stats['pending']) }}</p>
                <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-500">Pending</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-900/50 dark:bg-slate-900">
                <p class="text-2xl font-black text-emerald-600">{{ number_format($stats['shortlisted']) }}</p>
                <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-500">Shortlisted</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-2xl font-black text-slate-600 dark:text-slate-300">{{ number_format($stats['not_selected']) }}</p>
                <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-500">Not Selected</p>
            </div>
            <div class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm dark:border-indigo-900/50 dark:bg-slate-900">
                <p class="text-2xl font-black text-indigo-600">{{ number_format($stats['chairs']) }}</p>
                <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-500">Chairs</p>
            </div>
            <div class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm dark:border-blue-900/50 dark:bg-slate-900">
                <p class="text-2xl font-black text-blue-600">{{ number_format($stats['rapporteurs']) }}</p>
                <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-500">Rapporteurs</p>
            </div>
        </div>

        <form method="GET" class="mb-8 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email, affiliation..."
                       class="rounded-2xl border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white xl:col-span-2">
                <select name="role" class="rounded-2xl border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">All roles</option>
                    @foreach($roles as $value => $label)
                        <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-2xl border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
                <button class="rounded-2xl bg-indigo-600 px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-indigo-700">Filter</button>
            </div>
            <div class="mt-3">
                <select name="theme" class="w-full rounded-2xl border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">All preferred themes</option>
                    @foreach($themes as $theme)
                        <option value="{{ $theme }}" {{ request('theme') === $theme ? 'selected' : '' }}>{{ $theme }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="space-y-5">
            @forelse($applications as $application)
                @php($user = $application->user)
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr_320px] xl:items-start">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $application->role_requested === \App\Models\SessionRoleApplication::ROLE_CHAIR ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' }}">
                                    {{ $application->role_label }}
                                </span>
                                <span class="inline-flex rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $statusStyles[$application->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                                </span>
                                @if($application->attendance_confirmed)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">Attendance Confirmed</span>
                                @endif
                            </div>

                            <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ $user->full_name ?? 'Unknown applicant' }}</h2>
                            <div class="mt-2 grid gap-1 text-sm text-slate-500 dark:text-slate-400">
                                <p>{{ $user->email ?? 'No email recorded' }}</p>
                                @if($user?->phone)<p>{{ $user->phone }}</p>@endif
                                @if($user?->affiliation || $user?->institute)
                                    <p>{{ $user->affiliation ?: $user->institute }}</p>
                                @endif
                            </div>

                            @if($application->notes)
                                <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-600 dark:bg-slate-950 dark:text-slate-300">
                                    {{ $application->notes }}
                                </div>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Preferred Theme</p>
                            <p class="mt-2 text-sm font-bold text-slate-900 dark:text-white">{{ $application->preferred_theme }}</p>
                            <div class="mt-5 grid grid-cols-2 gap-4 text-xs text-slate-500 dark:text-slate-400">
                                <div>
                                    <p class="font-black uppercase tracking-widest text-slate-400">Submitted</p>
                                    <p class="mt-1 font-semibold">{{ $application->created_at->format('M d, Y H:i') }}</p>
                                </div>
                                <div>
                                    <p class="font-black uppercase tracking-widest text-slate-400">Reviewed</p>
                                    <p class="mt-1 font-semibold">{{ $application->reviewed_at ? $application->reviewed_at->format('M d, Y H:i') : 'Not yet' }}</p>
                                </div>
                            </div>
                            @if($application->reviewer)
                                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">Reviewed by <span class="font-bold">{{ $application->reviewer->full_name }}</span></p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.session-role-applications.update-status', $application) }}" class="rounded-2xl border border-slate-100 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950">
                            @csrf
                            @method('PATCH')
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Review Decision</label>
                            <select name="status" class="w-full rounded-2xl border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ $application->status === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                            <button class="mt-3 w-full rounded-2xl bg-slate-900 px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950">
                                Save Decision
                            </button>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <button name="status" value="shortlisted" class="rounded-xl bg-emerald-600 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-white hover:bg-emerald-700">
                                    Shortlist
                                </button>
                                <button name="status" value="not_selected" class="rounded-xl bg-slate-200 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-slate-700 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200">
                                    Not Selected
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white p-16 text-center dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-lg font-black text-slate-900 dark:text-white">No applications found</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Try clearing the filters or wait for participants to submit applications.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $applications->links() }}
        </div>
    </div>
</div>
@endsection
