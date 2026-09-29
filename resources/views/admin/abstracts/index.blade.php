@extends('layouts.app')

@section('title', 'Abstract Management')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="w-full max-w-[1800px] mx-auto py-8 px-4 sm:px-6 lg:px-8 2xl:px-10">

        @php
            $totalSubmitted = \App\Models\AbstractSubmission::where('status', '!=', 'draft')->count();
            $underReview = \App\Models\AbstractSubmission::where('status', 'under_review')->count();
            $readyForDecision = \App\Models\AbstractSubmission::where('status', 'ready_for_decision')->count();
            $accepted = \App\Models\AbstractSubmission::where('status', 'accepted')->count();
            $rejected = \App\Models\AbstractSubmission::where('status', 'rejected')->count();
            $pending = \App\Models\AbstractSubmission::where('status', 'submitted')->count();
            $acceptedWithPresentation = \App\Models\AbstractSubmission::where('status', 'accepted')
                ->where(function($q) {
                    $q->whereNotNull('oral_presentation_file')
                      ->orWhereNotNull('poster_presentation_file')
                      ->orWhereNotNull('audio_poster_file');
                })->count();
            $acceptedWithoutPresentation = $accepted - $acceptedWithPresentation;
            $completedCount = $accepted + $rejected;
            $completionRate = $totalSubmitted > 0 ? round(($completedCount / $totalSubmitted) * 100) : 0;
        @endphp

        {{-- Page-level loading overlay --}}
        <div id="table-loading-state" class="fixed inset-0 z-[100] hidden">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="bg-white rounded-2xl p-8 shadow-2xl border border-slate-200 flex flex-col items-center gap-4">
                    <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-slate-700 font-semibold text-sm">Loading abstracts…</p>
                </div>
            </div>
        </div>

        {{-- Floating bulk-action bar --}}
        <div id="bulk-action-bar" class="hidden fixed bottom-8 left-1/2 -translate-x-1/2 z-[100]">
            <div class="bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-2xl px-6 py-4 shadow-2xl flex items-center gap-6 text-white">
                <div class="flex items-center gap-3 pr-6 border-r border-white/10">
                    <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center font-black text-sm" id="selected-count">0</div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest leading-tight">Abstracts</p>
                        <p class="text-xs font-bold text-white">Selected</p>
                    </div>
                </div>

                <form action="{{ route('admin.abstracts.bulk-action') }}" method="POST" id="bulk-action-form" class="flex items-center gap-3">
                    @csrf
                    <input type="hidden" name="action" id="bulk-action-input" value="">
                    <div id="bulk-ids-container"></div>

                    <button type="button" onclick="executeBulkAction('accept')"
                            class="flex items-center gap-2 px-4 py-2 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white rounded-xl border border-emerald-500/20 transition-all font-semibold text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Accept
                    </button>

                    <button type="button" onclick="executeBulkAction('reject')"
                            class="flex items-center gap-2 px-4 py-2 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white rounded-xl border border-rose-500/20 transition-all font-semibold text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reject
                    </button>

                    @if(auth()->user()->hasRole('admin'))
                    <button type="button" onclick="executeBulkAction('delete')"
                            class="p-2 bg-slate-800 hover:bg-rose-600 text-slate-400 hover:text-white rounded-xl border border-slate-700 transition-all" title="Delete Selected">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    @endif
                </form>

                <button type="button" onclick="deselectAll()" class="text-xs font-semibold text-slate-500 hover:text-white transition-colors pl-4 border-l border-white/10">
                    Cancel
                </button>
            </div>
        </div>

        {{-- Bulk action confirmation modal --}}
        <div id="bulk-confirm-modal" class="hidden fixed inset-0 z-[200] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeBulkModal()"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-sm mx-4 p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div id="modal-icon" class="w-12 h-12 rounded-full flex items-center justify-center shrink-0"></div>
                    <div>
                        <p class="font-bold text-slate-900 text-base" id="modal-title"></p>
                        <p class="text-sm text-slate-500 mt-0.5" id="modal-desc"></p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button onclick="closeBulkModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">Cancel</button>
                    <button id="modal-confirm-btn" onclick="confirmBulkAction()" class="flex-1 py-2.5 rounded-xl text-sm font-bold text-white transition-colors">Confirm</button>
                </div>
            </div>
        </div>

        {{-- ── Header ── --}}
        <div class="relative overflow-hidden rounded-2xl bg-[#06153D] text-white px-8 py-10 shadow-2xl mb-6">
            <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-blue-500/20 blur-3xl pointer-events-none"></div>

            <div class="relative flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8">
                <div>
                    <nav class="flex items-center gap-2 text-sm text-blue-300/70 mb-4">
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors">Dashboard</a>
                        <svg class="w-4 h-4 opacity-40" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-white/80">Abstract Management</span>
                    </nav>
                    <h1 class="text-3xl font-black tracking-tight mb-1">Abstract Management</h1>
                    <p class="text-blue-200/60 text-sm font-medium">{{ config('conference.short_name') }} {{ config('conference.year') }} &middot; Peer-review oversight</p>
                </div>

                <div class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 flex items-center gap-6 shrink-0">
                    <div class="text-center">
                        <div class="text-2xl font-black">{{ $totalSubmitted }}</div>
                        <div class="text-[11px] text-blue-300/60 uppercase tracking-wider mt-0.5">Total</div>
                    </div>
                    <div class="w-px h-10 bg-white/10"></div>
                    <div class="text-center">
                        <div class="text-2xl font-black text-blue-300">{{ $pending + $underReview + $readyForDecision }}</div>
                        <div class="text-[11px] text-blue-300/60 uppercase tracking-wider mt-0.5">In Review</div>
                    </div>
                    <div class="w-px h-10 bg-white/10"></div>
                    <div class="text-center">
                        <div class="text-2xl font-black text-emerald-400">{{ $accepted }}</div>
                        <div class="text-[11px] text-blue-300/60 uppercase tracking-wider mt-0.5">Accepted</div>
                    </div>
                    <div class="w-px h-10 bg-white/10"></div>
                    <div class="text-center">
                        <div class="text-2xl font-black text-rose-400">{{ $rejected }}</div>
                        <div class="text-[11px] text-blue-300/60 uppercase tracking-wider mt-0.5">Rejected</div>
                    </div>
                </div>
            </div>

            <div class="relative mt-8 pt-6 border-t border-white/10 flex items-center gap-4">
                <span class="text-xs text-blue-300/50 shrink-0">Review completion</span>
                <div class="flex-1 h-1.5 bg-white/10 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-400 rounded-full" style="width: {{ $completionRate }}%"></div>
                </div>
                <span class="text-sm font-bold shrink-0">{{ $completionRate }}%</span>
            </div>
        </div>

        {{-- ── Filters ── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="p-6">
                <form action="{{ route('admin.abstracts.index') }}" method="GET" id="filter-form" class="space-y-5">

                    {{-- Search + subtheme row --}}
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text" name="search" id="main-search" value="{{ request('search') }}"
                                   placeholder="Search by ID, title, or author…"
                                   class="block w-full pl-11 pr-4 h-11 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>

                        <select name="subtheme" id="subtheme-filter" onchange="this.form.submit()"
                                class="h-11 px-4 border border-slate-200 rounded-xl text-sm text-slate-700 bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 appearance-none cursor-pointer sm:w-64">
                            <option value="">All Subthemes</option>
                            @foreach(array_keys(config('conference.subtheme_prefixes', [])) as $theme)
                                <option value="{{ $theme }}" {{ request('subtheme') === $theme ? 'selected' : '' }}>{{ $theme }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="h-11 px-6 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl transition-colors shrink-0">
                            Search
                        </button>
                    </div>

                    {{-- Status filter chips --}}
                    @php
                        $currentStatus = request('status', 'all');
                        $repositoryFilters = $stats['repository_filters'] ?? [];
                        $chips = [
                            'all'                      => ['label' => 'All',                    'count' => $repositoryFilters['all'] ?? 0],
                            'not_assigned'             => ['label' => 'Not Assigned',           'count' => $repositoryFilters['not_assigned'] ?? 0],
                            'assigned_one_reviewer'    => ['label' => 'Assigned 1 Reviewer',    'count' => $repositoryFilters['assigned_one_reviewer'] ?? 0],
                            'unreviewed'               => ['label' => 'No Reviews Yet',         'count' => $repositoryFilters['unreviewed'] ?? 0],
                            'partial_review'           => ['label' => '1 Reviewer Done',        'count' => $repositoryFilters['partial_review'] ?? 0],
                            'ready_for_decision'       => ['label' => 'Decision Required',      'count' => $repositoryFilters['ready_for_decision'] ?? 0],
                            'revision_with_authors'    => ['label' => 'Revision: Authors',      'count' => $repositoryFilters['revision_with_authors'] ?? 0],
                            'revision_with_reviewers'  => ['label' => 'Revision: Reviewers',   'count' => $repositoryFilters['revision_with_reviewers'] ?? 0],
                            'accepted'                 => ['label' => 'Accepted',               'count' => $repositoryFilters['accepted'] ?? 0],
                            'rejected'                 => ['label' => 'Rejected',               'count' => $repositoryFilters['rejected'] ?? 0],
                        ];
                    @endphp

                    <input type="hidden" name="status" id="status-input" value="{{ $currentStatus }}">

                    <div class="flex flex-wrap gap-2 pt-4 border-t border-slate-100">
                        @foreach($chips as $value => $data)
                            <button type="button"
                                    onclick="document.getElementById('status-input').value = '{{ $value }}'; document.getElementById('filter-form').submit();"
                                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-medium transition-all
                                    @if($currentStatus === $value)
                                        bg-slate-900 text-white
                                    @else
                                        bg-slate-100 text-slate-600 hover:bg-slate-200
                                    @endif">
                                <span>{{ $data['label'] }}</span>
                                <span class="text-xs font-bold px-1.5 py-0.5 rounded
                                    @if($currentStatus === $value) bg-white/20 text-white @else bg-slate-200 text-slate-500 @endif">
                                    {{ number_format($data['count']) }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </form>
            </div>

            @if(request()->hasAny(['search', 'status', 'subtheme']))
                <div class="bg-slate-50 px-6 py-3 flex items-center justify-between border-t border-slate-100">
                    <p class="text-xs text-slate-500">Filtered view active</p>
                    <a href="{{ route('admin.abstracts.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-red-600 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear filters
                    </a>
                </div>
            @endif
        </div>

        {{-- ── Table ── --}}
        @if($abstracts->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1200px] xl:min-w-0 divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="w-12 px-6 py-4">
                                    <input type="checkbox" id="master-checkbox" onchange="toggleAllCheckboxes(this)"
                                           class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <div class="flex flex-col gap-1">
                                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'title', 'sort_order' => request('sort_by') === 'title' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}"
                                           class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                            Abstract
                                            @if(request('sort_by') === 'title')
                                                <svg class="w-3 h-3 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            @endif
                                        </a>
                                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'id', 'sort_order' => request('sort_by') === 'id' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}"
                                           class="text-[10px] text-slate-400 hover:text-blue-500 transition-colors flex items-center gap-1">
                                            Sort by ID
                                            @if(request('sort_by') === 'id')
                                                <svg class="w-2 h-2 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            @endif
                                        </a>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'author_name', 'sort_order' => request('sort_by') === 'author_name' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}"
                                       class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                        Author
                                        @if(request('sort_by') === 'author_name')
                                            <svg class="w-3 h-3 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'average_score', 'sort_order' => request('sort_by') === 'average_score' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}"
                                       class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                        Avg. Score
                                        @if(request('sort_by') === 'average_score')
                                            <svg class="w-3 h-3 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'status', 'sort_order' => request('sort_by') === 'status' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}"
                                       class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                        Status
                                        @if(request('sort_by') === 'status')
                                            <svg class="w-3 h-3 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'presentation_status', 'sort_order' => request('sort_by') === 'presentation_status' && request('sort_order') === 'asc' ? 'desc' : 'asc']) }}"
                                       class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                        Presentation
                                        @if(request('sort_by') === 'presentation_status')
                                            <svg class="w-3 h-3 {{ request('sort_order') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @foreach($abstracts as $abstract)
                                <tr class="group hover:bg-blue-50/40 transition-colors">

                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="selected_ids[]" value="{{ $abstract->id }}" onchange="updateSelection()"
                                               class="row-checkbox w-4 h-4 text-blue-600 bg-slate-50 border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                                    </td>

                                    {{-- Abstract details --}}
                                    <td class="px-6 py-4">
                                        <div class="flex items-start gap-4">
                                            <div class="shrink-0 pt-0.5">
                                                <div class="h-10 w-10 rounded-xl bg-slate-100 border border-slate-200 group-hover:border-blue-200 flex items-center justify-center transition-colors">
                                                    @if($abstract->conference_code)
                                                        <span class="text-[9px] font-black text-blue-600 break-all px-1 text-center leading-tight">{{ $abstract->conference_code }}</span>
                                                    @else
                                                        <span class="text-xs font-bold text-slate-600">#{{ str_pad($abstract->id, 3, '0', STR_PAD_LEFT) }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-sm font-semibold text-slate-900 line-clamp-2 mb-1.5 group-hover:text-blue-600 transition-colors">
                                                    {{ $abstract->title }}
                                                </h3>
                                                @php
                                                    $color = \App\Support\ConferenceTopics::color($abstract->subtheme);
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-{{ $color }}-50 text-{{ $color }}-700 border border-{{ $color }}-100">
                                                    {{ $abstract->subtheme }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Author --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="text-sm font-semibold text-slate-900 truncate">{{ \App\Support\TitleFormatter::personName($abstract->author_name) }}</span>
                                            @if($abstract->user && in_array($abstract->user->payment_status, ['verified', 'waived'], true))
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200" title="Payment Verified">PAID</span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-700 border border-rose-200" title="Payment Pending">UNPAID</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500 mt-0.5 max-w-[150px] truncate" title="{{ $abstract->author_institute }}">
                                            {{ $abstract->author_institute }}
                                        </div>
                                    </td>

                                    {{-- Review score --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $progress = $abstract->getBestAvailableAdminReviewSummary();
                                            $totalCompleted = $progress['completed_count'];
                                            $requiredReviews = $progress['required_count'];
                                            $avgScore = $progress['avg_score'];
                                            $scoreColor = $avgScore >= 70 ? 'emerald' : ($avgScore >= 50 ? 'amber' : 'rose');
                                        @endphp
                                        @if($totalCompleted > 0)
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded border text-xs font-semibold bg-{{ $scoreColor }}-50 text-{{ $scoreColor }}-700 border-{{ $scoreColor }}-200">
                                                    {{ number_format($avgScore, 1) }}
                                                </span>
                                                @if($progress['is_ready'])
                                                    <span class="text-[10px] text-emerald-600 font-semibold">Ready</span>
                                                @else
                                                    <span class="text-[10px] text-blue-600 font-semibold">{{ $totalCompleted }}/{{ $requiredReviews }}</span>
                                                @endif
                                            </div>
                                            @if(!empty($progress['is_fallback']))
                                                <div class="text-[9px] text-amber-600 font-semibold mt-0.5">{{ $progress['source_label'] }}</div>
                                            @endif
                                        @elseif($progress['assigned_count'] > 0)
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-400">0.0</span>
                                                <span class="text-[10px] text-slate-400">Not started</span>
                                            </div>
                                        @else
                                            <span class="text-[10px] text-slate-400">No reviewers</span>
                                        @endif
                                    </td>

                                    {{-- Status badge --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php $statusBadge = $abstract->getAdminWorkflowStatusBadge(); @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide {{ $statusBadge['color'] }}">
                                            {{ $statusBadge['label'] }}
                                        </span>
                                    </td>

                                    {{-- Presentation --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($abstract->status === 'accepted')
                                            <span class="text-xs font-semibold {{ $abstract->hasPresentationUploaded() ? 'text-emerald-600' : 'text-amber-500' }}">
                                                {{ $abstract->hasPresentationUploaded() ? 'Ready' : 'Pending' }}
                                            </span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.abstracts.view', $abstract) }}"
                                               class="p-1.5 bg-slate-100 hover:bg-blue-600 hover:text-white rounded-lg transition-all text-slate-600" title="View Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            @if(auth()->user()->hasRole('admin'))
                                                <form action="{{ route('admin.abstracts.destroy', $abstract) }}" method="POST"
                                                      onsubmit="return confirm('Permanently delete this abstract? This cannot be undone.');" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white rounded-lg transition-all" title="Delete">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                    {{ $abstracts->links('components.pagination') }}
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-16 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">No abstracts found</h3>
                <p class="text-sm text-slate-500">Try adjusting your filters or search term.</p>
            </div>
        @endif

    </div>
</div>

<script>
    window.addEventListener('beforeunload', function() {
        const loading = document.getElementById('table-loading-state');
        if (loading) loading.classList.remove('hidden');
    });

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.getElementById('filter-form');
        const mainSearch = document.getElementById('main-search');

        if (mainSearch && mainSearch.value.length > 0) {
            mainSearch.focus();
            const val = mainSearch.value;
            mainSearch.value = '';
            mainSearch.value = val;
        }

        if (mainSearch) {
            mainSearch.addEventListener('input', debounce(() => {
                document.getElementById('table-loading-state').classList.remove('hidden');
                filterForm.submit();
            }, 600));
        }

        if (filterForm) {
            filterForm.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', () => {
                    document.getElementById('table-loading-state').classList.remove('hidden');
                    filterForm.submit();
                });
            });
        }
    });

    function toggleAllCheckboxes(master) {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = master.checked);
        updateSelection();
    }

    function updateSelection() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        const count = checkboxes.length;
        const bar = document.getElementById('bulk-action-bar');
        const display = document.getElementById('selected-count');
        const container = document.getElementById('bulk-ids-container');

        if (count > 0) {
            bar.classList.remove('hidden');
            display.innerText = count;
            container.innerHTML = '';
            checkboxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });
        } else {
            bar.classList.add('hidden');
            document.getElementById('master-checkbox').checked = false;
        }
    }

    function deselectAll() {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('master-checkbox').checked = false;
        updateSelection();
    }

    let pendingBulkAction = null;

    const bulkActionConfig = {
        accept: {
            title: 'Accept Selected Abstracts',
            desc: 'These abstracts will be marked as accepted.',
            iconBg: 'bg-emerald-100',
            iconColor: 'text-emerald-600',
            iconPath: 'M5 13l4 4L19 7',
            btnBg: 'bg-emerald-600 hover:bg-emerald-700',
        },
        reject: {
            title: 'Reject Selected Abstracts',
            desc: 'These abstracts will be marked as rejected.',
            iconBg: 'bg-red-100',
            iconColor: 'text-red-600',
            iconPath: 'M6 18L18 6M6 6l12 12',
            btnBg: 'bg-red-600 hover:bg-red-700',
        },
        delete: {
            title: 'Delete Selected Abstracts',
            desc: 'This cannot be undone. These abstracts will be permanently removed.',
            iconBg: 'bg-slate-100',
            iconColor: 'text-slate-600',
            iconPath: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
            btnBg: 'bg-slate-900 hover:bg-slate-700',
        },
    };

    function executeBulkAction(action) {
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        const cfg = bulkActionConfig[action];
        if (!cfg) return;
        pendingBulkAction = action;
        document.getElementById('modal-icon').className = `w-12 h-12 rounded-full flex items-center justify-center shrink-0 ${cfg.iconBg}`;
        document.getElementById('modal-icon').innerHTML = `<svg class="w-6 h-6 ${cfg.iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="${cfg.iconPath}"/></svg>`;
        document.getElementById('modal-title').textContent = cfg.title;
        document.getElementById('modal-desc').textContent = `${count} abstract${count !== 1 ? 's' : ''} selected. ${cfg.desc}`;
        document.getElementById('modal-confirm-btn').className = `flex-1 py-2.5 rounded-xl text-sm font-bold text-white transition-colors ${cfg.btnBg}`;
        document.getElementById('bulk-confirm-modal').classList.remove('hidden');
    }

    function closeBulkModal() {
        document.getElementById('bulk-confirm-modal').classList.add('hidden');
        pendingBulkAction = null;
    }

    function confirmBulkAction() {
        if (!pendingBulkAction) return;
        document.getElementById('bulk-action-input').value = pendingBulkAction;
        document.getElementById('bulk-action-form').submit();
        closeBulkModal();
    }
</script>
@endsection
