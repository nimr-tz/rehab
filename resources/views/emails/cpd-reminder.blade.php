@extends('emails.layout')

@section('title', 'CPD Details — ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
    <p class="message-intro">Dear {{ trim(($user->title ?? '') . ' ' . ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: config('conference.short_name') . ' Participant' }},</p>

    <p style="margin: 0 0 18px; color: #334155; line-height: 1.75;">
        Thank you for participating in the <strong>{{ config('conference.edition') }} {{ config('conference.name') }} {{ config('conference.year') }}</strong>.
        To ensure you receive your <strong>Continuing Professional Development (CPD) points</strong> certificate after the conference,
        we need two pieces of information from your profile.
    </p>

    <div style="margin: 28px 0; padding: 24px; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1px solid #bbf7d0; border-radius: 18px;">
        <p style="margin: 0; color: #166534; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em;">
            Required for your CPD certificate
        </p>
        <p style="margin: 12px 0 0; color: #14532d; font-size: 15px; line-height: 1.65; font-weight: 600;">
            Please log in to your profile and fill in the following:
        </p>
        <ol style="margin: 14px 0 0; padding-left: 22px; color: #166534; font-size: 15px; line-height: 2;">
            <li><strong>Professional Board / Council</strong> — e.g. Medical Council of Tanganyika, Nursing Council of Tanzania</li>
            <li><strong>Registration Number</strong> — your official registration number with that board</li>
        </ol>
    </div>

    <p style="margin: 0 0 16px; color: #475569; line-height: 1.7;">
        This information is used solely to generate and verify your CPD certificate. It takes less than a minute to complete.
    </p>

    <p style="text-align: center; margin: 34px 0;">
        <a href="{{ $profileUrl }}" class="cta-button" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.28);">
            Update My Profile Now
        </a>
    </p>

    <div style="margin: 30px 0 0; padding: 18px 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px;">
        <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.7;">
            If you have already filled in these details, you may disregard this message. If you have any questions,
            please reply to this email or contact the {{ config('conference.short_name') }} secretariat.
        </p>
    </div>

    <p style="margin-top: 28px;">Best regards,<br>
    The {{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</p>
@endsection
