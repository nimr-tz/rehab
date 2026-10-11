@php
    $edition = $registration->edition;
    $start = $edition->start_date;
    $end = $edition->end_date ?? $start;
    $dates = match (true) {
        ! $start => (string) $edition->year,
        $start->isSameDay($end) => $start->format('j F Y'),
        $start->isSameMonth($end) => $start->format('j').'–'.$end->format('j F Y'),
        default => $start->format('j M').' – '.$end->format('j M Y'),
    };
    $where = collect([$edition->venue, $edition->city, $edition->country])->filter()->implode(', ');
    $summitName = trim($edition->ordinal.' '.$edition->name.' '.$edition->year);
    $user = $registration->user;
    $attendedDays = $days->map(fn ($d) => $d->format('l j F'))->values();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #232733; }
        .frame { position: fixed; top: 24px; left: 24px; right: 24px; bottom: 24px; border: 3px solid #024f6d; }
        .frame-inner { position: fixed; top: 33px; left: 33px; right: 33px; bottom: 33px; border: 1px solid #b9d7e3; }
        .content { margin: 36px 36px 0; text-align: center; }
        .stripe { width: 100%; border-collapse: collapse; }
        .stripe td { height: 7px; padding: 0; }
        .org { margin-top: 22px; font-size: 13px; font-weight: bold; color: #024f6d; letter-spacing: 1px; }
        .tag { font-size: 8px; letter-spacing: 3px; color: #6b7386; text-transform: uppercase; }
        h1 { margin: 26px 0 0; font-size: 34px; color: #024f6d; letter-spacing: 1px; }
        .presented { margin-top: 18px; font-size: 11px; color: #6b7386; }
        .name { margin: 8px auto 0; font-size: 30px; font-weight: bold; color: #232733; border-bottom: 1px solid #dfe4eb; padding-bottom: 8px; width: 70%; }
        .institution { margin-top: 6px; font-size: 11px; color: #515a6c; }
        .event { margin: 16px auto 0; width: 76%; font-size: 12px; color: #515a6c; line-height: 1.6; }
        .event strong { color: #024f6d; }
        .days { margin-top: 8px; font-size: 10.5px; color: #515a6c; }
        .signatures { margin: 52px auto 0; width: 86%; border-collapse: collapse; }
        .signatures td { width: 33%; text-align: center; vertical-align: bottom; font-size: 9.5px; color: #515a6c; }
        .line { border-top: 1px solid #949cab; margin: 0 24px 5px; }
        .verify { position: fixed; bottom: 42px; right: 48px; text-align: center; font-size: 7px; color: #949cab; }
        .serial { position: fixed; bottom: 44px; left: 0; right: 0; text-align: center; font-size: 8px; color: #949cab; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="frame"></div>
    <div class="frame-inner"></div>
    <div class="content">
        <table class="stripe"><tr>
            <td style="background: #df670d"></td><td style="background: #fd9a8f"></td><td style="background: #45582e"></td><td style="background: #f2b302"></td>
        </tr></table>

        <div class="org"><img src="{{ $logo }}" style="height: 44px; vertical-align: middle" alt=""></div>
        <div class="org" style="margin-top: 6px">{{ $summit->get('organiser') }}</div>
        <div class="tag">Transforming lives</div>

        <h1>Certificate of Attendance</h1>

        <div class="presented">This is to certify that</div>
        <div class="name">{{ $user->name }}</div>
        @if ($user->institution)
            <div class="institution">{{ $user->institution }}</div>
        @endif

        <div class="event">
            attended the <strong>{{ $summitName }}</strong>,
            held {{ $dates }}@if ($where) at {{ $where }}@endif.
        </div>
        @if ($attendedDays->isNotEmpty() && $attendedDays->count() < max(1, (int) $start?->diffInDays($end) + 1))
            <div class="days">Days attended: {{ $attendedDays->implode(', ') }}</div>
        @endif

        <table class="signatures"><tr>
            <td><div class="line"></div>Chairperson, Organising Committee</td>
            <td></td>
            <td><div class="line"></div>Chairperson, Scientific Committee</td>
        </tr></table>
    </div>

    <div class="verify"><img src="{{ $qr }}" style="width: 64px; height: 64px" alt=""><br>Scan to verify</div>
    <div class="serial">Certificate {{ $registration->certificateNumber() }} · verify at {{ $verifyUrl }}</div>
</body>
</html>
