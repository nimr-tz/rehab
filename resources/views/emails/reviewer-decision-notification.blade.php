@extends('emails.layout')

@section('content')
<div class="header">
    <h1>
        @if($decision === 'accept' || $decision === 'accepted')
            ✅ Abstract Accepted
        @elseif($decision === 'reject' || $decision === 'rejected')
            ❌ Abstract Decision
        @elseif(str_contains($decision, 'minor'))
            📝 Minor Revision Requested
        @elseif(str_contains($decision, 'major'))
            📝 Major Revision Requested
        @else
            📋 Decision Update
        @endif
    </h1>
</div>

<div class="content">
    <p>Dear {{ $reviewer->first_name }} {{ $reviewer->last_name }},</p>
    
    <p>Thank you for your review of the abstract below. The admin has made a decision based on the reviews.</p>
    
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
                <th>Author:</th>
                <td>{{ $abstract->author_name }}</td>
            </tr>
            <tr>
                <th>Subtheme:</th>
                <td>{{ $abstract->subtheme }}</td>
            </tr>
            <tr>
                <th>Decision:</th>
                <td>
                    @if($decision === 'accept' || $decision === 'accepted')
                        <span style="color: #4caf50; font-weight: bold;">✅ Accepted</span>
                    @elseif($decision === 'reject' || $decision === 'rejected')
                        <span style="color: #f44336; font-weight: bold;">❌ Rejected</span>
                    @elseif(str_contains($decision, 'minor'))
                        <span style="color: #ff9800; font-weight: bold;">📝 Minor Revision Required</span>
                    @elseif(str_contains($decision, 'major'))
                        <span style="color: #ff5722; font-weight: bold;">📝 Major Revision Required</span>
                    @else
                        <span style="font-weight: bold;">{{ ucfirst(str_replace('_', ' ', $decision)) }}</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
    
    @if($adminMessage)
    <div style="background-color: #e3f2fd; padding: 20px; border-radius: 8px; border-left: 4px solid #2196f3; margin: 20px 0;">
        <h3>📋 Admin Notes</h3>
        <p style="line-height: 1.6;">{{ $adminMessage }}</p>
    </div>
    @endif
    
    <div style="background-color: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3>💡 Next Steps</h3>
        <p>
            @if($decision === 'accept' || $decision === 'accepted')
                The abstract has been accepted and will proceed to the conference program. The author has been notified.
            @elseif($decision === 'reject' || $decision === 'rejected')
                The abstract has been rejected. The author has been notified of this decision.
            @else
                The author has been requested to make revisions. Once the revision is submitted, you may be asked to review it again.
            @endif
        </p>
    </div>
    
    <p>Thank you for your valuable contribution to the review process.</p>
    
    <p>Best regards,<br>
    {{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</p>
</div>
@endsection
