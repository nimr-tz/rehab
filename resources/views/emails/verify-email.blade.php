@extends('emails.layout')

@section('title', 'Verify Your Email Address')

@section('content')
    <p>Dear {{ $user->first_name ?? 'Colleague' }},</p>
    
    <p>Welcome to the <strong>{{ config('conference.name') }} 2026</strong>! We are thrilled to have you join our scientific community.</p>
    
    <div class="highlight">
        <h3>📋 Account Details</h3>
        <table class="details-table">
            <tr>
                <th>Email Address:</th>
                <td><strong>{{ $user->email }}</strong></td>
            </tr>
            <tr>
                <th>Registration Date:</th>
                <td>{{ $user->created_at->format('M d, Y \a\t g:i A') }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span style="color: #f59e0b; font-weight: bold;">Pending Verification</span></td>
            </tr>
        </table>
    </div>
    
    <h3>Action Required</h3>
    <p>To ensure the security of your account and complete your registration, please verify your email address by clicking the button below. This link is valid for 60 minutes.</p>
    
    <div style="text-align: center; margin: 35px 0;">
        <a href="{{ $verificationUrl }}" class="button">
            Verify Email Address
        </a>
    </div>
    
    <div style="background-color: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin: 25px 0;">
        <h4 style="margin-top: 0; color: #475569;">Why is verification needed?</h4>
        <p style="margin-bottom: 0; font-size: 14px; color: #64748b;">
            Verifying your email ensures you receive important updates about your abstract submissions, review assignments, and conference announcements.
        </p>
    </div>
    
    <p style="font-size: 14px; color: #64748b; text-align: center; margin-top: 30px;">
        If you're having trouble clicking the button, copy and paste this URL into your browser:<br>
        <a href="{{ $verificationUrl }}" style="color: #3b82f6; word-break: break-all;">{{ $verificationUrl }}</a>
    </p>
@endsection 
