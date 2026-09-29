<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Delegate Badges</title>
    @include('registration.badge.partials.print-styles')
</head>
<body>
@foreach($attendees as $item)
    @include('registration.badge.partials.single-print', [
        'badgeData' => [
            'subject' => $item['subject'],
            'name' => $item['name'],
            'institution' => $item['institution'],
            'qrImage' => $item['qrImage'],
            'conference' => $conference,
        ],
    ])
@endforeach
</body>
</html>
