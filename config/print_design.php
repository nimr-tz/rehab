<?php

return [
    /*
     * Optional cover PDFs (paths under public/) wrapped around the generated
     * abstract book and programme. Leave unset to print without covers.
     */
    'covers' => [
        'abstract_book' => env('ABSTRACT_BOOK_COVER_PDF'),
        'programme' => env('PROGRAMME_COVER_PDF'),
        'back' => env('BACK_COVER_PDF'),
    ],

    'badge' => [
        'template' => [
            'background' => env('BADGE_ARTWORK', 'images/brand/badge.png'),
            'print_width_mm' => 80.88,
            'print_height_mm' => 137.4,
            'preview_width_px' => 430,
        ],
        'placeholders' => [
            'name' => [
                'left' => '5%',
                'top' => '56%',
                'top_print' => '59.5%',
                'width' => '90%',
                'min_height' => '13%',
                'font_size_print' => '5.3mm',
                'font_size_preview' => '1.75rem',
                'line_height' => '1.15',
                'letter_spacing' => '0.02em',
                'color' => '#154b5f',
                'color_print' => '#154b5f',
                'font_weight' => 900,
                'font_weight_print' => 700,
                'align' => 'center',
                'text_transform' => 'uppercase',
                'shadow' => '0.4px 0 0 #154b5f, -0.4px 0 0 #154b5f, 0 0.4px 0 #154b5f',
                'font_family' => '"Trebuchet MS", "Segoe UI", Arial, sans-serif',
                'font_family_print' => '"DejaVu Sans", Helvetica, Arial, sans-serif',
            ],
            'institution' => [
                'left' => '12%',
                'top' => '72%',
                'top_print' => '73.9%',
                'width' => '76%',
                'min_height' => '6%',
                'font_size_print' => '3.4mm',
                'font_size_preview' => '1.0rem',
                'line_height' => '1.32',
                'letter_spacing' => '0.08em',
                'color' => '#a07a2a',
                'color_print' => '#a07a2a',
                'font_weight' => 900,
                'font_weight_print' => 700,
                'align' => 'center',
                'text_transform' => 'uppercase',
                'shadow' => 'none',
                'font_family' => '"Gill Sans", "Trebuchet MS", "Segoe UI", Arial, sans-serif',
                'font_family_print' => '"DejaVu Sans", Helvetica, Arial, sans-serif',
            ],
            'qr' => [
                'left' => '40%',
                'top' => '81%',
                'top_print' => '84.2%',
                'width' => '19%',
                'height' => '10.5%',
            ],
            'divider' => [
                'left' => '32%',
                'top' => '69%',
                'top_print' => '72%',
                'width' => '36%',
                'height' => '0.4%',
                'background' => '#c89b3c',
            ],
            'divider_dot' => [
                'left' => '48%',
                'top' => '68.1%',
                'top_print' => '71.1%',
                'size_print' => '2mm',
                'size_preview' => '10px',
                'background' => '#154b5f',
            ],
        ],
    ],
    'certificate' => [
        /*
         * Certificates work like the ID badge: a finished artwork image is the
         * whole design, and we only stamp dynamic text (name, details, QR) on
         * top of it. The PDF page size follows the artwork — set width/height
         * to match its aspect ratio (e.g. 297x210 for A4-landscape artwork).
         */
        'template' => [
            'width_mm' => 297,
            'height_mm' => 210,
        ],

        /*
         * Custom fonts embedded into the PDF via @font-face.
         * Files live under public/. Weight/style must match what the file
         * actually contains, or dompdf falls back to the default font.
         */
        'fonts' => [
            [
                'family' => 'Permanent Marker',
                'file' => 'fonts/PermanentMarker-Regular.ttf',
                'weight' => 'normal',
                'style' => 'normal',
            ],
        ],

        /*
         * Placeholder geometry: left/top/width are % of the page.
         * Variants may override any of these via their own 'placeholders' key.
         */
        'placeholders' => [
            'name' => [
                'left' => 10.0,
                'top' => 40.0,
                'width' => 80.0,
                'font_size' => '12mm',
                // Names longer than max_chars shrink proportionally, down to min_font_size
                'max_chars' => 26,
                'min_font_size' => '6.5mm',
                'line_height' => 1.05,
                'letter_spacing' => '0.02em',
                'color' => '#154b5f',
                'font_weight' => 400,
                'align' => 'center',
                'text_transform' => 'uppercase',
                'font_family' => '"Permanent Marker", "DejaVu Sans", sans-serif',
            ],
            'body' => [
                'left' => 15.0,
                'top' => 63.0,
                'width' => 70.0,
                'font_size' => '4mm',
                'line_height' => 1.35,
                'letter_spacing' => '0',
                'color' => '#3b4a63',
                'font_weight' => 400,
                'align' => 'center',
                'text_transform' => 'none',
                'font_family' => '"DejaVu Sans", Helvetica, Arial, sans-serif',
            ],
            'qr' => [
                'left' => 47.3,
                'top' => 74.0,
                'width' => 5.4,
                'height' => 7.6,
            ],
            'meta' => [
                'enabled' => false,
                'left' => 38.0,
                'top' => 83.0,
                'width' => 24.0,
                'font_size' => '2.4mm',
                'line_height' => 1.3,
                'letter_spacing' => '0',
                'color' => '#7c8798',
                'font_weight' => 400,
                'align' => 'center',
                'text_transform' => 'none',
                'font_family' => '"DejaVu Sans", Helvetica, Arial, sans-serif',
            ],
        ],

        'variants' => [
            'attendance_full' => [
                'background' => env('CERTIFICATE_ATTENDANCE_ARTWORK', 'images/brand/certificate-attendance.png'),
            ],
            'attendance_partial' => [
                'background' => env('CERTIFICATE_ATTENDANCE_ARTWORK', 'images/brand/certificate-attendance.png'),
            ],
            'oral' => [
                'background' => env('CERTIFICATE_PRESENTATION_ARTWORK', 'images/brand/certificate-presentation.png'),
                'placeholders' => [
                    'name' => [
                        'top' => 40.0,
                    ],
                    'body' => [
                        'top' => 52.0,
                        'width' => 80.0,
                        'left' => 10.0,
                        'font_size' => '5.5mm',
                        'min_font_size' => '4.5mm',
                        'max_chars' => 300,
                        'line_height' => 1.55,
                        'color' => '#1e293b',
                    ],
                    'qr' => [
                        'top' => 76.0,
                    ],
                ],
            ],
            'poster' => [
                'background' => env('CERTIFICATE_ATTENDANCE_ARTWORK', 'images/brand/certificate-attendance.png'),
            ],
        ],
    ],
];
