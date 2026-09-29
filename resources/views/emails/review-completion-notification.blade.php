@extends('emails.layout')

@section('content')
<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">✅ Review Completed</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ config('conference.edition') }} {{ config('conference.name') }} {{ config('conference.year') }}</p>
    </div>

    <!-- Content -->
    <div style="background: white; padding: 30px; border: 1px solid #e5e7eb;">
        <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
            Dear <strong>{{ $reviewer->first_name }} {{ $reviewer->last_name }}</strong>,
        </p>

        <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">
            Thank you for completing your review! Your evaluation has been successfully submitted and recorded in our system.
        </p>

        <!-- Abstract Details -->
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px;">📝 Reviewed Abstract</h3>
            
            <div style="margin-bottom: 10px;">
                <strong style="color: #374151;">Title:</strong>
                <span style="color: #6b7280;">{{ $abstract->title }}</span>
            </div>
            
            <div style="margin-bottom: 10px;">
                <strong style="color: #374151;">Author:</strong>
                <span style="color: #6b7280;">{{ $abstract->author_name }}</span>
            </div>
            
            <div style="margin-bottom: 10px;">
                <strong style="color: #374151;">Subtheme:</strong>
                <span style="color: #6b7280;">{{ $abstract->subtheme ?? 'General' }}</span>
            </div>
            
            <div style="margin-bottom: 10px;">
                <strong style="color: #374151;">Status:</strong>
                <span style="color: #6b7280;">Review Submitted</span>
            </div>
        </div>

        <!-- Review Summary -->
        @if($reviewData)
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981;">
            <h4 style="margin: 0 0 15px 0; color: #065f46; font-size: 16px;">📊 Your Review Summary</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <strong style="color: #065f46;">Score:</strong>
                    <span style="color: #047857; font-weight: bold; font-size: 18px;">{{ $reviewData->score }}%</span>
                </div>
                <div>
                    <strong style="color: #065f46;">Recommendation:</strong>
                    <span style="color: #047857; font-weight: bold;">
                        @php
                            $recommendationLabels = [
                                'accept_oral' => '✅ Accept (Oral)',
                                'accept_poster' => '✅ Accept (Poster)',
                                'minor_revisions' => '🔄 Minor Revisions',
                                'major_revisions' => '🔄 Major Revisions',
                                'reject' => '❌ Reject'
                            ];
                            echo $recommendationLabels[$reviewData->recommendation] ?? $reviewData->recommendation;
                        @endphp
                    </span>
                </div>
            </div>
            
            @if($reviewData->comments)
            <div style="margin-top: 15px;">
                <strong style="color: #065f46;">Comments:</strong>
                <p style="margin: 10px 0 0 0; color: #047857; font-style: italic;">
                    "{{ Str::limit($reviewData->comments, 200) }}"
                </p>
            </div>
            @endif
        </div>
        @endif

        <!-- Next Steps -->
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3b82f6;">
            <h4 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px;">🔄 Next Steps</h4>
            <p style="margin: 0; color: #1e40af; font-size: 14px;">
                <strong>What happens next:</strong><br>
                • Your review will be combined with the other reviewer's evaluation<br>
                • The committee will make a final decision based on both reviews<br>
                • Authors will be notified of the final decision<br>
                • You can view the final outcome in your reviewer dashboard
            </p>
        </div>

        <!-- Action Button -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('reviewer.abstracts') }}" 
               style="background: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                📋 View All Assignments
            </a>
        </div>

        <div style="background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <h4 style="margin: 0 0 10px 0; color: #92400e; font-size: 16px;">🎯 Your Contribution</h4>
            <p style="margin: 0; color: #92400e; font-size: 14px;">
                <strong>Completion Date:</strong> {{ $completionDate->format('F j, Y \a\t g:i A') }}<br>
                <strong>Thank you</strong> for your valuable contribution to the conference review process!
            </p>
        </div>

        <p style="margin: 20px 0 0 0; font-size: 14px; color: #6b7280;">
            If you have any questions about your review or the process, please contact the conference organizers.
        </p>

        <p style="margin: 20px 0 0 0; font-size: 16px; color: #374151;">
            Best regards,<br>
            <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong>
        </p>
    </div>

    <!-- Footer -->
    <div style="background: #f9fafb; padding: 20px; border-radius: 0 0 10px 10px; text-align: center; border: 1px solid #e5e7eb; border-top: none;">
        <p style="margin: 0; font-size: 12px; color: #6b7280;">
            This confirmation was sent to {{ $reviewer->email }} on {{ $completionDate->format('F j, Y \a\t g:i A') }}
        </p>
    </div>
</div>
@endsection 
