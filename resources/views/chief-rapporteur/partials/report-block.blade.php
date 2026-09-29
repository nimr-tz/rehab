@php $session = $report->session; @endphp
<div class="report">
    <h3>{{ $session->name ?? 'Session' }}</h3>
    <div class="byline">
        Rapporteur: {{ trim(($report->user->title ?? '') . ' ' . ($report->user->first_name ?? '') . ' ' . ($report->user->last_name ?? '')) ?: ($report->user->email ?? '—') }}
        @if($report->rapporteur2) &amp; {{ trim(($report->rapporteur2->first_name ?? '') . ' ' . ($report->rapporteur2->last_name ?? '')) }} @endif
        @if(!empty($session?->schedule_days)) · {{ implode(', ', $session->schedule_days) }} @endif
        @if($session?->start_time) · {{ $session->start_time->format('H:i') }}–{{ $session->end_time?->format('H:i') }} @endif
        @if($report->subtheme) · {{ $report->subtheme }} @endif
    </div>

    @if(!empty($report->presentations))
    <div class="sec-label">Presentations</div>
    <table class="pres">
        <tr><th>ID</th><th>Presenter</th><th>Title</th><th>Key Finding</th><th>Implication</th></tr>
        @foreach($report->presentations as $p)
            @continue(empty($p['presenter']) && empty($p['title']) && empty($p['key_finding']))
            <tr>
                <td>{{ $p['abstract_id'] ?? '' }}</td>
                <td>{{ $p['presenter'] ?? '' }}</td>
                <td>{{ $p['title'] ?? '' }}</td>
                <td>{{ $p['key_finding'] ?? '' }}</td>
                <td>{{ $p['implication'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>
    @endif

    @if($report->scientific_message_1 || $report->scientific_message_2 || $report->scientific_message_3)
    <div class="sec-label">Main Scientific Messages</div>
    @if($report->scientific_message_1)<p>1. {{ $report->scientific_message_1 }}</p>@endif
    @if($report->scientific_message_2)<p>2. {{ $report->scientific_message_2 }}</p>@endif
    @if($report->scientific_message_3)<p>3. {{ $report->scientific_message_3 }}</p>@endif
    @endif

    @if($report->most_important_finding)
    <div class="sec-label">Most Important Finding</div>
    <p>{{ $report->most_important_finding }}</p>
    @endif

    @if($report->areas_of_agreement)
    <div class="sec-label">Areas of Agreement</div>
    <p>{{ $report->areas_of_agreement }}</p>
    @endif
    @if($report->areas_of_debate)
    <div class="sec-label">Areas of Debate / Uncertainty</div>
    <p>{{ $report->areas_of_debate }}</p>
    @endif
    @if($report->follow_up_issues)
    <div class="sec-label">Issues Requiring Follow-Up</div>
    <p>{{ $report->follow_up_issues }}</p>
    @endif

    @if(!empty($report->recommendations))
    <div class="sec-label">Recommendations</div>
    @foreach($report->recommendations as $rec)
        @continue(empty($rec['recommendation']))
        <div class="rec">
            @if(!empty($rec['priority']))<span class="tag">{{ $rec['priority'] }} priority</span>@endif
            @if(!empty($rec['timeline']))<span class="tag">{{ $rec['timeline'] }}</span>@endif
            <p>{{ $rec['recommendation'] }}</p>
            @if(!empty($rec['basis']))<p style="color:#64748b;"><em>Basis: {{ $rec['basis'] }}</em></p>@endif
            @if(!empty($rec['target_audience']))<p style="color:#64748b; font-size:9px;">Audience: {{ implode('; ', $rec['target_audience']) }}</p>@endif
        </div>
    @endforeach
    @endif
</div>
