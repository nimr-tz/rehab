@extends('emails.layout')

@section('title', 'Presentation Scheduled')

@section('content')
    <div class="message-intro">
        Dear {{ $abstract->author_name }},
    </div>
    
    <p>Great news! Your presenting abstract has been officially assigned to a session in the {{ config('conference.short_name') }} {{ config('conference.year') }} conference program. Please find your scheduling details below:</p>
    
    <!-- Conference Code Highlight -->
    <div class="info-card" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); color: white; border: none; text-align: center;">
        <h3 style="color: rgba(255,255,255,0.8); margin-bottom: 8px;">🎯 Conference Presentation Code</h3>
        <div style="font-size: 36px; font-weight: 800; letter-spacing: 4px; padding: 10px 0; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
            {{ $abstract->conference_code ?? 'TBD' }}
        </div>
        <p style="margin: 8px 0 0; font-size: 13px; opacity: 0.9;">Please use this code for all conference-related inquiries.</p>
    </div>
    
    <div class="info-card">
        <h3>📋 Presentation Details</h3>
        <table class="details-table">
            <tr>
                <th>Abstract Title:</th>
                <td>{{ $abstract->title }}</td>
            </tr>
            <tr>
                <th>Subtheme:</th>
                <td>{{ $abstract->subtheme ?? 'General' }}</td>
            </tr>
            <tr>
                <th>Review Status:</th>
                <td><span style="color: #10b981;">Accepted</span></td>
            </tr>
        </table>
    </div>
    
    @if($abstract->session)
    <div class="info-card" style="border-left: 6px solid #2563eb; background: #f0f9ff;">
        <h3 style="color: #1e40af;">📍 Session Information</h3>
        <table class="details-table">
            <tr>
                <th style="color: #1e40af;">Session Name:</th>
                <td>{{ $abstract->session->name }}</td>
            </tr>
            @if($abstract->session->schedule_days && count($abstract->session->schedule_days) > 0)
            <tr>
                <th style="color: #1e40af;">Date:</th>
                <td>{{ \Carbon\Carbon::parse($abstract->session->schedule_days[0])->format('l, F j, Y') }}</td>
            </tr>
            @endif
            @if($abstract->session->start_time && $abstract->session->end_time)
            <tr>
                <th style="color: #1e40af;">Time:</th>
                <td>{{ \Carbon\Carbon::parse($abstract->session->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($abstract->session->end_time)->format('H:i') }}</td>
            </tr>
            @endif
            @if($abstract->session->room_location)
            <tr>
                <th style="color: #1e40af;">Room/Venue:</th>
                <td>{{ $abstract->session->room_location }}</td>
            </tr>
            @endif
            @if($abstract->session->session_chair)
            <tr>
                <th style="color: #1e40af;">Session Chair:</th>
                <td>{{ $abstract->session->session_chair }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    <div class="info-card" style="border-left: 6px solid #3b82f6; background: #eff6ff;">
        <h3 style="color: #1d4ed8;">💳 Registration & Payments</h3>
        <p style="margin: 0; color: #1e40af;">Attendance and inclusion in the final scientific program are strictly dependent on verified registration payment. If you haven't completed your payment, please do so via the portal to confirm your attendance.</p>
    </div>
    
    <div class="info-card" style="background: #f8fafc;">
        <h3 style="color: #64748b;">✅ Next Steps</h3>
        <ul style="margin: 0; padding-left: 20px; color: #475569;">
            <li style="margin-bottom: 8px;"><strong>Await Specifications</strong> - Final presentation formats and duration will be sent to you in a separate email soon.</li>
            <li style="margin-bottom: 8px;"><strong>Arrive Early</strong> - Please be at your assigned venue at least 20 minutes before the session starts.</li>
            <li><strong>Coordinate</strong> - Introduce yourself to the Session Chair upon arrival.</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin-top: 32px;">
        <a href="{{ route('dashboard') }}" class="cta-button">Access Conference Portal</a>
    </div>
    
    <p style="margin-top: 32px;">We look forward to an enlightened presentation!</p>
    
    <p>Best regards,<br>
    <strong>The {{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong></p>
@endsection





