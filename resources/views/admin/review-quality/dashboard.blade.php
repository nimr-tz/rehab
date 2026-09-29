@extends('layouts.app')

@section('title', 'Reviewer Operations Dashboard')

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
    .status-glow-emerald { box-shadow: 0 0 15px rgba(16, 185, 129, 0.2); }
    .status-glow-amber { box-shadow: 0 0 15px rgba(245, 158, 11, 0.2); }
    .status-glow-rose { box-shadow: 0 0 15px rgba(244, 63, 94, 0.2); }
    
    @keyframes pulse-soft {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.8; }
    }
    .pulse-danger { animation: pulse-soft 2s infinite ease-in-out; }
    
    .data-row:hover {
        transform: translateY(-2px);
        transition: all 0.2s ease;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans pb-20">
    <div class="max-w-[1400px] mx-auto px-6 py-10">

        {{-- Premium Header --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 mb-4">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Reviewer Operations</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">Reviewer <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">Operations</span></h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-xl">Track the live review queue, spot reminders and overdue assignments early, and keep the workflow moving.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <button type="button" onclick="window.location.reload()" class="p-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl shadow-lg shadow-indigo-200 dark:shadow-none transition-all" title="Refresh dashboard">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>

        @php
            $totalReviewers = count($reviewers ?? []);
            $totalPending = collect($reviewers ?? [])->sum('pending_count');
            $totalAssigned = collect($reviewers ?? [])->sum('assigned');
            $totalCompleted = collect($reviewers ?? [])->sum('completed');
            $reminderDueCount = collect($reviewers ?? [])->sum('needs_reminder_count');
            $overdueCount = collect($reviewers ?? [])->sum('overdue_count');
            $completionRate = $totalAssigned > 0 ? round(($totalCompleted / $totalAssigned) * 100) : 0;
        @endphp

        {{-- Hero Stats with Glassmorphism --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card rounded-[2rem] p-8 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-all"></div>
                <p class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-4">Portfolio Completion</p>
                <div class="flex items-end gap-3">
                    <span class="text-5xl font-black text-slate-900 dark:text-white">{{ $completionRate }}%</span>
                    <span class="text-sm font-bold text-emerald-500 mb-2">Submitted</span>
                </div>
                <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full mt-6 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500" style="width: {{ $completionRate }}%"></div>
                </div>
            </div>

            <div class="glass-card rounded-[2rem] p-8 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all"></div>
                <p class="text-[10px] font-black text-amber-500 uppercase tracking-[0.2em] mb-4">Pending Queue</p>
                <div class="flex items-end gap-3">
                    <span class="text-5xl font-black text-slate-900 dark:text-white">{{ $totalPending }}</span>
                    <span class="text-sm font-bold text-amber-500 mb-2">Current Reviews</span>
                </div>
                <p class="text-xs text-slate-400 mt-4 font-medium italic">~{{ $totalReviewers > 0 ? round($totalPending / $totalReviewers, 1) : 0 }} per reviewer</p>
            </div>

            <div class="glass-card rounded-[2rem] p-8 relative overflow-hidden group border-rose-100 dark:border-rose-900/30">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/10 rounded-full blur-2xl group-hover:bg-rose-500/20 transition-all"></div>
                <p class="text-[10px] font-black text-rose-500 uppercase tracking-[0.2em] mb-4">Reminder Queue</p>
                <div class="flex items-end gap-3">
                    <span class="text-5xl font-black {{ $reminderDueCount > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">{{ $reminderDueCount }}</span>
                    <span class="text-sm font-bold text-rose-500 mb-2">3+ Days</span>
                </div>
                <div class="flex flex-wrap gap-1 mt-4">
                    @for($i = 0; $i < min($reminderDueCount, 12); $i++)
                        <span class="w-2 h-2 rounded-full bg-rose-500/40 pulse-danger"></span>
                    @endfor
                </div>
            </div>

        <div class="glass-card rounded-[2rem] p-8 relative overflow-hidden group border-rose-100 dark:border-rose-900/30">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-slate-500/10 rounded-full blur-2xl group-hover:bg-slate-500/20 transition-all"></div>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4">Overdue Queue</p>
                <div class="flex items-end gap-3">
                    <span class="text-5xl font-black {{ $overdueCount > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">{{ $overdueCount }}</span>
                    <span class="text-sm font-bold text-slate-500 mb-2">4+ Days</span>
                </div>
                <p class="text-xs text-slate-400 mt-4 font-medium">Eligible for auto-reassignment</p>
            </div>
        </div>

        {{-- Deep Quality Insight Section --}}
        <div class="mb-12">
            <h2 class="text-xl font-black text-slate-800 dark:text-slate-200 mb-6 flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                </span>
                Quality Snapshot
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">These cards summarize submitted-review quality across the system. They are historical checks, separate from the live queue above.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Briefness Alert --}}
                <div class="glass-card rounded-[2rem] p-8">
                    <p class="text-[10px] font-black {{ $qualityStats['short_comments'] > 0 ? 'text-rose-500' : 'text-emerald-500' }} uppercase tracking-[0.2em] mb-4">Briefness Monitor</p>
                    <div class="flex items-end justify-between mb-4">
                        <span class="text-4xl font-black text-slate-900 dark:text-white">{{ $qualityStats['short_comments'] }}</span>
                        <span class="text-xs font-bold text-slate-400 mb-1">Reviews with short comments</span>
                    </div>
                    @php 
                        $briefPercent = $qualityStats['total_reviews'] > 0 ? ($qualityStats['short_comments'] / $qualityStats['total_reviews']) * 100 : 0; 
                    @endphp
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mb-2">
                        <div class="h-full {{ $briefPercent > 20 ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $briefPercent }}%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400 font-medium tracking-tight">Requires admin follow-up for depth verification.</p>
                </div>

                {{-- Score Variance --}}
                <div class="glass-card rounded-[2rem] p-8">
                    <p class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-4">Score Distribution</p>
                    <div class="flex items-end gap-1 h-20 mb-4 px-2">
                        @foreach($qualityStats['score_distribution'] ?? [] as $range => $count)
                            @php $height = $qualityStats['total_reviews'] > 0 ? ($count / $qualityStats['total_reviews']) * 100 : 0; @endphp
                            <div class="flex-1 bg-indigo-500 rounded-t-sm group/bar relative" style="height: {{ max(4, $height) }}%" title="{{ $range }}: {{ $count }} reviews">
                                <div class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 bg-slate-900 text-[8px] text-white px-1.5 py-0.5 rounded opacity-0 group-hover/bar:opacity-100 transition-opacity">
                                    {{ $count }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between text-[8px] font-black text-slate-400 uppercase tracking-tighter">
                        <span>Low (0-20)</span>
                        <span>High (81-100)</span>
                    </div>
                </div>

                {{-- Depth Insights --}}
                <div class="glass-card rounded-[2rem] p-8">
                    <p class="text-[10px] font-black text-violet-500 uppercase tracking-[0.2em] mb-4">Review Depth</p>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span class="text-4xl font-black text-slate-900 dark:text-white">{{ round($qualityStats['average_comment_length']) }}</span>
                            <span class="text-[10px] font-black text-slate-400 uppercase ml-1">avg chars</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span class="text-slate-500">Missing Criteria Tags</span>
                            <span class="text-rose-500">{{ $qualityStats['missing_criteria'] }} reviews</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span class="text-slate-500">Extreme Sentiments</span>
                            <span class="text-amber-500">{{ $qualityStats['extreme_scores'] }} reviews</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            {{-- Main Performance Table --}}
            <div class="xl:col-span-2 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Current Reviewer Queue</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">All counts below are live portfolio counts for the current review round, not lifetime reviewer history.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-400">Sort by:</span>
                        <select onchange="window.location.href = updateDashboardSort(this.value)" class="bg-transparent border-none text-xs font-black text-indigo-600 focus:ring-0 cursor-pointer">
                            <option value="workload" {{ ($sortBy ?? 'workload') === 'workload' ? 'selected' : '' }}>Workload</option>
                            <option value="oldest" {{ ($sortBy ?? '') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                            <option value="success_rate" {{ ($sortBy ?? '') === 'success_rate' ? 'selected' : '' }}>Success Rate</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800">
                                    <th class="py-6 px-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Reviewer Identity</th>
                                    <th class="py-6 px-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Current</th>
                                    <th class="py-6 px-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Submitted</th>
                                    <th class="py-6 px-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Oldest Pending</th>
                                    <th class="py-6 px-8 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Portfolio Completion</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                                @forelse($reviewers as $item)
                                    <tr class="data-row hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all cursor-pointer">
                                        <td class="py-5 px-8">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-white font-black text-xs shadow-lg shadow-indigo-500/20">
                                                    {{ $item['reviewer']->initials ?? '??' }}
                                                </div>
                                                <div>
                                                    <a href="{{ route('admin.review-quality.reviewer-performance', $item['reviewer']->id) }}" class="text-sm font-black text-slate-900 dark:text-white hover:text-indigo-600 transition-colors">
                                                        {{ $item['reviewer']->full_name ?? $item['reviewer']->name }}
                                                    </a>
                                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight mt-0.5">{{ $item['reviewer']->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-5 px-4 text-center">
                                            <div class="inline-flex items-center justify-center w-12 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-black text-slate-900 dark:text-white">
                                                {{ $item['assigned'] }}
                                            </div>
                                        </td>
                                        <td class="py-5 px-4 text-center">
                                            <div class="inline-flex items-center justify-center w-12 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-xs font-black text-emerald-600 dark:text-emerald-400">
                                                {{ $item['completed'] }}
                                            </div>
                                        </td>
                                        <td class="py-5 px-4 text-center">
                                            @if($item['pending_count'] > 0)
                                                <span class="text-xs font-black {{ $item['oldest_days'] >= 4 ? 'text-rose-500' : ($item['oldest_days'] >= 3 ? 'text-amber-500' : 'text-slate-600 dark:text-slate-400') }}">
                                                    {{ $item['oldest_age_label'] ?? ($item['oldest_days'] . 'd') }}
                                                </span>
                                            @else
                                                <span class="text-slate-300 dark:text-slate-700">—</span>
                                            @endif
                                        </td>
                                        <td class="py-5 px-8 text-right">
                                            <div class="flex flex-col items-end">
                                                <span class="text-xs font-black text-slate-900 dark:text-white">{{ $item['completion_rate'] }}%</span>
                                                <div class="w-16 h-1 bg-slate-100 dark:bg-slate-800 rounded-full mt-1.5 overflow-hidden">
                                                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $item['completion_rate'] }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-20 text-center text-slate-400 font-bold italic">No reviewer data available in the current cycle.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Action Queue (Delayed Abstracts) --}}
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Attention Queue</h2>
                    <span class="px-2.5 py-1 rounded-full bg-rose-100 dark:bg-rose-900/30 text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest animate-pulse">Urgent</span>
                </div>

                <div class="space-y-4 max-h-[700px] overflow-y-auto pr-2 custom-scrollbar">
                    @php
                        $delayed = collect($reviewers ?? [])
                            ->flatMap(function ($item) {
                                $name = $item['reviewer']->full_name ?? $item['reviewer']->email;
                                return array_map(fn ($ab) => $ab + ['reviewer_name' => $name, 'reviewer_id' => $item['reviewer']->id], $item['pending_abstracts'] ?? []);
                            })
                            ->filter(fn ($ab) => ($ab['days'] ?? 0) >= 3)
                            ->sortByDesc('days')
                            ->values();
                    @endphp

                    @forelse($delayed as $ab)
                        <div class="glass-card rounded-3xl p-6 border-l-4 {{ $ab['days'] >= 4 ? 'border-l-rose-500' : 'border-l-amber-500' }} hover:scale-[1.02] transition-all">
                            <div class="flex justify-between items-start mb-4">
                                <div class="px-2.5 py-1 rounded-lg {{ $ab['days'] >= 4 ? 'bg-rose-50 dark:bg-rose-900/20 text-rose-600' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-600' }} text-[10px] font-black uppercase tracking-tighter">
                                    {{ $ab['days'] >= 4 ? 'Overdue' : 'Reminder Due' }} · {{ $ab['days'] }} days
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.review-quality.reviewer-performance', $ab['reviewer_id']) }}" title="Open Reviewer Portfolio" class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl hover:bg-indigo-600 hover:text-white transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zM19.5 19.5a9 9 0 10-15 0"/></svg>
                                    </a>
                                </div>
                            </div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white leading-tight mb-3">
                                <a href="{{ route('admin.abstracts.view', $ab['id']) }}" class="hover:text-indigo-600 transition-colors">
                                    {{ Str::limit($ab['title'], 65) }}
                                </a>
                            </h3>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500">
                                    {{ substr($ab['reviewer_name'], 0, 1) }}
                                </div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ $ab['reviewer_name'] }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[2rem] text-center">
                            <svg class="w-12 h-12 text-slate-200 dark:text-slate-800 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm font-bold text-slate-400 italic">Clear queue. No reminder or overdue items.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-12 flex justify-center">
            <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-2 text-sm font-black text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all">
                <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Return to Operational Control
            </a>
        </div>
    </div>
</div>
<script>
function updateDashboardSort(sortBy) {
    const url = new URL(window.location);
    const previousSort = url.searchParams.get('sort') || 'workload';
    const currentDirection = url.searchParams.get('direction') || 'desc';

    url.searchParams.set('sort', sortBy);
    url.searchParams.set('direction', previousSort === sortBy && currentDirection === 'desc' ? 'asc' : 'desc');

    return url.toString();
}
</script>
@endsection
