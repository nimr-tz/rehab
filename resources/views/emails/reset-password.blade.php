@extends('emails.layout')

@section('title', 'Reset Your Password')

@section('content')
    <p>Dear {{ $notifiable->first_name ?? 'Colleague' }},</p>
    
    <p>We received a request to reset the password for your <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Conference Portal</strong> account.</p>
    
    <div class="highlight">
        <h3>🔒 Security Notice</h3>
        <p style="margin: 0; font-size: 14px;">
            If you did not make this request, you can safely ignore this email. Your password will remain unchanged.
        </p>
    </div>
    
    <h3>Action Required</h3>
    <p>To choose a new password, please click the button below. This link is valid for 60 minutes.</p>
    
    <div style="text-align: center; margin: 35px 0;">
        <a href="{{ $url }}" class="button" style="display: inline-block; background-color: #2563eb; color: white !important; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
            Reset Password
        </a>
    </div>
    
    <div style="background-color: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin: 25px 0;">
        <h4 style="margin-top: 0; color: #475569;">Security Tip</h4>
        <p style="margin-bottom: 0; font-size: 14px; color: #64748b;">
            Choose a strong password that you don't use for other accounts. A mix of letters, numbers, and symbols is best.
        </p>
    </div>
    
    <p style="font-size: 14px; color: #64748b; text-align: center; margin-top: 30px;">
        If the button doesn't work, copy and paste this URL into your browser:<br>
        <a href="{{ $url }}" style="color: #3b82f6; word-break: break-all;">{{ $url }}</a>
    </p>
@endsection
