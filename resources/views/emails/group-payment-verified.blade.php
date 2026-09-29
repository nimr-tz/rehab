@extends('emails.layout')

@section('title', 'Group Payment Verified - ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="header" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
    <h1>✅ Group Payment Verified</h1>
</div>

<div class="content">
    <p>Dear {{ $groupRegistration->leader->first_name }},</p>
    
    <p>We are pleased to inform you that the registration payment for the group <strong>"{{ $groupRegistration->group_name ?: 'Group #' . $groupRegistration->id }}"</strong> has been successfully verified by our finance team.</p>
    
    <div class="highlight">
        <h3>💳 Group Payment Details</h3>
        <table class="details-table">
            <tr>
                <th>Group Name:</th>
                <td>{{ $groupRegistration->group_name ?: 'N/A' }}</td>
            </tr>
            <tr>
                <th>Members Covered:</th>
                <td>{{ $groupRegistration->member_count }}</td>
            </tr>
            <tr>
                <th>Total Amount:</th>
                <td>{{ $groupRegistration->formatted_total }}</td>
            </tr>
            <tr>
                <th>Verification Date:</th>
                <td>{{ $groupRegistration->payment_verified_at->format('M d, Y') }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span style="color: #059669; font-weight: bold;">VERIFIED</span></td>
            </tr>
        </table>
    </div>

    @if($notes)
    <div style="background-color: #f0fdf4; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;">
        <p><strong>📝 Finance Officer Notes:</strong></p>
        <p>{{ $notes }}</p>
    </div>
    @endif

    <p>Your group members are now active for registration desk check-in and badge printing. Members who already have portal accounts have also been marked as paid under this group registration.</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('group-registration.show', $groupRegistration) }}" class="button" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
            🚀 Manage Group Portal
        </a>
    </div>

    <p>We look forward to welcoming you and your colleagues at the conference!</p>
    
    <p>Best regards,<br>
    <strong>{{ config('conference.short_name') }} {{ config('conference.year') }} Finance Team</strong></p>
</div>
@endsection
