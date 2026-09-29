<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Review Decision Thresholds
    |--------------------------------------------------------------------------
    |
    | These values determine the automatic decision logic for abstract reviews.
    | Admins can adjust these thresholds through the admin panel.
    |
    */

    'score_thresholds' => [
        'excellent' => 90,          // Excellent quality threshold
        'good' => 80,              // Good quality threshold
        'satisfactory' => 70,      // Satisfactory quality threshold
        'accept' => 75,            // Automatic acceptance threshold
        'revision' => 50,          // Requires revision threshold
        'reject' => 49,            // Automatic rejection threshold (below this)
    ],

    /*
    |--------------------------------------------------------------------------
    | Disagreement Detection
    |--------------------------------------------------------------------------
    |
    | Thresholds for detecting disagreements between reviewers that may
    | require manual admin intervention.
    |
    */

    'disagreement_thresholds' => [
        'major' => 20,             // Major disagreement
        'minor' => 15,             // Minor disagreement (flag for attention)
        'significant' => 25,       // Highly significant disagreement
    ],

    /*
    |--------------------------------------------------------------------------
    | Decision Confidence Levels
    |--------------------------------------------------------------------------
    |
    | Thresholds for determining confidence in automatic decisions.
    |
    */

    'confidence_levels' => [
        'high_threshold' => 85,    // High confidence decisions
        'low_threshold' => 60,     // Low confidence decisions
        'score_agreement_threshold' => 10,  // Score difference for high confidence
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Colors and Styling
    |--------------------------------------------------------------------------
    |
    | Consistent color mapping for different statuses and quality levels.
    |
    */

    'status_colors' => [
        'submitted' => [
            'bg' => 'bg-blue-100 dark:bg-blue-900/30',
            'text' => 'text-blue-800 dark:text-blue-300',
            'border' => 'border-blue-300 dark:border-blue-600'
        ],
        'under_review' => [
            'bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
            'text' => 'text-yellow-800 dark:text-yellow-300',
            'border' => 'border-yellow-300 dark:border-yellow-600'
        ],
        'ready_for_decision' => [
            'bg' => 'bg-purple-100 dark:bg-purple-900/30',
            'text' => 'text-purple-800 dark:text-purple-300',
            'border' => 'border-purple-300 dark:border-purple-600'
        ],
        'accepted' => [
            'bg' => 'bg-green-100 dark:bg-green-900/30',
            'text' => 'text-green-800 dark:text-green-300',
            'border' => 'border-green-300 dark:border-green-600'
        ],
        'rejected' => [
            'bg' => 'bg-red-100 dark:bg-red-900/30',
            'text' => 'text-red-800 dark:text-red-300',
            'border' => 'border-red-300 dark:border-red-600'
        ],
        'minor_revision' => [
            'bg' => 'bg-amber-100 dark:bg-amber-900/30',
            'text' => 'text-amber-800 dark:text-amber-300',
            'border' => 'border-amber-300 dark:border-amber-600'
        ],
        'major_revision' => [
            'bg' => 'bg-orange-100 dark:bg-orange-900/30',
            'text' => 'text-orange-800 dark:text-orange-300',
            'border' => 'border-orange-300 dark:border-orange-600'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Score Quality Colors
    |--------------------------------------------------------------------------
    |
    | Color mapping for different score ranges to ensure consistency.
    |
    */

    'score_colors' => [
        'excellent' => [  // 90-100%
            'bg' => 'bg-green-100 dark:bg-green-900/30',
            'text' => 'text-green-800 dark:text-green-300'
        ],
        'good' => [      // 80-89%
            'bg' => 'bg-blue-100 dark:bg-blue-900/30',
            'text' => 'text-blue-800 dark:text-blue-300'
        ],
        'satisfactory' => [ // 70-79%
            'bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
            'text' => 'text-yellow-800 dark:text-yellow-300'
        ],
        'needs_improvement' => [ // 60-69%
            'bg' => 'bg-orange-100 dark:bg-orange-900/30',
            'text' => 'text-orange-800 dark:text-orange-300'
        ],
        'poor' => [      // Below 60%
            'bg' => 'bg-red-100 dark:bg-red-900/30',
            'text' => 'text-red-800 dark:text-red-300'
        ],
        'not_available' => [
            'bg' => 'bg-gray-100 dark:bg-gray-700',
            'text' => 'text-gray-600 dark:text-gray-400'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeline Warnings
    |--------------------------------------------------------------------------
    |
    | Settings for timeline-based warnings and notifications.
    |
    */

    'timeline_settings' => [
        'overdue_days' => 14,      // Days after which review is considered overdue
        'warning_days' => 10,      // Days after which to show warning
        'reminder_days' => 7,      // Days after which reminder can be sent
    ],

    /*
    |--------------------------------------------------------------------------
    | Decision Logic Configuration
    |--------------------------------------------------------------------------
    |
    | Settings that control the automatic decision-making process.
    |
    */

    'decision_logic' => [
        'require_both_reviewers' => true,
        'allow_automatic_decisions' => true,
        'min_score_for_acceptance' => 75,
        'max_score_for_rejection' => 49,
        'enable_minor_major_revision_distinction' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Indicators
    |--------------------------------------------------------------------------
    |
    | Configuration for quality indicators and flags.
    |
    */

    'quality_indicators' => [
        'variance_warning_threshold' => 20,
        'low_score_warning_threshold' => 50,
        'high_score_excellence_threshold' => 90,
        'enable_trend_analysis' => true,
    ],
];
