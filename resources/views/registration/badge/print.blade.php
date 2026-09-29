<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Conference Badge - {{ $user->full_name }}</title>
    @include('registration.badge.partials.print-styles')
</head>
<body>
@include('registration.badge.partials.single-print', [
    'badgeData' => [
        'subject' => $user,
        'name' => $printName ?? trim(($user->title ? $user->title . ' ' : '') . $user->first_name . ' ' . $user->last_name),
        'institution' => $printInstitute ?? ($user->institute ?: $user->affiliation),
        'qrImage' => $qrImage,
        'conference' => $conference,
    ],
])
</body>
</html>
