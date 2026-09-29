@extends('emails.layout')

@section('title', 'New Payment Submitted - ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="header" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
    <h1>🔔 New Payment Proof</h1>
</div>

<div class="content">
    <p>A new conference registration payment has been submitted and is awaiting verification.</p>
    
    <div class="highlight">
        <h3>👤 Payment Details</h3>
        <table class="details-table">
            @if(isset($groupRegistration) && $groupRegistration)
                <tr>
                    <th>Type:</th>
                    <td><strong>GROUP REGISTRATION</strong></td>
                </tr>
                <tr>
                    <th>Group Name:</th>
                    <td>{{ $groupRegistration->group_name }}</td>
                </tr>
                <tr>
                    <th>Group Leader:</th>
                    <td>{{ $user->full_name }}</td>
                </tr>
                <tr>
                    <th>Members:</th>
                    <td>{{ $groupRegistration->member_count }}</td>
                </tr>
            @else
                <tr>
                    <th>Type:</th>
                    <td>Individual Registration</td>
                </tr>
                <tr>
                    <th>Name:</th>
                    <td>{{ $user->full_name }}</td>
                </tr>
                <tr>
                    <th>Category:</th>
                    <td>{{ $user->registration_category }}</td>
                </tr>
            @endif
            <tr>
                <th>Email:</th>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <th>Submitted At:</th>
                <td>{{ now()->format('M d, Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <p>Please review the payment evidence in the finance panel and either verify or reject the submission.</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ isset($groupRegistration) && $groupRegistration ? route('finance.group.show', $groupRegistration) : route('finance.dashboard') }}" class="button">
            🔍 Review Payment Proof
        </a>
    </div>

    <p>This is an automated notification from the {{ config('conference.short_name') }} {{ config('conference.year') }} Conference Management System.</p>
</div>
@endsection

