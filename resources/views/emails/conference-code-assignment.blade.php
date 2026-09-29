@extends('emails.layout')

@section('content')
<div class="header">
    <h1>🏷️ Conference Code Assigned!</h1>
</div>

<div class="content">
    <p>Dear {{ $abstract->author_name }},</p>
    
    <p>Great news! Your accepted abstract has been assigned a conference presentation code. You are now officially part of our conference program!</p>
    
    <div class="highlight">
        <h3>📋 Presentation Details</h3>
        <table class="details-table">
            <tr>
                <th>Abstract Title:</th>
                <td>{{ $abstract->title }}</td>
            </tr>
            <tr>
                <th>Conference Code:</th>
                <td><strong style="font-size: 20px; color: #2196f3;">{{ $abstract->conference_code }}</strong></td>
            </tr>
            <tr>
                <th>Subtheme:</th>
                <td>{{ $abstract->subtheme }}</td>
            </tr>
            <tr>
                <th>Code Assigned:</th>
                <td>{{ $abstract->code_assigned_at ? $abstract->code_assigned_at->format('M d, Y \a\t g:i A') : 'Just now' }}</td>
            </tr>
            @if($abstract->committee_selected)
            <tr>
                <th>Program Status:</th>
                <td><span style="color: #4caf50; font-weight: bold;">✅ Selected for Conference Program</span></td>
            </tr>
            @endif
        </table>
    </div>
    
    <div style="background-color: #e8f5e8; padding: 20px; border-radius: 8px; border-left: 4px solid #4caf50; margin: 20px 0;">
        <h3>🎯 Important Information</h3>
        <p><strong>Your Conference Code: {{ $abstract->conference_code }}</strong></p>
        <ul>
            <li>This code will be used for scheduling your presentation</li>
            <li>Include this code in all conference-related correspondence</li>
            <li>Use this code when registering for the conference</li>
            <li>This will appear in the final conference program</li>
        </ul>
    </div>
    
    <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <h3>📅 What's Next?</h3>
        <ol>
            <li><strong>Session Scheduling:</strong> You'll receive your presentation time slot soon</li>
            <li><strong>Presentation Guidelines:</strong> Format and technical requirements will be shared</li>
            <li><strong>Conference Registration:</strong> Complete your registration if you haven't already</li>
            <li><strong>Final Program:</strong> The complete conference schedule will be published soon</li>
        </ol>
    </div>
    
    @if($abstract->committee_selected)
    <div style="background-color: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3; margin: 20px 0;">
        <p><strong>🌟 Special Recognition:</strong> Your abstract has been selected for inclusion in the main conference program! This indicates the high quality and relevance of your work.</p>
    </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('dashboard') }}" class="button">
            📊 View Your Presentation Details
        </a>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <h4>📞 Need Help?</h4>
        <p>If you have any questions about your presentation or the conference schedule, please don't hesitate to contact us.</p>
        <p><strong>Keep this email for your records!</strong></p>
    </div>
    
    <p>We look forward to your presentation and your participation in the conference!</p>
    
    <p>Best regards,<br>
    <strong>The Conference Program Committee</strong></p>
</div>
@endsection
