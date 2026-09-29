{{--
    Read-only display of a rapporteur report (Tables 1–6).
    Expects: $report (RapporteurReport), $author (User who filed it).
--}}
@php $session = $report->session; @endphp

{{-- ─── TABLE 1: Session identification ─── --}}
<div class="rr-show-section">
    <h2 class="rr-show-title"><span class="rr-show-num">1</span> Session Identification</h2>

    @if($session?->session_chair)
    <div class="rr-show-row">
        <span class="rr-show-label">Session Chair</span>
        <span class="rr-show-value">{{ $session->session_chair }}</span>
    </div>
    @endif

    <div class="mt-4">
        <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Rapporteur 1</p>
        <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm">
            <div><span class="text-slate-400">Name:</span> <span class="font-medium">{{ $author->title ?? '' }} {{ $author->first_name }} {{ $author->last_name }}</span></div>
            <div><span class="text-slate-400">Email:</span> <span class="font-medium">{{ $author->email }}</span></div>
            <div><span class="text-slate-400">Institution:</span> <span class="font-medium">{{ $author->affiliation ?? '—' }}</span></div>
            <div><span class="text-slate-400">Phone:</span> <span class="font-medium">{{ $author->phone ?? '—' }}</span></div>
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
