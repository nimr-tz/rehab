<?php

/*
| The photo gallery. Photographers upload from the portal; the public browses
| /gallery. Files live on the server's own disk for now. Pointing GALLERY_DISK
| at an S3-compatible disk later moves new uploads there without code changes.
*/

return [

    'disk' => env('GALLERY_DISK', 'local'),

    // Browsers send each photo in pieces of this size, so uploads stay under the
    // server's request limits (nginx allows 1 MB per request by default).
    'chunk_kb' => (int) env('GALLERY_CHUNK_KB', 900),

    'max_photo_mb' => (int) env('GALLERY_MAX_PHOTO_MB', 50),

    // Decoding a photo needs about 4 bytes per pixel, twice over when it is rotated.
    'max_megapixels' => 50,
    'memory_limit' => '768M',

    // Longest side in pixels, and JPEG quality, for the copies the site shows.
    'display' => ['size' => 2048, 'quality' => 82],
    'thumb' => ['size' => 720, 'quality' => 78],
    'original_quality' => 95, // only when a rotated original has to be re-encoded

    'per_page' => 120,

];
