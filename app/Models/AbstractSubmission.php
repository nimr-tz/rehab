<?php

namespace App\Models;

use App\Enums\AbstractStatus;
use App\Enums\PresentationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * An abstract submitted to the summit. The class is not called Abstract
 * because that is a reserved word in PHP.
 */
class AbstractSubmission extends Model
{
    protected $table = 'abstracts';

    protected $guarded = ['id'];

    public const WORD_LIMIT = 300;

    public const SECTIONS = ['background' => 'Background', 'methods' => 'Methods', 'results' => 'Results', 'conclusions' => 'Conclusions'];

    /** What an author may change in a revision. Topic, type and authors stay as reviewed. */
    public const REVISABLE = ['title', 'background', 'methods', 'results', 'conclusions', 'keywords'];

    protected function casts(): array
    {
        return [
            'status' => AbstractStatus::class,
            'preferred_type' => PresentationType::class,
            'decision_type' => PresentationType::class,
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'accepted_automatically' => 'boolean',
            'revision_requested_at' => 'datetime',
            'revision_due_on' => 'date',
            'original_version' => 'array',
            'revised_at' => 'datetime',
        ];
    }

    /** Reviewers per abstract (config/review.php): two. */
    public static function reviewersNeeded(): int
    {
        return (int) config('review.reviewers_per_abstract');
    }

    /** 1 for the first review; 2 once the author has sent a revised version. */
    public function currentRound(): int
    {
        return $this->revised_at ? 2 : 1;
    }

    /** @return Collection<int, ReviewAssignment> */
    public function roundReviews(?int $round = null): Collection
    {
        $round ??= $this->currentRound();

        return $this->reviews->where('round', $round)->values();
    }

    /**
     * Reviewers a round needs: two at first. The revision goes only to the
     * reviewers who did not accept.
     */
    public function reviewersNeededInRound(?int $round = null): int
    {
        $round ??= $this->currentRound();

        return $round === 1
            ? self::reviewersNeeded()
            : $this->roundReviews(1)->filter(fn (ReviewAssignment $r) => $r->isComplete() && ! $r->recommendation->isAcceptance())->count();
    }

    /** The round-1 reviewers who did not accept, and so review the revised version. */
    public function revisionReviewers(): Collection
    {
        return $this->roundReviews(1)->filter(fn (ReviewAssignment $r) => $r->isComplete() && ! $r->recommendation->isAcceptance())->values();
    }

    /**
     * Every review of the current round is in, and the committee must decide.
     * (When they all accept, the abstract is accepted automatically instead.)
     */
    public function awaitsDecision(): bool
    {
        $reviews = match ($this->status) {
            AbstractStatus::UnderReview => $this->roundReviews(1),
            AbstractStatus::Revised => $this->roundReviews(2),
            default => null,
        };

        if (! $reviews || $reviews->isEmpty() || ! $reviews->every->isComplete()) {
            return false;
        }

        // Round 1 needs both reviews. In round 2 the committee may have removed a reviewer.
        return $this->status === AbstractStatus::Revised || $reviews->count() >= self::reviewersNeeded();
    }

    /** The same rule as awaitsDecision(), as a query. */
    public function scopeAwaitingDecision(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $q) => $q->where('status', AbstractStatus::UnderReview)
                ->whereHas('reviews', fn (Builder $q) => $q->where('round', 1), '>=', self::reviewersNeeded())
                ->whereDoesntHave('reviews', fn (Builder $q) => $q->where('round', 1)->whereNull('completed_at')))
            ->orWhere(fn (Builder $q) => $q->where('status', AbstractStatus::Revised)
                ->whereHas('reviews', fn (Builder $q) => $q->where('round', 2))
                ->whereDoesntHave('reviews', fn (Builder $q) => $q->where('round', 2)->whereNull('completed_at'))));
    }

    public function isRevisionOverdue(): bool
    {
        return $this->status === AbstractStatus::RevisionRequested && $this->revision_due_on?->lt(today());
    }

    /**
     * The committee's choices now: accept or reject, plus a revision request
     * in the first round.
     *
     * @return array<string, string>
     */
    public function decisionOptions(bool $short = false): array
    {
        $options = PresentationType::decisionOptions($short);

        if ($this->status === AbstractStatus::UnderReview) {
            $reject = array_pop($options);
            $options += ['revise' => $short ? 'Revise' : 'Request revisions', 'reject' => $reject];
        }

        return $options;
    }

    /** A section as it was before the revision, or null when there was no revision. */
    public function originalText(string $field): ?string
    {
        return $this->original_version[$field] ?? null;
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(AbstractAuthor::class, 'abstract_id')->orderBy('position');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ReviewAssignment::class, 'abstract_id');
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(ProgrammeSession::class, 'programme_session_abstract', 'abstract_id');
    }

    public function presenter(): ?AbstractAuthor
    {
        return $this->authors->firstWhere('is_presenter', true) ?? $this->authors->first();
    }

    public function wordCount(): int
    {
        return static::countWords(implode(' ', [$this->background, $this->methods, $this->results, $this->conclusions]));
    }

    public static function countWords(?string $text): int
    {
        return count(preg_split('/\s+/u', trim((string) $text), -1, PREG_SPLIT_NO_EMPTY));
    }

    /** "#A27-014", used wherever the authors must stay hidden. */
    public function blindId(): string
    {
        return '#A'.$this->edition->shortYear().'-'.Str::padLeft((string) $this->id, 3, '0');
    }

    /** Average rubric total (out of 100) across the completed reviews of a round (the current one by default). */
    public function averageScore(?int $round = null): ?float
    {
        $done = $this->roundReviews($round)->filter->isComplete();

        return $done->isEmpty() ? null : round($done->avg(fn (ReviewAssignment $review) => $review->totalScore()), 1);
    }
}
