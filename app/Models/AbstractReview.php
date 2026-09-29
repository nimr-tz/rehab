<?php
// filepath: app/Models/AbstractReview.php
// Create this file: php artisan make:model AbstractReview -m

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbstractReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'abstract_submission_id',
        'reviewer_id',
        'reviewer_number', // 1 or 2
        'score',
        'technical_quality',
        'novelty',
        'relevance',
        'clarity',
        'title_score',
        'word_count_score',
        'writing_quality_score',
        'structure_score',
        'background_score',
        'rationale_score',
        'objective_score',
        'methodology_design_score',
        'methodology_analysis_score',
        'results_logic_score',
        'results_findings_score',
        'results_data_score',
        'conclusion_interpretation_score',
        'conclusion_impact_score',
        'relevance_theme_score',
        'comments',
        'recommendation',
        'subtheme_relevance',
        'suggested_subtheme',
        'completed_at',
        'review_round', // Add this for revision workflow
        'status', // draft or submitted
        'submitted_at', // when submitted to admin
        'is_revision_review', // flag for re-reviews
        'revision_notes', // notes on revision improvements
        'improvement_from_previous', // improved/same/declined/n/a
        'previous_round_id', // link to previous round review
        'last_reminded_at',
        'assigned_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'last_reminded_at' => 'datetime',
        'assigned_at' => 'datetime',
        'score' => 'decimal:1',
        'is_revision_review' => 'boolean',
    ];

    public function abstractSubmission()
    {
        return $this->belongsTo(AbstractSubmission::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function previousRoundReview()
    {
        return $this->belongsTo(AbstractReview::class, 'previous_round_id');
    }

    // Calculate overall score from individual criteria
    public function calculateScore()
    {
        $this->score =
            ($this->title_score ?? 0) +
            ($this->word_count_score ?? 0) +
            ($this->writing_quality_score ?? 0) +
            ($this->structure_score ?? 0) +
            ($this->background_score ?? 0) +
            ($this->rationale_score ?? 0) +
            ($this->objective_score ?? 0) +
            ($this->methodology_design_score ?? 0) +
            ($this->methodology_analysis_score ?? 0) +
            ($this->results_logic_score ?? 0) +
            ($this->results_findings_score ?? 0) +
            ($this->results_data_score ?? 0) +
            ($this->conclusion_interpretation_score ?? 0) +
            ($this->conclusion_impact_score ?? 0) +
            ($this->relevance_theme_score ?? 0);

        $this->save();
        return $this->score;
    }

    /**
     * Scope: Get reviews for a specific round
     */
    public function scopeForRound($query, int $round)
    {
        return $query->where('review_round', $round);
    }

    /**
     * Scope: Get only the latest round reviews
     */
    public function scopeLatestRound($query)
    {
        $maxRound = $query->max('review_round') ?? 1;
        return $query->where('review_round', $maxRound);
    }

    /**
     * Scope: Get only revision reviews (re-reviews)
     */
    public function scopeRevisionReviews($query)
    {
        return $query->where('is_revision_review', true);
    }

    /**
     * Scope: Get only initial reviews (round 1)
     */
    public function scopeInitialReviews($query)
    {
        return $query->where('review_round', 1);
    }

    /**
     * Scope: Get submitted reviews only
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope: Get draft reviews only
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Get the previous round's review for this reviewer
     */
    public function getPreviousRound(): ?AbstractReview
    {
        if ($this->review_round <= 1) {
            return null;
        }

        return self::where('abstract_submission_id', $this->abstract_submission_id)
            ->where('reviewer_id', $this->reviewer_id)
            ->where('review_round', $this->review_round - 1)
            ->first();
    }

    /**
     * Check if this review improved from the previous round
     */
    public function wasImproved(): ?bool
    {
        $previousReview = $this->getPreviousRound();

        if (!$previousReview || !$this->score || !$previousReview->score) {
            return null;
        }

        return $this->score > $previousReview->score;
    }

    /**
     * Get score change from previous round
     */
    public function getScoreChange(): ?float
    {
        $previousReview = $this->getPreviousRound();

        if (!$previousReview || !$this->score || !$previousReview->score) {
            return null;
        }

        return round($this->score - $previousReview->score, 2);
    }

    /**
     * Get recommendation change direction
     */
    public function getRecommendationChange(): ?string
    {
        $previousReview = $this->getPreviousRound();

        if (!$previousReview || !$this->recommendation || !$previousReview->recommendation) {
            return null;
        }

        $weights = [
            'reject' => 1,
            'accept_with_revisions' => 2,
            'accept' => 3,
        ];

        $oldWeight = $weights[$previousReview->recommendation] ?? 0;
        $newWeight = $weights[$this->recommendation] ?? 0;

        if ($newWeight > $oldWeight) {
            return 'improved';
        } elseif ($newWeight < $oldWeight) {
            return 'declined';
        }

        return 'unchanged';
    }

    /**
     * Check if this is the first review (round 1)
     */
    public function isInitialReview(): bool
    {
        return $this->review_round === 1;
    }

    /**
     * Check if this is a re-review (revision round)
     */
    public function isReReview(): bool
    {
        return $this->review_round > 1 || $this->is_revision_review;
    }

    /**
     * Get all reviews in the same round for comparison
     */
    public function getSameRoundReviews()
    {
        return self::where('abstract_submission_id', $this->abstract_submission_id)
            ->where('review_round', $this->review_round)
            ->where('id', '!=', $this->id)
            ->get();
    }

    /**
     * Get formatted round label for display
     */
    public function getRoundLabelAttribute(): string
    {
        if ($this->review_round === 1) {
            return 'Initial Review';
        }
        return "Re-Review Round {$this->review_round}";
    }

    /**
     * Get color class based on score
     */
    public function getScoreColorClass(): string
    {
        if (!$this->score) {
            return 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
        }

        if ($this->score >= 90) return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
        if ($this->score >= 80) return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        if ($this->score >= 70) return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300';
        if ($this->score >= 60) return 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300';

        return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
    }

    /**
     * Get recommendation badge color class
     */
    public function getRecommendationColorClass(): string
    {
        return match($this->recommendation) {
            'accept' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300',
            'accept_with_revisions' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300',
            'reject' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
            default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
        };
    }

    /**
     * Get formatted recommendation label
     */
    public function getRecommendationLabelAttribute(): string
    {
        return match($this->recommendation) {
            'accept' => 'Accept',
            'accept_with_revisions' => 'Accepted with Revisions',
            'reject' => 'Reject',
            default => 'No Recommendation',
        };
    }
}
