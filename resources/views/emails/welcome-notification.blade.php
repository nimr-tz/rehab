@extends('emails.layout')

@section('title', 'Welcome to ' . config('conference.short_name') . ' ' . config('conference.year') . '!')

@section('content')
<div class="content">
    <p>Dear {{ $user->title }} {{ $user->first_name }} {{ $user->last_name }},</p>

    <p>Welcome to the <strong>{{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</strong>! Your account has been successfully created and verified.</p>

    <div class="highlight">
        <h3>👤 Registration Details</h3>
        <table class="details-table">
            <tr>
                <th>Category:</th>
                <td>{{ ucwords(str_replace('_', ' ', $user->registration_category)) }}</td>
            </tr>
            <tr>
                <th>Affiliation:</th>
                <td>{{ $user->affiliation }}</td>
            </tr>
            <tr>
                <th>Country:</th>
                <td>{{ $user->country }}</td>
            </tr>
        </table>
    </div>

    {{-- Payment Details --}}
    <div style="background-color: #f0f9ff; padding: 25px; border-radius: 12px; border-left: 4px solid #0ea5e9; margin: 30px 0;">
        <h3 style="color: #0c4a6e; margin-top: 0;">💳 Payment Information</h3>
        <p style="color: #075985; font-size: 15px;">
            You can make your conference payment <strong>at any time</strong> leading up to the day of the conference.
        </p>

        <p style="color: #075985; font-size: 15px; margin-top: 10px;"><strong>When you are ready to pay:</strong></p>
        <ol style="color: #075985; font-size: 14px; padding-left: 20px;">
            <li>Log in to your <strong>{{ config('conference.short_name') }} Dashboard</strong>.</li>
            <li>Navigate to the <strong>Payments</strong> section.</li>
            <li>Pay by bank transfer or mobile money using your <strong>payment reference</strong>.</li>
            <li>Submit the transaction details for verification.</li>
        </ol>

        <p style="color: #075985; font-size: 14px; margin-top: 15px; font-style: italic;">
            Note: While you can pay later, your physical badge and conference access will only be fully confirmed upon payment verification.
        </p>
    </div>

    <h3>🚀 Your Next Steps</h3>
    <ul style="padding-left: 20px; color: #475569;">
        <li><strong>Dashboard Access:</strong> Monitor your registration status and download conference materials.</li>
        <li><strong>Networking:</strong> Get ready to engage with leading researchers and professionals.</li>
    </ul>

    <div style="text-align: center; margin: 40px 0;">
        <a href="{{ route('dashboard') }}" class="button" style="display: inline-block; background-color: #2563eb; color: white !important; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">
            Proceed to Dashboard
        </a>
    </div>

    <p style="color: #64748b; font-size: 14px;">If you encounter any issues with payment or need assistance, please contact us at <a href="mailto:{{ config('conference.contact_email') }}">{{ config('conference.contact_email') }}</a>.</p>

    <p>Best regards,<br>
    <strong>The {{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong></p>
</div>
@endsection

