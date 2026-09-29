@include('certificates.partials.base', [
    'variant' => 'attendance_partial',
    'pageTitle' => 'Certificate of Participation',
    'bodyLines' => [
        'Attended: ' . $certificate->attendance_dates,
    ],
])
