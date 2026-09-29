@extends('emails.layout')

@section('content')
<div class="header">
    <h1>↩️ Abstract Withdrawn</h1>
</div>

<div class="content">
    <p>Dear {{ $abstract->author_name }},</p>
    
    <p>Your abstract has been successfully withdrawn from the review process and returned to draft status.</p>
    
    <div class="highlight">
        <h3>📋 Withdrawn Abstract Details</h3>
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
                <th>Withdrawn On:</th>
                <td>{{ now()->format('M d, Y \a\t g:i A') }}</td>
            </tr>
            <tr>
                <th>Current Status:</th>
                <td><span style="color: #ff9800; font-weight: bold;">Draft</span></td>
            </tr>
        </table>
    </div>
    
    <h3>📝 What This Means</h3>
    <ul>
        <li><strong>Review Process Stopped:</strong> Your abstract is no longer under review</li>
        <li><strong>Editable Again:</strong> You can now edit and modify your abstract</li>
        <li><strong>Resubmission Available:</strong> You can submit it again when ready</li>
        <li><strong>No Penalty:</strong> Withdrawing does not affect future submissions</li>
    </ul>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('abstracts.edit', $abstract) }}" class="button">
            ✏️ Edit Abstract
        </a>
    </div>
    
    <div style="background-color: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3; margin: 20px 0;">
        <p><strong>💡 Next Steps:</strong></p>
        <ol>
            <li>Review and update your abstract as needed</li>
            <li>Make any necessary changes or improvements</li>
            <li>Submit again when you're satisfied with the content</li>
            <li>Your abstract will start the review process fresh</li>
        </ol>
    </div>
    
    <p>If you have any questions about the withdrawal process, please contact our support team.</p>
    
    <p>Best regards,<br>
    <strong>The Conference Committee</strong></p>
</div>
@endsection 
