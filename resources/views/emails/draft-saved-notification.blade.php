@extends('emails.layout')

@section('content')
<div class="header">
    <h1>💾 Abstract Draft Saved!</h1>
</div>

<div class="content">
    <p>Dear {{ $abstract->author_name }},</p>
    
    <p>Your abstract draft has been successfully saved. You can continue editing and submit it when you're ready.</p>
    
    <div class="highlight">
        <h3>📋 Draft Details</h3>
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
                <th>Last Saved:</th>
                <td>{{ $abstract->updated_at->format('M d, Y \a\t g:i A') }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span style="color: #ff9800; font-weight: bold;">Draft</span></td>
            </tr>
        </table>
    </div>
    
    <h3>📝 Next Steps</h3>
    <ol>
        <li><strong>Review Your Abstract:</strong> Make sure all information is complete and accurate</li>
        <li><strong>Check Co-authors:</strong> Verify all co-author information is correct</li>
        <li><strong>Select Presentation Mode:</strong> Choose between Oral or Poster presentation</li>
        <li><strong>Submit When Ready:</strong> Click the submit button when you're satisfied</li>
    </ol>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('abstracts.edit', $abstract) }}" class="button">
            ✏️ Continue Editing
        </a>
    </div>
    
    <div style="background-color: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3; margin: 20px 0;">
        <p><strong>💡 Tips:</strong></p>
        <ul>
            <li>You can edit your draft as many times as needed</li>
            <li>Drafts are not visible to reviewers or administrators</li>
            <li>Only submitted abstracts will be considered for review</li>
            <li>You can withdraw a submitted abstract if needed</li>
        </ul>
    </div>
    
    <p>Thank you for your contribution to our conference!</p>
    
    <p>Best regards,<br>
    <strong>The Conference Committee</strong></p>
</div>
@endsection 
