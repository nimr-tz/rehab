@extends('emails.layout')

@section('content')
<div class="header">
    <h1>📝 Revision Requested</h1>
</div>

<div class="content">
    <p>Dear {{ $report->user->first_name ?? 'Rapporteur' }},</p>

    <p>The Chief Rapporteur has reviewed your session report and would like you to make some changes before it can be approved.</p>

    <div class="highlight">
        <h3>📋 Report Details</h3>
        <table class="details-table">
            <tr><th>Session:</th><td><strong>{{ $report->session->name ?? 'Session' }}</strong></td></tr>
            @if($report->subtheme)<tr><th>Subtheme:</th><td>{{ $report->subtheme }}</td></tr>@endif
            <tr><th>Status:</th><td><span style="color: #ef6c00; font-weight: bold;">Needs Revision</span></td></tr>
        </table>
    </div>

    <div style="background-color: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800; margin: 20px 0;">
        <p><strong>What needs changing:</strong></p>
        <p style="white-space: pre-wrap;">{{ $feedback }}</p>
    </div>

    <p>Please open your report, make the requested changes, and submit it again. It will return to the Chief Rapporteur for another review.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('rapporteur-reports.edit', $report) }}" class="button">Revise Report</a>
    </div>

    <p>Best regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Rapporteur Team</strong></p>
</div>
@endsection
