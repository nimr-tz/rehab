@extends('emails.layout')

@section('content')
<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">📊 Your Review Progress</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ config('conference.edition') }} {{ config('conference.name') }} {{ config('conference.year') }}</p>
    </div>

    <!-- Content -->
    <div style="background: white; padding: 30px; border: 1px solid #e5e7eb;">
        <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
            Dear <strong>{{ $reviewer->first_name }} {{ $reviewer->last_name }}</strong>,
        </p>

        <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
            Here's your personalized review progress update for the {{ config('conference.short_name') }} {{ config('conference.year') }} conference. Thank you for your continued contribution to the review process!
        </p>

        <!-- Progress Overview -->
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #0ea5e9;">
            <h3 style="margin: 0 0 15px 0; color: #0c4a6e; font-size: 18px;">🎯 Your Review Overview</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="text-align: center; padding: 15px; background: white; border-radius: 6px;">
                    <div style="font-size: 24px; font-weight: bold; color: #0ea5e9;">{{ $totalAssigned }}</div>
                    <div style="font-size: 14px; color: #64748b;">Total Assigned</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 6px;">
                    <div style="font-size: 24px; font-weight: bold; color: #10b981;">{{ $completedReviews }}</div>
                    <div style="font-size: 14px; color: #64748b;">Completed</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="text-align: center; padding: 15px; background: white; border-radius: 6px;">
                    <div style="font-size: 24px; font-weight: bold; color: #f59e0b;">{{ $pendingReviews }}</div>
                    <div style="font-size: 14px; color: #64748b;">Pending</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 6px;">
                    <div style="font-size: 24px; font-weight: bold; color: #8b5cf6;">{{ $completionRate }}%</div>
                    <div style="font-size: 14px; color: #64748b;">Completion Rate</div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981;">
            <h4 style="margin: 0 0 15px 0; color: #065f46; font-size: 16px;">📈 Performance Metrics</h4>
            
            <div style="margin-bottom: 15px;">
                <strong style="color: #065f46;">Average Score Given:</strong>
                <span style="color: #047857; font-weight: bold; font-size: 18px;">{{ $averageScore }}%</span>
            </div>
            
            <div style="margin-bottom: 15px;">
                <strong style="color: #065f46;">Completion Rate:</strong>
                <span style="color: #047857; font-weight: bold;">{{ $completionRate }}%</span>
                @if($completionRate >= 80)
                    <span style="color: #10b981; margin-left: 10px;">✅ Excellent Progress!</span>
                @elseif($completionRate >= 60)
                    <span style="color: #f59e0b; margin-left: 10px;">🔄 Good Progress</span>
                @else
                    <span style="color: #ef4444; margin-left: 10px;">⚠️ Needs Attention</span>
                @endif
            </div>
            
            @if($pendingReviews > 0)
            <div style="background: #fef3c7; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #f59e0b;">
                <p style="margin: 0; color: #92400e; font-size: 14px;">
                    <strong>Reminder:</strong> You have {{ $pendingReviews }} pending review{{ $pendingReviews > 1 ? 's' : '' }}. 
                    Please complete them to help maintain the review timeline.
                </p>
            </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('reviewer.abstracts') }}" 
               style="background: #0ea5e9; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin: 0 10px;">
                📋 View All Assignments
            </a>
            @if($pendingReviews > 0)
            <a href="{{ route('reviewer.dashboard') }}" 
               style="background: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin: 0 10px;">
                🔍 Complete Pending Reviews
            </a>
            @endif
        </div>

        <!-- Tips Section -->
        <div style="background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <h4 style="margin: 0 0 10px 0; color: #92400e; font-size: 16px;">💡 Review Tips</h4>
            <ul style="margin: 0; padding-left: 20px; color: #92400e; font-size: 14px;">
                <li>Provide constructive feedback to help authors improve their work</li>
                <li>Be consistent in your scoring criteria across all reviews</li>
                <li>Complete reviews within 7 days of assignment for timely processing</li>
                <li>Consider both scientific merit and presentation quality</li>
            </ul>
        </div>

        <!-- Encouragement -->
        @if($completionRate >= 80)
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981;">
            <h4 style="margin: 0 0 10px 0; color: #065f46; font-size: 16px;">🎉 Outstanding Work!</h4>
            <p style="margin: 0; color: #047857; font-size: 14px;">
                Your high completion rate demonstrates excellent commitment to the review process. 
                Thank you for your valuable contribution to the conference!
            </p>
        </div>
        @elseif($completionRate >= 60)
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3b82f6;">
            <h4 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px;">👍 Good Progress</h4>
            <p style="margin: 0; color: #1e40af; font-size: 14px;">
                You're making good progress with your reviews. Keep up the excellent work!
            </p>
        </div>
        @else
        <div style="background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <h4 style="margin: 0 0 10px 0; color: #92400e; font-size: 16px;">📝 Need Support?</h4>
            <p style="margin: 0; color: #92400e; font-size: 14px;">
                If you need assistance with your reviews or have questions about the process, 
                please don't hesitate to contact the conference organizers.
            </p>
        </div>
        @endif

        <p style="margin: 20px 0 0 0; font-size: 14px; color: #6b7280;">
            This progress update was generated on {{ $updateDate->format('F j, Y \a\t g:i A') }}
        </p>

        <p style="margin: 20px 0 0 0; font-size: 16px; color: #374151;">
            Best regards,<br>
            <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong>
        </p>
    </div>

    <!-- Footer -->
    <div style="background: #f9fafb; padding: 20px; border-radius: 0 0 10px 10px; text-align: center; border: 1px solid #e5e7eb; border-top: none;">
        <p style="margin: 0; font-size: 12px; color: #6b7280;">
            This progress update was sent to {{ $reviewer->email }} on {{ $updateDate->format('F j, Y \a\t g:i A') }}
        </p>
    </div>
</div>
@endsection 
