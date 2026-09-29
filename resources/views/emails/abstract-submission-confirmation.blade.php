@extends('emails.layout')

@section('title', 'Submission Received')

@section('content')
    <div class="message-intro">
        Dear {{ $abstract->author_name }},
    </div>

    <p>Thank you for submitting your research to <strong>{{ config('app.name') }}</strong>. This email confirms that we have successfully received your abstract for review.</p>

    <div class="info-card">
        <h3>📋 Submission Summary</h3>
        <div style="margin-bottom: 24px;">
            <p style="margin: 0; font-size: 18px; font-weight: 800; color: #1e293b; line-height: 1.4;">{{ $abstract->title }}</p>
            <p style="margin: 6px 0 0; font-size: 14px; color: #64748b; font-style: italic;">{{ $abstract->subtheme }}</p>
        </div>

        <table class="details-table">
            <tr>
                <th>Submitted On:</th>
                <td>{{ $abstract->created_at->format('F d, Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="info-card" style="background: #f8fafc;">
        <h3 style="color: #475569;">📅 What Happens Next?</h3>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 16px;">
            <tr>
                <td style="vertical-align: top; width: 40px; padding-bottom: 24px;">
                    <div style="width: 28px; height: 28px; background-color: #2563eb; color: white; border-radius: 50%; text-align: center; line-height: 28px; font-size: 14px; font-weight: 800;">1</div>
                </td>
                <td style="padding-bottom: 24px; padding-left: 12px;">
                    <strong style="color: #1e293b; display: block; margin-bottom: 4px; font-size: 15px;">Scientific Review</strong>
                    <p style="margin: 0; color: #64748b; font-size: 14px;">Your abstract will be blind-reviewed by at least two expert panel members. This typically takes 2-3 weeks.</p>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: top; width: 40px;">
                    <div style="width: 28px; height: 28px; background-color: #cbd5e1; color: #64748b; border-radius: 50%; text-align: center; line-height: 28px; font-size: 14px; font-weight: 800;">2</div>
                </td>
                <td style="padding-left: 12px;">
                    <strong style="color: #1e293b; display: block; margin-bottom: 4px; font-size: 15px;">Decision Notification</strong>
                    <p style="margin: 0; color: #64748b; font-size: 14px;">You will receive an official notification email once the committee has reached a final decision.</p>
                </td>
            </tr>
        </table>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <a href="{{ route('dashboard') }}" class="cta-button">Track Submission Status</a>
    </div>

    <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid #f1f5f9; text-align: center;">
        <p style="color: #94a3b8; font-size: 13px; margin-bottom: 8px;">Have questions regarding your submission?</p>
        <p style="margin: 0; font-size: 14px;">
            Reach out to our support team at <a href="mailto:{{ config('conference.contact_email') }}" style="color: #2563eb; text-decoration: none; font-weight: 700;">{{ config('conference.contact_email') }}</a>
        </p>
    </div>
@endsection
