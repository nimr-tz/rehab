@extends('layouts.app')

@section('title', 'Rapporteur Report — ' . ($report->session->name ?? 'Session'))

@section('content')
@php
    $user = Auth::user();
    $author = $report->user ?? $user;
    $session = $report->session;
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">

    {{-- Back + actions --}}
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('rapporteur-reports.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            My Reports
        </a>
        <div class="flex items-center gap-3">
            @if($report->isEditableByAuthor())
            <a href="{{ route('rapporteur-reports.edit', $report) }}"
               class="px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600 transition-all">
                {{ $report->needsRevision() ? 'Revise Report' : 'Edit Draft' }}
            </a>
            @endif
            <a href="{{ route('rapporteur-reports.download', [$report, 'format' => 'pdf']) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                PDF
            </a>
            @switch($report->status)
                @case(\App\Models\RapporteurReport::STATUS_APPROVED)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-emerald-100 text-emerald-700 rounded-full dark:bg-emerald-900/30 dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved
                        {{ $report->approved_at?->format('d M Y, H:i') }}
                    </span>
                    @break
                @case(\App\Models\RapporteurReport::STATUS_NEEDS_REVISION)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-orange-100 text-orange-700 rounded-full dark:bg-orange-900/30 dark:text-orange-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span>Needs Revision
                    </span>
                    @break
                @case(\App\Models\RapporteurReport::STATUS_UNDER_REVIEW)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-sky-100 text-sky-700 rounded-full dark:bg-sky-900/30 dark:text-sky-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>Under Review
                    </span>
                    @break
                @case(\App\Models\RapporteurReport::STATUS_SUBMITTED)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-blue-100 text-blue-700 rounded-full dark:bg-blue-900/30 dark:text-blue-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Submitted
                        {{ $report->submitted_at?->format('d M Y, H:i') }}
                    </span>
                    @break
                @default
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-amber-100 text-amber-700 rounded-full dark:bg-amber-900/30 dark:text-amber-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>Draft
                    </span>
            @endswitch
        </div>
    </div>

    {{-- Chief rapporteur feedback when the report was returned for revision --}}
    @if($report->needsRevision() && $report->chief_feedback)
    <div class="mb-5 px-4 py-4 bg-orange-50 border border-orange-200 rounded-xl dark:bg-orange-900/15 dark:border-orange-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-orange-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-widest text-orange-600 dark:text-orange-400 mb-1">Chief Rapporteur — changes requested</p>
                <p class="text-sm text-orange-800 dark:text-orange-200 whitespace-pre-wrap">{{ $report->chief_feedback }}</p>
                <p class="text-xs text-orange-500 mt-2">Edit your report to address this feedback, then submit again.</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('success'))
    <div class="mb-5 flex items-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm font-medium dark:bg-emerald-900/20 dark:border-emerald-700 dark:text-emerald-300">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Report header --}}
    <div class="bg-gradient-to-r from-brand-600 to-blue-700 rounded-xl p-6 text-white mb-5">
        <p class="text-xs font-black uppercase tracking-widest text-white/70 mb-1">Session Rapporteur Report</p>
        <h1 class="text-xl font-bold">{{ $session->name ?? 'Unknown Session' }}</h1>
        <div class="flex flex-wrap gap-4 mt-3 text-sm text-white/80">
            @if($session->session_type)
            <span>{{ ucfirst(str_replace('_',' ',$session->session_type)) }}</span>
            @endif
            @if(!empty($session->schedule_days))
            <span>&bull; {{ implode(', ', $session->schedule_days) }}</span>
            @endif
            @if($session->start_time)
            <span>&bull; {{ $session->start_time->format('H:i') }}–{{ $session->end_time?->format('H:i') }}</span>
            @endif
            @if($session->room_location)
            <span>&bull; {{ $session->room_location }}</span>
            @endif
        </div>
    </div>

    {{-- ─── TABLE 1: Session identification ─── --}}
    <div class="rr-show-section">
        <h2 class="rr-show-title"><span class="rr-show-num">1</span> Session Identification</h2>

        @if($session->session_chair)
        <div class="rr-show-row">
            <span class="rr-show-label">Session Chair</span>
            <span class="rr-show-value">{{ $session->session_chair }}</span>
        </div>
        @endif

        <div class="mt-4">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Rapporteur 1</p>
            <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm">
                <div><span class="text-slate-400">Name:</span> <span class="font-medium">{{ $user->title ?? '' }} {{ $user->first_name }} {{ $user->last_name }}</span></div>
                <div><span class="text-slate-400">Email:</span> <span class="font-medium">{{ $user->email }}</span></div>
                <div><span class="text-slate-400">Institution:</span> <span class="font-medium">{{ $user->affiliation ?? '—' }}</span></div>
                <div><span class="text-slate-400">Phone:</span> <span class="font-medium">{{ $user->phone ?? '—' }}</span></div>
            </div>
        </div>

        @if($report->rapporteur2)
        @php $r2 = $report->rapporteur2; @endphp
        <div class="mt-4">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Rapporteur 2</p>
            <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm">
                <div><span class="text-slate-400">Name:</span> <span class="font-medium">{{ trim(($r2->title ?? '') . ' ' . $r2->first_name . ' ' . $r2->last_name) }}</span></div>
                <div><span class="text-slate-400">Email:</span> <span class="font-medium">{{ $r2->email }}</span></div>
                @if($r2->affiliation)<div><span class="text-slate-400">Institution:</span> <span class="font-medium">{{ $r2->affiliation }}</span></div>@endif
                @if($r2->phone)<div><span class="text-slate-400">Phone:</span> <span class="font-medium">{{ $r2->phone }}</span></div>@endif
            </div>
        </div>
        @endif
    </div>

    {{-- ─── TABLE 2: Subtheme ─── --}}
    @if($report->subtheme)
    <div class="rr-show-section">
        <h2 class="rr-show-title"><span class="rr-show-num">2</span> {{ config('conference.short_name') }} Subtheme</h2>
        <p class="text-sm text-slate-700 dark:text-slate-300">{{ $report->subtheme }}</p>
    </div>
    @endif

    {{-- ─── TABLE 3: Presentations ─── --}}
    @if(!empty($report->presentations))
    <div class="rr-show-section">
        <h2 class="rr-show-title"><span class="rr-show-num">3</span> Presentation Record</h2>
        <div class="overflow-x-auto -mx-1">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="text-left">
                        <th class="rr-th">Abstract ID</th>
                        <th class="rr-th">Presenter</th>
                        <th class="rr-th">Institution</th>
                        <th class="rr-th">Title</th>
                        <th class="rr-th">Setting</th>
                        <th class="rr-th">Method</th>
                        <th class="rr-th">Key Finding</th>
                        <th class="rr-th">Implication</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->presentations as $i => $p)
                    <tr class="{{ $i % 2 === 0 ? 'bg-slate-50 dark:bg-slate-800/30' : '' }}">
                        <td class="rr-td font-mono text-xs">{{ $p['abstract_id'] ?? '—' }}</td>
                        <td class="rr-td font-medium">{{ $p['presenter'] ?? '—' }}</td>
                        <td class="rr-td">{{ $p['institution'] ?? '—' }}</td>
                        <td class="rr-td">{{ $p['title'] ?? '—' }}</td>
                        <td class="rr-td">{{ $p['study_setting'] ?? '—' }}</td>
                        <td class="rr-td">{{ $p['method'] ?? '—' }}</td>
                        <td class="rr-td">{{ $p['key_finding'] ?? '—' }}</td>
                        <td class="rr-td">{{ $p['implication'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ─── TABLE 4: Discussion ─── --}}
    <div class="rr-show-section">
        <h2 class="rr-show-title"><span class="rr-show-num">4</span> Discussion, Questions, and Responses</h2>

        @if(!empty($report->discussion_questions))
        <div class="overflow-x-auto mb-4">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="rr-th w-8">No.</th>
                        <th class="rr-th">Question / Comment</th>
                        <th class="rr-th">Raised By</th>
                        <th class="rr-th">Response</th>
                        <th class="rr-th w-24">Resolved?</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->discussion_questions as $i => $q)
                    <tr class="{{ $i % 2 === 0 ? 'bg-slate-50 dark:bg-slate-800/30' : '' }}">
                        <td class="rr-td text-center text-xs font-bold text-slate-400">{{ $i + 1 }}</td>
                        <td class="rr-td">{{ $q['question'] ?? '—' }}</td>
                        <td class="rr-td">{{ $q['raised_by'] ?? '—' }}</td>
                        <td class="rr-td">{{ $q['response'] ?? '—' }}</td>
                        <td class="rr-td text-center">
                            @php $res = $q['resolved'] ?? '' @endphp
                            @if($res === 'Yes')
                                <span class="text-xs font-bold text-emerald-600">Yes</span>
                            @elseif($res === 'No')
                                <span class="text-xs font-bold text-red-500">No</span>
                            @elseif($res === 'Partially')
                                <span class="text-xs font-bold text-amber-600">Partially</span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($report->areas_of_agreement)
        <div class="rr-show-row"><span class="rr-show-label">Areas of Agreement</span><span class="rr-show-value">{{ $report->areas_of_agreement }}</span></div>
        @endif
        @if($report->areas_of_debate)
        <div class="rr-show-row"><span class="rr-show-label">Areas of Debate / Uncertainty</span><span class="rr-show-value">{{ $report->areas_of_debate }}</span></div>
        @endif
        @if($report->follow_up_issues)
        <div class="rr-show-row"><span class="rr-show-label">Issues Requiring Follow-Up</span><span class="rr-show-value">{{ $report->follow_up_issues }}</span></div>
        @endif
    </div>

    {{-- ─── TABLE 5: Scientific synthesis ─── --}}
    <div class="rr-show-section">
        <h2 class="rr-show-title"><span class="rr-show-num">5</span> Scientific and Technical Synthesis</h2>

        @if($report->scientific_message_1 || $report->scientific_message_2 || $report->scientific_message_3)
        <div class="mb-4">
            <p class="rr-show-label mb-2">Three Main Scientific / Technical Messages</p>
            @if($report->scientific_message_1)<p class="rr-show-value mb-1"><span class="font-bold text-brand-500">1.</span> {{ $report->scientific_message_1 }}</p>@endif
            @if($report->scientific_message_2)<p class="rr-show-value mb-1"><span class="font-bold text-brand-500">2.</span> {{ $report->scientific_message_2 }}</p>@endif
            @if($report->scientific_message_3)<p class="rr-show-value"><span class="font-bold text-brand-500">3.</span> {{ $report->scientific_message_3 }}</p>@endif
        </div>
        @endif

        @if($report->most_important_finding)
        <div class="rr-show-row"><span class="rr-show-label">Most Important Finding</span><span class="rr-show-value">{{ $report->most_important_finding }}</span></div>
        @endif

        @if(!empty($report->evidence_nature))
        <div class="rr-show-row">
            <span class="rr-show-label">Nature of Evidence</span>
            <div class="flex flex-wrap gap-2 mt-1">
                @foreach($report->evidence_nature as $n)
                <span class="px-2 py-0.5 text-xs bg-brand-50 text-brand-700 dark:bg-brand-900/20 dark:text-brand-400 rounded-full border border-brand-200 dark:border-brand-700">{{ $n }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if($report->evidence_status)
        <div class="rr-show-row"><span class="rr-show-label">Status of Evidence</span><span class="rr-show-value">{{ $report->evidence_status }}</span></div>
        @endif
        @if($report->important_method)
        <div class="rr-show-row"><span class="rr-show-label">Important Method / Tool / Innovation</span><span class="rr-show-value">{{ $report->important_method }}</span></div>
        @endif
        @if($report->main_limitation)
        <div class="rr-show-row"><span class="rr-show-label">Main Limitation or Caution</span><span class="rr-show-value">{{ $report->main_limitation }}</span></div>
        @endif
    </div>

    {{-- ─── TABLE 6: Recommendations ─── --}}
    @if(!empty($report->recommendations))
    <div class="rr-show-section">
        <h2 class="rr-show-title"><span class="rr-show-num">6</span> Recommendations</h2>
        <div class="space-y-4">
            @foreach($report->recommendations as $i => $rec)
            @if(!empty($rec['recommendation']))
            <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                <div class="flex items-start justify-between gap-4 mb-3">
                    <span class="text-xs font-black uppercase tracking-widest text-slate-400">Recommendation {{ $i + 1 }}</span>
                    <div class="flex gap-2">
                        @if(!empty($rec['priority']))
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full
                            {{ $rec['priority'] === 'High' ? 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400' : ($rec['priority'] === 'Medium' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400') }}">
                            {{ $rec['priority'] }} Priority
                        </span>
                        @endif
                        @if(!empty($rec['timeline']))
                        <span class="px-2 py-0.5 text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400 rounded-full">{{ $rec['timeline'] }}</span>
                        @endif
                    </div>
                </div>
                <p class="text-sm text-slate-800 dark:text-slate-200 font-medium mb-2">{{ $rec['recommendation'] }}</p>
                @if(!empty($rec['basis']))
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3"><span class="font-semibold">Basis:</span> {{ $rec['basis'] }}</p>
                @endif
                @if(!empty($rec['target_audience']))
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Target Audience</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($rec['target_audience'] as $aud)
                        <span class="px-2 py-0.5 text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-full">{{ $aud }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="text-center text-xs text-slate-400 mt-6">
        Report created {{ $report->created_at->format('d M Y') }}
        @if($report->isSubmitted())
            &bull; Submitted {{ $report->submitted_at->format('d M Y, H:i') }}
        @else
            &bull; Last saved {{ $report->updated_at->diffForHumans() }}
        @endif
    </div>

</div>

@push('styles')
<style>
.rr-show-section { background: white; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.25rem; }
.dark .rr-show-section { background: #1e293b; border-color: #334155; }
.rr-show-title { display: flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem; }
.dark .rr-show-title { color: white; }
.rr-show-num { display: inline-flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; background: #3a86ff; color: white; font-size: 0.75rem; font-weight: 900; border-radius: 0.5rem; flex-shrink: 0; }
.rr-show-row { display: flex; flex-direction: column; gap: 0.25rem; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; }
.dark .rr-show-row { border-bottom-color: #334155; }
.rr-show-row:last-child { border-bottom: none; }
.rr-show-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; }
.rr-show-value { font-size: 0.875rem; color: #334155; white-space: pre-wrap; }
.dark .rr-show-value { color: #cbd5e1; }
.rr-th { padding: 0.5rem 0.75rem; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.dark .rr-th { background: #0f172a; border-color: #334155; color: #94a3b8; }
.rr-td { padding: 0.5rem 0.75rem; font-size: 0.8rem; color: #475569; border-bottom: 1px solid #f1f5f9; vertical-align: top; min-width: 100px; }
.dark .rr-td { color: #94a3b8; border-color: #1e293b; }
</style>
@endpush
@endsection
