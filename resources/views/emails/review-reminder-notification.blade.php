@extends('emails.layout')

@section('content')
<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">⏰ Review Reminder</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ config('conference.edition') }} {{ config('conference.name') }} {{ config('conference.year') }}</p>
    </div>

    <!-- Content -->
    <div style="background: white; padding: 30px; border: 1px solid #e5e7eb;">
        <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
            Dear <strong>{{ $reviewer->first_name }} {{ $reviewer->last_name }}</strong>,
        </p>

        <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
            This is a friendly reminder that you have a pending review assignment that was assigned to you 
            <strong>{{ $daysSinceAssignment }} days ago</strong>. Please complete your review as soon as possible.
        </p>

        <!-- Abstract Details -->
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px;">📝 Pending Review</h3>
            
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
            
            <div style="margin-bottom: 10px;">
                <strong style="color: #374151;">Status:</strong>
                <span style="color: #6b7280;">Pending Review</span>
            </div>
        </div>

        <!-- Urgency Notice -->
        <div style="background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <h4 style="margin: 0 0 10px 0; color: #92400e; font-size: 16px;">⚠️ Important Reminder</h4>
            <p style="margin: 0; color: #92400e; font-size: 14px;">
                <strong>Timely reviews are crucial</strong> for the conference planning process. 
                Your review helps ensure fair evaluation and timely decisions for authors.
            </p>
        </div>

        <!-- Action Button -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('reviewer.review', $abstract) }}" 
               style="background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                🔍 Complete Review Now
            </a>
        </div>

        <!-- Quick Stats -->
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3b82f6;">
            <h4 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px;">📊 Your Review Progress</h4>
            <p style="margin: 0; color: #1e40af; font-size: 14px;">
                <strong>Assignment Date:</strong> {{ $assignmentDate->format('F j, Y') }}<br>
                <strong>Days Since Assignment:</strong> {{ $daysSinceAssignment }} days<br>
                <strong>Recommended Timeline:</strong> Complete within 7 days
            </p>
        </div>

        <p style="margin: 20px 0 0 0; font-size: 14px; color: #6b7280;">
            If you have any questions or need assistance, please contact the conference organizers.
        </p>

        <p style="margin: 20px 0 0 0; font-size: 16px; color: #374151;">
            Thank you for your contribution to the conference review process!<br>
            <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong>
        </p>
    </div>

    <!-- Footer -->
    <div style="background: #f9fafb; padding: 20px; border-radius: 0 0 10px 10px; text-align: center; border: 1px solid #e5e7eb; border-top: none;">
        <p style="margin: 0; font-size: 12px; color: #6b7280;">
            This reminder was sent to {{ $reviewer->email }} on {{ now()->format('F j, Y \a\t g:i A') }}
        </p>
    </div>
</div>
@endsection 
