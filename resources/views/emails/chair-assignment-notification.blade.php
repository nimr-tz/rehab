<x-mail::message>
# Scientific Session Chairperson Assignment

Dear {{ $user->title }} {{ $user->last_name }},

We are pleased to inform you that you have been assigned as the **Chairperson** for the following scientific session at the {{ config('conference.short_name') }} {{ config('conference.year') }} conference:

**Session:** {{ $session->name }}
**Type:** {{ ucfirst($session->session_type) }}
**Location:** {{ $session->room_location ?? 'To be announced' }}
**Schedule:** {{ $session->getPrimaryDayLabel() }}
**Time:** {{ $session->start_time ? $session->start_time->format('g:i A') : '--' }} - {{ $session->end_time ? $session->end_time->format('g:i A') : '--' }}

As a Chairperson, your role is crucial in ensuring the smooth flow of the session, introducing presenters, and managing the discussion/Q&A period.

You can view the full list of abstracts in your session and download the presentation files directly from your dashboard.

<x-mail::button :url="$confirmationUrl" color="success">
Confirm Assignment
</x-mail::button>

<x-mail::button :url="route('sessions.show', $session)">
View Session Details
</x-mail::button>

If you have any questions or are unable to fulfill this role, please contact the conference steering committee immediately.

Thank you for your valuable contribution to {{ config('conference.short_name') }} {{ config('conference.year') }}.

Best regards,<br>
{{ config('conference.short_name') }} {{ config('conference.year') }} Steering Committee
</x-mail::message>
