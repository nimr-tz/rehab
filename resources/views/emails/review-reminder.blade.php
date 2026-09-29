@extends('emails.layout')

@section('content')
<div class="header">
    <h1>⏰ Review Reminder</h1>
</div>

<div class="content">
    <p>Dear {{ $reviewer->first_name }} {{ $reviewer->last_name }},</p>

    <p>This is a friendly reminder that you have <strong>{{ $pendingReviews->count() }}</strong> abstract review{{ $pendingReviews->count() > 1 ? 's' : '' }} pending your evaluation.</p>

    <div class="highlight">
        <h3>📝 Pending Reviews ({{ $pendingReviews->count() }})</h3>
        <table class="details-table">
            <thead>
                <tr>
                    <th>Abstract ID</th>
                    <th>Title</th>
                    <th>Subtheme</th>
                    <th>Days Pending</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingReviews as $abstract)
                <tr>
                    <td><strong>#{{ $abstract->id }}</strong></td>
                    <td>{{ Str::limit($abstract->title, 50) }}</td>
                    <td>{{ $abstract->subtheme }}</td>
                    <td>
                        @php
                            $daysPending = (int)$abstract->created_at->diffInDays(now());
                        @endphp
                        <span style="color: {{ $daysPending > 7 ? '#f44336' : ($daysPending > 5 ? '#ff9800' : '#4caf50') }}">
                            {{ $daysPending }} day{{ $daysPending > 1 ? 's' : '' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($deadline)
    <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <h3>⚠️ Review Deadline</h3>
        <p><strong>Target Completion Date:</strong> {{ $deadline->format('l, M d, Y') }}</p>
        <p>Please complete your reviews by this date to ensure timely processing of submissions.</p>
    </div>
    @endif

    <div style="background-color: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50; margin: 20px 0;">
        <h3>📋 Review Process Reminder</h3>
        <ul>
            <li><strong>Access Reviews:</strong> Log in to your reviewer dashboard</li>
            <li><strong>Evaluate:</strong> Score each abstract based on the review criteria</li>
            <li><strong>Comment:</strong> Provide constructive feedback for authors</li>
            <li><strong>Recommend:</strong> Make your acceptance/rejection recommendation</li>
        </ul>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('reviewer.dashboard') }}" class="button">
            📝 Complete Your Reviews
        </a>
    </div>

    @if($pendingReviews->count() > 5)
    <div style="background-color: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3; margin: 20px 0;">
        <h4>🎯 Tip for Managing Multiple Reviews</h4>
        <p>With {{ $pendingReviews->count() }} reviews pending, consider:</p>
        <ul>
            <li>Reviewing 2-3 abstracts per session</li>
            <li>Starting with the oldest submissions first</li>
            <li>Setting aside dedicated time blocks for reviewing</li>
            <li>Focusing on one subtheme at a time for consistency</li>
        </ul>
    </div>
    @endif

    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <h4>❓ Need Assistance?</h4>
        <p>If you have any questions about the review process, criteria, or technical issues accessing the submissions, please don't hesitate to contact our support team.</p>
    </div>

    <p>Your expertise and timely reviews are crucial to maintaining the quality and integrity of our conference. Thank you for your dedicated service to the academic community!</p>

    <p>Best regards,<br>
    <strong>The Conference Program Committee</strong></p>
</div>
@endsection
