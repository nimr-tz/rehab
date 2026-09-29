<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $report->session->name ?? 'Session Report' }} — {{ config('conference.short_name') }} {{ config('conference.year') }}</title>
@include('chief-rapporteur.partials.print-styles')
</head>
<body style="padding: 24px;">

    <div style="text-align:center; margin-bottom: 18px;">
        <h1 style="font-size:18px; color:#0c4a6e; margin:0;">{{ config('conference.edition') }} {{ config('conference.name') }} — {{ config('conference.short_name') }} {{ config('conference.year') }}</h1>
        <p style="font-size:11px; color:#64748b; margin:4px 0 0;">
            Session Rapporteur Report
            @if($report->isApproved()) · Approved {{ $report->approved_at?->format('d F Y') }} @endif
        </p>
    </div>

    @include('chief-rapporteur.partials.report-block', ['report' => $report])

</body>
</html>
