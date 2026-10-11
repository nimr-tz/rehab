<?php

/*
| Slides for accepted abstracts. Presenters upload them before the edition's
| presentation deadline; the committee can upload for them at any time.
*/

return [

    'disk' => env('PRESENTATION_DISK', 'local'),

    // Sent in chunks of this size, like gallery photos (see config/gallery.php).
    'chunk_kb' => (int) env('PRESENTATION_CHUNK_KB', 900),

    'max_mb' => (int) env('PRESENTATION_MAX_MB', 100),

    'extensions' => ['pdf', 'ppt', 'pptx', 'key', 'odp'],

];
