@extends('emails.layout')

@section('title', 'Student Identity Verified - ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="header" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
    <h1>🎓 Student Identity Verified</h1>
</div>

<div class="content">
    <p>Dear {{ $user->first_name }},</p>

    <p>We are pleased to inform you that your student identity for the <strong>{{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</strong> has been successfully verified.</p>

    <div class="highlight">
        <h3>✅ Verification Details</h3>
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

    <p>With your student status confirmed, the discounted registration rate is now unlocked for your account. You can now complete your payment from the payment page.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('payment.show') }}" class="button" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
            💳 Proceed to Payment
        </a>
    </div>

    <p>Best regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong></p>
</div>
@endsection
