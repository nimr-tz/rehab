<?php

/*
| The feedback survey after the summit. Everyone recorded at the summit can
| answer it once, from its last day until `open_days` after it ends. Answers
| are anonymous: the organisers see them without names.
*/

return [

    'open_days' => 30,

    // Rated 1 (poor) to 5 (excellent).
    'ratings' => [
        'overall' => 'The summit overall',
        'programme' => 'The scientific programme',
        'speakers' => 'The speakers and presenters',
        'organisation' => 'Registration and organisation',
        'venue' => 'The venue, meals and breaks',
        'portal' => 'This portal',
    ],

    'next_year' => [
        'yes' => 'Yes',
        'maybe' => 'Maybe',
        'no' => 'No',
    ],

    'comments' => [
        'best' => 'What was the best part of the summit?',
        'improve' => 'What should we do better next time?',
        'topics' => 'Which topics would you like at the next summit?',
    ],

];
