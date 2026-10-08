<?php

/*
|--------------------------------------------------------------------------
| Summit awards
|--------------------------------------------------------------------------
|
| Award categories belong to an edition and are edited by the scientific
| committee in the portal. This file holds what does not change from year to
| year: the criteria judges score presentations on, and the suggested set of
| categories the committee can add with one click before adjusting it.
|
*/

return [

    // Judges score each finalist from 1 to `max` on every criterion.
    'max' => 10,

    'criteria' => [
        'science' => [
            'label' => 'Scientific quality',
            'hint' => 'Sound methods, and results that support the conclusions.',
        ],
        'impact' => [
            'label' => 'Relevance and impact',
            'hint' => 'What the work means for rehabilitation practice, services or policy.',
        ],
        'delivery' => [
            'label' => 'Presentation',
            'hint' => 'Clear structure, readable slides or poster, and keeping to time.',
        ],
        'discussion' => [
            'label' => 'Discussion',
            'hint' => 'Understanding shown when answering questions from judges and the audience.',
        ],
    ],

    /*
    | kind: presentation (judges score shortlisted accepted abstracts) or
    | honour (people are nominated, and the committee chooses).
    | presentation_type: oral, poster or null for both. places: 1 to 3.
    | nominations: whether participants may nominate (honours only).
    */
    'suggested' => [
        [
            'name' => 'Best Oral Presentation',
            'kind' => 'presentation',
            'presentation_type' => 'oral',
            'places' => 3,
            'description' => 'For the oral presentations that best combine strong evidence with a clear, engaging delivery.',
        ],
        [
            'name' => 'Best Poster',
            'kind' => 'presentation',
            'presentation_type' => 'poster',
            'places' => 3,
            'description' => 'For the posters that communicate their research most clearly, judged at the poster sessions.',
        ],
        [
            'name' => 'Student Research Award',
            'kind' => 'presentation',
            'students_only' => true,
            'places' => 1,
            'description' => 'For the best presentation by a student registered at the summit.',
        ],
        [
            'name' => 'Innovation in Rehabilitation Award',
            'kind' => 'presentation',
            'places' => 1,
            'description' => 'For the most innovative technology, service model or approach that widens access to rehabilitation.',
        ],
        [
            'name' => 'Rehabilitation Champion Award',
            'kind' => 'honour',
            'nominations' => true,
            'places' => 1,
            'description' => 'For a practitioner, community worker or organisation whose work has brought rehabilitation closer to the people who need it. Nominated by participants.',
        ],
        [
            'name' => 'Distinguished Service Award',
            'kind' => 'honour',
            'places' => 1,
            'description' => 'For outstanding, sustained contribution to rehabilitation in the region, chosen by the organising committee.',
        ],
    ],

];
