@extends('emails.layout')

@section('title', 'Registration Fee Waived - ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="header" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
    <h1>🎁 Fee Waived</h1>
</div>

<div class="content">
    <p>Dear {{ $user->first_name }},</p>
    
    <p>We are pleased to inform you that your registration fee for the <strong>{{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</strong> has been <strong>waived</strong> by the organizing committee.</p>
    
    <div class="highlight">
        <h3>📋 Waiver Details</h3>
        <table class="details-table">
            <tr>
                <th>Registration Category:</th>
                <td>{{ $user->registration_category }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span style="color: #6366f1; font-weight: bold;">WAIVED (Authorized)</span></td>
            </tr>
        </table>
    </div>

    @if($notes)
    <div style="background-color: #f5f3ff; padding: 15px; border-radius: 8px; border-left: 4px solid #6366f1; margin: 20px 0;">
        <p><strong>📝 Committee Notes:</strong></p>
        <p>{{ $notes }}</p>
    </div>
    @endif

    <p>Your registration is now fully confirmed. You have full access to the conference portal, presentation materials, and can download your official invitation letter.</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('dashboard') }}" class="button" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
            🚀 Access Conference Portal
        </a>
    </div>

    <p>We look forward to your participation in the upcoming conference!</p>
    
    <p>Best regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong></p>
</div>
@endsection
