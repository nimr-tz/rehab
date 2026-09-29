@extends('layouts.app')

@section('title', 'Decision Management')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Premium Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 to-indigo-900 rounded-3xl shadow-xl mb-10 text-white">
        <div class="absolute inset-0 bg-[url('/img/grid.svg')] opacity-10"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>

        <div class="relative px-8 py-10 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="flex items-center gap-6">
                <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                    <svg class="w-10 h-10 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-white mb-2">Decision Dashboard</h1>
                    <p class="text-indigo-200 text-lg">Manage conflicts, revisions, and final approvals with ease.</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button id="bulkActionsBtn" class="hidden bg-white/10 hover:bg-white/20 text-white px-5 py-2.5 rounded-xl font-semibold backdrop-blur-sm border border-white/10 transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Bulk Actions
                </button>
                <button onclick="refreshDashboard()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-semibold shadow-lg shadow-indigo-500/30 transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <!-- Needs Resolution -->
        <div onclick="window.location.href='?filter=action_required'" class="relative overflow-hidden rounded-2xl p-6 shadow-lg bg-gradient-to-br from-indigo-600 to-violet-700 text-white group transition-all hover:scale-[1.02] cursor-pointer">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110 blur-sm"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-black/10 rounded-tr-full -ml-8 -mb-8 pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-sm font-bold text-indigo-100 uppercase tracking-wider">Needs Resolution</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-bold text-white tracking-tight">{{ $stats['needs_resolution'] ?? 0 }}</span>
                    <span class="text-sm text-indigo-100 font-medium">pending actions</span>
                </div>
            </div>
        </div>

        <!-- Revisions Submitted -->
        <div onclick="window.location.href='?filter=revisions'" class="relative overflow-hidden rounded-2xl p-6 shadow-lg bg-gradient-to-br from-amber-500 to-orange-600 text-white group transition-all hover:scale-[1.02] cursor-pointer">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110 blur-sm"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-black/10 rounded-tr-full -ml-8 -mb-8 pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </div>
                    <span class="text-sm font-bold text-amber-100 uppercase tracking-wider">Revisions Submitted</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-bold text-white tracking-tight">{{ $stats['revisions'] ?? 0 }}</span>
                    <span class="text-sm text-amber-100 font-medium">need review</span>
                </div>
            </div>
        </div>

        <!-- Resolutions Made -->
        <div onclick="window.location.href='?filter=resolved'" class="relative overflow-hidden rounded-2xl p-6 shadow-lg bg-gradient-to-br from-emerald-500 to-teal-600 text-white group transition-all hover:scale-[1.02] cursor-pointer">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110 blur-sm"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-black/10 rounded-tr-full -ml-8 -mb-8 pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-sm font-bold text-emerald-100 uppercase tracking-wider">Resolutions Made</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-bold text-white tracking-tight">{{ $stats['my_resolutions'] ?? 0 }}</span>
                    <span class="text-sm text-emerald-100 font-medium">completed</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-2 mb-8 flex flex-col md:flex-row gap-2">
        <form method="GET" action="{{ route('admin.decisions.index') }}" class="flex-1 flex flex-col md:flex-row gap-2 w-full">
            <div class="relative flex-1 group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search }}"
                       class="block w-full pl-10 pr-3 py-3 border-transparent bg-transparent text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-0 sm:text-sm"
                       placeholder="Search abstracts, authors, IDs...">
            </div>

            <div class="h-px md:h-auto w-full md:w-px bg-slate-200 dark:bg-gray-700 mx-2"></div>

            <div class="relative md:w-64">
                <select name="filter" onchange="this.form.submit()" class="appearance-none block w-full pl-3 pr-10 py-3 text-base border-transparent bg-transparent text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-0 sm:text-sm cursor-pointer hover:bg-slate-50 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
                    <option value="action_required" {{ $filter === 'action_required' ? 'selected' : '' }}>Action Required</option>
                    <option value="in_progress" {{ $filter === 'in_progress' ? 'selected' : '' }}>In Progress (Waiting)</option>
                    <option value="resolved" {{ $filter === 'resolved' ? 'selected' : '' }}>Resolutions Made</option>
                    <option value="conflicts" {{ $filter === 'conflicts' ? 'selected' : '' }}>Conflicts Only</option>
                    <option value="revisions" {{ $filter === 'revisions' ? 'selected' : '' }}>Revisions Submitted</option>
                    <option value="urgent" {{ $filter === 'urgent' ? 'selected' : '' }}>Urgent (7+ days)</option>
                    <option value="high_priority" {{ $filter === 'high_priority' ? 'selected' : '' }}>High Priority</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </div>
            </div>
        </form>
    </div>

    <!-- Main Content List -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                Abstracts
                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-gray-700 text-xs text-slate-600 dark:text-slate-300">{{ $abstracts->total() }}</span>
            </h2>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <input type="checkbox" id="selectAll" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                    <label for="selectAll" class="cursor-pointer select-none">Select All</label>
                </div>
            </div>
        </div>

        @if($abstracts->count() > 0)
            <div class="divide-y divide-slate-100 dark:divide-gray-700/50">
                @foreach($abstracts as $abstract)
                @php
                    $borderClass = match($abstract->status) {
                        'ready_for_decision' => 'border-l-4 border-l-amber-500',
                        'revision_submitted' => 'border-l-4 border-l-teal-500',
                        'revision_required' => 'border-l-4 border-l-purple-500',
                        'under_review' => 'border-l-4 border-l-orange-400',
                        'accepted' => 'border-l-4 border-l-emerald-500',
                        'rejected' => 'border-l-4 border-l-rose-500',
                        default => 'border-l-4 border-l-transparent hover:border-l-indigo-500'
                    };
                    if ($abstract->decision_type === 'conflict_resolution') $borderClass = 'border-l-4 border-l-rose-600';
                @endphp
                    <div class="p-6 hover:bg-slate-50 dark:hover:bg-gray-700/20 transition-all group {{ $borderClass }}">
                        <div class="flex gap-4 items-start">
                            <div class="pt-1">
                                <input type="checkbox" value="{{ $abstract->id }}" class="abstract-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5 cursor-pointer">
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-3 mb-2">
                                    <span class="font-mono text-xs font-bold text-slate-400">#{{ $abstract->id }}</span>

                                    <!-- Status Badge -->
                                    @php
                                        $latestReviews = $abstract->reviews
                                            ->where('status', 'submitted')
                                            ->sortByDesc('review_round')
                                            ->unique('reviewer_id');
                                        $completedReviews = $latestReviews->count();

                                        $statusConfig = [
                                            'ready_for_decision' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Ready for Decision'],
                                            'revision_submitted' => ['bg' => 'bg-teal-100', 'text' => 'text-teal-700', 'label' => 'Revision Submitted'],
                                            'revision_required' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Accepted with Revisions'],
                                            'under_review' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'label' => 'Under Review'],
                                            'accepted' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'Accepted'],
                                            'rejected' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-700', 'label' => 'Rejected'],
                                        ];

                                        // Override for under_review with 2+ reviews
                                        if (in_array($abstract->status, ['under_review', 'revision_submitted']) && $completedReviews >= 2) {
                                            $config = $statusConfig['ready_for_decision'];
                                        } else {
                                            $config = $statusConfig[$abstract->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'label' => Str::title(str_replace('_', ' ', $abstract->status))];
                                        }
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide {{ $config['bg'] }} {{ $config['text'] }} dark:bg-opacity-20">
                                        {{ $config['label'] }}
                                    </span>

                                    <!-- Tags -->
                                    @if($abstract->decision_type === 'conflict_resolution')
                                        <span class="flex items-center gap-1 text-xs font-bold text-rose-600 bg-rose-50 dark:bg-rose-900/30 px-2 py-0.5 rounded-md">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5l-6.928-12c-.77-1.333-2.694-1.333-3.464 0l-6.928 12c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                            Conflict
                                        </span>
                                    @endif
                                    @if($abstract->urgency_level === 'high')
                                        <span class="flex items-center gap-1 text-xs font-bold text-red-600 bg-red-50 dark:bg-red-900/30 px-2 py-0.5 rounded-md">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Urgent
                                        </span>
                                    @endif
                                </div>

                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                            <a href="{{ route('admin.decisions.show', $abstract) }}">{{ $abstract->title }}</a>
                                        </h3>
                                        <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-3">
                                            <span class="font-medium">{{ $abstract->author_name }}</span>
                                            <span>&bull;</span>
                                            <span>{{ Str::limit($abstract->author_institute, 50) }}</span>
                                            <span>&bull;</span>
                                            <span>{{ $abstract->updated_at->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.decisions.show', $abstract) }}" class="bg-white dark:bg-gray-700 border border-slate-200 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-600 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-all">
                                            Review
                                        </a>
                                    </div>
                                </div>

                                <!-- Review Summary -->
                                @if($latestReviews->count() > 0)
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        @foreach($latestReviews as $review)
                                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-gray-900/50 border border-slate-100 dark:border-gray-700/50">
                                                <div class="w-2 h-2 rounded-full
                                                    {{ str_contains($review->recommendation, 'accept') ? 'bg-emerald-500' : (str_contains($review->recommendation, 'reject') ? 'bg-rose-500' : 'bg-amber-500') }}">
                                                </div>
                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                    {{ $review->reviewer ? Str::limit($review->reviewer->first_name, 1, '.') . ' ' . $review->reviewer->last_name : 'Reviewer' }}
                                                </span>
                                                <span class="text-xs text-slate-400 font-bold px-1 rounded bg-slate-100 dark:bg-slate-800 text-[10px]">R{{ $review->review_round }}</span>
                                                <span class="text-xs text-slate-400">|</span>
                                                <span class="text-xs font-medium uppercase {{ str_contains($review->recommendation, 'accept') ? 'text-emerald-600' : (str_contains($review->recommendation, 'reject') ? 'text-rose-600' : 'text-amber-600') }}">
                                                    {{ ucfirst(str_replace('_', ' ', $review->recommendation)) }} ({{ $review->score }})
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="bg-slate-50 dark:bg-gray-800/50 px-6 py-4 border-t border-slate-200 dark:border-gray-700">
                {{ $abstracts->appends(request()->query())->links() }}
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-24 h-24 bg-slate-50 dark:bg-gray-700/50 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">All Caught Up!</h3>
                <p class="text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    {{ $filter !== 'action_required' ? 'No items found matching the current filters.' : 'There are no abstracts requiring your attention right now.' }}
                </p>
                @if($filter !== 'action_required')
                    <a href="{{ route('admin.decisions.index') }}" class="mt-4 px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">Clear Filters</a>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Quick Decision Modal -->
<div id="quickDecisionModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('quickDecisionModal')"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pointer-events-none">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 relative transform transition-all pointer-events-auto">
            <button onclick="closeModal('quickDecisionModal')" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Quick Decision</h3>
            <p class="text-sm text-slate-500 mb-6">Make a fast decision for this abstract.</p>

            <form id="quickDecisionForm" method="POST">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Decision</label>
                        <select name="decision_type" required class="w-full rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 focus:ring-2 focus:ring-indigo-500 transition-shadow p-3">
                            <option value="">Select Outcome...</option>
                            <option value="accept">Accept Abstract</option>
                            <option value="reject">Reject Abstract</option>
                            <option value="accept_with_revisions">Accept with Revisions</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Admin Notes (Optional)</label>
                        <textarea name="admin_notes" rows="4" class="w-full rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 focus:ring-2 focus:ring-indigo-500 transition-shadow p-3" placeholder="Enter any internal notes or feedback..."></textarea>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" onclick="closeModal('quickDecisionModal')" class="flex-1 px-4 py-3 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-colors">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all">Submit Decision</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div id="bulkActionsModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('bulkActionsModal')"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pointer-events-none">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 relative transform transition-all pointer-events-auto">
             <button onclick="closeModal('bulkActionsModal')" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Bulk Processing</h3>
            <p class="text-sm text-slate-500 mb-6">Apply an action to <span id="selectedCount" class="font-bold text-indigo-600">0</span> items.</p>

            <form id="bulkActionsForm" method="POST" action="{{ route('admin.decisions.bulk-process') }}">
                @csrf
                <input type="hidden" name="abstract_ids" id="selectedIds">

                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Action</label>
                        <select name="bulk_action" required class="w-full rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 focus:ring-2 focus:ring-indigo-500 transition-shadow p-3">
                            <option value="">Choose Bulk Action...</option>
                            <option value="accept">Accept All Selected</option>
                            <option value="reject">Reject All Selected</option>
                            <option value="priority_high">Mark as High Priority</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Notes</label>
                        <textarea name="admin_notes" rows="3" class="w-full rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 focus:ring-2 focus:ring-indigo-500 transition-shadow p-3" placeholder="Notes apply to all selected items..."></textarea>
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-100 dark:border-amber-800 p-4 rounded-xl flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5l-6.928-12c-.77-1.333-2.694-1.333-3.464 0l-6.928 12c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <p class="text-sm text-amber-800 dark:text-amber-200">This action cannot be undone. Notifications will be sent to authors accordingly.</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" onclick="closeModal('bulkActionsModal')" class="flex-1 px-4 py-3 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-colors">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all">Execute</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Handle checkbox selections
    const selectAllChkBx = document.getElementById('selectAll');
    if(selectAllChkBx) {
        selectAllChkBx.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.abstract-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActions();
        });
    }

    // Update bulk actions visibility
    function updateBulkActions() {
        const selected = document.querySelectorAll('.abstract-checkbox:checked');
        const bulkBtn = document.getElementById('bulkActionsBtn');
        const countSpan = document.getElementById('selectedCount');

        if (selected.length > 0) {
            bulkBtn.classList.remove('hidden');
            if(countSpan) countSpan.textContent = selected.length;
        } else {
            bulkBtn.classList.add('hidden');
        }
    }

    // Listen for individual checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('abstract-checkbox')) {
            updateBulkActions();
        }
    });

    // Show quick decision modal
    function showQuickDecisionModal(abstractId) {
        const modal = document.getElementById('quickDecisionModal');
        const form = document.getElementById('quickDecisionForm');
        form.action = `/admin/decisions/${abstractId}/process`;
        modal.classList.remove('hidden');
    }

    // Show bulk actions modal
    const bulkBtn = document.getElementById('bulkActionsBtn');
    if(bulkBtn) {
        bulkBtn.addEventListener('click', function() {
            const selected = Array.from(document.querySelectorAll('.abstract-checkbox:checked')).map(cb => cb.value);
            document.getElementById('selectedIds').value = JSON.stringify(selected);
            document.getElementById('bulkActionsModal').classList.remove('hidden');
        });
    }

    // Close modal function
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Refresh dashboard
    function refreshDashboard() {
        window.location.reload();
    }
</script>
@endsection
