<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ReviewConflict;

class AbstractSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_name',
        'author_institute',
        'title',
        'description',
        'subtheme',
        'presentation_mode',
        'include_in_proceedings',
        'coauthors',
        'user_id',
        'status',
        'submitted_at',
        'reviewer_id',
        'reviewer_2_id',
        'reviewer_1_score',
        'reviewer_2_score',
        'average_score',
        'admin_comment',
        'revision_feedback',
        'revision_file',
        'revision_round',
        'revision_requested_at',
        'revision_submitted_at',
        'revision_deadline',
        'original_submission_id',
        'automated_decision',
        'automated_decision_at',
        'automated_decision_reason',
        'status_changed_at',
        'status_changed_by',
        'assigned_at',
        'review_completed_at',
        'conference_code',
        'committee_notes',
        'committee_selected',
        'code_assigned_by',
        'code_assigned_at',
        'code_is_final',
        'proceedings_corrected_at',
        'proceedings_corrected_by',
        'is_invited',
        'invited_created_by',
        'invited_created_at',
        'presentation_date',
        'presentation_time',
        'session_id', // Link to ConferenceSession
        'session_order', // Order within the session
        'session_topic',
        'session_topic_source',
        'session_topic_detected_at',
        'session_topic_locked_at',
        'session_topic_locked_by',
        'oral_presentation_file',
        'poster_presentation_file',
        'poster_preview_image', // Legacy field; poster previews are no longer collected.
        'presentation_files',
        'presentation_uploaded_at',
        'presentation_notes',
        'presentation_status',
        'audio_poster_file',
        'audio_poster_poster_file',
        'audio_poster_description',
        'audio_poster_duration',
        'audio_poster_metadata',
        // Quality management fields
        'quality_flag',
        'quality_flag_reason',
        'quality_flag_priority',
        'quality_flagged_at',
        'quality_flagged_by',
        'quality_resolution_notes',
        'quality_resolution_action',
        'quality_resolved_at',
        'quality_resolved_by',
        'keywords',
        'is_auto_managed',
    ];

    protected $casts = [
        'coauthors' => 'array',
        'include_in_proceedings' => 'boolean',
        'submitted_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'review_completed_at' => 'datetime',
        'revision_requested_at' => 'datetime',
        'revision_submitted_at' => 'datetime',
        'revision_approved_at' => 'datetime',
        'revision_deadline' => 'datetime',
        'is_blind_review' => 'boolean',
        'conflict_checked' => 'boolean',
        'committee_selected' => 'boolean',
        'is_invited' => 'boolean',
        'code_assigned_at' => 'datetime',
        'proceedings_corrected_at' => 'datetime',
        'code_is_final'    => 'boolean',
        'invited_created_at' => 'datetime',
        'presentation_date' => 'date',
        'presentation_time' => 'datetime:H:i',
        'session_topic_detected_at' => 'datetime',
        'session_topic_locked_at' => 'datetime',
        'presentation_files' => 'array',
        'presentation_uploaded_at' => 'datetime',
        'audio_poster_metadata' => 'array',
        // Quality management casts
        'quality_flag' => 'boolean',
        'quality_flagged_at' => 'datetime',
        'quality_resolved_at' => 'datetime',
    ];

    public static function canonicalSubthemeNames(): array
    {
        return array_keys(config('conference.subtheme_prefixes', []));
    }

    /**
     * The configured topic name matching the raw value (case-insensitive),
     * else the reviewers' suggested topic, else the raw value.
     */
    public static function normalizeSubthemeLabel(?string $rawSubtheme, ?string $reviewerSuggestion = null): ?string
    {
        $canonicalNames = static::canonicalSubthemeNames();
        if (empty($canonicalNames)) {
            return $rawSubtheme;
        }

        $matchCanonical = static function (?string $value) use ($canonicalNames) {
            $needle = strtolower(trim((string) $value));
            if ($needle === '') {
                return null;
            }

            foreach ($canonicalNames as $canonicalName) {
                if (strtolower($canonicalName) === $needle) {
                    return $canonicalName;
                }
            }

            return null;
        };

        if ($matched = $matchCanonical($rawSubtheme)) {
            return $matched;
        }

        if ($matched = $matchCanonical($reviewerSuggestion)) {
            return $matched;
        }

        return $rawSubtheme;
    }

    public function getNormalizedSubthemeAttribute(): ?string
    {
        $reviewerSuggestion = null;

        if ($this->relationLoaded('reviews')) {
            $reviewerSuggestion = $this->getSubthemeRecommendationSummary()['primary_suggestion'] ?? null;
        }

        return static::normalizeSubthemeLabel($this->subtheme, $reviewerSuggestion);
    }

    public function reviewer1()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewer2()
    {
        return $this->belongsTo(User::class, 'reviewer_2_id');
    }

    public function codeAssignedBy()
    {
        return $this->belongsTo(User::class, 'code_assigned_by');
    }

    public function qualityFlaggedBy()
    {
        return $this->belongsTo(User::class, 'quality_flagged_by');
    }

    public function qualityResolvedBy()
    {
        return $this->belongsTo(User::class, 'quality_resolved_by');
    }

    public function invitedCreatedBy()
    {
        return $this->belongsTo(User::class, 'invited_created_by');
    }

    public function reviews()
    {
        return $this->hasMany(AbstractReview::class);
    }

    public function getAuthorVisibleFeedbackItems()
    {
        $reviewFeedback = $this->reviews()
            ->where('status', 'submitted')
            ->whereNotNull('comments')
            ->whereRaw("TRIM(comments) <> ''")
            ->orderBy('review_round')
            ->orderBy('reviewer_number')
            ->get()
            ->map(function (AbstractReview $review) {
                return [
                    'type' => 'reviewer',
                    'review_round' => (int) ($review->review_round ?? 0),
                    'source_label' => 'Reviewer ' . ($review->reviewer_number ?? '?'),
                    'recommendation_label' => $review->recommendation_label,
                    'comment' => trim((string) $review->comments),
                ];
            });

        $items = $reviewFeedback->values();

        if (!empty(trim((string) $this->admin_comment))) {
            $items->push([
                'type' => 'admin',
                'review_round' => (int) ($this->revision_round ?? 0),
                'source_label' => 'Editorial Note',
                'recommendation_label' => null,
                'comment' => trim((string) $this->admin_comment),
            ]);
        }

        return $items->values();
    }

    public function hasAuthorVisibleFeedback(): bool
    {
        if (!empty(trim((string) $this->admin_comment))) {
            return true;
        }

        return $this->reviews()
            ->where('status', 'submitted')
            ->whereNotNull('comments')
            ->whereRaw("TRIM(comments) <> ''")
            ->exists();
    }

    public function getSubthemeRecommendationSummary(): array
    {
        $reviews = $this->relationLoaded('reviews')
            ? $this->reviews
            : $this->reviews()
                ->where('status', 'submitted')
                ->where('subtheme_relevance', 'suggest_change')
                ->get();

        $relevantReviews = $reviews
            ->where('status', 'submitted')
            ->where('subtheme_relevance', 'suggest_change')
            ->filter(function (AbstractReview $review) {
                return !empty(trim((string) $review->suggested_subtheme));
            })
            ->values();

        if ($relevantReviews->isEmpty()) {
            return [
                'has_recommendation' => false,
                'count' => 0,
                'suggestions' => [],
                'primary_suggestion' => null,
                'latest_reviewed_at' => null,
            ];
        }

        $counts = $relevantReviews
            ->map(fn (AbstractReview $review) => trim((string) $review->suggested_subtheme))
            ->countBy()
            ->sortDesc();

        return [
            'has_recommendation' => true,
            'count' => $relevantReviews->count(),
            'suggestions' => $counts->toArray(),
            'primary_suggestion' => $counts->keys()->first(),
            'latest_reviewed_at' => $relevantReviews
                ->sortByDesc(fn (AbstractReview $review) => $review->submitted_at ?? $review->updated_at)
                ->first()?->submitted_at ?? $relevantReviews->sortByDesc('updated_at')->first()?->updated_at,
        ];
    }

    public function reviewerExclusions()
    {
        return $this->hasMany(AbstractReviewerExclusion::class);
    }

    public function review1()
    {
        return $this->hasOne(AbstractReview::class)->where('reviewer_number', 1);
    }

    public function review2()
    {
        return $this->hasOne(AbstractReview::class)->where('reviewer_number', 2);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Who last corrected this entry for the conference proceedings — the author
     * themselves, or an admin acting on their behalf.
     */
    public function proceedingsCorrectedBy()
    {
        return $this->belongsTo(User::class, 'proceedings_corrected_by');
    }

    public function session()
    {
        return $this->belongsTo(ConferenceSession::class, 'session_id');
    }

    /**
     * Synchronize review scores and status based on the latest unique reviews.
     * The active workflow is now intentionally simple:
     * unanimous `accept` => accepted, anything else => ready_for_decision.
     */
    public function getAssignedReviewerIdsAttribute()
    {
        return array_values(array_filter([$this->reviewer_id, $this->reviewer_2_id]));
    }

    public function getLatestSubmittedReviewsForAssignedReviewers()
    {
        $assignedReviewerIds = collect($this->assigned_reviewer_ids);

        if ($assignedReviewerIds->isEmpty()) {
            return collect();
        }

        return $assignedReviewerIds->map(function ($reviewerId) {
            return \App\Models\AbstractReview::where('abstract_submission_id', $this->id)
                ->where('reviewer_id', $reviewerId)
                ->where('status', 'submitted')
                ->orderByDesc('review_round')
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->first();
        })->filter()->values();
    }

    public function isSubmittedReviewInEffectiveCurrentRound(\App\Models\AbstractReview $review): bool
    {
        $currentRound = (int) ($this->revision_round ?? 0);
        $reviewRound = (int) ($review->review_round ?? 0);

        if ($reviewRound === $currentRound) {
            return true;
        }

        // Some older/live records appear to store the initial review as round 1
        // while the abstract stays on round 0. Treat those as equivalent.
        return $currentRound === 0 && $reviewRound === 1;
    }

    public function getAssignedReviewProgressSummary(): array
    {
        $assignedReviewerIds = collect($this->assigned_reviewer_ids);
        $latestReviews = $this->getLatestSubmittedReviewsForAssignedReviewers();
        $effectiveCurrentReviews = $latestReviews->filter(function (\App\Models\AbstractReview $review) {
            return $this->isSubmittedReviewInEffectiveCurrentRound($review);
        })->values();

        $assignedCount = $assignedReviewerIds->count();
        $completedCount = $effectiveCurrentReviews->count();
        $requiredCount = 2;
        $avgScore = $completedCount > 0 ? round((float) $effectiveCurrentReviews->avg('score'), 1) : 0.0;

        return [
            'required_count' => $requiredCount,
            'assigned_count' => $assignedCount,
            'completed_count' => $completedCount,
            'avg_score' => $avgScore,
            'latest_reviews' => $latestReviews,
            'effective_current_reviews' => $effectiveCurrentReviews,
            'is_ready' => $completedCount >= $requiredCount,
            'is_partial' => $completedCount > 0 && $completedCount < $requiredCount,
        ];
    }

    public function getBestAvailableAdminReviewSummary(): array
    {
        $assignedProgress = $this->getAssignedReviewProgressSummary();

        if (($assignedProgress['completed_count'] ?? 0) > 0) {
            return array_merge($assignedProgress, [
                'source' => 'current_assignment',
                'source_label' => 'Current Assigned Reviews',
                'is_fallback' => false,
            ]);
        }

        $latestSubmittedRound = $this->reviews()
            ->where('status', 'submitted')
            ->max('review_round');

        if ($latestSubmittedRound === null) {
            return array_merge($assignedProgress, [
                'source' => 'none',
                'source_label' => 'No Submitted Reviews',
                'is_fallback' => false,
            ]);
        }

        $latestRoundReviews = $this->reviews()
            ->where('status', 'submitted')
            ->where('review_round', $latestSubmittedRound)
            ->orderBy('reviewer_number')
            ->get();

        return [
            'required_count' => 2,
            'assigned_count' => (int) ($assignedProgress['assigned_count'] ?? 0),
            'completed_count' => $latestRoundReviews->count(),
            'avg_score' => $latestRoundReviews->count() > 0 ? round((float) $latestRoundReviews->avg('score'), 1) : 0.0,
            'latest_reviews' => $latestRoundReviews,
            'effective_current_reviews' => $latestRoundReviews,
            'is_ready' => $latestRoundReviews->count() >= 2,
            'is_partial' => $latestRoundReviews->count() > 0 && $latestRoundReviews->count() < 2,
            'source' => 'latest_submitted_round',
            'source_label' => ((int) $latestSubmittedRound > 0 ? 'Previous Review Round' : 'Latest Submitted Reviews'),
            'is_fallback' => true,
        ];
    }

    public function getAdminWorkflowStatusBadge(): array
    {
        $status = (string) $this->status;
        $round = (int) ($this->revision_round ?? 0);
        $progress = $this->getAssignedReviewProgressSummary();
        $assignedCount = (int) ($progress['assigned_count'] ?? 0);
        $completedCount = (int) ($progress['completed_count'] ?? 0);

        if ($status === 'accepted') {
            return ['label' => 'Accepted for Conference', 'color' => 'bg-emerald-100 text-emerald-700'];
        }

        if ($status === 'rejected') {
            return ['label' => 'Rejected', 'color' => 'bg-rose-100 text-rose-700'];
        }

        if (in_array($status, ['revision_required', 'revision_requested', 'minor_revision_required', 'major_revision_required'], true)) {
            return ['label' => 'With Author (Revisions)', 'color' => 'bg-amber-100 text-amber-700'];
        }

        if (in_array($status, ['revision_submitted', 'minor_revision_submitted', 'major_revision_submitted', 'revision_under_review', 'revision_review'], true)) {
            if ($completedCount >= 2) {
                return ['label' => 'Decision Required', 'color' => 'bg-violet-100 text-violet-700'];
            }

            if ($completedCount === 1) {
                return ['label' => 'Revision Review (Partial)', 'color' => 'bg-indigo-100 text-indigo-700'];
            }

            return ['label' => 'Revision Under Review', 'color' => 'bg-indigo-100 text-indigo-700'];
        }

        if ($status === 'ready_for_decision') {
            return ['label' => 'Decision Required', 'color' => 'bg-violet-100 text-violet-700 animate-pulse'];
        }

        if ($completedCount >= 2) {
            return ['label' => 'Decision Required', 'color' => 'bg-violet-100 text-violet-700'];
        }

        if ($status === 'submitted' && $assignedCount === 0) {
            return ['label' => 'Awaiting Reviewer Assignment', 'color' => 'bg-sky-100 text-sky-700'];
        }

        if (($status === 'submitted' || $status === 'reviewer_assigned' || $status === 'under_review') && $assignedCount < 2) {
            return ['label' => 'Reviewer Assignment Incomplete', 'color' => 'bg-orange-100 text-orange-700'];
        }

        if ($completedCount === 1) {
            return ['label' => $round > 0 ? 'Revision Review (Partial)' : 'Partial Peer Review', 'color' => 'bg-blue-100 text-blue-700'];
        }

        if ($status === 'under_review') {
            return ['label' => $round > 0 ? 'Revision Under Review' : 'Initial Peer Review', 'color' => 'bg-indigo-100 text-indigo-700'];
        }

        if ($status === 'reviewer_assigned') {
            return ['label' => 'Reviewer Assignment Incomplete', 'color' => 'bg-orange-100 text-orange-700'];
        }

        if ($status === 'submitted') {
            return ['label' => 'New Submission', 'color' => 'bg-sky-100 text-sky-700'];
        }

        return [
            'label' => str_replace('_', ' ', $status),
            'color' => 'bg-slate-100 text-slate-700',
        ];
    }

    public function syncReviewStatus()
    {
        $progress = $this->getAssignedReviewProgressSummary();
        $latestReviews = collect($progress['latest_reviews'] ?? []);
        $effectiveCurrentReviews = collect($progress['effective_current_reviews'] ?? []);

        foreach ($latestReviews as $latest) {
            if ((int) $latest->reviewer_id === (int) $this->reviewer_id) {
                $this->reviewer_1_score = $latest->score;
            } elseif ((int) $latest->reviewer_id === (int) $this->reviewer_2_id) {
                $this->reviewer_2_score = $latest->score;
            }
        }

        $currentScores = array_filter([$this->reviewer_1_score, $this->reviewer_2_score], fn ($s) => !is_null($s));
        $this->average_score = !empty($currentScores)
            ? array_sum($currentScores) / count($currentScores)
            : null;
        $this->save();

        $statusesThatAllowUpdate = [
            'under_review', 'revision_submitted', 'revision_under_review',
            'submitted', 'ready_for_decision'
        ];

        if (!$progress['is_ready'] || !in_array($this->status, $statusesThatAllowUpdate, true)) {
            return;
        }

        $acceptRecommendations = ['accept', 'accept_oral', 'accept_poster'];
        $allAccepted = $effectiveCurrentReviews->isNotEmpty()
            && $effectiveCurrentReviews->every(function (\App\Models\AbstractReview $review) use ($acceptRecommendations) {
                return in_array(strtolower((string) $review->recommendation), $acceptRecommendations, true);
            });

        $statusService = app(\App\Services\AbstractStatusService::class);

        if ($allAccepted) {
            $statusService->changeStatus($this, 'accepted', 'Auto-accepted based on unanimous positive reviews', [
                'review_completed_at' => now()
            ]);
            return;
        }

        $statusService->changeStatus($this, 'ready_for_decision', 'Ready for admin decision after reviews completed', [
            'review_completed_at' => now()
        ]);
    }

    // Keep legacy name for backward compatibility, but use new logic
    public function calculateAverageScore()
    {
        $this->syncReviewStatus();
        return $this->average_score;
    }

    // Check if both reviewers assigned
    public function hasBothReviewers()
    {
        return $this->reviewer_id && $this->reviewer_2_id;
    }

    public function hasFinalDecisionStatus(): bool
    {
        return in_array($this->status, ['accepted', 'rejected', 'withdrawn'], true);
    }

    // Check if both reviews completed
    public function isReviewComplete()
    {
        return $this->review1?->completed_at && $this->review2?->completed_at;
    }

    // Get review progress
    public function getReviewProgress()
    {
        $completed = 0;

        // Check if review1 is completed (has score)
        if ($this->reviewer_1_score !== null) $completed++;

        // Check if review2 is completed (has score)
        if ($this->reviewer_2_score !== null) $completed++;

        return [
            'completed' => $completed,
            'total' => 2,
            'percentage' => ($completed / 2) * 100
        ];
    }

    public function getReviewProgressDataAttribute()
    {
        $progressData = $this->getReviewProgress();
        $reviewers = [];

        // Reviewer 1 data
        if ($this->reviewer_id) {
            $reviewers[] = [
                'position' => 1,
                'name' => $this->reviewer1 ? $this->reviewer1->name : 'Unassigned',
                'email' => $this->reviewer1 ? $this->reviewer1->email : null,
                'status_color' => $this->reviewer_1_score ? 'bg-green-500' : 'bg-blue-500',
                'completion_status' => [
                    'color' => $this->reviewer_1_score ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400',
                    'text' => $this->reviewer_1_score ? 'Completed' : 'In Progress'
                ]
            ];
        }

        // Reviewer 2 data
        if ($this->reviewer_2_id) {
            $reviewers[] = [
                'position' => 2,
                'name' => $this->reviewer2 ? $this->reviewer2->name : 'Unassigned',
                'email' => $this->reviewer2 ? $this->reviewer2->email : null,
                'status_color' => $this->reviewer_2_score ? 'bg-green-500' : 'bg-indigo-500',
                'completion_status' => [
                    'color' => $this->reviewer_2_score ? 'text-green-600 dark:text-green-400' : 'text-indigo-600 dark:text-indigo-400',
                    'text' => $this->reviewer_2_score ? 'Completed' : 'In Progress'
                ]
            ];
        }

        // Timeline status
        $daysInReview = $this->assigned_at ? (int)$this->assigned_at->diffInDays(now()) : 0;
        $isOverdue = $daysInReview > 14;

        return [
            'completion_percentage' => $progressData['percentage'],
            'reviewers' => $reviewers,
            'timeline' => [
                'days_in_review' => $daysInReview,
                'is_overdue' => $isOverdue,
                'warning_text' => $isOverdue ? "Overdue by " . ($daysInReview - 14) . " days" : null
            ],
            'next_action' => $this->getNextRequiredAction()
        ];
    }

    public function getReviewScoresDataAttribute()
    {
        $scores = [
            'reviewer_1' => $this->reviewer_1_score,
            'reviewer_2' => $this->reviewer_2_score
        ];

        // Calculate aggregate metrics
        $averageScore = null;
        $variance = null;
        $agreementLevel = null;

        if ($scores['reviewer_1'] && $scores['reviewer_2']) {
            $averageScore = ($scores['reviewer_1'] + $scores['reviewer_2']) / 2;
            $variance = abs($scores['reviewer_1'] - $scores['reviewer_2']);

            if ($variance <= 10) $agreementLevel = 'high';
            elseif ($variance <= 20) $agreementLevel = 'medium';
            else $agreementLevel = 'low';
        }

        return [
            'individual_scores' => $scores,
            'aggregate' => [
                'average' => $averageScore,
                'variance' => $variance,
                'agreement_level' => $agreementLevel
            ],
            'quality_indicators' => $this->getQualityIndicators($averageScore, $variance)
        ];
    }

    public function getStatusColorClassAttribute()
    {
        return $this->getStatusColorClass($this->status);
    }

    public function getScoreColorClass($score)
    {
        if (!$score) return 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';

        if ($score >= 90) return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
        if ($score >= 80) return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        if ($score >= 70) return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300';
        if ($score >= 60) return 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300';

        return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
    }

    private function getStatusColorClass($status, $context = 'bg')
    {
        $colors = [
            'submitted' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-800 dark:text-blue-300'],
            'under_review' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-800 dark:text-yellow-300'],
            'ready_for_decision' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-800 dark:text-purple-300'],
            'accepted' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-800 dark:text-green-300'],
            'rejected' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-800 dark:text-red-300'],
            'revision_required' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-800 dark:text-amber-300'],
            'revision_submitted' => ['bg' => 'bg-teal-100 dark:bg-teal-900/30', 'text' => 'text-teal-800 dark:text-teal-300'],
        ];

        return $colors[$status][$context] ?? $colors['submitted'][$context];
    }

    private function getQualityIndicators($averageScore, $variance)
    {
        $indicators = [];

        if ($averageScore >= 90) {
            $indicators[] = [
                'type' => 'excellent',
                'color' => 'text-green-600 dark:text-green-400',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                'text' => 'Excellent Quality'
            ];
        } elseif ($averageScore >= 80) {
            $indicators[] = [
                'type' => 'good',
                'color' => 'text-blue-600 dark:text-blue-400',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>',
                'text' => 'Good Quality'
            ];
        }

        if ($variance && $variance > 20) {
            $indicators[] = [
                'type' => 'high_variance',
                'color' => 'text-orange-600 dark:text-orange-400',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/>',
                'text' => 'High Variance'
            ];
        }

        return $indicators;
    }

    private function getNextRequiredAction()
    {
        if (!$this->hasBothReviewers()) {
            return 'assign_reviewers';
        }

        if ($this->status === 'ready_for_decision') {
            return 'make_decision';
        }

        if ($this->status === 'accepted' && !$this->session_id) {
            return 'assign_session';
        }

        if ($this->status === 'under_review' && $this->assigned_at && (int)$this->assigned_at->diffInDays(now()) > 7) {
            return 'send_reminder';
        }

        return null;
    }

    public function getReviewerRole($reviewerId)
    {
        if ($this->reviewer_id == $reviewerId) {
            return 'primary';
        }

        if ($this->reviewer_2_id == $reviewerId) {
            return 'secondary';
        }

        return null;
    }

    public function getReviewerScore($reviewerId)
    {
        if ($this->reviewer_id == $reviewerId) {
            return $this->reviewer_1_score;
        }

        if ($this->reviewer_2_id == $reviewerId) {
            return $this->reviewer_2_score;
        }

        return null;
    }

    public function getReviewerComments($reviewerId)
    {
        if ($this->reviewer_id == $reviewerId) {
            return $this->reviewer_1_comments;
        }

        if ($this->reviewer_2_id == $reviewerId) {
            return $this->reviewer_2_comments;
        }

        return null;
    }

    public function getAverageScoreAttribute()
    {
        $scores = array_filter([$this->reviewer_1_score, $this->reviewer_2_score]);

        if (empty($scores)) {
            return null;
        }

        return array_sum($scores) / count($scores);
    }

    /**
     * Get detailed status information for display
     */
    public function getDetailedStatusAttribute()
    {
        // Check database status first - if it's already processed, return that status
        if ($this->status === 'accepted') {
            return [
                'status' => 'accepted',
                'label' => 'Accepted',
                'description' => 'Abstract has been accepted',
                'color' => 'green',
                'icon' => '✅',
                'priority' => 'low'
            ];
        } elseif ($this->status === 'rejected') {
            return [
                'status' => 'rejected',
                'label' => 'Rejected',
                'description' => 'Abstract has been rejected',
                'color' => 'red',
                'icon' => '❌',
                'priority' => 'low'
            ];
        } elseif ($this->status === 'revision_required') {
            return [
                'status' => 'revision_required',
                'label' => 'Accepted with Revisions',
                'description' => 'Author needs to address feedback before final acceptance',
                'color' => 'orange',
                'icon' => '📝',
                'priority' => 'medium'
            ];
        } elseif ($this->status === 'revision_submitted') {
            return [
                'status' => 'revision_submitted',
                'label' => 'Revision Submitted',
                'description' => 'Author has submitted revised abstract',
                'color' => 'indigo',
                'icon' => '📩',
                'priority' => 'medium'
            ];
        } elseif ($this->status === 'revision_review') {
            return [
                'status' => 'revision_review',
                'label' => 'Revision Under Review',
                'description' => 'Revision is being reviewed by experts',
                'color' => 'purple',
                'icon' => '🔍',
                'priority' => 'medium'
            ];
        }

        $hasReviewer1 = !is_null($this->reviewer_id);
        $hasReviewer2 = !is_null($this->reviewer_2_id);

        // Get the latest submitted review for each reviewer
        $reviewerIds = array_filter([$this->reviewer_id, $this->reviewer_2_id]);
        $latestReviews = collect();
        if (!empty($reviewerIds)) {
            $latestReviews = $this->reviews()
                ->whereIn('reviewer_id', $reviewerIds)
                ->where('status', 'submitted')
                ->orderBy('review_round', 'desc')
                ->get()
                ->unique('reviewer_id');
        }

        $reviewer1Submitted = $hasReviewer1 && $latestReviews->where('reviewer_id', $this->reviewer_id)->isNotEmpty();
        $reviewer2Submitted = $hasReviewer2 && $latestReviews->where('reviewer_id', $this->reviewer_2_id)->isNotEmpty();

        // Determine the real status based on reviewer assignments and review progress
        if (!$hasReviewer1 && !$hasReviewer2) {
            return [
                'status' => 'unassigned',
                'label' => 'Needs Reviewers',
                'description' => 'Waiting for reviewer assignment',
                'color' => 'red',
                'icon' => '🔴',
                'priority' => 'high'
            ];
        } elseif ($hasReviewer1 && $hasReviewer2 && !$reviewer1Submitted && !$reviewer2Submitted) {
            return [
                'status' => 'assigned',
                'label' => 'Assigned',
                'description' => 'Reviewers assigned, awaiting reviews',
                'color' => 'blue',
                'icon' => '📋',
                'priority' => 'medium'
            ];
        } elseif ($reviewer1Submitted && $reviewer2Submitted) {
            // Both reviews completed - get average score from latest submitted reviews
            $avgScore = $latestReviews->avg('score');
            $avgScore = round($avgScore, 1);

            if ($avgScore >= 85) {
                return [
                    'status' => 'high_quality',
                    'label' => 'High Quality',
                    'description' => "Both reviews completed (Score: {$avgScore})",
                    'color' => 'green',
                    'icon' => '🌟',
                    'priority' => 'low'
                ];
            } elseif ($avgScore >= 70) {
                return [
                    'status' => 'medium_quality',
                    'label' => 'Medium Quality',
                    'description' => "Both reviews completed (Score: {$avgScore})",
                    'color' => 'yellow',
                    'icon' => '⭐',
                    'priority' => 'medium'
                ];
            } else {
                return [
                    'status' => 'low_quality',
                    'label' => 'Low Quality',
                    'description' => "Both reviews completed (Score: {$avgScore})",
                    'color' => 'orange',
                    'icon' => '⚠️',
                    'priority' => 'high'
                ];
            }
        } elseif ($reviewer1Submitted || $reviewer2Submitted) {
            // One review completed
            $completedCount = ($reviewer1Submitted ? 1 : 0) + ($reviewer2Submitted ? 1 : 0);
            $totalAssigned = count($reviewerIds);

            return [
                'status' => 'partial_review',
                'label' => 'Partial Review',
                'description' => "{$completedCount}/{$totalAssigned} reviews completed",
                'color' => 'purple',
                'icon' => '🔄',
                'priority' => 'medium'
            ];
        } else {
            // Has reviewers but no submitted reviews yet
            $daysSinceAssignment = $this->assigned_at ? (int)$this->assigned_at->diffInDays(now()) : 0;

            // Check if there are any draft reviews (work in progress)
            $hasDraftReviews = $this->reviews->where('status', 'draft')->isNotEmpty();

            if ($hasDraftReviews) {
                return [
                    'status' => 'in_progress',
                    'label' => 'In Progress',
                    'description' => "Review in progress ({$daysSinceAssignment} days)",
                    'color' => 'blue',
                    'icon' => '🔄',
                    'priority' => 'low'
                ];
            } elseif ($daysSinceAssignment > 14) {
                return [
                    'status' => 'overdue',
                    'label' => 'Overdue',
                    'description' => "Review overdue by {$daysSinceAssignment} days",
                    'color' => 'red',
                    'icon' => '🚨',
                    'priority' => 'high'
                ];
            } elseif ($daysSinceAssignment > 7) {
                return [
                    'status' => 'pending',
                    'label' => 'Pending',
                    'description' => "Review pending for {$daysSinceAssignment} days",
                    'color' => 'amber',
                    'icon' => '⏳',
                    'priority' => 'medium'
                ];
            } else {
                return [
                    'status' => 'assigned',
                    'label' => 'Assigned',
                    'description' => "Reviewers assigned ({$daysSinceAssignment} days ago)",
                    'color' => 'blue',
                    'icon' => '📋',
                    'priority' => 'medium'
                ];
            }
        }
    }

    /**
     * Get review progress percentage
     */
    public function getReviewProgressPercentageAttribute()
    {
        $hasReviewer1 = !is_null($this->reviewer_id);
        $hasReviewer2 = !is_null($this->reviewer_2_id);
        $totalReviewers = ($hasReviewer1 ? 1 : 0) + ($hasReviewer2 ? 1 : 0);

        if ($totalReviewers === 0) return 0;

        $reviewerIds = array_filter([$this->reviewer_id, $this->reviewer_2_id]);
        $completedCount = 0;
        foreach ($reviewerIds as $rid) {
            if ($this->reviews()->where('reviewer_id', $rid)->where('status', 'submitted')->exists()) {
                $completedCount++;
            }
        }

        return ($completedCount / $totalReviewers) * 100;
    }

    /**
     * Check if review is overdue
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->assigned_at) return false;

        $daysSinceAssignment = (int)$this->assigned_at->diffInDays(now());
        return $daysSinceAssignment > 14;
    }

    /**
     * Get days since assignment
     */
    public function getDaysSinceAssignmentAttribute()
    {
        if (!$this->assigned_at) return null;
        return (int)$this->assigned_at->diffInDays(now());
    }

    public function conflicts()
    {
        return $this->hasMany(ReviewConflict::class);
    }

    public function getAnonymizedDataAttribute($value)
    {
        if (!$this->is_blind_review) {
            return null;
        }

        if ($value) {
            return json_decode($value, true);
        }

        // Generate anonymized data on the fly
        return $this->generateAnonymizedData();
    }

    public function generateAnonymizedData()
    {
        if (!$this->is_blind_review) {
            return null;
        }

        return [
            'title' => $this->title,
            'description' => $this->description,
            'subtheme' => $this->subtheme,
            'presentation_mode' => $this->presentation_mode,
            'include_in_proceedings' => $this->include_in_proceedings,
            'anonymized_author' => 'Author ' . ($this->id % 1000 + 1),
            'anonymized_institute' => 'Institution ' . chr(65 + ($this->id % 26)),
            'submission_date' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
            'coauthor_count' => is_array($this->coauthors) ? count($this->coauthors) : 0,
            'abstract_id' => config('conference.code') . '-' . str_pad($this->id, 4, '0', STR_PAD_LEFT) // Anonymous ID for reviewers
        ];
    }

    public function hasConflictWith($reviewerId)
    {
        return $this->conflicts()->where('reviewer_id', $reviewerId)->where('is_resolved', false)->exists();
    }

    public function checkAllConflicts($reviewerId)
    {
        $conflicts = [];

        // Author conflict - reviewer is the main author
        if (ReviewConflict::detectAuthorConflict($this->id, $reviewerId)) {
            $conflicts[] = [
                'type' => 'author',
                'reason' => 'Reviewer appears to be the main author of this submission'
            ];
        }

        // Collaboration conflict - reviewer is a co-author
        if (ReviewConflict::detectCollaborationConflict($this->id, $reviewerId)) {
            $conflicts[] = [
                'type' => 'collaboration',
                'reason' => 'Reviewer appears to be listed as a co-author on this submission'
            ];
        }

        // Self-review conflict - user trying to review their own submission
        if (ReviewConflict::detectSelfReview($this->id, $reviewerId)) {
            $conflicts[] = [
                'type' => 'self_review',
                'reason' => 'User is attempting to review their own submission'
            ];
        }

        return $conflicts;
    }

    public function enableBlindReview()
    {
        $anonymizedData = $this->generateAnonymizedData();

        $this->update([
            'is_blind_review' => true,
            'anonymized_data' => json_encode($anonymizedData),
            'conflict_checked' => false // Reset conflict check when enabling blind review
        ]);

        return $anonymizedData;
    }

    public function disableBlindReview()
    {
        $this->update([
            'is_blind_review' => false,
            'anonymized_data' => null
        ]);
    }

    public function getReviewDataForReviewer($reviewerId)
    {
        // Verify reviewer is assigned
        if ($this->reviewer_id !== $reviewerId && $this->reviewer_2_id !== $reviewerId) {
            return null;
        }

        if ($this->is_blind_review) {
            return (object) $this->anonymized_data;
        }

        return $this; // Return full data for non-blind review
    }

    // Scope for accepted abstracts only
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    // Scope for categorized abstracts
    public function scopeCategorized($query)
    {
        return $query->whereNotNull('category_id');
    }

    // Presentation upload methods
    public function hasPresentationUploaded()
    {
        return $this->presentation_status === 'uploaded' || $this->presentation_status === 'approved';
    }

    public function getPresentationFiles()
    {
        $files = [];

        if ($this->oral_presentation_file) {
            $files['oral'] = $this->oral_presentation_file;
        }

        if ($this->poster_presentation_file) {
            $files['poster'] = $this->poster_presentation_file;
        }

        if ($this->audio_poster_file) {
            $files['audio_poster'] = [
                'audio' => $this->audio_poster_file,
                'poster' => $this->audio_poster_poster_file,
                'description' => $this->audio_poster_description,
                'duration' => $this->audio_poster_duration,
                'metadata' => $this->audio_poster_metadata
            ];
        }

        if ($this->presentation_files) {
            $files['additional'] = $this->presentation_files;
        }

        return $files;
    }

    public function getPresentationFileCount()
    {
        $count = 0;

        if ($this->oral_presentation_file) $count++;
        if ($this->poster_presentation_file) $count++;
        if ($this->audio_poster_file) $count++;
        if ($this->presentation_files) $count += count($this->presentation_files);

        return $count;
    }

    public function requiresPresentationUpload()
    {
        return $this->status === 'accepted' && $this->presentation_status === 'pending';
    }

    public function getPresentationRequirements()
    {
        $requirements = [];

        // Normalize presentation_mode to lowercase for comparison
        $presentationMode = strtolower($this->presentation_mode ?? '');

        switch ($presentationMode) {
            case 'oral':
                $requirements = [
                    'primary' => 'Oral presentation slides (PowerPoint only)',
                    'formats' => ['ppt', 'pptx'],
                    'max_size' => '50MB',
                    'duration' => '10 minutes maximum'
                ];
                break;

            case 'poster':
                $requirements = [
                    'primary' => 'Narrated digital poster video in MP4 format',
                    'formats' => ['mp4'],
                    'max_size' => '100MB',
                    'duration' => '5 minutes maximum',
                    'dimensions' => '16:9 widescreen landscape'
                ];
                break;

            case 'audio_poster':
                $requirements = [
                    'primary' => 'Audio poster (MP3/WAV audio + PowerPoint poster)',
                    'formats' => [
                        'audio' => ['mp3', 'wav', 'aac'],
                        'poster' => ['ppt', 'pptx']
                    ],
                    'max_size' => '100MB total',
                    'duration' => '10 minutes maximum',
                    'dimensions' => 'A0 size recommended for poster (841 × 1189 mm)'
                ];
                break;
        }

        return $requirements;
    }

    public function canUploadPresentation()
    {
        return $this->status === 'accepted' &&
               in_array($this->presentation_status, ['pending', 'uploaded']) &&
               $this->user_id === auth()->id();
    }

    public function markPresentationAsUploaded()
    {
        $this->update([
            'presentation_status' => 'uploaded',
            'presentation_uploaded_at' => now()
        ]);
    }

    public function approvePresentationUpload()
    {
        $this->update([
            'presentation_status' => 'approved'
        ]);
    }

    public function rejectPresentationUpload($reason = null)
    {
        $this->update([
            'presentation_status' => 'pending',
            'presentation_notes' => $reason
        ]);
    }

    public function getPresentationStatusBadge()
    {
        // Presentation badges only apply to accepted abstracts
        if ($this->status !== 'accepted') {
            // Return the abstract status badge instead
            $statusColors = [
                'draft' => 'bg-slate-100 text-slate-600',
                'submitted' => 'bg-blue-100 text-blue-800',
                'under_review' => 'bg-purple-100 text-purple-800',
                'revision_requested' => 'bg-amber-100 text-amber-800',
                'minor_revision_required' => 'bg-amber-100 text-amber-800',
                'major_revision_required' => 'bg-orange-100 text-orange-800',
                'revision_submitted' => 'bg-indigo-100 text-indigo-800',
                'rejected' => 'bg-red-100 text-red-800',
            ];
            $colorClass = $statusColors[$this->status] ?? 'bg-gray-100 text-gray-800';
            $label = ucwords(str_replace('_', ' ', $this->status));
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $colorClass . '">' . $label . '</span>';
        }

        // Check if files actually exist, regardless of presentation_status
        $hasFiles = $this->hasActualPresentationFiles();

        if (!$hasFiles) {
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending Upload</span>';
        }

        // If files exist, show status based on presentation_status
        switch ($this->presentation_status) {
            case 'pending':
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending Upload</span>';
            case 'uploaded':
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Uploaded</span>';
            case 'approved':
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>';
            default:
                // If status is null or unknown but files exist, show as uploaded
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Uploaded</span>';
        }
    }

    /**
     * Check if presentation files actually exist
     */
    public function hasActualPresentationFiles()
    {
        $presentationMode = strtolower($this->presentation_mode ?? '');

        switch ($presentationMode) {
            case 'oral':
                return !empty($this->oral_presentation_file);
            case 'poster':
                return !empty($this->poster_presentation_file);
            case 'audio_poster':
                return !empty($this->audio_poster_file) || !empty($this->audio_poster_poster_file);
            default:
                return false;
        }
    }

    // Audio poster specific methods
    public function hasAudioPoster()
    {
        return $this->presentation_mode === 'audio_poster' && $this->audio_poster_file;
    }

    public function getAudioPosterDuration()
    {
        if (!$this->audio_poster_duration) return null;

        $minutes = floor($this->audio_poster_duration / 60);
        $seconds = $this->audio_poster_duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function setAudioPosterMetadata($metadata)
    {
        $this->audio_poster_metadata = array_merge($this->audio_poster_metadata ?? [], $metadata);
        $this->save();
    }

    // Workflow Automation Methods
    public function isReadyForAutomaticDecision()
    {
        // Must have both reviewers assigned
        if (!$this->reviewer_id || !$this->reviewer_2_id) {
            return false;
        }

        // Must be under review
        if ($this->status !== 'under_review') {
            return false;
        }

        // Must have both reviews submitted
        $submittedReviews = $this->reviews->where('status', 'submitted');
        if ($submittedReviews->count() < 2) {
            return false;
        }

        // Check if both reviewers have submitted
        $reviewer1Submitted = $submittedReviews->where('reviewer_id', $this->reviewer_id)->isNotEmpty();
        $reviewer2Submitted = $submittedReviews->where('reviewer_id', $this->reviewer_2_id)->isNotEmpty();

        return $reviewer1Submitted && $reviewer2Submitted;
    }

    public function hasConflictingReviews()
    {
        if (!$this->isReadyForAutomaticDecision()) {
            return false;
        }

        $submittedReviews = $this->reviews->where('status', 'submitted');
        $recommendations = $submittedReviews->pluck('recommendation')->toArray();

        // Check for exact match
        if ($recommendations[0] === $recommendations[1]) {
            return false;
        }

        // Check for consensus within categories
        $acceptTypes = ['accept_oral', 'accept_poster'];
        $rejectTypes = ['reject'];
        $minorTypes = ['minor_revisions'];
        $majorTypes = ['major_revisions'];

        $r1 = $recommendations[0];
        $r2 = $recommendations[1];

        // Both accept (different types) - not a conflict
        if ((in_array($r1, $acceptTypes) && in_array($r2, $acceptTypes))) {
            return false;
        }

        // Both reject - not a conflict
        if ((in_array($r1, $rejectTypes) && in_array($r2, $rejectTypes))) {
            return false;
        }

        // Both minor revisions - not a conflict
        if ((in_array($r1, $minorTypes) && in_array($r2, $minorTypes))) {
            return false;
        }

        // Both major revisions - not a conflict
        if ((in_array($r1, $majorTypes) && in_array($r2, $majorTypes))) {
            return false;
        }

        // Any other combination is a conflict
        return true;
    }

    public function getConsensusDecision()
    {
        if (!$this->isReadyForAutomaticDecision() || $this->hasConflictingReviews()) {
            return null;
        }

        $submittedReviews = $this->reviews->where('status', 'submitted');
        $recommendations = $submittedReviews->pluck('recommendation')->toArray();

        // Exact match
        if ($recommendations[0] === $recommendations[1]) {
            return $recommendations[0];
        }

        // Consensus within categories
        $acceptTypes = ['accept_oral', 'accept_poster'];
        $rejectTypes = ['reject'];
        $minorTypes = ['minor_revisions'];
        $majorTypes = ['major_revisions'];

        $r1 = $recommendations[0];
        $r2 = $recommendations[1];

        if ((in_array($r1, $acceptTypes) && in_array($r2, $acceptTypes))) {
            return 'accept_oral'; // Default to oral presentation
        }

        if ((in_array($r1, $rejectTypes) && in_array($r2, $rejectTypes))) {
            return 'reject';
        }

        if ((in_array($r1, $minorTypes) && in_array($r2, $minorTypes))) {
            return 'minor_revisions';
        }

        if ((in_array($r1, $majorTypes) && in_array($r2, $majorTypes))) {
            return 'major_revisions';
        }

        return null;
    }

    public function isAutomatedDecision()
    {
        return $this->automated_decision;
    }

    public function getAutomationReason()
    {
        return $this->automated_decision_reason;
    }

    public function getAutomationTimestamp()
    {
        return $this->automated_decision_at;
    }

    /**
     * Get reviews for a specific round
     */
    public function getReviewsForRound(int $round)
    {
        return $this->reviews()->where('review_round', $round)->get();
    }

    /**
     * Get reviews for the current round
     */
    public function getCurrentRoundReviews()
    {
        $currentRound = $this->revision_round ?? 1;
        return $this->getReviewsForRound($currentRound);
    }

    /**
     * Get all unique review rounds for this abstract
     */
    public function getAllReviewRounds(): array
    {
        return $this->reviews()
            ->select('review_round')
            ->distinct()
            ->orderBy('review_round', 'asc')
            ->pluck('review_round')
            ->toArray();
    }

    /**
     * Check if all required reviews are complete for a specific round
     */
    public function hasReviewsCompleteForRound(int $round): bool
    {
        $reviewCount = $this->reviews()
            ->where('review_round', $round)
            ->where('status', 'submitted')
            ->count();

        // Need reviews from both assigned reviewers
        $expectedReviewers = 0;
        if ($this->reviewer_id) $expectedReviewers++;
        if ($this->reviewer_2_id) $expectedReviewers++;

        return $reviewCount >= $expectedReviewers && $expectedReviewers > 0;
    }

    /**
     * Get the latest completed round number
     */
    public function getLatestCompletedRound(): ?int
    {
        $rounds = $this->getAllReviewRounds();

        foreach (array_reverse($rounds) as $round) {
            if ($this->hasReviewsCompleteForRound($round)) {
                return $round;
            }
        }

        return null;
    }

    /**
     * Get revision changes between rounds (using RevisionComparisonService)
     */
    public function getRevisionChanges(int $fromRound = 1, ?int $toRound = null)
    {
        $comparisonService = app(\App\Services\RevisionComparisonService::class);
        return $comparisonService->generateComparisonData($this, $fromRound, $toRound);
    }

    /**
     * Get review history timeline for display
     */
    public function getReviewHistoryTimeline()
    {
        $historyService = app(\App\Services\ReviewHistoryService::class);
        $rounds = $this->getAllReviewRounds();
        $timeline = [];

        foreach ($rounds as $round) {
            $reviews = $this->getReviewsForRound($round);
            $timeline[$round] = [
                'round' => $round,
                'reviews' => $reviews,
                'completed' => $this->hasReviewsCompleteForRound($round),
                'review_count' => $reviews->count(),
            ];
        }

        return $timeline;
    }

    /**
     * Check if abstract is currently in a revision round
     */
    public function isInRevisionRound(): bool
    {
        return ($this->revision_round ?? 1) > 1;
    }

    /**
     * Get the current round number
     */
    public function getCurrentRound(): int
    {
        return $this->revision_round ?? 1;
    }

    /**
     * Check if abstract needs re-review (has revisions pending review)
     */
    public function needsReReview(): bool
    {
        return in_array($this->status, [
            'revision_under_review',
            'revision_submitted'
        ]);
    }

    /**
     * Get review statistics for all rounds
     */
    public function getReviewStatistics(): array
    {
        $historyService = app(\App\Services\ReviewHistoryService::class);
        return $historyService->getReviewStatistics($this->id);
    }
}
