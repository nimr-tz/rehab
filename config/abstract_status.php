<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Abstract Submission Status Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines all possible status values for abstract submissions
    | and their transitions. This ensures consistency across the entire system.
    |
    */

    'statuses' => [
        // Initial submission states
        'draft' => [
            'label' => 'Draft',
            'description' => 'Abstract is being prepared by author',
            'color' => 'gray',
            'icon' => '✏️',
            'transitions' => ['submitted', 'withdrawn']
        ],

        'submitted' => [
            'label' => 'Submitted',
            'description' => 'Abstract submitted and awaiting review assignment',
            'color' => 'blue',
            'icon' => '📤',
            'transitions' => ['reviewer_assigned', 'withdrawn', 'accepted', 'rejected']
        ],

        'reviewer_assigned' => [
            'label' => 'Reviewer Assigned',
            'description' => 'One reviewer assigned, awaiting second reviewer',
            'color' => 'yellow',
            'icon' => '👥',
            'transitions' => ['under_review', 'submitted']
        ],

        // Review process states
        'under_review' => [
            'label' => 'Under Review',
            'description' => 'Both reviewers assigned and reviewing',
            'color' => 'orange',
            'icon' => '📋',
            'transitions' => ['accepted', 'ready_for_decision', 'revision_required']
        ],

        'ready_for_decision' => [
            'label' => 'Ready for Decision',
            'description' => 'Reviews completed, awaiting admin decision',
            'color' => 'purple',
            'icon' => '⚖️',
            'transitions' => ['accepted', 'rejected', 'revision_required']
        ],

        // Revision workflow states
        'revision_required' => [
            'label' => 'Accepted with Revisions',
            'description' => 'Author needs to address feedback before final acceptance',
            'color' => 'orange',
            'icon' => '📝',
            'transitions' => ['revision_submitted', 'accepted', 'rejected', 'withdrawn']
        ],

        'revision_submitted' => [
            'label' => 'Revision Submitted',
            'description' => 'Author has resubmitted with revisions',
            'color' => 'teal',
            'icon' => '📨',
            'transitions' => ['revision_under_review', 'accepted', 'rejected', 'revision_required']
        ],

        'revision_under_review' => [
            'label' => 'Revision Under Review',
            'description' => 'Revised version being reviewed',
            'color' => 'indigo',
            'icon' => '🔍',
            'transitions' => ['accepted', 'rejected', 'revision_required', 'ready_for_decision']
        ],


        'revision_review' => [
            'label' => 'Revision Under Review',
            'description' => 'Revised version being reviewed',
            'color' => 'indigo',
            'icon' => '🔍',
            'transitions' => ['accepted', 'rejected', 'revision_required', 'ready_for_decision']
        ],

        'partial_peer_review' => [
            'label' => 'Partial Peer Review',
            'description' => 'Only one reviewer has submitted a review',
            'color' => 'yellow',
            'icon' => '📋',
            'transitions' => ['accepted', 'rejected', 'under_review', 'ready_for_decision']
        ],

        // Final states
        'accepted' => [
            'label' => 'Accepted',
            'description' => 'Abstract accepted for presentation',
            'color' => 'green',
            'icon' => '✅',
            'transitions' => ['rejected', 'withdrawn']
        ],

        'rejected' => [
            'label' => 'Rejected',
            'description' => 'Abstract not accepted',
            'color' => 'red',
            'icon' => '❌',
            'transitions' => ['accepted']
        ],

        'withdrawn' => [
            'label' => 'Withdrawn',
            'description' => 'Abstract withdrawn by author',
            'color' => 'gray',
            'icon' => '🚫',
            'transitions' => [] // Final state
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Groups for Filtering and Display
    |--------------------------------------------------------------------------
    */

    'status_groups' => [
        'pending_review' => ['submitted', 'reviewer_assigned'],
        'in_review' => ['under_review', 'revision_under_review'],
        'needs_decision' => ['ready_for_decision', 'revision_submitted'],
        'needs_revision' => ['revision_required'],
        'completed' => ['accepted', 'rejected', 'withdrawn']
    ],

    /*
    |--------------------------------------------------------------------------
    | Decision Types and Their Resulting Status
    |--------------------------------------------------------------------------
    */

    'decision_mappings' => [
        'accept' => 'accepted',
        'reject' => 'rejected',
        'accept_with_revisions' => 'revision_required'
    ],

    /*
    |--------------------------------------------------------------------------
    | Revision Types Configuration
    |--------------------------------------------------------------------------
    */

    'revision_types' => [
        'standard' => [
            'label' => 'Standard Revision',
            'description' => 'Address reviewer and editor feedback',
            'status' => 'revision_required',
            'default_deadline_days' => 14,
            'color' => 'orange'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Levels
    |--------------------------------------------------------------------------
    */

    'priority_levels' => [
        'low' => [
            'label' => 'Low Priority',
            'color' => 'gray',
            'icon' => '⬇️'
        ],
        'normal' => [
            'label' => 'Normal Priority',
            'color' => 'blue',
            'icon' => '➡️'
        ],
        'high' => [
            'label' => 'High Priority',
            'color' => 'red',
            'icon' => '⬆️'
        ],
        'urgent' => [
            'label' => 'Urgent',
            'color' => 'red',
            'icon' => '🚨'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'author' => [
            'status_changes' => true,
            'revision_requests' => true,
            'deadline_reminders' => true
        ],
        'reviewers' => [
            'assignments' => true,
            'revisions' => true,
            'decisions' => false
        ],
        'admin' => [
            'conflicts' => true,
            'revisions' => true,
            'deadlines' => true
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Deadline Configuration
    |--------------------------------------------------------------------------
    */

    'deadlines' => [
        'review' => 14, // days
        'revision' => 14, // days
        'author_response' => 5 // days for author to respond to revision request
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Validation Rules
    |--------------------------------------------------------------------------
    */

    'validation' => [
        // Statuses that require admin approval to change
        'admin_only' => [
            'accepted',
            'rejected'
        ],

        // Statuses that authors can change
        'author_changeable' => [
            'draft',
            'submitted',
            'withdrawn'
        ],

        // Statuses that prevent further changes
        'locked' => [
            'withdrawn'
        ],

        // Statuses that count as "active" submissions
        'active' => [
            'submitted',
            'reviewer_assigned',
            'under_review',
            'ready_for_decision',
            'revision_required',
            'revision_submitted',
            'revision_under_review'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Status Mappings (for migration)
    |--------------------------------------------------------------------------
    */

    'legacy_mappings' => [
        'revision' => 'revision_required',
        'needs_revision' => 'revision_required',
        'minor_revision_required' => 'revision_required',
        'major_revision_required' => 'revision_required',
        'pending' => 'submitted',
        'in_progress' => 'under_review',
        'revision_review' => 'revision_under_review',
    ]
];
