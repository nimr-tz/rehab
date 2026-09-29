@extends('emails.layout')

@section('title', 'Action Required: Student Identity Rejected - ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="header" style="background: linear-gradient(135deg, #e11d48 0%, #fb7185 100%);">
    <h1>⚠️ Identity Verification Issue</h1>
</div>

<div class="content">
    <p>Dear {{ $user->first_name }},</p>

    <p>Thank you for submitting your student document for the <strong>{{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</strong>. Unfortunately, our administration team was unable to verify your student status based on the document provided.</p>

    <div style="background-color: #fff1f2; padding: 20px; border-radius: 12px; border-left: 4px solid #e11d48; margin: 25px 0;">
        <p style="color: #9f1239; font-weight: bold; margin-bottom: 10px;">📝 Reason for Rejection:</p>
        <p style="color: #be123c; font-style: italic;">"{{ $notes ?: 'The document provided does not clearly show current enrollment or valid student identification for the current academic year.' }}"</p>
    </div>

    <p>To proceed with the student discount, please update your profile with a valid Student ID card, current semester registration form, or an official letter from your institution.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('profile.edit') }}" class="button" style="background: linear-gradient(135deg, #e11d48 0%, #fb7185 100%); text-decoration: none;">
            🔄 Update Student ID
        </a>
    </div>

    <p>Once you upload a new document, our team will re-review your status as soon as possible. If you believe this is an error or have questions, please reach out to our support team.</p>

    <p>Best regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong></p>
</div>
@endsection
