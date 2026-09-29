@extends('layouts.app')

@section('title', 'Assign Reviewers')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-3"></div>

    <!-- Enhanced Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 to-indigo-900 rounded-3xl shadow-xl mb-10 text-white">
        <div class="absolute inset-0 bg-[url('/img/grid.svg')] opacity-10"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>

        <div class="relative px-8 py-12 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="flex items-center gap-6">
                <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                    <svg class="w-10 h-10 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-white mb-2">Reviewer Management</h1>
                    <p class="text-indigo-200 text-lg">Assign and manage abstract reviews efficiently.</p>
                </div>
            </div>

            <!-- Global Stats -->
            <div class="flex gap-4" id="stats-container">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10 min-w-[100px]">
                    <div class="text-2xl font-bold text-white" id="stat-total">{{ $stats['total'] }}</div>
                    <div class="text-xs text-indigo-200 uppercase tracking-wider font-semibold">Total</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10 min-w-[100px]">
                    <div class="text-2xl font-bold text-emerald-400" id="stat-percentage">{{ $stats['percentage'] }}%</div>
                    <div class="text-xs text-indigo-200 uppercase tracking-wider font-semibold">Assigned</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10 min-w-[100px]">
                    <div class="text-2xl font-bold text-amber-400" id="stat-unassigned">{{ $stats['unassigned'] }}</div>
                    <div class="text-xs text-indigo-200 uppercase tracking-wider font-semibold">Pending</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">

        <!-- Sidebar Filters & Workload -->
        <div class="xl:col-span-1 space-y-8">
            <!-- Filter Panel -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-6 sticky top-6">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filters
                </h3>

                <form method="GET" action="{{ route('admin.abstracts.assign-reviewers-page') }}" class="space-y-4">
                    <!-- Status -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Submission Status</label>
                        <select name="filter" onchange="this.form.submit()"
                                class="w-full bg-slate-50 dark:bg-gray-900 border-slate-200 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                            <option value="all" {{ request('filter', 'unassigned') == 'all' ? 'selected' : '' }}>All Abstracts</option>
                            <option value="unassigned" {{ request('filter', 'unassigned') == 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                            <option value="assigned" {{ request('filter') == 'assigned' ? 'selected' : '' }}>Assigned (Pending)</option>
                            <option value="in_review" {{ request('filter') == 'in_review' ? 'selected' : '' }}>In Progress</option>
                            <option value="reviewed" {{ request('filter') == 'reviewed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>

                    <!-- Subtheme -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Subtheme</label>
                        <select name="subtheme" onchange="this.form.submit()"
                                class="w-full bg-slate-50 dark:bg-gray-900 border-slate-200 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                            <option value="all">All Subthemes</option>
                            @foreach($subthemes as $subtheme)
                                <option value="{{ $subtheme }}" {{ request('subtheme') == $subtheme ? 'selected' : '' }}>
                                    {{ $subtheme }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Search</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Title, author, ID..."
                                   class="w-full bg-slate-50 dark:bg-gray-900 border-slate-200 dark:border-gray-700 rounded-xl pl-9 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                            <div class="absolute left-3 top-2.5 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Sorting -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Sort Order</label>
                        <select name="sort" onchange="this.form.submit()"
                                class="w-full bg-slate-50 dark:bg-gray-900 border-slate-200 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                            <option value="created_desc" {{ request('sort') == 'created_desc' ? 'selected' : '' }}>Newest First</option>
                            <option value="created_asc" {{ request('sort') == 'created_asc' ? 'selected' : '' }}>Oldest First</option>
                            <option value="title_asc" {{ request('sort') == 'title_asc' ? 'selected' : '' }}>Title (A-Z)</option>
                            <option value="title_desc" {{ request('sort') == 'title_desc' ? 'selected' : '' }}>Title (Z-A)</option>
                            <option value="subtheme_asc" {{ request('sort') == 'subtheme_asc' ? 'selected' : '' }}>By Subtheme</option>
                        </select>
                    </div>

                    <!-- Per Page -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Items Per Page</label>
                        <select name="per_page" onchange="this.form.submit()"
                                class="w-full bg-slate-50 dark:bg-gray-900 border-slate-200 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 results</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 results</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 results</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 results</option>
                        </select>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors shadow-md">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Reviewer Workload Visualization -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white mb-4 uppercase tracking-wider">Reviewer Load</h3>
                <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar" id="reviewer-workload-list">
                    @foreach($availableReviewers->sortByDesc('workload') as $reviewer)
                        <div class="p-3 bg-slate-50 dark:bg-gray-800/50 rounded-xl border border-slate-100 dark:border-gray-700/50 group reviewer-workload-item" data-reviewer-id="{{ $reviewer->id }}">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <div class="w-2 h-2 rounded-full workload-indicator {{ $reviewer->workload >= ($reviewer->reviewer_max_load ?? 10) ? 'bg-rose-500' : ($reviewer->workload >= (($reviewer->reviewer_max_load ?? 10)/2) ? 'bg-amber-500' : 'bg-emerald-500') }}"></div>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate reviewer-name" title="{{ $reviewer->name }}">
                                        {{ $reviewer->first_name }} {{ $reviewer->last_name }}
                                    </span>
                                </div>
                                <span class="text-[10px] font-mono font-black px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 text-slate-600 dark:text-gray-300 border border-slate-200 dark:border-gray-600 shadow-sm workload-count">
                                    {{ $reviewer->workload }}<span class="opacity-40">/{{ $reviewer->reviewer_max_load ?? 10 }}</span>
                                </span>
                            </div>
                            
                            @php $prefs = $reviewer->interests->pluck('subtheme_name')->toArray(); @endphp
                            @if(count($prefs) > 0)
                                <div class="flex flex-wrap gap-1 mt-1 interests-container">
                                    @foreach(array_slice($prefs, 0, 2) as $pref)
                                        <span class="text-[9px] px-1 py-0.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded border border-indigo-100 dark:border-indigo-800/50 inline-block truncate max-w-[80px]" title="{{ $pref }}">{{ $pref }}</span>
                                    @endforeach
                                    @if(count($prefs) > 2)
                                        <span class="text-[9px] px-1 py-0.5 bg-slate-100 dark:bg-gray-700 text-slate-500 rounded border border-slate-200 dark:border-gray-600">+{{ count($prefs) - 2 }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-[9px] text-slate-400 italic font-medium tracking-tight mt-1 inline-block">No subthemes set</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Abstracts List -->
        <div class="xl:col-span-3 space-y-6" id="abstracts-list">
            @forelse($abstracts as $abstract)
                <div class="abstract-card bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-all duration-300" data-abstract-id="{{ $abstract->id }}" id="abstract-card-{{ $abstract->id }}">
                    <div class="flex flex-col lg:flex-row h-full">
                        <!-- Content Side -->
                        <div class="flex-1 p-6 border-b lg:border-b-0 lg:border-r border-slate-100 dark:border-gray-700 relative">
                            <!-- Status Indicator Line -->
                            <div class="status-indicator absolute left-0 top-6 bottom-6 w-1 rounded-r-lg
                                {{ $abstract->reviewer1_completed && $abstract->reviewer2_completed ? 'bg-emerald-500' : '' }}
                                {{ ($abstract->reviewer1_completed || $abstract->reviewer2_completed) && !($abstract->reviewer1_completed && $abstract->reviewer2_completed) ? 'bg-amber-500' : '' }}
                                {{ !$abstract->reviewer1_completed && !$abstract->reviewer2_completed && $abstract->reviewer_id && $abstract->reviewer_2_id ? 'bg-blue-500' : '' }}
                                {{ !$abstract->reviewer_id || !$abstract->reviewer_2_id ? 'bg-rose-500' : '' }}">
                            </div>

                            <div class="pl-4">
                                <div class="flex flex-wrap items-center gap-3 mb-2">
                                    <span class="font-mono text-xs font-bold text-slate-400">#{{ $abstract->id }}</span>
                                    @if(!is_null($abstract->current_avg_score))
                                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/30">
                                            <span class="text-[10px] font-bold text-indigo-400 dark:text-indigo-400 uppercase tracking-widest">Avg</span>
                                            <span class="text-xs font-black text-indigo-700 dark:text-indigo-200">{{ number_format($abstract->current_avg_score, 1) }}</span>
                                        </div>
                                    @endif
                                    <span class="status-badge px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide
                                        {{ $abstract->reviewer_id && $abstract->reviewer_2_id ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-900/20 dark:text-rose-400' }}">
                                        {{ $abstract->reviewer_id && $abstract->reviewer_2_id ? 'Assigned' : 'Unassigned' }}
                                    </span>
                                    <span class="text-xs text-slate-400 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $abstract->created_at->format('M d, Y') }}
                                    </span>
                                </div>

                                <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight mb-3">
                                    {{ $abstract->title }}
                                </h3>

                                <div class="flex flex-wrap gap-4 text-sm text-slate-600 dark:text-slate-400 mb-4">
                                    <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-gray-700/50 px-3 py-1.5 rounded-lg">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span class="font-medium">{{ $abstract->author_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-gray-700/50 px-3 py-1.5 rounded-lg" title="{{ $abstract->subtheme }}">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        <span>{{ $abstract->subtheme }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assignment Side -->
                        <div class="lg:w-[320px] bg-slate-50/50 dark:bg-gray-900/30 p-5 flex flex-col justify-center assignment-panel">

                            @if($abstract->reviewer_id && $abstract->reviewer_2_id)
                                <!-- Display Mode - Already Assigned -->
                                <div class="space-y-3 display-mode" id="display-mode-{{ $abstract->id }}">
                                    <!-- Reviewer 1 Display -->
                                    <div class="reviewer-display flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl border {{ $abstract->reviewer1_completed ? 'border-emerald-200 dark:border-emerald-900/50' : 'border-slate-200 dark:border-gray-700' }} shadow-sm" data-reviewer-slot="1">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">R</div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-slate-900 dark:text-white truncate max-w-[120px] reviewer-name-display">
                                                    {{ $abstract->reviewer1->first_name }} {{ $abstract->reviewer1->last_name }}
                                                </div>
                                                <div class="text-[10px] text-slate-500 uppercase tracking-wide">Reviewer</div>
                                            </div>
                                        </div>
                                        @if($abstract->reviewer1_completed)
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-black text-emerald-600 dark:text-emerald-400">{{ number_format($abstract->reviewer1_score, 1) }}</span>
                                                <div class="text-emerald-500 completion-icon"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div>
                                            </div>
                                        @else
                                            <div class="text-amber-400 completion-icon" title="Pending"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                                        @endif
                                    </div>

                                    <!-- Reviewer 2 Display -->
                                    <div class="reviewer-display flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl border {{ $abstract->reviewer2_completed ? 'border-emerald-200 dark:border-emerald-900/50' : 'border-slate-200 dark:border-gray-700' }} shadow-sm" data-reviewer-slot="2">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold text-xs">R</div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-slate-900 dark:text-white truncate max-w-[120px] reviewer-name-display">
                                                    {{ $abstract->reviewer2->first_name }} {{ $abstract->reviewer2->last_name }}
                                                </div>
                                                <div class="text-[10px] text-slate-500 uppercase tracking-wide">Reviewer</div>
                                            </div>
                                        </div>
                                        @if($abstract->reviewer2_completed)
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-black text-emerald-600 dark:text-emerald-400">{{ number_format($abstract->reviewer2_score, 1) }}</span>
                                                <div class="text-emerald-500 completion-icon"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div>
                                            </div>
                                        @else
                                            <div class="text-amber-400 completion-icon" title="Pending"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                                        @endif
                                    </div>

                                    <button onclick="toggleReassignForm({{ $abstract->id }})" class="w-full mt-2 text-xs font-medium text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors flex items-center justify-center gap-1 py-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        Change Reviewers
                                    </button>
                                </div>

                                <!-- Hidden Reassign Form -->
                                <div id="reassign-form-{{ $abstract->id }}" class="hidden reassign-form">
                                     <form class="space-y-3 ajax-assignment-form" data-abstract-id="{{ $abstract->id }}" data-action="{{ route('admin.abstracts.assign-reviewers', $abstract) }}">
                                        @csrf

                                        <!-- R1 Select -->
                                        <div class="relative">
                                            @if($abstract->reviewer1_completed)
                                                 <div class="p-2.5 bg-slate-100 dark:bg-gray-700 rounded-lg text-sm text-slate-500 flex justify-between items-center cursor-not-allowed">
                                                    <span>{{ $abstract->reviewer1->name }}</span>
                                                    <span class="text-[10px] font-bold bg-slate-200 px-1.5 py-0.5 rounded">LOCKED</span>
                                                 </div>
                                            @else
                                                <select name="reviewer_1_id" class="w-full text-sm rounded-lg border-slate-300 dark:border-gray-600 p-2.5 dark:bg-gray-800" data-abstract="{{ $abstract->id }}" data-position="primary" data-author="{{ $abstract->user_id }}">
                                                    <option value="">Select Reviewer...</option>
                                                    @foreach($availableReviewers as $reviewer)
                                                        @if($reviewer->id != $abstract->user_id)
                                                            <option value="{{ $reviewer->id }}" {{ $abstract->reviewer_id == $reviewer->id ? 'selected' : '' }}>
                                                                {{ $reviewer->name }} 
                                                                @if($reviewer->interests->isNotEmpty())
                                                                    [{{ implode(', ', $reviewer->interests->pluck('subtheme_name')->slice(0, 2)->toArray()) }}{{ count($reviewer->interests) > 2 ? '...' : '' }}]
                                                                @endif
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <div class="absolute right-8 top-3 text-[10px] font-bold text-slate-400 pointer-events-none">R</div>
                                            @endif
                                        </div>

                                        <!-- R2 Select -->
                                        <div class="relative">
                                            @if($abstract->reviewer2_completed)
                                                 <div class="p-2.5 bg-slate-100 dark:bg-gray-700 rounded-lg text-sm text-slate-500 flex justify-between items-center cursor-not-allowed">
                                                    <span>{{ $abstract->reviewer2->name }}</span>
                                                    <span class="text-[10px] font-bold bg-slate-200 px-1.5 py-0.5 rounded">LOCKED</span>
                                                 </div>
                                            @else
                                                <select name="reviewer_2_id" class="w-full text-sm rounded-lg border-slate-300 dark:border-gray-600 p-2.5 dark:bg-gray-800" data-abstract="{{ $abstract->id }}" data-position="secondary" data-author="{{ $abstract->user_id }}">
                                                    <option value="">Select Reviewer...</option>
                                                    @foreach($availableReviewers as $reviewer)
                                                        @if($reviewer->id != $abstract->user_id)
                                                            <option value="{{ $reviewer->id }}" {{ $abstract->reviewer_2_id == $reviewer->id ? 'selected' : '' }}>
                                                                {{ $reviewer->name }}
                                                                @if($reviewer->interests->isNotEmpty())
                                                                    [{{ implode(', ', $reviewer->interests->pluck('subtheme_name')->slice(0, 2)->toArray()) }}{{ count($reviewer->interests) > 2 ? '...' : '' }}]
                                                                @endif
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <div class="absolute right-8 top-3 text-[10px] font-bold text-slate-400 pointer-events-none">R</div>
                                            @endif
                                        </div>

                                        <div class="flex gap-2">
                                            <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 rounded-lg transition-colors flex items-center justify-center gap-2">
                                                <span class="btn-text">Save</span>
                                                <svg class="btn-spinner hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </button>
                                            <button type="button" onclick="toggleReassignForm({{ $abstract->id }})" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-gray-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold transition-colors">Cancel</button>
                                        </div>
                                    </form>
                                </div>

                            @else
                                <!-- Initial Assignment Form -->
                                <form class="space-y-3 ajax-assignment-form" data-abstract-id="{{ $abstract->id }}" data-action="{{ route('admin.abstracts.assign-reviewers', $abstract) }}">
                                    @csrf
                                    <div>
                                        <label class="text-[10px] uppercase font-bold text-slate-400 mb-1 block">First Reviewer</label>
                                        <select name="reviewer_1_id" required class="w-full text-sm rounded-lg border-slate-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-indigo-500 transition-shadow" data-abstract="{{ $abstract->id }}" data-position="primary" data-author="{{ $abstract->user_id }}">
                                            <option value="">Select Reviewer...</option>
                                            @foreach($availableReviewers as $reviewer)
                                                @if($reviewer->id != $abstract->user_id)
                                                    <option value="{{ $reviewer->id }}">
                                                        {{ $reviewer->name }}
                                                        @if($reviewer->interests->isNotEmpty())
                                                            [{{ implode(', ', $reviewer->interests->pluck('subtheme_name')->slice(0, 2)->toArray()) }}{{ count($reviewer->interests) > 2 ? '...' : '' }}]
                                                        @endif
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="text-[10px] uppercase font-bold text-slate-400 mb-1 block">Second Reviewer</label>
                                        <select name="reviewer_2_id" required class="w-full text-sm rounded-lg border-slate-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-indigo-500 transition-shadow" data-abstract="{{ $abstract->id }}" data-position="secondary" data-author="{{ $abstract->user_id }}">
                                            <option value="">Select Reviewer...</option>
                                            @foreach($availableReviewers as $reviewer)
                                                @if($reviewer->id != $abstract->user_id)
                                                    <option value="{{ $reviewer->id }}">
                                                        {{ $reviewer->name }}
                                                        @if($reviewer->interests->isNotEmpty())
                                                            [{{ implode(', ', $reviewer->interests->pluck('subtheme_name')->slice(0, 2)->toArray()) }}{{ count($reviewer->interests) > 2 ? '...' : '' }}]
                                                        @endif
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>

                                    <button type="submit" class="w-full mt-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2.5 rounded-xl shadow-md transition-all hover:shadow-lg flex justify-center items-center gap-2 group">
                                        <span class="btn-text">Assign Both</span>
                                        <svg class="btn-arrow w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        <svg class="btn-spinner hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-12 text-center">
                    <div class="w-20 h-20 bg-slate-50 dark:bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No abstracts found</h3>
                    <p class="text-slate-500 dark:text-slate-400 mb-6">There are no abstracts matching your current filters.</p>
                    <a href="{{ route('admin.abstracts.assign-reviewers-page') }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-700 font-semibold">
                        Clear all filters
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            @endforelse

            @if($abstracts->total() > 0)
                <div class="pt-8 flex flex-col md:flex-row items-center justify-between gap-6 border-t border-slate-100 dark:border-gray-700 mt-4">
                    <div class="text-sm text-slate-500 dark:text-slate-400 order-2 md:order-1">
                        @if($abstracts->count() > 0)
                            Showing <span class="font-bold text-slate-900 dark:text-white">{{ $abstracts->firstItem() }}</span>
                            to <span class="font-bold text-slate-900 dark:text-white">{{ $abstracts->lastItem() }}</span>
                            of <span class="font-bold text-slate-900 dark:text-white">{{ $abstracts->total() }}</span> results
                        @else
                            No results match your criteria (<span class="font-bold text-slate-900 dark:text-white">{{ $abstracts->total() }}</span> total items available)
                        @endif
                    </div>
                    @if($abstracts->hasPages())
                        <div class="order-1 md:order-2">
                            {{ $abstracts->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Get current filter for behavior
    const currentFilter = '{{ request('filter', 'unassigned') }}';

    // Toast notification system
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');

        const bgColor = type === 'success' ? 'bg-emerald-500' : type === 'error' ? 'bg-rose-500' : 'bg-amber-500';
        const icon = type === 'success'
            ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
            : type === 'error'
            ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
            : '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';

        toast.className = `${bgColor} text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 transform translate-x-full transition-transform duration-300 ease-out min-w-[300px]`;
        toast.innerHTML = `${icon}<span class="flex-1 text-sm font-medium">${message}</span>`;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
        });

        // Remove after 4 seconds
        setTimeout(() => {
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // Toggle reassign form
    function toggleReassignForm(id) {
        const display = document.getElementById(`display-mode-${id}`);
        const form = document.getElementById(`reassign-form-${id}`);

        if (display && form) {
            display.classList.toggle('hidden');
            form.classList.toggle('hidden');
        }
    }

    // Update stats in header
    function updateStats(stats) {
        const totalEl = document.getElementById('stat-total');
        const percentEl = document.getElementById('stat-percentage');
        const unassignedEl = document.getElementById('stat-unassigned');

        if (totalEl) totalEl.textContent = stats.total;
        if (percentEl) percentEl.textContent = stats.percentage + '%';
        if (unassignedEl) unassignedEl.textContent = stats.unassigned;

        // Add pulse animation
        [totalEl, percentEl, unassignedEl].forEach(el => {
            if (el) {
                el.classList.add('animate-pulse');
                setTimeout(() => el.classList.remove('animate-pulse'), 1000);
            }
        });
    }

    // Update reviewer workload sidebar
    function updateReviewerWorkloads(workloads) {
        const list = document.getElementById('reviewer-workload-list');
        if (!list || !workloads) return;

        workloads.forEach(reviewer => {
            const item = list.querySelector(`[data-reviewer-id="${reviewer.id}"]`);
            if (item) {
                const countEl = item.querySelector('.workload-count');
                const indicator = item.querySelector('.workload-indicator');
                const interestsContainer = item.querySelector('.interests-container');

                if (countEl) {
                    const maxLoad = reviewer.max_load || 10;
                    const oldCount = parseInt(countEl.textContent);
                    countEl.innerHTML = `${reviewer.workload}<span class="opacity-40">/${maxLoad}</span>`;

                    // Highlight if changed
                    if (oldCount !== reviewer.workload) {
                        countEl.classList.add('ring-2', 'ring-indigo-400');
                        setTimeout(() => countEl.classList.remove('ring-2', 'ring-indigo-400'), 2000);
                    }
                }

                // Update indicator color
                if (indicator) {
                    const maxLoad = reviewer.max_load || 10;
                    indicator.className = `w-2 h-2 rounded-full workload-indicator ${
                        reviewer.workload >= maxLoad ? 'bg-rose-500' :
                        reviewer.workload >= (maxLoad/2) ? 'bg-amber-500' : 'bg-emerald-500'
                    }`;
                }

                // Update interests/preferences
                if (interestsContainer && reviewer.interests) {
                    let interestsHTML = '';
                    reviewer.interests.slice(0, 2).forEach(pref => {
                        interestsHTML += `<span class="text-[9px] px-1 py-0.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded border border-indigo-100 dark:border-indigo-800/50 inline-block truncate max-w-[80px]" title="${pref}">${pref}</span>`;
                    });
                    if (reviewer.interests.length > 2) {
                        interestsHTML += `<span class="text-[9px] px-1 py-0.5 bg-slate-100 dark:bg-gray-700 text-slate-500 rounded border border-slate-200 dark:border-gray-600">+${reviewer.interests.length - 2}</span>`;
                    }
                    if (reviewer.interests.length === 0) {
                        interestsHTML = '<span class="text-[9px] text-slate-400 italic font-medium tracking-tight mt-1 inline-block">No subthemes set</span>';
                    }
                    interestsContainer.innerHTML = interestsHTML;
                }
            }
        });
    }

    // Update abstract card after assignment
    function updateAbstractCard(abstractData) {
        const card = document.getElementById(`abstract-card-${abstractData.id}`);
        if (!card) return;

        const assignmentPanel = card.querySelector('.assignment-panel');
        if (!assignmentPanel) return;

        // Update status badge
        const statusBadge = card.querySelector('.status-badge');
        if (statusBadge && abstractData.is_fully_assigned) {
            statusBadge.textContent = 'Assigned';
            statusBadge.className = 'status-badge px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400';
        }

        // Update status indicator line
        const statusIndicator = card.querySelector('.status-indicator');
        if (statusIndicator && abstractData.is_fully_assigned) {
            statusIndicator.className = 'status-indicator absolute left-0 top-6 bottom-6 w-1 rounded-r-lg bg-blue-500';
        }

        // If in "unassigned" filter and now assigned, fade out the card
        if (currentFilter === 'unassigned' && abstractData.is_fully_assigned) {
            card.style.transition = 'all 0.5s ease-out';
            card.style.opacity = '0';
            card.style.transform = 'translateX(50px)';
            setTimeout(() => {
                card.style.height = card.offsetHeight + 'px';
                card.style.overflow = 'hidden';
                requestAnimationFrame(() => {
                    card.style.height = '0';
                    card.style.margin = '0';
                    card.style.padding = '0';
                    setTimeout(() => card.remove(), 500);
                });
            }, 300);
            return;
        }

        // Update the display mode with new reviewer info
        if (abstractData.is_fully_assigned && abstractData.reviewer1 && abstractData.reviewer2) {
            // Build new display HTML
            const displayHTML = `
                <div class="space-y-3 display-mode" id="display-mode-${abstractData.id}">
                    <!-- Reviewer 1 Display -->
                    <div class="reviewer-display flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl border ${abstractData.reviewer1_completed ? 'border-emerald-200 dark:border-emerald-900/50' : 'border-slate-200 dark:border-gray-700'} shadow-sm" data-reviewer-slot="1">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">R</div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900 dark:text-white truncate max-w-[120px] reviewer-name-display">
                                    ${abstractData.reviewer1.first_name} ${abstractData.reviewer1.last_name}
                                </div>
                                <div class="text-[10px] text-slate-500 uppercase tracking-wide">Reviewer</div>
                            </div>
                        </div>
                        ${abstractData.reviewer1_completed
                            ? '<div class="text-emerald-500 completion-icon"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div>'
                            : '<div class="text-amber-400 completion-icon" title="Pending"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>'
                        }
                    </div>

                    <!-- Reviewer 2 Display -->
                    <div class="reviewer-display flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl border ${abstractData.reviewer2_completed ? 'border-emerald-200 dark:border-emerald-900/50' : 'border-slate-200 dark:border-gray-700'} shadow-sm" data-reviewer-slot="2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold text-xs">R</div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900 dark:text-white truncate max-w-[120px] reviewer-name-display">
                                    ${abstractData.reviewer2.first_name} ${abstractData.reviewer2.last_name}
                                </div>
                                <div class="text-[10px] text-slate-500 uppercase tracking-wide">Reviewer</div>
                            </div>
                        </div>
                        ${abstractData.reviewer2_completed
                            ? '<div class="text-emerald-500 completion-icon"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div>'
                            : '<div class="text-amber-400 completion-icon" title="Pending"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>'
                        }
                    </div>

                    <button onclick="toggleReassignForm(${abstractData.id})" class="w-full mt-2 text-xs font-medium text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors flex items-center justify-center gap-1 py-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        Change Reviewers
                    </button>
                </div>
            `;

            // Find existing forms/displays and replace
            const existingDisplay = assignmentPanel.querySelector('.display-mode');
            const existingForm = assignmentPanel.querySelector('.ajax-assignment-form');
            const reassignForm = assignmentPanel.querySelector('.reassign-form');

            if (existingDisplay) existingDisplay.remove();
            if (existingForm && !existingForm.closest('.reassign-form')) existingForm.remove();
            if (reassignForm) reassignForm.classList.add('hidden');

            // Insert new display
            assignmentPanel.insertAdjacentHTML('afterbegin', displayHTML);

            // Add success animation
            card.classList.add('ring-2', 'ring-emerald-400');
            setTimeout(() => card.classList.remove('ring-2', 'ring-emerald-400'), 2000);
        }
    }

    // Handle form submissions via AJAX
    document.addEventListener('DOMContentLoaded', function() {
        // Handle select dropdowns preventing duplicate selection
        const selects = document.querySelectorAll('select[data-abstract]');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                const abstractId = this.dataset.abstract;
                const position = this.dataset.position;
                const authorId = this.dataset.author;
                const selectedValue = this.value;

                const otherPosition = position === 'primary' ? 'secondary' : 'primary';
                const otherSelect = document.querySelector(`select[data-abstract="${abstractId}"][data-position="${otherPosition}"]`);

                if (otherSelect) {
                    const options = otherSelect.querySelectorAll('option');
                    options.forEach(option => {
                        if (option.value === '') {
                            option.disabled = false;
                            return;
                        }
                        if (option.value === authorId) {
                            option.disabled = true;
                            option.style.display = 'none';
                        } else if (option.value === selectedValue) {
                            option.disabled = true;
                            option.classList.add('bg-slate-100', 'text-slate-400');
                        } else {
                            option.disabled = false;
                            option.classList.remove('bg-slate-100', 'text-slate-400');
                        }
                    });
                }
            });
        });

        // Handle AJAX form submissions
        document.querySelectorAll('.ajax-assignment-form').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const abstractId = this.dataset.abstractId;
                const action = this.dataset.action;
                const submitBtn = this.querySelector('button[type="submit"]');
                const btnText = submitBtn.querySelector('.btn-text');
                const btnSpinner = submitBtn.querySelector('.btn-spinner');
                const btnArrow = submitBtn.querySelector('.btn-arrow');

                // Show loading state
                submitBtn.disabled = true;
                if (btnText) btnText.classList.add('hidden');
                if (btnArrow) btnArrow.classList.add('hidden');
                if (btnSpinner) btnSpinner.classList.remove('hidden');

                try {
                    const formData = new FormData(this);

                    const response = await fetch(action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast(data.message, 'success');

                        // Update the abstract card
                        if (data.abstract) {
                            updateAbstractCard(data.abstract);
                        }

                        // Update stats
                        if (data.stats) {
                            updateStats(data.stats);
                        }

                        // Update reviewer workloads
                        if (data.reviewerWorkloads) {
                            updateReviewerWorkloads(data.reviewerWorkloads);
                        }
                    } else {
                        showToast(data.message || 'An error occurred', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Failed to assign reviewers. Please try again.', 'error');
                } finally {
                    // Reset button state
                    submitBtn.disabled = false;
                    if (btnText) btnText.classList.remove('hidden');
                    if (btnArrow) btnArrow.classList.remove('hidden');
                    if (btnSpinner) btnSpinner.classList.add('hidden');
                }
            });
        });
    });
</script>
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.05);
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.2);
        border-radius: 4px;
    }

    /* Smooth animations for cards */
    .abstract-card {
        transition: all 0.3s ease;
    }

    /* Toast animation */
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    /* Pulse animation for stats update */
    @keyframes statPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
</style>
@endsection
