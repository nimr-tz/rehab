@extends('emails.layout')

@section('title', 'Review Assignment Reassigned')

@section('content')
<div style="margin-bottom: 30px;">
    <p style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">
        Dear {{ $reviewer->first_name }} {{ $reviewer->last_name }},
    </p>
    <p style="color: #475569; line-height: 1.7;">
        This is to inform you that your review assignment has been reassigned to another reviewer to keep the review process on schedule.
    </p>
</div>

<div class="highlight">
    <h3 style="margin-bottom: 20px; color: #ef4444; font-weight: 800;">Reassignment Notice</h3>
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
            <th>Reason</th>
            <td>{{ $reason }}</td>
        </tr>
    </table>
</div>

<div style="color: #64748b; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
    <p style="margin-bottom: 10px;">
        If this reassignment was unexpected or you believe this is a mistake, please contact the scientific committee.
    </p>
</div>

<div style="margin-top: 40px;">
    <p style="color: #475569; margin-bottom: 5px;">Best regards,</p>
    <p style="color: #0f172a; font-weight: 700; font-size: 16px;">{{ config('conference.short_name') }} {{ config('conference.year') }} Scientific Committee</p>
</div>
@endsection
