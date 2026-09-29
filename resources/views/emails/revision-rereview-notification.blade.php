@extends('emails.layout')

@section('content')
<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">🔄 Revision Re-Review Required</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ config('conference.edition') }} {{ config('conference.name') }} {{ config('conference.year') }}</p>
    </div>

    <!-- Content -->
    <div style="background: white; padding: 30px; border: 1px solid #e5e7eb;">
        <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
            Dear <strong>{{ $reviewer->first_name }} {{ $reviewer->last_name }}</strong>,
        </p>

        <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
            The author has submitted a <strong>revised version</strong> of their abstract in response to reviewer feedback. 
            Please review the updated submission as <strong>{{ $reviewerPosition === 1 ? 'Reviewer A' : 'Reviewer B' }}</strong>.
        </p>

        <!-- Abstract Details -->
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px;">📝 Abstract Details</h3>
            
            <div style="margin-bottom: 10px;">
                <strong style="color: #374151;">Title:</strong>
                <span style="color: #6b7280;">{{ $abstract->title }}</span>
            </div>
            
            <div style="margin-bottom: 10px;">
                <strong style="color: #374151;">Author:</strong>
                <span style="color: #6b7280;">{{ $abstract->author_name }}</span>
            </div>
            
            <div style="margin-bottom: 10px;">
                <strong style="color: #374151;">Institution:</strong>
                <span style="color: #6b7280;">{{ $abstract->author_institute }}</span>
            </div>
            
            <div style="margin-bottom: 10px;">
                <strong style="color: #374151;">Revision Round:</strong>
                <span style="color: #f59e0b; font-weight: bold;">Round {{ $abstract->revision_round ?? 1 }}</span>
            </div>
        </div>

        <!-- Urgent Notice -->
        <div style="background: #fef2f2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626;">
            <h4 style="margin: 0 0 10px 0; color: #991b1b; font-size: 16px;">⏰ URGENT: 24-Hour Deadline</h4>
            <p style="margin: 0; color: #991b1b;">
                <strong>Please complete your re-review within 24 hours.</strong><br>
                Your prompt evaluation ensures timely decisions for authors and maintains the conference schedule.
            </p>
        </div>

        <!-- What to Look For -->
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3b82f6;">
            <h4 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px;">🔍 What to Evaluate</h4>
            <ul style="margin: 0; padding-left: 20px; color: #1e40af;">
                <li>Has the author adequately addressed previous feedback?</li>
                <li>Are the revisions satisfactory and complete?</li>
                <li>Does the updated abstract meet conference standards?</li>
                <li>Is additional revision needed, or can a final decision be made?</li>
            </ul>
        </div>

        <!-- Action Button -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('reviewer.review', $abstract) }}" 
               style="background: #f59e0b; color: white; padding: 14px 35px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 16px;">
                🔍 Review Revised Abstract
            </a>
        </div>

        <p style="margin: 20px 0 0 0; font-size: 14px; color: #6b7280;">
            If you have any questions about this re-review, please contact the conference organizers.
        </p>

        <p style="margin: 20px 0 0 0; font-size: 16px; color: #374151;">
            Best regards,<br>
            <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong>
        </p>
    </div>

    <!-- Footer -->
    <div style="background: #f9fafb; padding: 20px; border-radius: 0 0 10px 10px; text-align: center; border: 1px solid #e5e7eb; border-top: none;">
        <p style="margin: 0; font-size: 12px; color: #6b7280;">
            This email was sent to {{ $reviewer->email }} on {{ now()->format('F j, Y \a\t g:i A') }}
        </p>
    </div>
</div>
@endsection
