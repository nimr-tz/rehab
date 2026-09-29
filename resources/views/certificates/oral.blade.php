@include('certificates.partials.base', [
    'variant' => 'oral',
    'pageTitle' => 'Certificate of Presentation',
    'bodyLines' => array_filter([
        'For presenting an oral paper entitled:',
        $abstract?->title ? '<em><strong>&ldquo;' . e($abstract->title) . '&rdquo;</strong></em>' : null,
        'at the ' . e(config('conference.edition')) . ' ' . e(config('conference.name')) . ', ' . e(config('conference.year')) . ', ' . e(config('conference.city')) . '.',
    ]),
])
