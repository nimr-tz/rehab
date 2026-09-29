@extends('emails.layout')

@section('title', 'Your Presentation Certificate is Ready')

@section('content')
    @php
        $isPoster = strtolower($abstract->presentation_mode) === 'poster';
        $certType = $isPoster ? 'Poster Presentation' : 'Oral Presentation';
        $headerColor = $isPoster ? 'linear-gradient(135deg, #6d28d9 0%, #7c3aed 100%)' : 'linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%)';
        $accentColor = $isPoster ? '#7c3aed' : '#2563eb';
        $accentLight = $isPoster ? '#f5f3ff' : '#eff6ff';
        $accentText = $isPoster ? '#5b21b6' : '#1d4ed8';
        $icon = $isPoster ? '🖼️' : '🎤';
    @endphp

    <div class="message-intro">
        Dear {{ $user->first_name }},
    </div>

    <p>Congratulations! Your <strong>Certificate of {{ $certType }}</strong> for the <strong>{{ config('conference.edition') }} {{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</strong> is now available for download.</p>

    <!-- Certificate highlight -->
    <div class="info-card" style="background: {{ $headerColor }}; color: white; border: none; text-align: center;">
        <h3 style="color: rgba(255,255,255,0.8); margin-bottom: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">Certificate of {{ $certType }}</h3>
        <div style="font-size: 22px; font-weight: 800; padding: 10px 0;">
            {{ $user->title ? $user->title . ' ' : '' }}{{ $user->full_name }}
        </div>
        <div style="margin: 10px 24px 4px; font-size: 14px; font-style: italic; opacity: 0.92; line-height: 1.5;">
            &ldquo;{{ $abstract->title }}&rdquo;
        </div>
        <p style="margin: 8px 0 0; font-size: 13px; opacity: 0.8;">
            {{ config('conference.display_dates') }} &mdash; {{ config('conference.city') }}
        </p>
    </div>

    <div class="info-card">
        <h3>📋 Presentation Details</h3>
        <table class="details-table">
            <tr>
                <th>Abstract Code:</th>
                <td style="font-family: monospace; font-weight: 800; color: {{ $accentColor }};">{{ $abstract->conference_code }}</td>
            </tr>
            <tr>
                <th>Title:</th>
                <td>{{ $abstract->title }}</td>
            </tr>
            <tr>
                <th>Type:</th>
                <td>{{ $certType }}</td>
            </tr>
            @if($abstract->session)
            <tr>
                <th>Session:</th>
                <td>{{ $abstract->session->name }}</td>
            </tr>
            @if($abstract->session->schedule_days && count($abstract->session->schedule_days) > 0)
            <tr>
                <th>Date:</th>
                <td>{{ \Carbon\Carbon::parse($abstract->session->schedule_days[0])->format('l, F j, Y') }}</td>
            </tr>
            @endif
            @endif
            <tr>
                <th>Certificate No:</th>
                <td style="color: {{ $accentColor }}; font-family: monospace; font-size: 15px;">{{ $certificateNumber }}</td>
            </tr>
        </table>
    </div>

    <div class="info-card" style="border-left: 6px solid {{ $accentColor }}; background: {{ $accentLight }};">
        <h3 style="color: {{ $accentText }};">ℹ️ How to Download</h3>
        <ul style="margin: 0; padding-left: 20px; color: {{ $accentText }};">
            <li style="margin-bottom: 8px;">Log in to your conference portal</li>
            <li style="margin-bottom: 8px;">Navigate to <strong>My Certificates</strong></li>
            <li>Click <strong>Download PDF</strong> next to your {{ strtolower($certType) }} certificate</li>
        </ul>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ route('certificate.index') }}" class="cta-button" style="background: {{ $headerColor }};">Download My Certificate</a>
    </div>

    <p>The certificate carries a unique QR code that can be used to verify its authenticity. You are welcome to share it with your institution or include it in your professional profile.</p>

    <p>We thank you for your valuable contribution to {{ config('conference.short_name') }} {{ config('conference.year') }} and look forward to seeing you at the next edition!</p>

    <p>Best regards,<br>
    <strong>The {{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong></p>
@endsection
