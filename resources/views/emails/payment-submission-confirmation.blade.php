@extends('emails.layout')

@section('title', 'Payment Received - ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="header" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
    <h1>📩 Payment Received</h1>
</div>

<div class="content">
    <p>Dear {{ $user->first_name }},</p>
    
    <p>Thank you for submitting your proof of payment for the <strong>{{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</strong>.</p>
    
    <div style="background-color: #f0fdfa; padding: 25px; border-radius: 12px; border-left: 4px solid #0d9488; margin: 30px 0;">
        <h3 style="color: #0f766e; margin-top: 0;">✨ Our team is on it!</h3>
        <p style="color: #115e59;">We've received your proof of payment! Our team is now busy securing your spot for {{ config('conference.short_name') }} {{ config('conference.year') }}. We are carefully reviewing your submission to finalize your registration.</p>
        
        <p style="margin-bottom: 0; font-size: 14px; color: #134e4a;">You will receive another email from us as soon as your seat is fully authorized and your registration is confirmed.</p>
    </div>

    <p>In the meantime, you can explore the conference portal or review the conference program.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('dashboard') }}" class="button" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
            📊 View My Dashboard
        </a>
    </div>

    <p>Best regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong></p>
</div>
@endsection
