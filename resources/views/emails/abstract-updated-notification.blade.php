@extends('emails.layout')

@section('content')
<div class="header">
    <h1>✏️ Abstract Updated!</h1>
</div>

<div class="content">
    <p>Dear {{ $abstract->author_name }},</p>
    
    <p>Your abstract has been successfully updated. Here are the current details:</p>
    
    <div class="highlight">
        <h3>📋 Updated Abstract Details</h3>
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
                <th>Last Updated:</th>
                <td>{{ $abstract->updated_at->format('M d, Y \a\t g:i A') }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span style="color: #2196f3; font-weight: bold;">{{ ucfirst($abstract->status) }}</span></td>
            </tr>
        </table>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('abstracts.show', $abstract) }}" class="button">
            👁️ View Abstract
        </a>
    </div>
    
    <div style="background-color: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50; margin: 20px 0;">
        <p><strong>✅ Update Successful:</strong></p>
        <ul>
            <li>All changes have been saved</li>
            <li>Your abstract is ready for review (if submitted)</li>
            <li>You can continue editing if it's still a draft</li>
            <li>Reviewers will see the updated version</li>
        </ul>
    </div>
    
    <p>Thank you for keeping your abstract information current!</p>
    
    <p>Best regards,<br>
    <strong>The Conference Committee</strong></p>
</div>
@endsection 
