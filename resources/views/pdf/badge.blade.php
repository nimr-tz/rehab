<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #232733; }
        .top { background: #024f6d; color: #fff; padding: 18px 20px 14px; }
        .top table { width: 100%; border-collapse: collapse; }
        .logo { width: 46px; }
        .event { font-size: 13px; font-weight: bold; line-height: 1.25; }
        .event small { display: block; font-size: 8.5px; font-weight: normal; letter-spacing: 1px; color: #b0dbe9; text-transform: uppercase; }
        .stripe { height: 6px; }
        .stripe td { height: 6px; padding: 0; }
        .body { padding: 22px 20px 0; text-align: center; }
        .name { font-size: 21px; font-weight: bold; line-height: 1.2; color: #024f6d; }
        .institution { margin-top: 6px; font-size: 10.5px; color: #515a6c; }
        .country { margin-top: 2px; font-size: 9.5px; color: #6b7386; }
        .qr { margin-top: 16px; }
        .qr img { width: 118px; height: 118px; }
        .ref { margin-top: 4px; font-family: DejaVu Sans Mono, monospace; font-size: 9px; color: #6b7386; letter-spacing: 1px; }
        .category { position: absolute; bottom: 0; left: 0; right: 0; background: #fff4ec; color: #963f0b; text-align: center; padding: 11px 0; font-size: 11px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="top">
        <table><tr>
            <td style="width: 54px"><img class="logo" src="{{ $logo }}" alt=""></td>
            <td class="event">{{ $summit->title() }}<small>{{ $summit->dateRange() ?? $summit->get('year') }}{{ $summit->venueLine() ? ' · '.$summit->get('city') : '' }}</small></td>
        </tr></table>
    </div>
    <table class="stripe" style="width: 100%; border-collapse: collapse;"><tr>
        <td style="background: #df670d"></td><td style="background: #fd9a8f"></td><td style="background: #45582e"></td><td style="background: #f2b302"></td>
    </tr></table>

    <div class="body">
        <div class="name">{{ $registration->displayName() }}</div>
        @if ($registration->user->institution)
            <div class="institution">{{ $registration->user->institution }}</div>
        @endif
        <div class="country">{{ $registration->user->countryName() }}</div>
        <div class="qr"><img src="{{ $qr }}" alt=""></div>
        <div class="ref">{{ $registration->reference }}</div>
    </div>

    <div class="category">{{ $registration->category->is_student ? 'Student' : 'Participant' }}</div>
</body>
</html>
