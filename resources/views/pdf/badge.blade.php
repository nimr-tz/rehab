{{-- One A6 badge per page, matching the on-screen badge (components/badge-card). $badges: list of ['registration' => Registration, 'qr' => data URI]. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #232733; }
        /* DomPDF reads px as 0.75pt: the A6 page is 297.64 x 419.53pt. */
        .page { position: relative; height: 419pt; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .top { background: #024f6d; color: #fff; padding: 20px 20px 14px; }
        .top table { width: 100%; border-collapse: collapse; }
        .logo { width: 40px; }
        .event { font-size: 12.5px; font-weight: bold; line-height: 1.25; }
        .event small { display: block; font-size: 8px; font-weight: normal; letter-spacing: 1px; color: #b0dbe9; text-transform: uppercase; }
        .stripe td { height: 5px; padding: 0; }
        .body { padding: 18px 20px 0; text-align: center; }
        .initials { margin: 0 auto; width: 64px; padding: 21px 0; line-height: 22px; border-radius: 32px; background: #eaf6fa; color: #024f6d; font-size: 21px; font-weight: bold; text-align: center; }
        .name { margin-top: 12px; font-size: 24px; font-weight: bold; line-height: 1.2; color: #232733; }
        .profession { margin-top: 5px; font-size: 13px; font-weight: bold; color: #024f6d; }
        .institution { margin-top: 4px; font-size: 11px; color: #515a6c; }
        .country { margin-top: 2px; font-size: 10px; color: #6b7386; }
        .foot { margin: 34px 20px 0; width: 356px; border-collapse: collapse; }
        .label { font-size: 7.5px; font-weight: bold; letter-spacing: 1.2px; color: #9aa1b1; text-transform: uppercase; }
        .ref { font-family: DejaVu Sans Mono, monospace; font-size: 12px; font-weight: bold; color: #232733; letter-spacing: 0.5px; }
        .qr img { width: 84px; height: 84px; }
        .role { position: absolute; bottom: 0; left: 0; right: 0; color: #fff; text-align: center; padding: 10px 0; font-size: 11px; font-weight: bold; letter-spacing: 3px; text-transform: uppercase; }
    </style>
</head>
<body>
    @foreach ($badges as ['registration' => $registration, 'qr' => $qr])
        @php
            $user = $registration->user;
            $role = $registration->badgeRole();
            $initials = collect(preg_split('/\s+/', trim($user->first_name.' '.$user->last_name)))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
        @endphp
        <div class="page">
            <div class="top">
                <table><tr>
                    <td style="width: 48px"><img class="logo" src="{{ $logo }}" alt=""></td>
                    <td class="event">{{ $summit->title() }}<small>{{ $summit->dateRange() ?? $summit->get('year') }}{{ $summit->get('city') ? ' · '.$summit->get('city') : '' }}</small></td>
                </tr></table>
            </div>
            <table class="stripe" style="width: 100%; border-collapse: collapse;"><tr>
                <td style="background: #df670d"></td><td style="background: #fd9a8f"></td><td style="background: #45582e"></td><td style="background: #f2b302"></td>
            </tr></table>

            <div class="body">
                <div class="initials">{{ $initials }}</div>
                <div class="name">{{ $registration->displayName() }}</div>
                @if ($user->profession)
                    <div class="profession">{{ $user->profession }}</div>
                @endif
                @if ($user->institution)
                    <div class="institution">{{ $user->institution }}</div>
                @endif
                <div class="country">{{ $user->countryName() }}</div>
            </div>

            <table class="foot"><tr>
                <td style="vertical-align: bottom;"><div class="label">Badge no.</div><div class="ref">{{ $registration->reference }}</div></td>
                <td class="qr" style="text-align: right; vertical-align: bottom;"><img src="{{ $qr }}" alt=""></td>
            </tr></table>

            <div class="role" style="background: {{ match ($role) { 'Presenter' => '#024f6d', 'Student' => '#45582e', default => '#df670d' } }}">{{ $role }}</div>
        </div>
    @endforeach
</body>
</html>
