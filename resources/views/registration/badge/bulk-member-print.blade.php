<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Group Badges</title>
    @include('registration.badge.partials.print-styles')
</head>
<body>
@foreach ($attendees as $data)
    @include('registration.badge.partials.single-print', [
        'badgeData' => [
            'subject' => $data['member'],
            'name' => $data['member']->full_name,
            'institution' => $data['member']->institution,
            'qrImage' => $data['qrImage'],
            'conference' => $conference,
        ],
    ])
@endforeach
</body>
</html>
