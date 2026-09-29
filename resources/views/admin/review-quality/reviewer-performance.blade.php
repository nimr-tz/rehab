@extends('layouts.app')

@section('title', 'Reviewer Queue Detail')

@push('styles')
<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    }
    .dark .glass-card {
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    }
    .assignment-row:hover {
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }
    .status-badge-completed {
        background: linear-gradient(135deg, #10b981, #059669);
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans pb-20">
    <div class="max-w-[1400px] mx-auto px-6 py-10">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.review-quality.dashboard') }}" class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 mb-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Reviewer Queue Detail</span>
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ $reviewer->full_name ?? $reviewer->name }}</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">{{ $reviewer->email }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6 mb-10">
            <div class="glass-card rounded-[2rem] p-8 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-all"></div>
                <p class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-4">Current Portfolio</p>
                <div class="flex items-end gap-3">
                    <span class="text-5xl font-black text-slate-900 dark:text-white">{{ $workloadStats['total_assigned'] }}</span>
                    <span class="text-sm font-bold text-indigo-500 mb-2">Abstracts</span>
                </div>
            </div>

            <div class="glass-card rounded-[2rem] p-8 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
                <p class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.2em] mb-4">Submitted</p>
                <div class="flex items-end gap-3">
                    <span class="text-5xl font-black text-slate-900 dark:text-white">{{ $workloadStats['total_completed'] }}</span>
                    <span class="text-sm font-bold text-emerald-500 mb-2">This Portfolio</span>
                </div>
            </div>

            <div class="glass-card rounded-[2rem] p-8 relative overflow-hidden group border-amber-100 dark:border-amber-900/30">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all"></div>
                <p class="text-[10px] font-black text-amber-500 uppercase tracking-[0.2em] mb-4">Pending Active</p>
                <div class="flex items-end gap-3">
                    <span class="text-5xl font-black text-slate-900 dark:text-white">{{ $workloadStats['total_pending'] }}</span>
                    <span class="text-sm font-bold text-amber-500 mb-2">Reviews</span>
                </div>
            </div>

            <div class="glass-card rounded-[2rem] p-8 relative overflow-hidden group border-rose-100 dark:border-rose-900/30">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/10 rounded-full blur-2xl group-hover:bg-rose-500/20 transition-all"></div>
                <p class="text-[10px] font-black text-rose-500 uppercase tracking-[0.2em] mb-4">Reminder Due</p>
                <div class="flex items-end gap-3">
                    <span class="text-5xl font-black {{ $workloadStats['needs_reminder_count'] > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">{{ $workloadStats['needs_reminder_count'] }}</span>
                    <span class="text-sm font-bold text-rose-500 mb-2">3+ Days</span>
                </div>
            </div>

            <div class="glass-card rounded-[2rem] p-8 relative overflow-hidden group border-rose-100 dark:border-rose-900/30">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/10 rounded-full blur-2xl group-hover:bg-rose-500/20 transition-all"></div>
                <p class="text-[10px] font-black text-rose-500 uppercase tracking-[0.2em] mb-4">Overdue</p>
                <div class="flex items-end gap-3">
                    <span class="text-5xl font-black {{ $workloadStats['overdue_count'] > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">{{ $workloadStats['overdue_count'] }}</span>
                    <span class="text-sm font-bold text-rose-500 mb-2">4+ Days</span>
                </div>
            </div>
        </div>

        @if($fullMetrics)
        <div class="mb-12">
            <h2 class="text-xl font-black text-slate-800 dark:text-slate-200 mb-2 flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                Historical Review Quality
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">These cards summarize this reviewer&apos;s submitted review history. The live queue below follows the 3-day reminder and 4-day overdue rule.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="glass-card rounded-[2rem] p-8">
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em]">Quality Integrity</p>
                        <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="relative w-24 h-24 flex items-center justify-center">
                            <svg class="w-full h-full transform -rotate-90">
                                <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="8" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                                <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="8" fill="transparent" class="text-indigo-600" stroke-dasharray="{{ 2 * pi() * 40 }}" stroke-dashoffset="{{ 2 * pi() * 40 * (1 - ($fullMetrics['quality_metrics']['quality_score'] / 100)) }}" stroke-linecap="round" />
                            </svg>
                            <span class="absolute text-xl font-black text-slate-800 dark:text-white">{{ round($fullMetrics['quality_metrics']['quality_score']) }}%</span>
                        </div>
                        <div class="flex-1 space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500">Short Comments</span>
                                <span class="font-bold {{ $fullMetrics['quality_metrics']['short_comments'] > 0 ? 'text-rose-500' : 'text-emerald-500' }}">{{ $fullMetrics['quality_metrics']['short_comments'] }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500">Missing Criteria</span>
                                <span class="font-bold {{ $fullMetrics['quality_metrics']['missing_criteria'] > 0 ? 'text-rose-500' : 'text-emerald-500' }}">{{ $fullMetrics['quality_metrics']['missing_criteria'] }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500">Extreme Scores</span>
                                <span class="font-bold text-amber-500">{{ $fullMetrics['quality_metrics']['extreme_scores'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-[2rem] p-8">
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.2em]">Peer Consistency</p>
                        <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="relative w-24 h-24 flex items-center justify-center">
                            <svg class="w-full h-full transform -rotate-90">
                                <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="8" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                                <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="8" fill="transparent" class="text-emerald-500" stroke-dasharray="{{ 2 * pi() * 40 }}" stroke-dashoffset="{{ 2 * pi() * 40 * (1 - ($fullMetrics['consistency_metrics']['average_consistency_score'] / 100)) }}" stroke-linecap="round" />
                            </svg>
                            <span class="absolute text-xl font-black text-slate-800 dark:text-white">{{ round($fullMetrics['consistency_metrics']['average_consistency_score']) }}%</span>
                        </div>
                        <div class="flex-1 space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500">Avg Diff</span>
                                <span class="font-bold text-slate-800 dark:text-white">{{ $fullMetrics['consistency_metrics']['average_score_difference'] }}%</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500">Conflicts</span>
                                <span class="font-bold {{ $fullMetrics['consistency_metrics']['high_disagreement_reviews'] > 0 ? 'text-rose-500' : 'text-emerald-500' }}">{{ $fullMetrics['consistency_metrics']['high_disagreement_reviews'] }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500">Comparisons</span>
                                <span class="font-bold text-slate-800 dark:text-white">{{ $fullMetrics['consistency_metrics']['consistency_comparisons'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-[2rem] p-8">
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-[10px] font-black text-violet-500 uppercase tracking-[0.2em]">Engagement Depth</p>
                        <div class="w-10 h-10 rounded-full bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center text-violet-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        </div>
                    </div>
                    <div class="space-y-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500">Avg Comment Length</span>
                            <span class="text-lg font-black text-slate-800 dark:text-white">{{ round($fullMetrics['basic_stats']['average_comment_length']) }} <small class="text-[10px] uppercase text-slate-400">chars</small></span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-violet-500" style="width: {{ min(100, ($fullMetrics['basic_stats']['average_comment_length'] / 200) * 100) }}%"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Within 4 Days</p>
                                <p class="text-sm font-black text-emerald-500">{{ round($fullMetrics['timeliness_metrics']['timeliness_score']) }}%</p>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Avg Time</p>
                                <p class="text-sm font-black text-indigo-500">{{ $fullMetrics['timeliness_metrics']['average_review_time_hours'] }}h</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 gap-8">
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Reviewer Portfolio Queue</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">This table shows the reviewer&apos;s full current-round portfolio. Pending live items load first, then submitted reviews.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-bold text-slate-400">{{ $assignments->count() }} current-round records</span>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-400">Sort by:</span>
                            <select onchange="window.location.href = updateSort(this.value)" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-400 px-3 py-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="assigned_at" {{ $sortBy === 'assigned_at' ? 'selected' : '' }}>Date Assigned</option>
                                <option value="status" {{ $sortBy === 'status' ? 'selected' : '' }}>Status</option>
                                <option value="days" {{ $sortBy === 'days' ? 'selected' : '' }}>Days</option>
                                <option value="score" {{ $sortBy === 'score' ? 'selected' : '' }}>Score</option>
                                <option value="title" {{ $sortBy === 'title' ? 'selected' : '' }}>Title</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800">
                                    <th class="py-6 px-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Abstract Details</th>
                                    <th class="py-6 px-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                                    <th class="py-6 px-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Days</th>
                                    <th class="py-6 px-8 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                                @forelse($assignments as $assignment)
                                    @php
                                        $abstract = $assignment->abstractSubmission;
                                        $displayAssignedAt = $assignment->assigned_at ?? $abstract->assigned_at ?? $assignment->created_at ?? $abstract->created_at;
                                        $minutesSinceAssigned = $displayAssignedAt ? (int) $displayAssignedAt->diffInMinutes(now()) : 0;
                                        $ageLabel = $minutesSinceAssigned < 1
                                            ? '<1m'
                                            : ($minutesSinceAssigned < 60
                                                ? $minutesSinceAssigned . 'm'
                                                : ($minutesSinceAssigned < 1440
                                                    ? (int) $displayAssignedAt->diffInHours(now()) . 'h'
                                                    : (int) $displayAssignedAt->diffInDays(now()) . 'd'));
                                        $daysSinceAssigned = $displayAssignedAt ? (int) $displayAssignedAt->diffInDays(now()) : 0;
                                        $needsReminder = $assignment->status === 'draft' && $daysSinceAssigned >= 3;
                                        $isOverdue = $assignment->status === 'draft' && $daysSinceAssigned >= 4;
                                        $isPending = $assignment->status === 'draft';
                                        $isCompleted = $assignment->status === 'submitted';
                                        $reminderSent = $assignment->status === 'draft' && !is_null($assignment->last_reminded_at);
                                    @endphp
                                    <tr class="assignment-row hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all">
                                        <td class="py-5 px-8">
                                            <div class="flex items-start gap-4">
                                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-slate-500 to-slate-600 flex items-center justify-center text-white font-black text-xs shadow-lg">
                                                    {{ $abstract->id ?? '?' }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <a href="{{ route('admin.abstracts.view', $abstract->id) }}" class="text-sm font-black text-slate-900 dark:text-white hover:text-indigo-600 transition-colors block leading-tight">
                                                        {{ Str::limit($abstract->title ?? 'Untitled Abstract', 60) }}
                                                    </a>
                                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1">
                                                        {{ $abstract->user->full_name ?? $abstract->user->name ?? 'Unknown Author' }}
                                                    </p>
                                                    <div class="flex items-center gap-2 mt-2">
                                                        @if($abstract->subtheme)
                                                            <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-[10px] font-bold text-slate-600 dark:text-slate-400">
                                                                {{ $abstract->subtheme }}
                                                            </span>
                                                        @endif
                                                        <span class="text-[10px] text-slate-400 font-medium">
                                                            Assigned {{ $displayAssignedAt?->format('M j, Y') ?? 'Unknown' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-5 px-4 text-center">
                                            @if($isCompleted)
                                                <span class="status-badge-completed inline-flex items-center justify-center px-3 py-1.5 rounded-xl text-[10px] font-black text-white uppercase tracking-tight">
                                                    Submitted
                                                </span>
                                            @elseif($isOverdue)
                                                <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl text-[10px] font-black text-white uppercase tracking-tight" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                                                    Overdue
                                                </span>
                                            @elseif($reminderSent)
                                                <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl text-[10px] font-black text-white uppercase tracking-tight" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                                    Reminder Sent
                                                </span>
                                            @elseif($needsReminder)
                                                <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl text-[10px] font-black text-white uppercase tracking-tight" style="background: linear-gradient(135deg, #f59e0b, #d97706); animation: pulse 2s infinite;">
                                                    Reminder Due
                                                </span>
                                            @else
                                                <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl text-[10px] font-black text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 uppercase tracking-tight">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-5 px-4 text-center">
                                            <span class="text-sm font-black {{ $isOverdue ? 'text-rose-600' : ($needsReminder ? 'text-amber-600' : 'text-slate-600 dark:text-slate-400') }}">
                                                {{ $ageLabel }}
                                            </span>
                                        </td>
                                        <td class="py-5 px-8 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('admin.abstracts.view', $abstract->id) }}" title="View Abstract" class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl hover:bg-indigo-600 hover:text-white transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </a>
                                                @if($isOverdue)
                                                    <span class="p-2 bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-xl text-[10px] font-black" title="Will be auto re-assigned">
                                                        AUTO
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-20 text-center text-slate-400 font-bold italic">No assignments found for this reviewer.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Historical Submitted Reviews</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">This table shows everything this reviewer has actually submitted over time, including older rounds and abstracts no longer in the live queue.</p>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Submitted Reviews</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white">{{ $historyStats['total_reviews'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Distinct Abstracts</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white">{{ $historyStats['distinct_abstracts'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800">
                                    <th class="py-6 px-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Abstract Details</th>
                                    <th class="py-6 px-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Round</th>
                                    <th class="py-6 px-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Score</th>
                                    <th class="py-6 px-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Recommendation</th>
                                    <th class="py-6 px-8 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Submitted</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                                @forelse($submittedHistory as $review)
                                    @php
                                        $abstract = $review->abstractSubmission;
                                        $submittedAt = $review->submitted_at ?? $review->completed_at ?? $review->updated_at;
                                        $recommendationLabel = match($review->recommendation) {
                                            'accept' => 'Accept',
                                            'accept_with_revisions' => 'Revision',
                                            'reject' => 'Reject',
                                            default => $review->recommendation ? Str::headline(str_replace('_', ' ', $review->recommendation)) : 'N/A',
                                        };
                                        $recommendationClasses = match($review->recommendation) {
                                            'accept' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                            'accept_with_revisions' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                            'reject' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                                            default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all">
                                        <td class="py-5 px-8">
                                            <div class="flex items-start gap-4">
                                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-black text-xs shadow-lg">
                                                    {{ $abstract->id ?? '?' }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    @if($abstract)
                                                        <a href="{{ route('admin.abstracts.view', $abstract->id) }}" class="text-sm font-black text-slate-900 dark:text-white hover:text-indigo-600 transition-colors block leading-tight">
                                                            {{ Str::limit($abstract->title ?? 'Untitled Abstract', 70) }}
                                                        </a>
                                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1">
                                                            {{ $abstract->user->full_name ?? $abstract->user->name ?? 'Unknown Author' }}
                                                        </p>
                                                        @if($abstract->subtheme)
                                                            <span class="inline-flex mt-2 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-[10px] font-bold text-slate-600 dark:text-slate-400">
                                                                {{ $abstract->subtheme }}
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="text-sm font-black text-slate-900 dark:text-white">Deleted / Missing Abstract</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-5 px-4 text-center">
                                            <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl text-[10px] font-black text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/30 uppercase tracking-tight">
                                                {{ (int) $review->review_round + 1 }}
                                            </span>
                                        </td>
                                        <td class="py-5 px-4 text-center">
                                            <span class="text-sm font-black text-slate-900 dark:text-white">
                                                {{ is_null($review->score) ? 'N/A' : $review->score }}
                                            </span>
                                        </td>
                                        <td class="py-5 px-4 text-center">
                                            <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-tight {{ $recommendationClasses }}">
                                                {{ $recommendationLabel }}
                                            </span>
                                        </td>
                                        <td class="py-5 px-8 text-right">
                                            <div class="text-sm font-black text-slate-900 dark:text-white">
                                                {{ $submittedAt?->format('M j, Y') ?? 'Unknown' }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-medium">
                                                {{ $submittedAt?->format('H:i') ?? '' }}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-20 text-center text-slate-400 font-bold italic">No submitted review history found for this reviewer.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($submittedHistory->hasPages())
                        <div class="px-8 py-5 border-t border-slate-100 dark:border-slate-800">
                            {{ $submittedHistory->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <script>
        function updateSort(sortBy) {
            const url = new URL(window.location);
            const previousSort = url.searchParams.get('sort') || 'assigned_at';
            const currentDirection = url.searchParams.get('direction') || 'desc';
            url.searchParams.set('sort', sortBy);
            url.searchParams.set('direction', previousSort === sortBy && currentDirection === 'desc' ? 'asc' : 'desc');
            return url.toString();
        }
        </script>

        <div class="mt-12 flex justify-center">
            <a href="{{ route('admin.review-quality.dashboard') }}" class="group flex items-center gap-2 text-sm font-black text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all">
                <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Return to Quality Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
