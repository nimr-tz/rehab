@extends('emails.layout')

@section('title', 'Important Submitter Update')

@section('content')
<p class="message-intro">Dear {{ $user->first_name }} {{ $user->last_name }},</p>

<p>Thank you for your abstract submission to {{ config('conference.short_name') }} {{ config('conference.year') }}.</p>

<p>We would like to share an important clarification regarding presentation communication and the current status of submitted abstracts.</p>

<div class="info-card" style="border-left: 4px solid #2563eb; background-color: #f8fbff;">
    <h3 style="color: #1d4ed8;">Important Clarification</h3>
    <p style="margin: 0 0 12px 0;">
        Some of you may have received earlier communication that mentioned an oral or poster presentation format.
        That wording reflected the presentation preference indicated at submission and was not intended to communicate
        a final presentation format.
    </p>
    <p style="margin: 0; color: #1e3a8a; font-weight: 700;">
        The final presentation format will still be communicated through an official update.
    </p>
</div>

<div class="info-card" style="border-left: 4px solid #0f766e; background-color: #f5fbfa;">
    <h3 style="color: #0f766e;">What This Means</h3>
    <p style="margin: 0;">
        While we do not anticipate major changes for most presentations with regard to the preference you indicated at
        submission, the final confirmation will still be shared officially. We apologize for any confusion this may
        have caused.
    </p>
</div>

<div class="info-card" style="border-left: 4px solid #059669; background-color: #f4fbf8;">
    <h3 style="color: #047857;">If Your Abstract Has Been Accepted And You Have Already Submitted Materials</h3>
    <p style="margin: 0;">
        You may revise and re-upload your presentation materials as many times as needed. The final submission
        deadline for presentation materials will be one day before the conference.
    </p>
</div>

<div class="info-card" style="border-left: 4px solid #7c3aed; background-color: #faf7ff;">
    <h3 style="color: #6d28d9;">If Your Abstract Has Been Accepted And You Have Not Yet Submitted Materials</h3>
    <p style="margin: 0;">
        We kindly ask you to await the next official communication regarding final presentation format and further
        presentation guidance.
    </p>
</div>

<div class="info-card" style="border-left: 4px solid #475569; background-color: #f8fafc;">
    <h3 style="color: #334155;">If Your Abstract Is Still Under Review Or Awaiting A Final Decision</h3>
    <p style="margin: 0;">
        Please continue to monitor your dashboard and the official {{ config('conference.short_name') }} {{ config('conference.year') }} communication channels. Further updates
        will be shared as decisions are finalized.
    </p>
</div>

<div class="info-card" style="border-left: 4px solid #1e40af; background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);">
    <h3 style="color: #1e40af;">Next Official Update</h3>
    <p style="margin: 0;">
        Further guidance regarding presentation arrangements, timelines, and final presentation format will be
        communicated in a later official update.
    </p>
</div>

<p>Thank you for your understanding and for your contribution to {{ config('conference.short_name') }} {{ config('conference.year') }}.</p>

<p style="margin-top: 32px;">Warm regards,<br><strong>{{ config('conference.short_name') }} {{ config('conference.year') }}</strong></p>
@endsection
