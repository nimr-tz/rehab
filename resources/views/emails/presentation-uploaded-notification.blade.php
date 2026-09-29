@extends('emails.layout')

@section('content')
<div class="header">
    <h1>📤 Presentation Uploaded!</h1>
</div>

<div class="content">
    <p>Dear {{ $abstract->author_name }},</p>
    
    <p>Your presentation materials have been successfully uploaded and are now available for the conference.</p>
    
    <div class="highlight">
        <h3>📋 Presentation Details</h3>
        <table class="details-table">
            <tr>
                <th>Abstract ID:</th>
                <td><strong>#{{ $abstract->id }}</strong></td>
            </tr>
            <tr>
                <th>Abstract Title:</th>
                <td>{{ $abstract->title }}</td>
            </tr>
            <tr>
                <th>Conference Code:</th>
                <td><strong>{{ $abstract->conference_code ?? 'Pending' }}</strong></td>
            </tr>
            <tr>
                <th>Upload Date:</th>
                <td>{{ $abstract->presentation_uploaded_at?->format('M d, Y \a\t g:i A') ?? now()->format('M d, Y \a\t g:i A') }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span style="color: #4caf50; font-weight: bold;">Uploaded</span></td>
            </tr>
        </table>
    </div>
    
    <h3>📁 Uploaded Files</h3>
    @if($abstract->presentation_files)
        <ul>
            @foreach($abstract->presentation_files as $file)
                <li><strong>{{ $file['name'] }}</strong> ({{ $file['size'] ?? 'Unknown size' }})</li>
            @endforeach
        </ul>
    @else
        <p>Presentation files have been uploaded successfully.</p>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('presentations.show', $abstract) }}" class="button">
            👁️ View Presentation
        </a>
    </div>
    
    <div style="background-color: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50; margin: 20px 0;">
        <p><strong>✅ Upload Successful:</strong></p>
        <ul>
            <li>Your presentation materials are now available</li>
            <li>Conference organizers can access your files</li>
            <li>Your presentation is ready for the conference</li>
            <li>You can update files if needed before the deadline</li>
        </ul>
    </div>
    
    <h3>📅 Next Steps</h3>
    <ol>
        <li><strong>Review:</strong> Check that all files uploaded correctly</li>
        <li><strong>Test:</strong> Ensure your presentation works properly</li>
        <li><strong>Schedule:</strong> Wait for session assignment details</li>
        <li><strong>Prepare:</strong> Get ready for your presentation</li>
    </ol>
    
    <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <p><strong>📌 Important Reminders:</strong></p>
        <ul>
            <li>Keep a backup of your presentation files</li>
            <li>Test your presentation on different devices</li>
            <li>Check file formats are compatible with conference systems</li>
            <li>Contact us if you need to update your files</li>
        </ul>
    </div>
    
    <p>Thank you for your preparation! We look forward to your presentation.</p>
    
    <p>Best regards,<br>
    <strong>The Conference Committee</strong></p>
</div>
@endsection 
