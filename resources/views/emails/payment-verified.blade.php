@extends('emails.layout')

@section('title', 'Payment Verified - ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="header" style="background-color: #059669; background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
    <h1 style="color: white !important;">✅ Payment Verified</h1>
</div>

<div class="content">
    <p>Dear {{ $user->first_name }},</p>
    
    <p>We are pleased to inform you that your payment for the <strong>{{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</strong> has been successfully verified by our finance team.</p>
    
    <div class="highlight">
        <h3>💳 Payment Details</h3>
        <table class="details-table">
            <tr>
                <th>Registration Category:</th>
                <td>{{ $user->registration_category }}</td>
            </tr>
            <tr>
                <th>Verification Date:</th>
                <td>{{ now()->format('M d, Y') }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span style="color: #059669; font-weight: bold;">VERIFIED</span></td>
            </tr>
        </table>
    </div>

    @if($notes)
    <div style="background-color: #f0fdf4; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;">
        <p><strong>📝 Administrator Notes:</strong></p>
        <p>{{ $notes }}</p>
    </div>
    @endif

    <p>Your registration is now complete and confirmed. You can now access all conference materials, download your invitation letter, and manage your participation through the portal.</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('dashboard') }}" class="button" style="background-color: #059669; background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white !important; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            🚀 Access Conference Portal
        </a>
    </div>

    <h3>📅 Important Next Steps:</h3>
    <ul style="padding-left: 20px;">
        <li><strong>Download Receipt:</strong> Access your payment receipt from your dashboard</li>
        <li><strong>Invitation Letter:</strong> Your official invitation letter is now available for download</li>
        <li><strong>Abstract Status:</strong> If you submitted an abstract, you can track its review progress in the portal</li>
    </ul>

    <p>Get ready for an impactful conference experience on {{ config('conference.display_dates') }}!</p>
    
    <p>Best regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong></p>
</div>
@endsection
