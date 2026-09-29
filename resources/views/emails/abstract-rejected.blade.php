<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Abstract Decision - {{ config('conference.short_name') }} Conference</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .abstract-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545; }
        .review-summary { background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .feedback { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Abstract Decision</h1>
            <h2>Thank You for Your Submission</h2>
        </div>
        
        <div class="content">
            <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>
            
            <p>Thank you for your submission to the {{ config('conference.short_name') }} Conference. After careful review by our expert panel, we regret to inform you that your abstract was not selected for presentation at this year's conference.</p>
            
            <div class="abstract-details">
                <h3>📋 Abstract Details</h3>
                <p><strong>Title:</strong> {{ $abstract->title }}</p>
                <p><strong>Subtheme:</strong> {{ $abstract->subtheme }}</p>
                <p><strong>Submission ID:</strong> #{{ $abstract->id }}</p>
            </div>
            
            <div class="review-summary">
                <h3>📊 Review Summary</h3>
                <p>Your abstract was reviewed by our expert panel:</p>
                <ul>
                    @foreach($reviews as $review)
                        <li><strong>Reviewer {{ $review->reviewer_number }}:</strong> Review completed</li>
                    @endforeach
                </ul>
            </div>
            
            <div class="feedback">
                <h3>💡 Reviewer Feedback</h3>
                @foreach($reviews as $review)
                    @if($review->comments)
                        <div style="margin-bottom: 15px;">
                            <strong>Reviewer {{ $review->reviewer_number }}:</strong>
                            <p style="margin: 5px 0; font-style: italic;">"{{ $review->comments }}"</p>
                        </div>
                    @endif
                @endforeach
            </div>
            
            <p>We encourage you to consider the reviewers' feedback for future submissions and thank you for your interest in the {{ config('conference.short_name') }} Conference.</p>
            
            <p>Best regards,<br>
            The {{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message from the {{ config('conference.short_name') }} Conference Management System.</p>
            <p>If you have any questions, please contact: <a href="mailto:{{ config('conference.contact_email') }}">{{ config('conference.contact_email') }}</a></p>
        </div>
    </div>
</body>
</html>
