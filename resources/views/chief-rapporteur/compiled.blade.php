<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ config('conference.short_name') }} {{ config('conference.year') }} — Consolidated Rapporteur Report</title>
@include('chief-rapporteur.partials.print-styles')
</head>
<body>

    <div class="cover">
        <h1>{{ config('conference.edition') }} {{ config('conference.name') }}</h1>
        <h2>{{ config('conference.short_name') }} {{ config('conference.year') }} — Consolidated Rapporteur Report</h2>
        <p class="meta">{{ $total }} approved session {{ \Illuminate\Support\Str::plural('report', $total) }}</p>
        <p class="meta">Generated {{ $generatedAt->format('d F Y, H:i') }}</p>
    </div>

    @foreach($grouped as $subtheme => $reports)
        <div class="pagebreak">
            <div class="subtheme">{{ $subtheme }}</div>
            @foreach($reports as $report)
                @include('chief-rapporteur.partials.report-block', ['report' => $report])
            @endforeach
        </div>
    @endforeach

    @if($total === 0)
    <div style="text-align:center; padding:60px; color:#94a3b8;">
        No reports have been approved yet. Approve reports to include them in the consolidated report.
    </div>
    @endif

</body>
</html>
