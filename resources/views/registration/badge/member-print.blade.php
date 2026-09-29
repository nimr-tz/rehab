<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Conference Badge - {{ $member->full_name }}</title>
    @include('registration.badge.partials.print-styles')
</head>
<body>
@include('registration.badge.partials.single-print', [
    'badgeData' => [
        'subject' => $member,
        'name' => $member->full_name,
        'institution' => $printInstitute ?? $member->institution,
        'qrImage' => $qrImage,
        'conference' => $conference,
    ],
])
</body>
</html>
