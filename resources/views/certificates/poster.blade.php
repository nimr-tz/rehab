@include('certificates.partials.base', [
    'variant' => 'poster',
    'pageTitle' => 'Certificate of Poster Presentation',
    'bodyLines' => array_filter([
        $abstract?->title ? '"' . $abstract->title . '"' : null,
        'Poster #' . ($abstract->poster_id ?? ('P-' . str_pad($abstract->id, 3, '0', STR_PAD_LEFT))),
    ]),
])
