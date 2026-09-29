@extends('layouts.app')

@section('title', 'Decision — ' . $abstract->title)

@section('content')
@php
    $submittedReviews = $abstract->reviews->where('status', 'submitted');
    $hasPendingReviews = $abstract->reviews->where('status', 'draft')->count() > 0;
    $currentRevisionRound = $abstract->revision_round ?? 0;
    $isResolved = in_array($abstract->status, ['accepted', 'withdrawn']);
    $isWaiting = in_array($abstract->status, ['revision_required', 'revision_requested']);
    $isRejected = $abstract->status === 'rejected';
    $isActionable = !$isResolved; // Admin can always decide unless accepted or withdrawn

    $avgScore = $submittedReviews->count() > 0 ? round($submittedReviews->avg('score')) : null;
    $recommendations = $submittedReviews->pluck('recommendation')->map(fn($r) => strtolower($r));
    $allAccept = $recommendations->count() > 0 && $recommendations->every(fn($r) => str_contains($r, 'accept'));
    $allReject = $recommendations->count() > 0 && $recommendations->every(fn($r) => str_contains($r, 'reject'));
    $isConflicted = $recommendations->count() >= 2 && !$allAccept && !$allReject;

    $statusLabels = [
        'ready_for_decision'       => ['label' => 'Ready for Decision', 'class' => 'bg-blue-600 text-white'],
        'under_review'             => ['label' => 'Under Review',        'class' => 'bg-violet-600 text-white'],
        'revision_submitted'       => ['label' => 'Revision Submitted',  'class' => 'bg-amber-500 text-white'],
        'minor_revision_submitted' => ['label' => 'Revision Submitted',  'class' => 'bg-amber-500 text-white'],
        'major_revision_submitted' => ['label' => 'Revision Submitted',  'class' => 'bg-amber-500 text-white'],
        'revision_required'        => ['label' => 'Awaiting Revision',   'class' => 'bg-orange-500 text-white'],
        'revision_requested'       => ['label' => 'Awaiting Revision',   'class' => 'bg-orange-500 text-white'],
        'submitted'                => ['label' => 'Submitted',           'class' => 'bg-slate-500 text-white'],
        'reviewer_assigned'        => ['label' => 'Reviewer Assigned',   'class' => 'bg-indigo-500 text-white'],
        'accepted'                 => ['label' => 'Accepted',            'class' => 'bg-emerald-600 text-white'],
        'rejected'                 => ['label' => 'Rejected',            'class' => 'bg-red-600 text-white'],
    ];
    $statusInfo = $statusLabels[$abstract->status] ?? ['label' => ucfirst(str_replace('_', ' ', $abstract->status)), 'class' => 'bg-slate-500 text-white'];
@endphp

<div class="min-h-screen bg-slate-100 dark:bg-gray-950">
<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    {{-- Back link --}}
    <a href="{{ route('admin.decisions.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 mb-6 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to list
    </a>

    {{-- Page header --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-800 overflow-hidden mb-6">
        <div class="px-8 py-6 flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-3">
                    <span class="px-3 py-1 rounded-full text-xs font-bold tracking-wide {{ $statusInfo['class'] }}">
                        {{ $statusInfo['label'] }}
                    </span>
                    @if($abstract->presentation_mode)
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ $abstract->presentation_mode }}
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white leading-snug mb-2">{{ $abstract->title }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $abstract->author_name }}</span>
                    &nbsp;&middot;&nbsp;{{ $abstract->author_institute }}
                    @if($abstract->user?->email)
                        &nbsp;&middot;&nbsp;{{ $abstract->user->email }}
                    @endif
                </p>
            </div>

            {{-- Review signal --}}
            @if($submittedReviews->count() > 0)
            <div class="shrink-0 flex items-center gap-4">
                @if($avgScore !== null)
                <div class="text-center">
                    <div class="text-3xl font-black {{ $avgScore >= 70 ? 'text-emerald-600' : ($avgScore >= 50 ? 'text-amber-500' : 'text-red-500') }}">
                        {{ $avgScore }}
                    </div>
                    <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Avg Score</div>
                </div>
                @endif
                <div class="text-center">
                    @if($allAccept)
                        <div class="text-2xl font-black text-emerald-600">✓</div>
                        <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Consensus</div>
                    @elseif($allReject)
                        <div class="text-2xl font-black text-red-500">✗</div>
                        <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Consensus</div>
                    @elseif($isConflicted)
                        <div class="text-2xl font-black text-amber-500">⚡</div>
                        <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Conflict</div>
                    @endif
                </div>
                <div class="text-center">
                    <div class="text-3xl font-black text-slate-700 dark:text-slate-200">{{ $submittedReviews->count() }}</div>
                    <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Reviews</div>
                </div>
            </div>
            @endif
        </div>

        {{-- Meta strip --}}
        <div class="px-8 py-3 bg-slate-50 dark:bg-gray-800/60 border-t border-slate-100 dark:border-gray-800 flex flex-wrap gap-6 text-xs text-slate-500 dark:text-slate-400">
            <span><strong class="text-slate-700 dark:text-slate-300">Subtheme:</strong> {{ $abstract->subtheme }}</span>
            <span><strong class="text-slate-700 dark:text-slate-300">Submitted:</strong> {{ $abstract->created_at?->format('M d, Y') }}</span>
            <span><strong class="text-slate-700 dark:text-slate-300">Days open:</strong> {{ intval($abstract->created_at?->diffInDays()) }}</span>
            @if($hasPendingReviews)
                <span class="text-amber-600 font-semibold">{{ $abstract->reviews->where('status', 'draft')->count() }} review(s) still pending</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Left: Abstract content + chart --}}
        <div class="lg:col-span-3 space-y-6">

            {{-- Abstract body --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-gray-800 bg-slate-50 dark:bg-gray-800/50">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Abstract</h2>
                </div>
                <div class="p-6 prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 leading-relaxed">
                    {!! $abstract->description !!}
                </div>
                @if($abstract->keywords)
                <div class="px-6 pb-5">
                    <p class="text-xs text-slate-400"><span class="font-semibold text-slate-500">Keywords:</span> {{ $abstract->keywords }}</p>
                </div>
                @endif
            </div>

            {{-- Reviewer consensus chart --}}
            @if($submittedReviews->count() > 0)
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-gray-800 bg-slate-50 dark:bg-gray-800/50 flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Reviewer Scores</h2>
                    @if($isConflicted)
                        <span class="text-xs font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-2.5 py-1 rounded-full border border-amber-200 dark:border-amber-800">Reviewers disagree</span>
                    @endif
                </div>
                <div class="p-6 flex flex-col md:flex-row gap-6 items-center">
                    <div class="relative w-64 h-64 shrink-0 mx-auto">
                        <canvas id="consensusChart"></canvas>
                        <div id="chartLoading" class="absolute inset-0 flex items-center justify-center">
                            <div class="w-5 h-5 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                        </div>
                    </div>
                    <div class="flex-1 w-full space-y-2">
                        @foreach($submittedReviews->sortByDesc('review_round')->unique('reviewer_id')->values() as $i => $review)
                        <div class="flex items-center justify-between py-2.5 px-4 rounded-xl {{ $loop->even ? 'bg-slate-50 dark:bg-gray-800/60' : '' }}">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs font-bold flex items-center justify-center">
                                    R{{ $i + 1 }}
                                </span>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ $review->reviewer ? trim($review->reviewer->first_name . ' ' . $review->reviewer->last_name) : 'Reviewer ' . ($i + 1) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-base font-bold {{ $review->score >= 70 ? 'text-emerald-600' : ($review->score >= 50 ? 'text-amber-500' : 'text-red-500') }}">
                                    {{ $review->score }}<span class="text-xs font-normal text-slate-400">/100</span>
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold
                                    @if(str_contains($review->recommendation, 'accept')) bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                    @elseif(str_contains($review->recommendation, 'reject')) bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                    @else bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $review->recommendation)) }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Revision history (if any) --}}
            @if($abstract->admin_comment && $currentRevisionRound > 0)
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm p-6">
                <h2 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Admin Revision Request</h2>
                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">{{ $abstract->admin_comment }}</p>
                @if($abstract->revision_requested_at)
                    <p class="text-xs text-slate-400 mt-3">{{ $abstract->revision_requested_at->format('M d, Y \a\t g:i A') }}</p>
                @endif
            </div>
            @endif

            @if($abstract->revision_feedback && $currentRevisionRound > 0)
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm p-6">
                <h2 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Author Response</h2>
                <div class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{!! $abstract->revision_feedback !!}</div>
                @if($abstract->revision_submitted_at)
                    <p class="text-xs text-slate-400 mt-3">{{ $abstract->revision_submitted_at->format('M d, Y \a\t g:i A') }}</p>
                @endif
            </div>
            @endif

        </div>

        {{-- Right: Decision panel --}}
        <div class="lg:col-span-2">
            <div class="sticky top-6">

                @if(!$isResolved)
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-gray-800 bg-slate-50 dark:bg-gray-800/50">
                        <h2 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                            Make a Decision
                        </h2>
                    </div>

                    @if($isWaiting)
                    <div class="mx-5 mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-700 dark:text-amber-300">
                        This abstract is awaiting author revisions — you are overriding that and making a final decision now.
                    </div>
                    @elseif($isRejected)
                    <div class="mx-5 mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-xs text-red-700 dark:text-red-300">
                        This abstract was previously rejected — you are overriding that decision.
                    </div>
                    @endif

                    <form method="POST" action="{{ route('admin.decisions.process', $abstract) }}" class="p-6 space-y-4">
                        @csrf

                        <div class="space-y-3">
                            <label class="group flex items-center gap-4 p-4 border-2 border-slate-200 dark:border-gray-700 rounded-xl cursor-pointer transition-all hover:border-emerald-400 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 has-[:checked]:shadow-sm">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center shrink-0 group-hover:bg-emerald-200 dark:group-hover:bg-emerald-900/60 transition-colors">
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-900 dark:text-white">Accept</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Approve for presentation at {{ config('conference.short_name') }} {{ config('conference.year') }}</p>
                                </div>
                                <input type="radio" name="decision_type" value="accept" class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            </label>

                            <label class="group flex items-center gap-4 p-4 border-2 border-slate-200 dark:border-gray-700 rounded-xl cursor-pointer transition-all hover:border-red-400 hover:bg-red-50/50 dark:hover:bg-red-900/10 has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20 has-[:checked]:shadow-sm">
                                <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center shrink-0 group-hover:bg-red-200 dark:group-hover:bg-red-900/60 transition-colors">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-900 dark:text-white">Reject</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Decline this submission</p>
                                </div>
                                <input type="radio" name="decision_type" value="reject" class="text-red-600 focus:ring-red-500 w-4 h-4">
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Internal Notes</label>
                            <textarea name="admin_notes" rows="3"
                                      class="w-full rounded-xl border-slate-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                                      placeholder="Private notes — not sent to author..."></textarea>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="notify_author" name="notify_author" value="1" checked
                                   class="rounded border-slate-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                            <label for="notify_author" class="text-sm text-slate-600 dark:text-slate-400 cursor-pointer select-none">
                                Notify author via email
                            </label>
                        </div>

                        <button type="submit"
                                class="w-full py-3 bg-slate-900 hover:bg-slate-700 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition-colors shadow-sm tracking-wide">
                            Confirm Decision
                        </button>
                    </form>
                </div>

                @elseif($isResolved)
                <div class="bg-white dark:bg-gray-900 rounded-2xl border-2 {{ $abstract->status === 'accepted' ? 'border-emerald-400' : 'border-red-400' }} shadow-sm p-6 text-center">
                    <div class="w-14 h-14 rounded-full {{ $abstract->status === 'accepted' ? 'bg-emerald-100 dark:bg-emerald-900/40' : 'bg-red-100 dark:bg-red-900/40' }} flex items-center justify-center mx-auto mb-3">
                        @if($abstract->status === 'accepted')
                            <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                    </div>
                    <p class="font-bold text-slate-900 dark:text-white text-lg capitalize">{{ $abstract->status }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ $abstract->updated_at->format('M d, Y') }}</p>
                </div>

                @endif

            </div>
        </div>

    </div>
</div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('consensusChart');
    if (!ctx) return;

    const reviewerData = {!! $submittedReviews->sortByDesc('review_round')->unique('reviewer_id')->values()->map(function($review, $i) {
        return [
            'label' => 'R' . ($i + 1) . ($review->reviewer ? ' — ' . trim($review->reviewer->first_name . ' ' . $review->reviewer->last_name) : ''),
            'data' => [
                (float)($review->title_score ?? 0) / 4 * 100,
                (float)(($review->word_count_score ?? 0) + ($review->writing_quality_score ?? 0) + ($review->structure_score ?? 0)) / 11 * 100,
                (float)(($review->background_score ?? 0) + ($review->rationale_score ?? 0)) / 10 * 100,
                (float)($review->objective_score ?? 0) / 10 * 100,
                (float)(($review->methodology_design_score ?? 0) + ($review->methodology_analysis_score ?? 0)) / 15 * 100,
                (float)(($review->results_logic_score ?? 0) + ($review->results_findings_score ?? 0) + ($review->results_data_score ?? 0)) / 30 * 100,
                (float)(($review->conclusion_interpretation_score ?? 0) + ($review->conclusion_impact_score ?? 0)) / 15 * 100,
                (float)($review->relevance_theme_score ?? 0) / 5 * 100,
            ],
        ];
    })->values()->toJson() !!};

    const loadingEl = document.getElementById('chartLoading');

    const init = () => {
        if (typeof Chart === 'undefined') return;
        if (loadingEl) loadingEl.style.display = 'none';
        if (!reviewerData.length) return;

        const palette = [
            { border: 'rgba(99,102,241,1)',  bg: 'rgba(99,102,241,0.12)'  },
            { border: 'rgba(236,72,153,1)',  bg: 'rgba(236,72,153,0.12)'  },
            { border: 'rgba(245,158,11,1)',  bg: 'rgba(245,158,11,0.12)'  },
        ];

        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Title','Quality','Background','Objective','Method.','Results','Conclusion','Relevance'],
                datasets: reviewerData.map((r, i) => ({
                    label: r.label,
                    data: r.data,
                    fill: true,
                    backgroundColor: palette[i % palette.length].bg,
                    borderColor: palette[i % palette.length].border,
                    pointBackgroundColor: palette[i % palette.length].border,
                    borderWidth: 2,
                    pointRadius: 3,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true, min: 0, max: 100,
                        ticks: { stepSize: 25, display: false },
                        grid: { color: 'rgba(148,163,184,0.15)', circular: true },
                        angleLines: { color: 'rgba(148,163,184,0.15)' },
                        pointLabels: { font: { size: 9, weight: '600' }, color: '#94a3b8' },
                    },
                },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, font: { size: 10 } } },
                },
            },
        });
    };

    if (typeof Chart !== 'undefined') {
        init();
    } else {
        let n = 0;
        const t = setInterval(() => {
            if (typeof Chart !== 'undefined') { clearInterval(t); init(); }
            else if (++n > 50) { clearInterval(t); if (loadingEl) loadingEl.innerHTML = ''; }
        }, 100);
    }
});
</script>
@endpush
@endsection
