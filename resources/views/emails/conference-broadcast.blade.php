@extends('emails.layout')

@section('title', config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<p class="message-intro">Dear {{ $user->first_name }},</p>

@php
    // Escape HTML first, then convert bare URLs to clickable links.
    // Order matters: escape before linking to prevent XSS.
    $safeText = e($messageText);
    $linkedText = preg_replace(
        '~(https?://[^\s<>"]+)~i',
        '<a href="$1" style="color:#2563eb;text-decoration:underline;font-weight:600;">$1</a>',
        $safeText
    );
@endphp
<div style="margin: 20px 0; line-height: 1.8; color: #334155; font-size: 16px;">
    {!! nl2br($linkedText) !!}
</div>

@if($actionUrl && $actionUrl !== route('dashboard'))
<div style="text-align: center; margin: 36px 0;">
    <a href="{{ $actionUrl }}" class="cta-button">{{ $actionText ?? 'View Details' }}</a>
</div>
@endif

<div class="info-card" style="margin-top: 32px;">
    <h3>📅 Conference At a Glance</h3>
    <table class="details-table">
        <tr>
            <th>Event</th>
            <td>{{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</td>
        </tr>
        <tr>
            <th>Dates</th>
            <td>{{ config('conference.display_dates', 'June 9–11, 2026') }}</td>
        </tr>
        <tr>
            <th>Organiser</th>
            <td>{{ config('conference.host') }}</td>
        </tr>
    </table>
</div>

<p style="margin-top: 32px;">
    Warm regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong><br>
    {{ config('conference.host') }}
</p>
@endsection
