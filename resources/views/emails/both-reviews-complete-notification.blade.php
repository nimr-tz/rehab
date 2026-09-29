@extends('emails.layout')

@section('content')
<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">🎯 Both Reviews Complete</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ config('conference.edition') }} {{ config('conference.name') }} {{ config('conference.year') }}</p>
    </div>

    <!-- Content -->
    <div style="background: white; padding: 30px; border: 1px solid #e5e7eb;">
        <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
            <strong>System Notification:</strong> Both reviews have been completed for the following abstract submission.
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
                <strong style="color: #374151;">Subtheme:</strong>
                <span style="color: #6b7280;">{{ $abstract->subtheme ?? 'General' }}</span>
            </div>
        </div>

        <!-- Review Summary -->
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981;">
            <h4 style="margin: 0 0 15px 0; color: #065f46; font-size: 16px;">📊 Review Summary</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <strong style="color: #065f46;">Reviewer A:</strong>
                    <span style="color: #047857;">{{ $reviewer1->first_name }} {{ $reviewer1->last_name }}</span>
                </div>
                <div>
                    <strong style="color: #065f46;">Reviewer B:</strong>
                    <span style="color: #047857;">{{ $reviewer2->first_name }} {{ $reviewer2->last_name }}</span>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <strong style="color: #065f46;">Average Score:</strong>
                <span style="color: #047857; font-weight: bold; font-size: 18px;">{{ number_format($averageScore, 1) }}%</span>
            </div>
        </div>

        <!-- Committee Action Required -->
        <div style="background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <h4 style="margin: 0 0 10px 0; color: #92400e; font-size: 16px;">⚠️ Committee Action Required</h4>
            <p style="margin: 0; color: #92400e; font-size: 14px;">
                <strong>Next Steps:</strong><br>
                • Review both evaluations and scores<br>
                • Consider reviewer recommendations<br>
                • Make final acceptance/rejection decision<br>
                • Update abstract status in the system<br>
                • Notify authors of the final decision
            </p>
        </div>

        <!-- Action Buttons -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('admin.abstracts.view', $abstract) }}" 
               style="background: #8b5cf6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin: 0 10px;">
                📋 View Abstract Details
            </a>
            <a href="{{ route('admin.decisions.show', $abstract) }}" 
               style="background: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin: 0 10px;">
                🎯 Make Decision
            </a>
        </div>

        <!-- Review Timeline -->
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3b82f6;">
            <h4 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px;">⏰ Review Timeline</h4>
            <p style="margin: 0; color: #1e40af; font-size: 14px;">
                <strong>Completion Date:</strong> {{ $completionDate->format('F j, Y \a\t g:i A') }}<br>
                <strong>Status:</strong> Both reviews submitted and ready for committee decision<br>
                <strong>Priority:</strong> High - Authors awaiting final decision
            </p>
        </div>

        <p style="margin: 20px 0 0 0; font-size: 14px; color: #6b7280;">
            This is an automated system notification. Please take appropriate action to complete the review process.
        </p>

        <p style="margin: 20px 0 0 0; font-size: 16px; color: #374151;">
            Best regards,<br>
            <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Conference Management System</strong>
        </p>
    </div>

    <!-- Footer -->
    <div style="background: #f9fafb; padding: 20px; border-radius: 0 0 10px 10px; text-align: center; border: 1px solid #e5e7eb; border-top: none;">
        <p style="margin: 0; font-size: 12px; color: #6b7280;">
            System notification sent on {{ $completionDate->format('F j, Y \a\t g:i A') }}
        </p>
    </div>
</div>
@endsection 
