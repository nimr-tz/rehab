@extends('layouts.app')

@section('title', 'My Submissions')

@section('content')
@php
    $submissionWindowStatus = app(\App\Services\SubmissionWindowService::class)->status();
    $submissionWindowOpen = $submissionWindowStatus['is_open'];
    $submissionWindowOverrideActive = $submissionWindowStatus['override_active'];
    $submissionDeadlineText = $submissionWindowStatus['deadline']->timezone(config('app.timezone'))->format('F d, Y \a\t H:i');
    // Camera-ready window for accepted abstracts — drives the "Review proceedings entry" action.
    $proceedingsCorrectionsOpen = app(\App\Services\ProceedingsCorrectionService::class)->isOpen();
@endphp
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 relative font-sans">
    <!-- Sophisticated Professional Header -->
    <div class="relative bg-gradient-to-br from-indigo-700 via-indigo-800 to-blue-900 py-12 md:py-16 lg:py-20 rounded-b-[3rem] md:rounded-b-[5rem] shadow-2xl overflow-hidden mb-8 md:mb-12">
        <!-- Abstract Precision Background -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.05]"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-black/20 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-end gap-8 md:gap-10">
                <div class="space-y-3">
                    <h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-white leading-none tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        My <span class="text-blue-200">Submissions.</span>
                    </h1>
                    <p class="text-base lg:text-lg text-indigo-100/60 font-medium tracking-wide">
                        Manage and track your research submissions for {{ config('conference.short_name') }} {{ config('conference.year') }}.
                    </p>
                </div>

                <!-- Discrete Modern Metrics -->
                <div class="flex items-center gap-8 lg:gap-12 bg-white/5 backdrop-blur-md px-6 lg:px-10 py-4 lg:py-5 rounded-2xl md:rounded-[2rem] border border-white/10 shadow-2xl">
                    <div class="text-center">
                        <p class="text-[9px] font-black text-blue-200/50 uppercase tracking-[0.3em] mb-1">Total</p>
                        <p class="text-2xl lg:text-3xl font-black text-white leading-none">{{ $stats['total'] }}</p>
                    </div>
                    <div class="h-10 w-[1px] bg-white/10"></div>
                    <div class="text-center">
                        <p class="text-[9px] font-black text-blue-200/50 uppercase tracking-[0.3em] mb-1">Accepted</p>
                        <p class="text-2xl lg:text-3xl font-black text-white leading-none">{{ $stats['accepted'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 relative z-20 mt-[-1.5rem] md:mt-[-2rem] space-y-6 md:space-y-8">

        <!-- Section Header Actions -->
        <div class="flex items-center justify-end">
            @if($submissionWindowOpen)
                <a href="{{ route('abstracts.create') }}" class="group relative px-6 py-3 bg-[#3969B7] text-white font-bold rounded-xl transition-all shadow-[0_15px_30px_rgba(57,105,183,0.2)] hover:shadow-[0_20px_40px_rgba(57,105,183,0.3)] hover:-translate-y-1">
                    <span class="relative z-10 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ $submissionWindowOverrideActive ? 'New Abstract (Open)' : 'New Abstract' }}
                    </span>
                </a>
            @else
                <div class="px-6 py-3 bg-slate-200 dark:bg-gray-800 text-slate-500 dark:text-slate-400 font-bold rounded-xl border border-slate-300 dark:border-gray-700">
                    Submissions Closed
                </div>
            @endif
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-100 dark:border-gray-700 p-5 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Status Filter -->
                <div>
                    <label for="statusFilter" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Filter by Status</label>
                    <select id="statusFilter" onchange="applyFilters()"
                            class="w-full rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-700 dark:text-white text-sm focus:ring-author-500 focus:border-author-500 transition-shadow">
                        <option value="all">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="under_review">Under Review</option>
                        <option value="revision_required">Revision Required</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <!-- Sort By -->
                <div>
                    <label for="sortFilter" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Sort Order</label>
                    <select id="sortFilter" onchange="applyFilters()"
                            class="w-full rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-700 dark:text-white text-sm focus:ring-author-500 focus:border-author-500 transition-shadow">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="title_asc">Title (A-Z)</option>
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label for="searchInput" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Search Abstracts</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" id="searchInput" onkeyup="applyFilters()"
                               placeholder="Search by title..."
                               class="w-full pl-9 rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-700 dark:text-white text-sm focus:ring-author-500 focus:border-author-500 transition-shadow">
                    </div>
                </div>
            </div>
        </div>

        <!-- Abstracts List (Card Layout) -->
        <div class="space-y-4" id="abstractsList">
            @forelse($abstracts as $abstract)
                @php
                    $statusConfig = [
                        'draft' => ['color' => 'slate', 'label' => 'Draft', 'step' => 1],
                        'submitted' => ['color' => 'author', 'label' => 'Submitted', 'step' => 1],
                        'under_review' => ['color' => 'purple', 'label' => 'Under Review', 'step' => 2],
                        'ready_for_decision' => ['color' => 'purple', 'label' => 'Decision Pending', 'step' => 3],
                        'revision_required' => ['color' => 'amber', 'label' => 'Accepted with Revisions', 'step' => 3],
                        'revision_submitted' => ['color' => 'teal', 'label' => 'Revision Submitted', 'step' => 3],
                        'revision_review' => ['color' => 'indigo', 'label' => 'Revision Under Review', 'step' => 3],
                        'accepted' => ['color' => 'emerald', 'label' => 'Accepted', 'step' => 4],
                        'rejected' => ['color' => 'rose', 'label' => 'Rejected', 'step' => 4],
                    ];
                    // Get config, with fallback for unknown statuses
                    $config = $statusConfig[$abstract->status] ?? ['color' => 'slate', 'label' => ucwords(str_replace('_', ' ', $abstract->status)), 'step' => 1];
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden abstract-card group"
                     data-status="{{ $abstract->status }}"
                     data-title="{{ strtolower($abstract->title) }}"
                     data-id="{{ $abstract->id }}">
                    <div class="p-6">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="px-2.5 py-1 bg-{{ $config['color'] }}-50 dark:bg-{{ $config['color'] }}-900/20 text-{{ $config['color'] }}-700 dark:text-{{ $config['color'] }}-300 rounded-md text-xs font-bold uppercase tracking-wide">
                                        {{ $config['label'] }}
                                    </span>
                                    @if($abstract->subtheme)
                                        <span class="px-2.5 py-1 bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300 rounded-md text-xs font-medium">
                                            {{ $abstract->subtheme }}
                                        </span>
                                    @endif
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2 leading-tight group-hover:text-author-600 dark:group-hover:text-author-400 transition-colors">
                                    {{ $abstract->title }}
                                </h3>
                                <div class="flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        {{ $abstract->created_at->format('M d, Y') }}
                                    </span>
                                    @if($abstract->updated_at->gt($abstract->created_at))
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Updated {{ $abstract->updated_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Timeline & Actions -->
                            <div class="flex flex-col sm:flex-row items-center gap-8 min-w-[45%]">
                                <!-- Visual Timeline -->
                                <div class="flex-1 w-full sm:w-auto hidden md:block">
                                    <div class="relative flex items-center justify-between w-full {{ $abstract->status === 'accepted' ? 'min-w-[280px]' : 'min-w-[200px]' }}">
                                        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-100 dark:bg-gray-700 rounded-full -z-0"></div>

                                        <!-- Step 1: Submit -->
                                        <div class="relative z-10 flex flex-col items-center gap-2">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $config['step'] >= 1 ? 'bg-author-600 text-white shadow-lg shadow-author-200 dark:shadow-none' : 'bg-slate-200 dark:bg-gray-700 text-slate-400' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <span class="text-[10px] font-semibold uppercase tracking-wider {{ $config['step'] >= 1 ? 'text-author-600 dark:text-author-400' : 'text-slate-400' }}">Submitted</span>
                                        </div>

                                        <!-- Step 2: Review -->
                                        <div class="relative z-10 flex flex-col items-center gap-2">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $config['step'] >= 2 ? 'bg-author-600 text-white shadow-lg shadow-author-200 dark:shadow-none' : ($config['step'] == 2 ? 'bg-author-100 text-author-600 ring-4 ring-author-50' : 'bg-slate-200 dark:bg-gray-700 text-slate-400') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            </div>
                                            <span class="text-[10px] font-semibold uppercase tracking-wider {{ $config['step'] >= 2 ? 'text-author-600 dark:text-author-400' : 'text-slate-400' }}">Reviewed</span>
                                        </div>

                                        @php
                                            // Determine result step color based on status
                                            $resultStepColor = 'bg-slate-200 dark:bg-gray-700 text-slate-400';
                                            $resultTextColor = 'text-slate-400';
                                            $resultLabel = 'Pending';

                                            if ($abstract->status === 'accepted') {
                                                $resultStepColor = 'bg-emerald-600 text-white shadow-lg shadow-emerald-200 dark:shadow-none';
                                                $resultTextColor = 'text-emerald-600 dark:text-emerald-400';
                                                $resultLabel = 'Accepted';
                                            } elseif ($abstract->status === 'rejected') {
                                                $resultStepColor = 'bg-rose-600 text-white shadow-lg shadow-rose-200 dark:shadow-none';
                                                $resultTextColor = 'text-rose-600 dark:text-rose-400';
                                                $resultLabel = 'Rejected';
                                            } elseif (in_array($abstract->status, ['revision_required', 'revision_requested'])) {
                                                $resultStepColor = 'bg-amber-600 text-white shadow-lg shadow-amber-200 dark:shadow-none';
                                                $resultTextColor = 'text-amber-600 dark:text-amber-400';
                                                $resultLabel = 'Revision';
                                            } elseif ($config['step'] >= 4) {
                                                $resultStepColor = 'bg-author-600 text-white shadow-lg shadow-author-200 dark:shadow-none';
                                                $resultTextColor = 'text-author-600 dark:text-author-400';
                                                $resultLabel = 'Decided';
                                            }
                                        @endphp
                                        <div class="relative z-10 flex flex-col items-center gap-2">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $resultStepColor }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                            <span class="text-[10px] font-semibold uppercase tracking-wider {{ $resultTextColor }}">{{ $resultLabel }}</span>
                                        </div>

                                        <!-- Step 4: Presentation (only for accepted abstracts) -->
                                        @if($abstract->status === 'accepted')
                                            @php
                                                $hasPresentation = $abstract->hasActualPresentationFiles();
                                                $presentationStepColor = $hasPresentation ? 'bg-blue-600 text-white shadow-lg shadow-blue-200 dark:shadow-none' : 'bg-slate-200 dark:bg-gray-700 text-slate-400';
                                                $presentationTextColor = $hasPresentation ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400';
                                            @endphp
                                            <a href="{{ route('presentations.show', $abstract) }}" class="relative z-10 flex flex-col items-center gap-2 group cursor-pointer" title="Click to upload/view presentation">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $presentationStepColor }} group-hover:ring-2 group-hover:ring-blue-300 transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                    </svg>
                                                </div>
                                                <span class="text-[10px] font-semibold uppercase tracking-wider {{ $presentationTextColor }} group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Presented</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                                    @if($abstract->status === 'draft')
                                        <form action="{{ route('abstracts.submit', $abstract) }}" method="POST" id="submit-form-{{ $abstract->id }}" class="hidden">
                                            @csrf
                                        </form>
                                        @if($submissionWindowOpen)
                                            <button type="button"
                                                    class="js-open-submit-modal px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-colors shadow-lg shadow-emerald-200 dark:shadow-none"
                                                    data-form-id="submit-form-{{ $abstract->id }}">
                                                Submit
                                            </button>
                                        @else
                                            <span class="px-4 py-2 bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-slate-400 text-sm font-bold rounded-lg border border-slate-200 dark:border-gray-600">
                                                Closed
                                            </span>
                                        @endif
                                        <a href="{{ route('abstracts.edit', $abstract) }}"
                                           class="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold rounded-lg hover:bg-slate-800 dark:hover:bg-slate-100 transition-colors shadow-lg shadow-slate-200 dark:shadow-none">
                                            Edit
                                        </a>
                                        <button type="button"
                                                onclick="openDeleteModal({{ $abstract->id }}, '{{ addslashes($abstract->title) }}')"
                                                class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @elseif(in_array($abstract->status, ['revision_required', 'revision_requested']))
                                        <a href="{{ route('user.abstract.revision', $abstract) }}"
                                           class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-lg transition-colors shadow-lg shadow-amber-200 dark:shadow-none flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Revise
                                        </a>
                                    @elseif($abstract->status === 'accepted' && $abstract->conference_code && $proceedingsCorrectionsOpen)
                                        {{-- Camera-ready window: authors fix what goes into the conference proceedings. --}}
                                        <a href="{{ route('abstracts.proceedings.edit', $abstract) }}"
                                           class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-lg transition-colors shadow-lg shadow-violet-200 dark:shadow-none flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                            Review proceedings entry
                                        </a>
                                    @endif

                                    <a href="{{ route('abstracts.show', $abstract) }}"
                                       class="px-4 py-2 bg-white dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-lg hover:bg-slate-50 dark:hover:bg-gray-600 transition-colors">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-20 bg-white dark:bg-gray-800 rounded-2xl border border-dashed border-slate-300 dark:border-gray-700">
                    <div class="w-20 h-20 bg-author-50 dark:bg-author-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-author-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Start Your Journey</h3>
                    <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-8">
                        {{ $submissionWindowOpen ? "You haven't submitted any abstracts yet. Share your research with the " . config('conference.short_name') . " " . config('conference.year') . " community today." : "New submissions are currently closed. The deadline passed on {$submissionDeadlineText} EAT." }}
                    </p>
                    @if($submissionWindowOpen)
                        <a href="{{ route('abstracts.create') }}"
                           class="inline-flex items-center px-6 py-3 bg-author-600 hover:bg-author-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-author-500/30 transition-all duration-200">
                            Create First Submission
                        </a>
                    @else
                        <div class="inline-flex items-center px-6 py-3 bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-slate-300 font-semibold rounded-xl border border-slate-200 dark:border-gray-600">
                            Submissions Closed
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    <script>
    function applyFilters() {
        const statusFilter = document.getElementById('statusFilter').value;
        const sortFilter = document.getElementById('sortFilter').value;
        const searchText = document.getElementById('searchInput').value.toLowerCase();

        let cards = Array.from(document.querySelectorAll('.abstract-card'));

        cards.forEach(card => {
            const cardStatus = card.dataset.status;
            const cardTitle = card.dataset.title;

            let statusMatch = statusFilter === 'all' ||
                             cardStatus === statusFilter ||
                             (statusFilter === 'revision_required' && (cardStatus.includes('revision') || cardStatus.includes('required')));

            let searchMatch = !searchText ||
                             cardTitle.includes(searchText);

            card.style.display = (statusMatch && searchMatch) ? '' : 'none';
        });

        // Sort logic
        const container = document.getElementById('abstractsList');
        cards.sort((a, b) => {
            const aTitle = a.dataset.title;
            const bTitle = b.dataset.title;
            const aId = parseInt(a.dataset.id);
            const bId = parseInt(b.dataset.id);

            if (sortFilter === 'newest') return bId - aId;
            if (sortFilter === 'oldest') return aId - bId;
            if (sortFilter === 'title_asc') return aTitle.localeCompare(bTitle);
            return 0;
        });

        cards.forEach(card => container.appendChild(card));
    }
    </script>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" onclick="if(event.target === this) closeDeleteModal()">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full transform transition-all scale-100" onclick="event.stopPropagation()">
            <div class="p-6">
                <!-- Icon -->
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-rose-100 dark:bg-rose-900/30">
                    <svg class="w-8 h-8 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>

                <!-- Title -->
                <h3 class="text-xl font-bold text-slate-900 dark:text-white text-center mb-2">
                    Delete Abstract?
                </h3>

                <!-- Message -->
                <p class="text-slate-600 dark:text-slate-300 text-center mb-1">
                    Are you sure you want to delete this draft abstract?
                </p>
                <p class="text-sm font-bold text-slate-900 dark:text-white text-center mb-6 px-4 py-2 bg-slate-50 dark:bg-gray-700/50 rounded-lg border border-slate-100 dark:border-gray-700">
                    "<span id="deleteModalTitle"></span>"
                </p>
                <p class="text-xs text-rose-600 dark:text-rose-400 text-center mb-6 font-bold uppercase tracking-wide">
                    ⚠️ This action cannot be undone
                </p>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button onclick="closeDeleteModal()"
                            class="flex-1 px-4 py-2.5 bg-white dark:bg-gray-700 border border-slate-200 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-600 text-slate-700 dark:text-slate-200 rounded-xl font-bold transition-colors">
                        Cancel
                    </button>
                    <form id="deleteForm" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2.5 bg-rose-600 hover:bg-rose-700 dark:bg-rose-500 dark:hover:bg-rose-600 text-white rounded-xl font-bold transition-colors shadow-lg shadow-rose-200 dark:shadow-none">
                            Delete Abstract
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal(abstractId, abstractTitle) {
            const modal = document.getElementById('deleteModal');
            const form = document.getElementById('deleteForm');
            const titleSpan = document.getElementById('deleteModalTitle');

            form.action = `/abstracts/${abstractId}`;
            titleSpan.textContent = abstractTitle;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>

    <!-- Submit Confirmation Modal -->
    <div id="submit-confirm-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeSubmitModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-gray-700 w-full max-w-md p-6 transform transition-all scale-100">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h3 class="text-xl font-bold text-slate-900 dark:text-white text-center mb-2">Submit Abstract?</h3>
            <p class="text-slate-600 dark:text-slate-300 text-sm text-center mb-6">
                Once submitted, your abstract will be sent for review. You can no longer edit it unless a revision is requested by the committee.
            </p>

            <div class="flex gap-3">
                <button type="button" onclick="closeSubmitModal()" class="flex-1 px-4 py-2.5 bg-white dark:bg-gray-700 border border-slate-200 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-600 text-slate-700 dark:text-slate-200 rounded-xl font-bold transition-colors">
                    Cancel
                </button>
                <button type="button" id="submit-confirm-btn" class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 text-white rounded-xl font-bold transition-colors shadow-lg shadow-emerald-200 dark:shadow-none">
                    Submit Now
                </button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('submit-confirm-modal');
            const confirmBtn = document.getElementById('submit-confirm-btn');
            let pendingFormId = null;

            window.openSubmitModal = function(formId) {
                pendingFormId = formId;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            };

            window.closeSubmitModal = function() {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
                pendingFormId = null;
            };

            document.addEventListener('click', function(e) {
                const trigger = e.target.closest('.js-open-submit-modal');
                if (trigger) {
                    e.preventDefault();
                    const formId = trigger.getAttribute('data-form-id');
                    if (formId) openSubmitModal(formId);
                }
            });

            confirmBtn && confirmBtn.addEventListener('click', function() {
                if (pendingFormId) {
                    const form = document.getElementById(pendingFormId);
                    if (form) form.submit();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeSubmitModal();
            });
        })();
    </script>
</div>
@endsection
