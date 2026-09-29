<?php

/*
|--------------------------------------------------------------------------
| Conference edition
|--------------------------------------------------------------------------
|
| Identity, dates and scientific topics for the current edition of the
| Rehabilitation Summit. Everything is overridable from .env so a new
| edition can be configured without code changes.
|
*/

return [

    'name' => env('CONFERENCE_NAME', 'Rehabilitation Summit'),
    'short_name' => env('CONFERENCE_SHORT_NAME', 'Rehab Summit'),
    'edition' => env('CONFERENCE_EDITION', '4th'),
    'year' => env('CONFERENCE_YEAR', '2026'),

    'theme' => env('CONFERENCE_THEME', 'Rehabilitation Across the Life Course in Universal Health Coverage (UHC)'),

    // Format: YYYY-MM-DD
    'start_date' => env('CONFERENCE_START_DATE', '2026-09-16'),
    'end_date' => env('CONFERENCE_END_DATE', '2026-09-18'),
    'timezone' => env('CONFERENCE_TIMEZONE', env('APP_TIMEZONE', 'Africa/Dar_es_Salaam')),
    'total_days' => (int) env('CONFERENCE_TOTAL_DAYS', 3),

    // Human-readable dates for badges and printed materials.
    'display_dates' => env('CONFERENCE_DISPLAY_DATES', 'September 16-18, 2026'),

    'submission_deadline' => env('SUBMISSION_DEADLINE', '2026-08-24'),

    // Closing time for session chair / rapporteur applications.
    'session_role_deadline' => env('SESSION_ROLE_DEADLINE', env('SUBMISSION_DEADLINE', '2026-08-24').' 23:59:59'),

    // When certificate downloads unlock (conference timezone). Defaults to the
    // day after the conference ends.
    'certificates_release_at' => env(
        'CERTIFICATES_RELEASE_AT',
        date('Y-m-d', strtotime(env('CONFERENCE_END_DATE', '2026-09-18').' +1 day')).' 00:00:00'
    ),

    'venue' => env('CONFERENCE_VENUE', 'Julius Nyerere International Convention Centre (JNICC)'),
    'city' => env('CONFERENCE_CITY', 'Dar es Salaam'),
    'country' => env('CONFERENCE_COUNTRY', 'Tanzania'),

    // Organiser and partners
    'host' => env('CONFERENCE_HOST', 'Rehab Health'),
    'host_short' => env('CONFERENCE_HOST_SHORT', 'Rehab Health'),
    'co_organiser' => env('CONFERENCE_CO_ORGANISER', 'Ministry of Health'),
    'website' => env('CONFERENCE_WEBSITE', 'https://rehabhealth.or.tz'),
    'contact_email' => env('CONFERENCE_CONTACT_EMAIL', 'info@rehabhealth.or.tz'),
    'abstracts_email' => env('CONFERENCE_ABSTRACTS_EMAIL', 'abstract@rehabhealth.or.tz'),

    // Brand assets (paths under public/). Replace the placeholders with the
    // approved logo files.
    'logo_path' => env('CONFERENCE_LOGO_PATH', 'images/brand/logo.png'),
    'logo_mark_path' => env('CONFERENCE_LOGO_MARK_PATH', 'images/brand/logo-mark.png'),

    // Optional slide template offered to presenters (path under public/).
    'slide_template_path' => env('SLIDE_TEMPLATE_PATH'),

    // Prefix for badge QR tokens.
    'qr_prefix' => env('CONFERENCE_QR_PREFIX', 'RH26'),

    // Short code used for fallback abstract codes (RHS-001) and anonymous
    // reviewer IDs.
    'code' => env('CONFERENCE_CODE', 'RHS'),

    // Prefix for generated download file names (no spaces).
    'file_prefix' => env('CONFERENCE_FILE_PREFIX', 'Rehab-Summit-'.env('CONFERENCE_YEAR', '2026')),

    // Venue halls in the order they appear as programme columns (comma
    // separated), and the hall used for plenary/panel sessions with no room.
    'halls' => array_values(array_filter(array_map('trim', explode(',', (string) env('CONFERENCE_HALLS', ''))))),
    'plenary_hall' => env('CONFERENCE_PLENARY_HALL'),

    // Official invitation / visa support letter. Put the signature image under
    // public/ and leave it unset until an approved one is available.
    'invitation_letter' => [
        'reference_prefix' => env('INVITATION_REFERENCE_PREFIX', 'RHS/INV'),
        'signatory_name' => env('INVITATION_SIGNATORY_NAME'),
        'signatory_title' => env('INVITATION_SIGNATORY_TITLE', 'Chairperson, Organising Committee'),
        'signature_path' => env('INVITATION_SIGNATURE_PATH'),
        'contact_name' => env('INVITATION_CONTACT_NAME'),
        'contact_email' => env('INVITATION_CONTACT_EMAIL', env('CONFERENCE_CONTACT_EMAIL', 'info@rehabhealth.or.tz')),
        'contact_phone' => env('INVITATION_CONTACT_PHONE'),
        'address' => env('INVITATION_ADDRESS'),
    ],

    // Optional single coordinator per programme day for poster sessions,
    // keyed by day number. Leave empty when there are no poster sessions.
    'poster_coordinators' => array_filter([
        1 => env('POSTER_COORDINATOR_DAY1'),
        2 => env('POSTER_COORDINATOR_DAY2'),
        3 => env('POSTER_COORDINATOR_DAY3'),
    ]),

    /*
    |--------------------------------------------------------------------------
    | Scientific topics
    |--------------------------------------------------------------------------
    |
    | Topic name => code prefix used for conference codes (e.g. OR-HBR-01).
    | Abstracts, reviewer interests and sessions refer to topics by name.
    |
    */

    'subtheme_prefixes' => [
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

    /*
    | Optional scope text shown to authors under each topic
    | (topic name => description).
    */
    'subtheme_descriptions' => [],

    /*
    | Related topics used when no reviewer matches a topic exactly.
    */
    'related_subthemes' => [
        'Home-Based Rehabilitation' => [
            'Rehabilitation Across the Life Course',
            'Rehabilitation and Non-Communicable Diseases (NCDs)',
            'Technology and AI in Rehabilitation',
        ],
        'Technology and AI in Rehabilitation' => [
            'Research and Innovation',
            'Home-Based Rehabilitation',
        ],
        'Financing Rehabilitation Services' => [
            'Rehabilitation Leadership and Advocacy',
            'Occupational Health and Workplace Rehabilitation',
        ],
        'Occupational Health and Workplace Rehabilitation' => [
            'Rehabilitation and Non-Communicable Diseases (NCDs)',
            'Financing Rehabilitation Services',
        ],
        'Rehabilitation Leadership and Advocacy' => [
            'Financing Rehabilitation Services',
            'Women, Disability and Inclusive Development',
        ],
        'Rehabilitation Across the Life Course' => [
            'Home-Based Rehabilitation',
            'Women, Disability and Inclusive Development',
            'Rehabilitation and Non-Communicable Diseases (NCDs)',
        ],
        'Rehabilitation and Non-Communicable Diseases (NCDs)' => [
            'Rehabilitation Across the Life Course',
            'Occupational Health and Workplace Rehabilitation',
            'Home-Based Rehabilitation',
        ],
        'Women, Disability and Inclusive Development' => [
            'Rehabilitation Leadership and Advocacy',
            'Rehabilitation Across the Life Course',
        ],
        'Research and Innovation' => [
            'Technology and AI in Rehabilitation',
            'Rehabilitation Across the Life Course',
        ],
    ],
];
