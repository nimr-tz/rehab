@extends('emails.layout')

@section('title')
    Payment Reminder
@endsection

@section('content')
    <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>

    <p><strong>Registration closes tomorrow, 2 June 2026 at 6:00 PM EAT.</strong></p>

    <p>We'd love to see you at {{ config('conference.short_name') }} {{ config('conference.year') }}, but our records show your registration fee is still outstanding. Please complete your payment before the deadline — once registration closes, we will not be able to accept payment.</p>

    <div style="background-color: #f8fafc; padding: 20px; border-radius: 8px; margin: 25px 0; border: 1px solid #e2e8f0;">
        <h3 style="margin-top: 0; color: #1e293b;">How to Complete Your Payment:</h3>
        <ol style="margin-bottom: 0;">
            <li>Log in to your dashboard.</li>
            <li>Navigate to the <strong>Payment</strong> section.</li>
            <li>Select your payment method and follow the instructions.</li>
            <li>Upload your proof of payment if paying via bank transfer.</li>
        </ol>
    </div>

    <p style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="cta-button" style="display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; padding: 14px 32px; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 16px;">Complete My Payment Now</a>
    </p>

    <p>If you have already made your payment, please ignore this email or check your dashboard status. For any questions, feel free to reply to this email.</p>

    <p>We look forward to seeing you there.<br><br>
    Best regards,<br>
    The {{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</p>
@endsection
