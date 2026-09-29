@extends('emails.layout')

@section('title', 'Action Required: Payment Rejected - ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="header" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);">
    <h1>❌ Payment Evidence Rejected</h1>
</div>

<div class="content">
    <p>Dear {{ $user->first_name }},</p>

    <p>Thank you for submitting your proof of payment for the <strong>{{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</strong>.</p>

    <p>Upon review, our finance team could not verify your payment based on the evidence provided. To complete your registration, please address the following:</p>

    <div style="background-color: #fef2f2; padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 25px 0;">
        <h4 style="color: #991b1b; margin-top: 0;">⚠️ Reason for Rejection:</h4>
        <p style="color: #b91c1c; font-style: italic; margin-bottom: 0;">"{{ $notes }}"</p>
    </div>

    <p><strong>What should you do next?</strong></p>
    <p>Please log in to your account and upload a valid proof of payment. Accepted documents include:</p>
    <ul style="padding-left: 20px;">
        <li>Official Bank Deposit Slip</li>
        <li>Mobile Money Confirmation SMS or Screenshot</li>
        <li>Online Bank Transfer Receipt (PDF or Image)</li>
    </ul>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('payment.show') }}" class="button" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);">
            📤 Re-upload Proof of Payment
        </a>
    </div>

    <p>If you believe this is an error or if you have already corrected the payment, please contact us at <a href="mailto:{{ config('conference.contact_email') }}">{{ config('conference.contact_email') }}</a>.</p>

    <p>Best regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Finance Team</strong></p>
</div>
@endsection
