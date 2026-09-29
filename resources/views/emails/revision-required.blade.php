<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Revisions Required - {{ config('conference.short_name') }} Conference</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .abstract-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .revision-info { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .reviewer-feedback { background: #e2e3e5; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .deadline { background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Revisions Required</h1>
            <h2>Your Abstract Needs Some Updates</h2>
        </div>
        
        <div class="content">
            <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>
            
            <p>Thank you for your submission to {{ config('conference.short_name') }} {{ config('conference.year') }}. Our reviewers have provided feedback and requested {{ $decision === 'minor_revisions' ? 'minor' : 'major' }} revisions to your abstract.</p>
            
            <div class="abstract-details">
                <h3>📋 Abstract Details</h3>
                <p><strong>Title:</strong> {{ $abstract->title }}</p>
                <p><strong>Subtheme:</strong> {{ $abstract->subtheme }}</p>
                <p><strong>Submission ID:</strong> #{{ $abstract->id }}</p>
                <p><strong>Revision Type:</strong> {{ ucfirst(str_replace('_', ' ', $decision)) }}</p>
            </div>
            
            <div class="revision-info">
                <h3>📝 Revision Requirements</h3>
                <p><strong>Type of Revision:</strong> {{ $decision === 'minor_revisions' ? 'Minor' : 'Major' }} revisions are required.</p>
                
                @if($decision === 'minor_revisions')
                    <p>Minor revisions typically include:</p>
                    <ul>
                        <li>Clarification of methodology</li>
                        <li>Minor language corrections</li>
                        <li>Small additions to the literature review</li>
                        <li>Formatting improvements</li>
                    </ul>
                @else
                    <p>Major revisions typically include:</p>
                    <ul>
                        <li>Significant changes to methodology</li>
                        <li>Additional data analysis or results</li>
                        <li>Major restructuring of content</li>
                        <li>Substantial additions to the literature review</li>
                    </ul>
                @endif
            </div>
            
            <div class="reviewer-feedback">
                <h3>💬 Reviewer Feedback</h3>
                @foreach($reviews as $review)
                    <div style="margin-bottom: 20px; padding: 10px; background: white; border-radius: 5px;">
                        <h4>Reviewer {{ $review->reviewer_number }}</h4>
                        @if($review->comments)
                            <p style="margin: 10px 0; font-style: italic;">"{{ $review->comments }}"</p>
                        @else
                            <p style="margin: 10px 0; color: #666;">No specific comments provided.</p>
                        @endif
                    </div>
                @endforeach
            </div>
            
            <div class="deadline">
                <h3>⏰ Important Deadlines</h3>
                @if($decision === 'minor_revisions')
                    <p><strong>Revision Deadline:</strong> April 2, 2026</p>
                    <p><strong>Process:</strong> After submission, your revisions will be reviewed as part of the {{ config('conference.short_name') }} {{ config('conference.year') }} review workflow.</p>
                @else
                    <p><strong>Revision Deadline:</strong> April 2, 2026</p>
                    <p><strong>Process:</strong> After submission, your revisions will be sent back to the original reviewers for re-evaluation.</p>
                @endif
            </div>
            
            <p>Please log into the conference system to submit your revised abstract. We look forward to receiving your updated submission.</p>
            
            <p>If you have any questions about the revision requirements, please contact us at <a href="mailto:{{ config('conference.contact_email') }}">{{ config('conference.contact_email') }}</a>.</p>
            
            <p>Best regards,<br>
            {{ config('conference.short_name') }} {{ config('conference.year') }}</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message from the {{ config('conference.short_name') }} {{ config('conference.year') }} Conference Management System.</p>
            <p>If you have any questions, please contact: <a href="mailto:{{ config('conference.contact_email') }}">{{ config('conference.contact_email') }}</a></p>
        </div>
    </div>
</body>
</html>
