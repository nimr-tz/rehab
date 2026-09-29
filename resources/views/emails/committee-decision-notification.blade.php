@extends('emails.layout')

@section('title', $decision === 'accepted' ? 'Decision: Accepted' : 'Abstract Update')

@section('content')
    <div class="message-intro">
        Dear {{ $user->first_name }} {{ $user->last_name }},
    </div>

    @if($decision === 'accepted')
        <p>We are delighted to inform you that the Scientific Committee has <strong>accepted</strong> your abstract for presentation at the <strong>{{ config('conference.edition') }} {{ config('conference.name') }} {{ config('conference.year') }}</strong>!</p>
    @elseif($decision === 'rejected')
        <p>Thank you for submitting your abstract to the {{ config('conference.short_name') }} {{ config('conference.year') }} Conference. After careful review by our Scientific Committee, we regret to inform you that your abstract was not selected for presentation at this conference.</p>
    @elseif(str_contains($decision, 'minor'))
        <p>The Scientific Committee has reviewed your abstract and determined that <strong>minor revisions</strong> are required before final acceptance.</p>
    @elseif(str_contains($decision, 'major'))
        <p>The Scientific Committee has reviewed your abstract and determined that <strong>major revisions</strong> are required before we can make a final decision.</p>
    @else
        <p>The Scientific Committee has made a decision regarding your abstract submission.</p>
    @endif

    <div class="info-card" style="{{ $decision === 'accepted' ? 'border-left: 6px solid #10b981; background: #f0fdf4;' : '' }}">
        <h3 style="{{ $decision === 'accepted' ? 'color: #059669;' : '' }}">📋 Abstract Details</h3>
        <table class="details-table">
            <tr>
                <th>Title:</th>
                <td>{{ $abstract->title }}</td>
            </tr>
            <tr>
                <th>Subtheme:</th>
                <td>{{ $abstract->subtheme }}</td>
            </tr>
            <tr>
                <th>Decision:</th>
                <td>
                    <span style="color: {{ $decision === 'accepted' ? '#059669' : ($decision === 'rejected' ? '#dc2626' : '#d97706') }};">
                        {{ ucfirst(str_replace('_', ' ', $decision)) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    @if($decision === 'accepted')
        <div class="info-card" style="border-left: 6px solid #3b82f6; background: #eff6ff;">
            <h3 style="color: #1d4ed8;">💳 Registration & Payments</h3>
            <p style="margin: 0; color: #1e40af;">To confirm your attendance and for inclusion in the final conference program, please ensure your conference registration payment is complete in the portal.</p>
        </div>
    @endif

    @if($notes)
        <div class="info-card" style="border-left: 6px solid #f59e0b; background: #fffbeb;">
            <h3 style="color: #d97706;">💬 Committee Notes</h3>
            <p style="margin: 0; color: #92400e; font-style: italic;">{{ $notes }}</p>
        </div>
    @endif

    <div class="info-card" style="background: #f8fafc;">
        <h3 style="color: #64748b;">📅 Next Steps</h3>
        @if($decision === 'accepted')
            <ul style="margin: 0; padding-left: 20px; color: #475569;">
                <li style="margin-bottom: 8px;">Final <strong>presentation specifications</strong> (formatting, presentation duration, etc.) will be communicated to you soon.</li>
                <li style="margin-bottom: 8px;">Complete your conference registration if you haven't already.</li>
                <li>Mark your calendar for the conference dates: <strong>{{ config('conference.display_dates') }}</strong>.</li>
            </ul>
            <div style="text-align: center; margin-top: 24px;">
                <a href="{{ route('dashboard') }}" class="cta-button">Go to Dashboard</a>
            </div>
        @elseif($decision === 'rejected')
            <p style="margin: 0; color: #64748b;">We encourage you to continue your research and consider submitting to future {{ config('conference.short_name') }} conferences. Your work is valuable to the scientific community.</p>
        @elseif(str_contains($decision, 'revision'))
            <ul style="margin: 0; padding-left: 20px; color: #475569;">
                <li style="margin-bottom: 8px;">Please review the committee notes carefully.</li>
                <li>Submit your revised abstract through your dashboard.</li>
                @if($abstract->revision_deadline)
                    <li style="margin-top: 8px;"><strong>Deadline:</strong> {{ $abstract->revision_deadline->format('F j, Y') }}</li>
                @endif
            </ul>
            <div style="text-align: center; margin-top: 24px;">
                <a href="{{ route('abstracts.edit', $abstract) }}" class="cta-button" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);">Submit Revision</a>
            </div>
        @else
            <div style="text-align: center;">
                <a href="{{ route('dashboard') }}" class="cta-button">Go to Dashboard</a>
            </div>
        @endif
    </div>

    <p style="margin-top: 32px;">If you have any questions about this decision, please log in to the portal or contact us at <a href="mailto:{{ config('conference.contact_email') }}">{{ config('conference.contact_email') }}</a>.</p>
    
    <p>Best regards,<br>
    <strong>The {{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong></p>
@endsection
