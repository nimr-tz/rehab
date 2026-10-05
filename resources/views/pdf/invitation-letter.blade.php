@php
    $user = $registration->user;
    $dates = $summit->dateRange() ?? 'dates to be confirmed';
    $venue = $summit->venueLine() ? $summit->get('venue').', '.$summit->get('city').', '.$summit->get('country') : 'a venue to be confirmed';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 48px 56px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.6; color: #232733; }
        .head { width: 100%; border-collapse: collapse; border-bottom: 2px solid #024f6d; padding-bottom: 10px; }
        .org { font-size: 16px; font-weight: bold; color: #024f6d; }
        .tag { font-size: 8.5px; letter-spacing: 2px; color: #6b7386; text-transform: uppercase; }
        .meta { text-align: right; font-size: 9.5px; color: #515a6c; }
        .stripe td { height: 4px; padding: 0; }
        h1 { font-size: 13px; color: #024f6d; margin: 26px 0 12px; text-transform: uppercase; letter-spacing: 1px; }
        .facts { width: 100%; border-collapse: collapse; margin: 14px 0; }
        .facts td { padding: 5px 8px; border-bottom: 1px solid #dfe4eb; vertical-align: top; }
        .facts td:first-child { width: 34%; color: #6b7386; }
        .sign { margin-top: 36px; }
        .foot { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 8.5px; color: #949cab; text-align: center; }
    </style>
</head>
<body>
    <table class="head"><tr>
        <td style="width: 60px"><img src="{{ $logo }}" style="width: 52px" alt=""></td>
        <td><div class="org">{{ $summit->get('organiser') }}</div><div class="tag">Transforming lives</div></td>
        <td class="meta">Ref: {{ $registration->reference }}/INV<br>{{ now()->format('j F Y') }}</td>
    </tr></table>
    <table class="stripe" style="width: 100%; border-collapse: collapse;"><tr>
        <td style="background: #df670d"></td><td style="background: #fd9a8f"></td><td style="background: #45582e"></td><td style="background: #f2b302"></td>
    </tr></table>

    <p style="margin-top: 24px">
        {{ $user->name }}<br>
        {{ $user->institution }}<br>
        {{ $user->countryName() }}
    </p>

    <h1>Letter of invitation: {{ $summit->title() }}</h1>

    <p>Dear {{ $user->title ? $user->title.' '.$user->last_name : $user->first_name.' '.$user->last_name }},</p>

    <p>
        On behalf of the organising committee, it is my pleasure to invite you to the {{ $summit->get('edition') }}
        {{ $summit->get('name') }}, organised by {{ $summit->get('organiser') }}@if ($summit->get('co_organiser')) in collaboration with the {{ $summit->get('co_organiser') }}@endif,
        to be held on {{ $dates }} at {{ $venue }}.
    </p>

    <p>Your registration is confirmed and your registration fee has been received. The details of your participation are:</p>

    <table class="facts">
        <tr><td>Full name</td><td>{{ $user->first_name }} {{ $user->last_name }}</td></tr>
        <tr><td>Passport number</td><td>{{ $registration->passport_number ?? '—' }}</td></tr>
        <tr><td>Nationality</td><td>{{ $user->countryName() }}</td></tr>
        <tr><td>Institution</td><td>{{ $user->institution ?? '—' }}</td></tr>
        <tr><td>Registration</td><td>{{ $registration->reference }} · {{ $registration->category->name }}</td></tr>
    </table>

    <p>
        This letter is issued to support your visa application. It does not commit {{ $summit->get('organiser') }}
        to any financial obligation for travel, accommodation or other expenses, which remain your responsibility.
    </p>

    <p>We look forward to welcoming you.</p>

    <div class="sign">
        Yours sincerely,<br><br><br>
        <strong>Chairperson, Organising Committee</strong><br>
        {{ $summit->title() }}<br>
        {{ $summit->get('contact_email') }}
    </div>

    <div class="foot">{{ $summit->get('organiser') }} · {{ $summit->get('contact_email') }} · {{ preg_replace('#^https?://#', '', $summit->get('website')) }}</div>
</body>
</html>
