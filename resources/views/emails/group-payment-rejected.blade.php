@extends('emails.layout')

@section('title', 'Action Required: Group Payment Rejected - ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="header" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);">
    <h1>❌ Group Payment Proof Rejected</h1>
</div>

<div class="content">
    <p>Dear {{ $groupRegistration->leader->first_name }},</p>

    <p>Thank you for submitting the proof of payment for the group <strong>"{{ $groupRegistration->group_name ?: 'Group #' . $groupRegistration->id }}"</strong>.</p>

    <p>Upon review, our finance team could not verify the payment based on the evidence provided. Individual member accounts will remain inactive until a valid proof is uploaded and verified.</p>

    <div style="background-color: #fef2f2; padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 25px 0;">
        <h4 style="color: #991b1b; margin-top: 0;">⚠️ Reason for Rejection:</h4>
        <p style="color: #b91c1c; font-style: italic; margin-bottom: 0;">"{{ $notes }}"</p>
    </div>

    <p><strong>What should you do next?</strong></p>
    <p>Please log in to your account and upload a valid proof of payment for the entire group amount ({{ $groupRegistration->formatted_total }}). Accepted documents include:</p>
    <ul style="padding-left: 20px;">
        <li>Official Bank Deposit Slip</li>
        <li>Mobile Money Confirmation SMS or Screenshot</li>
        <li>Online Bank Transfer Receipt (PDF or Image)</li>
    </ul>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('group-registration.show', $groupRegistration) }}" class="button" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);">
            📤 Re-upload Proof of Payment
        </a>
    </div>

    <p>If you believe this is an error, please contact us at <a href="mailto:{{ config('conference.contact_email') }}">{{ config('conference.contact_email') }}</a>.</p>

    <p>Best regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Finance Team</strong></p>
</div>
@endsection
