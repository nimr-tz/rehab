@extends('emails.layout')

@section('content')
<div class="header">
    <h1>📝 Revision Requested</h1>
</div>

<div class="content">
    <p>Dear {{ $abstract->author_name }},</p>
    
    <p>Our review committee has completed the evaluation of your abstract and has requested revisions before final acceptance.</p>
    
    <div class="highlight">
        <h3>📋 Abstract Details</h3>
        <table class="details-table">
            <tr>
                <th>Abstract ID:</th>
                <td><strong>#{{ $abstract->id }}</strong></td>
            </tr>
            <tr>
                <th>Title:</th>
                <td>{{ $abstract->title }}</td>
            </tr>
            <tr>
                <th>Subtheme:</th>
                <td>{{ $abstract->subtheme }}</td>
            </tr>
            <tr>
                <th>Current Status:</th>
                <td><span style="color: #ff9800; font-weight: bold;">Revision Required</span></td>
            </tr>
        </table>
    </div>
    
    @if($revisionMessage)
    <div style="background-color: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <h3>📋 Revision Instructions</h3>
        <p style="font-style: italic; line-height: 1.6;">{{ $revisionMessage }}</p>
    </div>
    @endif
    
    <h3>📝 What You Need to Do</h3>
    <ol>
        <li><strong>Review Feedback:</strong> Carefully read the revision instructions above</li>
        <li><strong>Make Changes:</strong> Update your abstract according to the feedback</li>
        <li><strong>Resubmit:</strong> Submit the revised version for re-review</li>
        <li><strong>Timeline:</strong> <span style="color: #dc2626; font-weight: bold;">Please complete revisions within 24 hours</span></li>
    </ol>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('abstracts.edit', $abstract) }}" class="button">
            ✏️ Revise Abstract
        </a>
    </div>
    
    <div style="background-color: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50; margin: 20px 0;">
        <p><strong>✅ Positive News:</strong></p>
        <ul>
            <li>Your abstract shows promise and is worth revising</li>
            <li>This is not a rejection - it's an opportunity to improve</li>
            <li>Most revised abstracts are eventually accepted</li>
            <li>We're here to help you succeed</li>
        </ul>
    </div>
    
    <div style="background-color: #ffebee; padding: 15px; border-radius: 8px; border-left: 4px solid #f44336; margin: 20px 0;">
        <p><strong>⚠️ Important:</strong></p>
        <ul>
            <li>Address ALL points mentioned in the feedback</li>
            <li>Don't just make minor changes - substantial improvements are expected</li>
            <li>If you need clarification, contact us before the deadline</li>
            <li>Late submissions may not be considered</li>
        </ul>
    </div>
    
    <p>We look forward to receiving your revised abstract!</p>
    
    <p>Best regards,<br>
    <strong>The Conference Review Committee</strong></p>
</div>
@endsection 
