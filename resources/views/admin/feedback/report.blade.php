<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ config('conference.short_name') }} Feedback Committee Report</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; background: #fff; }

  /* Cover / Header */
  .cover {
    background: #1e3a5f;
    color: #fff;
    padding: 40px 40px 30px;
    margin-bottom: 24px;
  }
  .cover h1 { font-size: 22px; font-weight: bold; margin-bottom: 6px; letter-spacing: 0.5px; }
  .cover .sub { font-size: 12px; color: #93c5fd; margin-bottom: 16px; }
  .cover .meta { font-size: 9px; color: #cbd5e1; border-top: 1px solid #334155; padding-top: 10px; }
  .cover .meta span { margin-right: 24px; }

  /* Sections */
  .section { margin: 0 30px 24px; }
  .section-title {
    font-size: 12px; font-weight: bold; text-transform: uppercase;
    letter-spacing: 1px; color: #1e3a5f;
    border-bottom: 2px solid #1e3a5f;
    padding-bottom: 4px; margin-bottom: 12px;
  }

  /* KPI Cards row */
  .kpi-row { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 8px; }
  .kpi-cell {
    width: 25%; text-align: center;
    background: #f0f4ff; border: 1px solid #c7d2fe;
    border-radius: 6px; padding: 12px 8px;
  }
  .kpi-value { font-size: 22px; font-weight: bold; color: #1e3a5f; }
  .kpi-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
  .kpi-cell.green { background: #f0fdf4; border-color: #86efac; }
  .kpi-cell.green .kpi-value { color: #166534; }
  .kpi-cell.amber { background: #fffbeb; border-color: #fcd34d; }
  .kpi-cell.amber .kpi-value { color: #92400e; }

  /* Tables */
  table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  table.data th {
    background: #e8edf7; color: #1e3a5f; font-weight: bold;
    font-size: 9px; padding: 5px 8px; text-align: left;
    border: 1px solid #c7d2fe;
  }
  table.data td { padding: 5px 8px; border: 1px solid #e2e8f0; vertical-align: top; font-size: 9.5px; }
  table.data tr:nth-child(even) td { background: #f8fafc; }

  /* Rating bar */
  .bar-wrap { display: inline-block; width: 90px; height: 8px; background: #e2e8f0; border-radius: 4px; vertical-align: middle; margin-left: 6px; }
  .bar-fill  { height: 8px; border-radius: 4px; background: #3b82f6; }
  .bar-fill.good  { background: #22c55e; }
  .bar-fill.warn  { background: #f59e0b; }
  .bar-fill.bad   { background: #ef4444; }

  /* Distribution row */
  .dist-table { width: 100%; border-collapse: collapse; }
  .dist-table td { padding: 3px 8px; font-size: 9px; }

  /* Qualitative block */
  .qual-block { margin-bottom: 16px; }
  .qual-subtitle { font-size: 10px; font-weight: bold; color: #1e3a5f; margin-bottom: 6px; }
  .response-item {
    border-left: 3px solid #3b82f6; padding: 5px 8px;
    margin-bottom: 5px; background: #f8fafc; font-size: 9px;
    color: #334155; line-height: 1.5;
  }
  .response-meta { font-size: 8px; color: #94a3b8; margin-top: 2px; }

  /* Two-column layout */
  .two-col { width: 100%; border-collapse: separate; border-spacing: 12px 0; }
  .two-col td { width: 50%; vertical-align: top; }

  .page-break { page-break-after: always; }
  .no-break { page-break-inside: avoid; }

  .footer { margin: 20px 30px 0; padding-top: 8px; border-top: 1px solid #e2e8f0;
    font-size: 8px; color: #94a3b8; }
</style>
</head>
<body>

{{-- ─── COVER ─── --}}
<div class="cover">
  <h1>{{ $conferenceName }}</h1>
  <div class="sub">Post-Conference Feedback — Organizing Committee Report</div>
  <div class="meta">
    <span>Generated: {{ $generatedAt }}</span>
    <span>Total Responses: {{ $total }}</span>
    <span>Report prepared for: Organizing Committee</span>
  </div>
</div>

{{-- ─── 1. EXECUTIVE SUMMARY ─── --}}
<div class="section">
  <div class="section-title">1. Executive Summary</div>
  <table class="kpi-row">
    <tr>
      <td class="kpi-cell">
        <div class="kpi-value">{{ number_format($avg['overall'] ?? 0, 1) }}<span style="font-size:12px;color:#64748b">/5</span></div>
        <div class="kpi-label">Overall Satisfaction</div>
      </td>
      <td class="kpi-cell {{ $nps >= 50 ? 'green' : ($nps >= 0 ? 'amber' : '') }}">
        <div class="kpi-value">{{ $nps }}</div>
        <div class="kpi-label">Net Promoter Score</div>
      </td>
      <td class="kpi-cell green">
        <div class="kpi-value">{{ $recommendStats['definitely'] + $recommendStats['probably'] }}<span style="font-size:10px;color:#64748b"> / {{ $total }}</span></div>
        <div class="kpi-label">Would Recommend</div>
      </td>
      <td class="kpi-cell">
        <div class="kpi-value">{{ $futureStats['definitely'] + $futureStats['probably'] }}<span style="font-size:10px;color:#64748b"> / {{ $total }}</span></div>
        <div class="kpi-label">Likely to Return</div>
      </td>
    </tr>
  </table>

  {{-- Met Expectations --}}
  <table class="data" style="margin-top:12px">
    <tr>
      <th colspan="3">Did the conference meet your expectations?</th>
    </tr>
    @php
      $expLabels = ['exceeded'=>'Exceeded','fully_met'=>'Fully Met','partially_met'=>'Partially Met','not_met'=>'Not Met'];
      $expTotal = array_sum($metExpectations);
    @endphp
    @foreach($expLabels as $key => $label)
      @if(($metExpectations[$key] ?? 0) > 0 || true)
      <tr>
        <td style="width:30%">{{ $label }}</td>
        <td style="width:10%;text-align:right">{{ $metExpectations[$key] ?? 0 }}</td>
        <td>
          @php $pct = $expTotal > 0 ? (($metExpectations[$key] ?? 0) / $expTotal * 100) : 0; @endphp
          <div class="bar-wrap"><div class="bar-fill {{ $key=='exceeded'||$key=='fully_met' ? 'good' : ($key=='partially_met' ? 'warn' : 'bad') }}" style="width:{{ round($pct) }}%"></div></div>
          <span style="margin-left:6px;font-size:9px">{{ round($pct) }}%</span>
        </td>
      </tr>
      @endif
    @endforeach
  </table>

  {{-- Overall rating distribution --}}
  <table class="data" style="margin-top:4px">
    <tr><th colspan="3">Overall Satisfaction Distribution (1–5 stars)</th></tr>
    @for($s = 5; $s >= 1; $s--)
    <tr>
      <td style="width:30%">{{ str_repeat('★', $s) }}{{ str_repeat('☆', 5-$s) }} ({{ $s }})</td>
      <td style="width:10%;text-align:right">{{ $satisfactionDist[$s] ?? 0 }}</td>
      <td>
        @php $pct = $total > 0 ? (($satisfactionDist[$s] ?? 0) / $total * 100) : 0; @endphp
        <div class="bar-wrap"><div class="bar-fill {{ $s >= 4 ? 'good' : ($s == 3 ? 'warn' : 'bad') }}" style="width:{{ round($pct) }}%"></div></div>
        <span style="margin-left:6px;font-size:9px">{{ round($pct) }}%</span>
      </td>
    </tr>
    @endfor
  </table>
</div>

{{-- ─── 2. CATEGORY RATINGS ─── --}}
<div class="section page-break">
  <div class="section-title">2. Category Ratings</div>
  <p style="font-size:9px;color:#64748b;margin-bottom:10px">All scores out of 5.0. Items with no responses are omitted.</p>
  @foreach($categoryAvgs as $group => $items)
  <div class="no-break" style="margin-bottom:10px">
    <div style="font-size:10px;font-weight:bold;color:#334155;background:#e8edf7;padding:4px 8px;border-left:4px solid #1e3a5f;margin-bottom:2px">{{ $group }}</div>
    <table class="data">
      @foreach($items as $label => $score)
        @if(!is_null($score))
        @php $pct = min(round($score / 5 * 100), 100); @endphp
        <tr>
          <td style="width:45%">{{ $label }}</td>
          <td style="width:12%;text-align:center;font-weight:bold">{{ number_format($score, 1) }}</td>
          <td>
            <div class="bar-wrap"><div class="bar-fill {{ $score >= 4 ? 'good' : ($score >= 3 ? 'warn' : 'bad') }}" style="width:{{ $pct }}%"></div></div>
          </td>
        </tr>
        @endif
      @endforeach
    </table>
  </div>
  @endforeach

  {{-- Emerging areas --}}
  @if(!empty($emergingAreas))
  <div class="no-break" style="margin-top:8px">
    <div style="font-size:10px;font-weight:bold;color:#334155;background:#e8edf7;padding:4px 8px;border-left:4px solid #1e3a5f;margin-bottom:2px">Emerging Research Areas Covered</div>
    <table class="data">
      @php $eTotal = array_sum($emergingAreas); @endphp
      @foreach(['yes'=>'Yes','partially'=>'Partially','no'=>'No'] as $k => $lbl)
        @if(isset($emergingAreas[$k]))
        <tr>
          <td style="width:45%">{{ $lbl }}</td>
          <td style="width:12%;text-align:center">{{ $emergingAreas[$k] }}</td>
          <td><span style="font-size:9px">{{ $eTotal > 0 ? round($emergingAreas[$k]/$eTotal*100) : 0 }}%</span></td>
        </tr>
        @endif
      @endforeach
    </table>
  </div>
  @endif

  {{-- Review fairness --}}
  @if(!empty($reviewFair))
  <div class="no-break" style="margin-top:8px">
    <div style="font-size:10px;font-weight:bold;color:#334155;background:#e8edf7;padding:4px 8px;border-left:4px solid #1e3a5f;margin-bottom:2px">Was the Abstract Review Process Fair?</div>
    <table class="data">
      @php $rfTotal = array_sum($reviewFair); @endphp
      @foreach(['yes'=>'Yes','no'=>'No','unsure'=>'Unsure'] as $k => $lbl)
        @if(isset($reviewFair[$k]))
        <tr>
          <td style="width:45%">{{ $lbl }}</td>
          <td style="width:12%;text-align:center">{{ $reviewFair[$k] }}</td>
          <td><span style="font-size:9px">{{ $rfTotal > 0 ? round($reviewFair[$k]/$rfTotal*100) : 0 }}%</span></td>
        </tr>
        @endif
      @endforeach
    </table>
  </div>
  @endif
</div>

{{-- ─── 3. FORWARD-LOOKING INDICATORS ─── --}}
<div class="section">
  <div class="section-title">3. Forward-Looking Indicators</div>
  <table class="two-col">
    <tr>
      <td>
        <table class="data">
          <tr><th colspan="3">Likely to Attend Next Year</th></tr>
          @foreach(['definitely'=>'Definitely','probably'=>'Probably','maybe'=>'Maybe','no'=>'No'] as $k => $lbl)
          <tr>
            <td>{{ $lbl }}</td>
            <td style="text-align:right;width:10%">{{ $futureStats[$k] }}</td>
            <td>
              @php $pct = $total > 0 ? round($futureStats[$k]/$total*100) : 0; @endphp
              <div class="bar-wrap"><div class="bar-fill {{ in_array($k,['definitely','probably']) ? 'good' : ($k=='maybe' ? 'warn' : 'bad') }}" style="width:{{ $pct }}%"></div></div>
              <span style="font-size:9px;margin-left:4px">{{ $pct }}%</span>
            </td>
          </tr>
          @endforeach
        </table>
      </td>
      <td>
        <table class="data">
          <tr><th colspan="3">Likely to Recommend to a Colleague</th></tr>
          @foreach(['definitely'=>'Definitely','probably'=>'Probably','maybe'=>'Maybe','no'=>'No'] as $k => $lbl)
          <tr>
            <td>{{ $lbl }}</td>
            <td style="text-align:right;width:10%">{{ $recommendStats[$k] }}</td>
            <td>
              @php $pct = $total > 0 ? round($recommendStats[$k]/$total*100) : 0; @endphp
              <div class="bar-wrap"><div class="bar-fill {{ in_array($k,['definitely','probably']) ? 'good' : ($k=='maybe' ? 'warn' : 'bad') }}" style="width:{{ $pct }}%"></div></div>
              <span style="font-size:9px;margin-left:4px">{{ $pct }}%</span>
            </td>
          </tr>
          @endforeach
        </table>
      </td>
    </tr>
  </table>

  @if($wouldPresentAgain > 0)
  <div style="margin-top:8px;padding:8px 12px;background:#f0fdf4;border:1px solid #86efac;border-radius:4px;font-size:9.5px">
    <strong>Would Present Again:</strong>
    {{ $wouldPresentYes }} of {{ $wouldPresentAgain }} presenters who answered said yes
    ({{ round($wouldPresentYes / $wouldPresentAgain * 100) }}%).
  </div>
  @endif
</div>

{{-- ─── 4. PARTICIPANT PROFILE ─── --}}
<div class="section">
  <div class="section-title">4. Participant Profile</div>
  <table class="two-col">
    <tr>
      <td>
        @if(!empty($participantTypes))
        <table class="data">
          <tr><th colspan="2">Participant Type</th></tr>
          @foreach($participantTypes as $type => $cnt)
          <tr>
            <td>{{ ucfirst($type) }}</td>
            <td style="text-align:right">{{ $cnt }} ({{ round($cnt/$total*100) }}%)</td>
          </tr>
          @endforeach
        </table>
        @endif

        @if(!empty($attendanceHistory))
        <table class="data" style="margin-top:8px">
          <tr><th colspan="2">First-Time vs. Returning</th></tr>
          @foreach(['first_time'=>'First Time','returning'=>'Returning'] as $k => $lbl)
            @if(isset($attendanceHistory[$k]))
            <tr>
              <td>{{ $lbl }}</td>
              <td style="text-align:right">{{ $attendanceHistory[$k] }} ({{ round($attendanceHistory[$k]/$total*100) }}%)</td>
            </tr>
            @endif
          @endforeach
        </table>
        @endif
      </td>
      <td>
        @if(!empty($ageGroups))
        <table class="data">
          <tr><th colspan="2">Age Group</th></tr>
          @foreach($ageGroups as $group => $cnt)
          <tr>
            <td>{{ $group }}</td>
            <td style="text-align:right">{{ $cnt }} ({{ round($cnt/$total*100) }}%)</td>
          </tr>
          @endforeach
        </table>
        @endif

        @if(!empty($experienceLevels))
        <table class="data" style="margin-top:8px">
          <tr><th colspan="2">Career Stage</th></tr>
          @foreach(['student'=>'Student','early_career'=>'Early Career','mid_career'=>'Mid Career','senior'=>'Senior','retired'=>'Retired'] as $k => $lbl)
            @if(isset($experienceLevels[$k]))
            <tr>
              <td>{{ $lbl }}</td>
              <td style="text-align:right">{{ $experienceLevels[$k] }} ({{ round($experienceLevels[$k]/$total*100) }}%)</td>
            </tr>
            @endif
          @endforeach
        </table>
        @endif
      </td>
    </tr>
  </table>
</div>

{{-- ─── 5. QUALITATIVE FEEDBACK ─── --}}
@if($qualitative->count())
<div class="section page-break">
  <div class="section-title">5. Qualitative Feedback</div>

  @php
    $workedWell = $qualitative->filter(fn($r) => !empty(trim($r->what_worked_well ?? '')));
    $needsImprovement = $qualitative->filter(fn($r) => !empty(trim($r->what_needs_improvement ?? '')));
    $suggestions = $qualitative->filter(fn($r) => !empty(trim($r->suggestions_for_next_year ?? '')));
    $topics = $qualitative->filter(fn($r) => !empty(trim($r->topics_want_to_see ?? '')));
  @endphp

  @if($workedWell->count())
  <div class="qual-block no-break">
    <div class="qual-subtitle">What Worked Well ({{ $workedWell->count() }} responses)</div>
    @foreach($workedWell as $r)
    <div class="response-item">
      {{ $r->what_worked_well }}
      <div class="response-meta">{{ ucfirst($r->participant_type ?? 'Participant') }} &middot; Rating: {{ $r->overall_rating }}/5 &middot; {{ $r->created_at->format('d M Y') }}</div>
    </div>
    @endforeach
  </div>
  @endif

  @if($needsImprovement->count())
  <div class="qual-block no-break">
    <div class="qual-subtitle">What Needs Improvement ({{ $needsImprovement->count() }} responses)</div>
    @foreach($needsImprovement as $r)
    <div class="response-item" style="border-left-color:#f59e0b">
      {{ $r->what_needs_improvement }}
      <div class="response-meta">{{ ucfirst($r->participant_type ?? 'Participant') }} &middot; Rating: {{ $r->overall_rating }}/5 &middot; {{ $r->created_at->format('d M Y') }}</div>
    </div>
    @endforeach
  </div>
  @endif

  @if($suggestions->count())
  <div class="qual-block no-break">
    <div class="qual-subtitle">Suggestions for Next Year ({{ $suggestions->count() }} responses)</div>
    @foreach($suggestions as $r)
    <div class="response-item" style="border-left-color:#8b5cf6">
      {{ $r->suggestions_for_next_year }}
      <div class="response-meta">{{ ucfirst($r->participant_type ?? 'Participant') }} &middot; Rating: {{ $r->overall_rating }}/5 &middot; {{ $r->created_at->format('d M Y') }}</div>
    </div>
    @endforeach
  </div>
  @endif

  @if($topics->count())
  <div class="qual-block no-break">
    <div class="qual-subtitle">Topics to Cover in Future Editions ({{ $topics->count() }} responses)</div>
    @foreach($topics as $r)
    <div class="response-item" style="border-left-color:#10b981">
      {{ $r->topics_want_to_see }}
      <div class="response-meta">{{ ucfirst($r->participant_type ?? 'Participant') }} &middot; Rating: {{ $r->overall_rating }}/5 &middot; {{ $r->created_at->format('d M Y') }}</div>
    </div>
    @endforeach
  </div>
  @endif
</div>
@endif

{{-- Footer --}}
<div class="footer">
  {{ config('conference.short_name') }} Feedback Committee Report &mdash; Confidential &mdash; For Organizing Committee Use Only &mdash; Generated {{ $generatedAt }}
</div>

</body>
</html>
