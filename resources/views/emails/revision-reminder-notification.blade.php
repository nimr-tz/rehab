@extends('emails.layout')

@section('title', 'Revision Reminder')

@section('content')
<p class="message-intro">Dear {{ $abstract->author_name }},</p>

<p>This is a kind reminder that your abstract is still awaiting revision resubmission for {{ config('conference.short_name') }} {{ config('conference.year') }}.</p>

<div class="info-card">
    <h3>Abstract Details</h3>
    <table class="details-table">
        <tr>
            <th>Title:</th>
            <td>{{ $abstract->title }}</td>
        </tr>
        <tr>
            <th>Subtheme:</th>
            <td>{{ $abstract->subtheme }}</td>
        </tr>
        <tr>
            <th>Status:</th>
            <td><span style="color: #f59e0b; font-weight: 700;">Revision Required</span></td>
        </tr>
    </table>
</div>

@if($revisionMessage)
<div class="info-card" style="border-left: 4px solid #f59e0b;">
    <h3>Revision Notes</h3>
    <p style="margin: 0; font-style: italic;">{{ $revisionMessage }}</p>
</div>
@endif

<div class="info-card" style="border-left: 4px solid #dc2626;">
    <h3>Deadline</h3>
    <p style="margin: 0;"><strong>Please submit your revised abstract by April 2, 2026.</strong></p>
</div>

<p>Please log into the conference system and submit your revised abstract at your earliest convenience.</p>

<div style="text-align: center; margin: 32px 0;">
    <a href="{{ route('abstracts.edit', $abstract) }}" class="cta-button">Submit Revised Abstract</a>
</div>

<p>If you have any questions, please contact us at <a href="mailto:{{ config('conference.contact_email') }}">{{ config('conference.contact_email') }}</a>.</p>

<p style="margin-top: 32px;">Best regards,<br><strong>{{ config('conference.short_name') }} {{ config('conference.year') }}</strong></p>
@endsection
