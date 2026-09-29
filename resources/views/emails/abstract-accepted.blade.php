<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Abstract Accepted - {{ config('conference.short_name') }} Conference</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .success-icon { font-size: 48px; margin-bottom: 20px; }
        .abstract-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745; }
        .review-summary { background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .next-steps { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">Congratulations</div>
            <h1>Your Abstract Has Been Accepted</h1>
        </div>

        <div class="content">
            <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>

            <p>We are delighted to inform you that your abstract submission has been <strong>accepted</strong> for {{ config('conference.short_name') }} {{ config('conference.year') }}.</p>

            <div class="abstract-details">
                <h3>Abstract Details</h3>
                <p><strong>Title:</strong> {{ $abstract->title }}</p>
            </div>

            <div class="review-summary">
                <h3>Review Summary</h3>
                <p>Your abstract was positively received by our review panel.</p>
                <ul>
                    @foreach($reviews as $review)
                        <li><strong>Reviewer {{ $review->reviewer_number }}:</strong> Positive recommendation received</li>
                    @endforeach
                </ul>
            </div>

            <div class="next-steps">
                <h3>Next Steps</h3>
                <ul>
                    <li>Further communication about your final presentation format and presentation instructions will be shared later.</li>
                    <li>Please ensure your conference registration and portal details remain up to date.</li>
                    <li>If you have any questions, please contact us at <a href="mailto:{{ config('conference.contact_email') }}">{{ config('conference.contact_email') }}</a>.</li>
                </ul>
            </div>

            <p>Thank you for your contribution to {{ config('conference.short_name') }} {{ config('conference.year') }}. We look forward to sharing the next presentation update with you.</p>

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
