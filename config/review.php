<?php

/*
|--------------------------------------------------------------------------
| Abstract scoring rubric
|--------------------------------------------------------------------------
|
| The scientific committee's rubric. Each criterion is scored in whole points
| from 0 to its maximum; the maxima add up to 100, so the total reads as a
| percentage. Keys are the review_assignments columns that hold the points.
|
*/

return [

    'criteria' => [
        'score_originality' => [
            'label' => 'Originality and novelty',
            'short' => 'Originality',
            'max' => 20,
            'question' => 'Does it bring something new to the field?',
            'guidance' => [
                'Presents a new idea, method or application that advances the field',
                'Offers a unique perspective or approach to a problem',
            ],
        ],

        'score_technical' => [
            'label' => 'Technical quality',
            'short' => 'Technical',
            'max' => 40,
            'question' => 'Is the work sound, and is it reported well?',
            'guidance' => [
                'Title is clear, precise and on theme',
                'Methodology is appropriate',
                'Analysis and interpretation of results hold up',
                'Relevant to the conference theme',
                'Concise',
            ],
            // Optional sub-checks that suggest a score. Stored in technical_checks.
            'checks' => [
                'title' => ['label' => 'Title', 'hint' => 'Clear, precise and on theme'],
                'methodology' => ['label' => 'Methodology', 'hint' => 'Appropriate design and sample'],
                'analysis' => ['label' => 'Analysis', 'hint' => 'Results analysed and interpreted soundly'],
                'relevance' => ['label' => 'Theme relevance', 'hint' => 'Fits the conference theme'],
                'concise' => ['label' => 'Conciseness', 'hint' => 'Says it without padding'],
            ],
        ],

        'score_significance' => [
            'label' => 'Significance and impact',
            'short' => 'Significance',
            'max' => 30,
            'question' => 'Does it matter, and could it change practice?',
            'guidance' => [
                'Addresses an important problem or question related to the theme of the conference',
                'Findings could advance knowledge in the field',
            ],
        ],

        'score_clarity' => [
            'label' => 'Clarity and organisation',
            'short' => 'Clarity',
            'max' => 10,
            'question' => 'Is it easy to follow?',
            'guidance' => [
                'Organised as introduction, methods, results and conclusion, within the word limit',
                'Clear and easy to follow',
                'Proper grammar, spelling and language',
            ],
        ],
    ],

    /*
    | Verdict bands on the total (out of 100), highest first. A reviewer's
    | recommendation stays their own; the band is guidance shown beside it.
    */
    'bands' => [
        ['key' => 'accept', 'min' => 70, 'label' => 'Consider for acceptance', 'tone' => 'success'],
        ['key' => 'revise', 'min' => 50, 'label' => 'Accept with revisions', 'tone' => 'warning'],
        ['key' => 'reject', 'min' => 0, 'label' => 'Likely reject', 'tone' => 'danger'],
    ],

];
