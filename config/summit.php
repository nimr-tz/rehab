<?php

/*
|--------------------------------------------------------------------------
| Current summit edition
|--------------------------------------------------------------------------
|
| Temporary home for the edition details shown on the public pages. Phase 0
| moves these into the `editions` table so admins can edit them. Until then,
| anything left null is shown as "To be announced".
|
*/

return [

    'year' => (int) env('SUMMIT_YEAR', 2027),
    'name' => env('SUMMIT_NAME', 'Rehabilitation Summit'),
    'short_name' => env('SUMMIT_SHORT_NAME', 'Rehab Summit'),
    'edition' => env('SUMMIT_EDITION', '5th'),
    'theme' => env('SUMMIT_THEME'),

    // Y-m-d. Null until announced.
    'start_date' => env('SUMMIT_START_DATE'),
    'end_date' => env('SUMMIT_END_DATE'),
    'days' => (int) env('SUMMIT_DAYS', 3),

    'venue' => env('SUMMIT_VENUE'),
    'city' => env('SUMMIT_CITY'),
    'country' => env('SUMMIT_COUNTRY', 'Tanzania'),

    'organiser' => env('SUMMIT_ORGANISER', 'Rehab Health'),
    'co_organiser' => env('SUMMIT_CO_ORGANISER', 'Ministry of Health'),
    'website' => env('SUMMIT_WEBSITE', 'https://rehabhealth.or.tz'),
    'contact_email' => env('SUMMIT_CONTACT_EMAIL', 'info@rehabhealth.or.tz'),
    'abstracts_email' => env('SUMMIT_ABSTRACTS_EMAIL', 'abstract@rehabhealth.or.tz'),

    'abstracts_open' => (bool) env('SUMMIT_ABSTRACTS_OPEN', true),

    // Key dates, in order. Null dates show as "To be announced".
    'key_dates' => [
        ['label' => 'Abstract submission deadline', 'date' => env('SUMMIT_ABSTRACT_DEADLINE')],
        ['label' => 'Session chair and rapporteur applications close', 'date' => env('SUMMIT_SESSION_ROLE_DEADLINE')],
        ['label' => 'Presentation upload deadline', 'date' => env('SUMMIT_PRESENTATION_DEADLINE')],
    ],

    // Amounts are null until the organisers confirm them.
    'fees' => [
        ['label' => 'Professional (Tanzania)', 'currency' => 'TZS', 'amount' => env('FEE_PROFESSIONAL_LOCAL')],
        ['label' => 'Professional (International)', 'currency' => 'USD', 'amount' => env('FEE_PROFESSIONAL_INTERNATIONAL')],
        ['label' => 'Student (Tanzania)', 'currency' => 'TZS', 'amount' => env('FEE_STUDENT_LOCAL')],
        ['label' => 'Student (International)', 'currency' => 'USD', 'amount' => env('FEE_STUDENT_INTERNATIONAL')],
    ],

    'payment_methods' => ['Bank transfer', 'M-Pesa', 'Airtel Money', 'Mixx by Yas', 'HaloPesa'],

    // Topic name => code. The 2026 list, kept until the 2027 topics are set.
    'topics' => [
        'Home-Based Rehabilitation' => 'HBR',
        'Technology and AI in Rehabilitation' => 'TAI',
        'Financing Rehabilitation Services' => 'FIN',
        'Occupational Health and Workplace Rehabilitation' => 'OCC',
        'Rehabilitation Leadership and Advocacy' => 'LEA',
        'Rehabilitation Across the Life Course' => 'LIF',
        'Rehabilitation and Non-Communicable Diseases (NCDs)' => 'NCD',
        'Women, Disability and Inclusive Development' => 'WDI',
        'Research and Innovation' => 'RIN',
    ],

];
