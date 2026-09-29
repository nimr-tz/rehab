@extends('emails.layout')

@section('content')
<div class="header">
    <h1>🗑️ Abstract Deleted</h1>
</div>

<div class="content">
    <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>
    
    <p>Your abstract has been successfully deleted from our system.</p>
    
    <div class="highlight">
        <h3>📋 Deleted Abstract Details</h3>
        <table class="details-table">
            <tr>
                <th>Abstract Title:</th>
                <td><strong>{{ $abstractTitle }}</strong></td>
            </tr>
            <tr>
                <th>Deleted On:</th>
                <td>{{ now()->format('M d, Y \a\t g:i A') }}</td>
            </tr>
        </table>
    </div>
    
    <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <p><strong>📌 Important Information:</strong></p>
        <ul>
            <li>This action cannot be undone</li>
            <li>If you need to submit an abstract, please create a new one</li>
            <li>Your account and other abstracts are unaffected</li>
            <li>You can submit multiple abstracts if needed</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('abstracts.create') }}" class="button">
            📝 Create New Abstract
        </a>
    </div>
    
    <p>If you have any questions or need assistance, please contact our support team.</p>
    
    <p>Best regards,<br>
    <strong>The Conference Committee</strong></p>
</div>
@endsection 
