@extends('emails.layout')

@section('title', 'Reviewer Assignment')

@section('content')
<div style="margin-bottom: 30px;">
    <p style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">
        Dear {{ $reviewer->first_name }} {{ $reviewer->last_name }},
    </p>
    <p style="color: #475569; line-height: 1.7;">
        On behalf of the Scientific Committee, we are pleased to invite you to evaluate a new abstract submission for the <strong>{{ config('conference.edition') }} {{ config('conference.name') }} {{ config('conference.year') }}</strong>. Your expertise is invaluable to maintaining the high scientific standards of our conference.
    </p>
</div>

@if(($assignmentType ?? 'assigned') === 'reassigned')
<div style="background-color: #fff7ed; border-left: 4px solid #f97316; padding: 16px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0; color: #9a3412; font-size: 14px;">
        <strong>Re-assigned:</strong> This abstract has been reassigned to you to keep the review process on schedule.
    </p>
</div>
@endif

@if(in_array(($matchType ?? 'exact'), ['related', 'fallback'], true))
<div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 16px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0; color: #991b1b; font-size: 14px;">
        <strong>Expertise Note:</strong>
        @if(($matchType ?? 'exact') === 'related')
            This assignment is based on a related subtheme due to limited availability in the exact area.
        @else
            This assignment is outside your selected expertise due to limited availability.
        @endif
        If this is not within your expertise, please notify the committee immediately so we can reassign it.
    </p>
</div>
@endif

<div class="highlight">
    <h3 style="margin-bottom: 20px; color: #2563eb; font-weight: 800;">Abstract for Review</h3>
    
    <table class="details-table">
        <tr>
            <th>Abstract ID</th>
            <td>#{{ str_pad($abstract->id, 4, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <th>Title</th>
            <td>{{ $abstract->title }}</td>
        </tr>
        <tr>
            <th>Subtheme</th>
            <td>{{ $abstract->subtheme ?? 'General Scientific' }}</td>
        </tr>
        <tr>
            <th>Round</th>
            <td>{{ $abstract->current_revision_round > 0 ? 'Revision Round ' . $abstract->current_revision_round : 'Initial Submission' }}</td>
        </tr>
    </table>
</div>

<div style="margin: 35px 0; text-align: center;">
    <a href="{{ route('reviewer.review', $abstract) }}" class="button">
        Access Review Interface
    </a>
</div>

<div style="background-color: #f0fdf4; border-left: 4px solid #22c55e; padding: 20px; border-radius: 8px; margin: 30px 0;">
    <p style="margin: 0; color: #166534; font-size: 15px; font-weight: 500;">
        <strong>🛡️ Blind Review Policy:</strong> To ensure impartiality, this is a blind review. Please evaluate the work based solely on its scientific merit and relevance to the conference themes.
    </p>
</div>

<div style="color: #64748b; font-size: 14px; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
    <p style="margin-bottom: 10px;">
        <strong>Timeline:</strong> We kindly request your evaluation within <strong>48 hours</strong> to facilitate timely notification of the authors.
    </p>
    <p>
        If you are unable to review this abstract at this time, please let us know immediately so we can reassign it.
    </p>
</div>

<div style="margin-top: 40px;">
    <p style="color: #475569; margin-bottom: 5px;">Best regards,</p>
    <p style="color: #0f172a; font-weight: 700; font-size: 16px;">{{ config('conference.short_name') }} {{ config('conference.year') }} Scientific Committee</p>
</div>
@endsection
