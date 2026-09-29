<x-mail::message>
# Scientific Session Rapporteur Assignment

Dear {{ $user->title }} {{ $user->last_name }},

We are pleased to inform you that you have been assigned as the **Rapporteur** for the following scientific session at the {{ config('conference.short_name') }} {{ config('conference.year') }} conference:

**Session:** {{ $session->name }}
**Type:** {{ ucfirst($session->session_type) }}
**Location:** {{ $session->room_location ?? 'To be announced' }}
**Schedule:** {{ $session->getPrimaryDayLabel() }}
**Time:** {{ $session->start_time ? $session->start_time->format('g:i A') : '--' }} - {{ $session->end_time ? $session->end_time->format('g:i A') : '--' }}

As a Rapporteur, your role is to document the proceedings of the session, including summary of presentations, key discussion points, and recommendations. You will be able to input these notes directly into the conference system during or after the session.

<x-mail::button :url="$confirmationUrl" color="success">
Confirm Assignment
</x-mail::button>

<x-mail::button :url="route('sessions.show', $session)">
View Session & Access Report Form
</x-mail::button>

Thank you for your valuable contribution to {{ config('conference.short_name') }} {{ config('conference.year') }}.

Best regards,<br>
{{ config('conference.short_name') }} {{ config('conference.year') }} Steering Committee
</x-mail::message>
