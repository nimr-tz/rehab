@extends('emails.layout')

@section('content')
<div class="header">
    <h1>✅ Session Report Approved</h1>
</div>

<div class="content">
    <p>Dear {{ $report->user->first_name ?? 'Rapporteur' }},</p>

    <p>The Chief Rapporteur has reviewed and <strong>approved</strong> your report for the following session. No further action is required — thank you for your contribution.</p>

    <div class="highlight">
        <h3>📋 Report Details</h3>
        <table class="details-table">
            <tr><th>Session:</th><td><strong>{{ $report->session->name ?? 'Session' }}</strong></td></tr>
            @if($report->subtheme)<tr><th>Subtheme:</th><td>{{ $report->subtheme }}</td></tr>@endif
            <tr><th>Status:</th><td><span style="color: #2e7d32; font-weight: bold;">Approved</span></td></tr>
            <tr><th>Approved:</th><td>{{ $report->approved_at?->format('M d, Y \a\t g:i A') }}</td></tr>
        </table>
    </div>

    @if($note)
    <div style="background-color: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50; margin: 20px 0;">
        <p><strong>Note from the Chief Rapporteur:</strong></p>
        <p style="white-space: pre-wrap;">{{ $note }}</p>
    </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('rapporteur-reports.show', $report) }}" class="button">View Report</a>
    </div>

    <p>Best regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Rapporteur Team</strong></p>
</div>
@endsection
