<?php

/*
|--------------------------------------------------------------------------
| Programme and abstract book front matter
|--------------------------------------------------------------------------
|
| Edition-specific content printed at the front of the abstract book.
| Sections are left out of the book while they are empty.
|
*/

return [

    // Paragraphs of the foreword, and who signs it.
    'foreword' => [
        'paragraphs' => [],
        'signatory_name' => env('ABSTRACT_BOOK_FOREWORD_NAME'),
        'signatory_role' => env('ABSTRACT_BOOK_FOREWORD_ROLE'),
    ],

    // Committee name => list of members. A member is
    // ['name' => 'Dr. A. Example', 'role' => 'Chairperson' | 'Secretary' | 'Member'].
    'committees' => [],

];
