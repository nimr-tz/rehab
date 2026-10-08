<?php

namespace App\Services;

use App\Enums\AbstractStatus;
use App\Enums\PresentationType;
use App\Enums\Recommendation;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\ReviewAssignment;
use App\Models\User;
use App\Notifications\AbstractDecided;
use App\Notifications\AbstractSubmitted;
use App\Notifications\ReviewAssigned;
use App\Notifications\RevisionRequested;
use App\Support\Rubric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Abstracts from submission to decision.
 *
 * Each abstract has exactly two reviewers. When both accept, it is accepted
 * automatically. Otherwise the scientific committee decides: accept, reject,
 * or (once) ask the author to revise. A revised abstract goes back only to the
 * reviewers who did not accept; if they all accept it, it is accepted
 * automatically, and otherwise the committee accepts or rejects it.
 */
class AbstractService
{
    /**
     * Create or update an abstract, as a draft or submitted.
     *
     * @param  array{topic_id: int, preferred_type: string, title: string, background: string, methods: string, results: string, conclusions: string, keywords?: ?string, authors: list<array{name: string, email?: ?string, affiliation: string}>, presenter: int}  $data
     */
    public function save(User $user, Edition $edition, array $data, bool $submit, ?AbstractSubmission $abstract = null): AbstractSubmission
    {
        if ($abstract && ! $abstract->status->isEditable()) {
            throw new InvalidArgumentException('This abstract can no longer be changed.');
        }

        $wasSubmitted = $abstract?->status === AbstractStatus::Submitted;

        $abstract = DB::transaction(function () use ($user, $edition, $data, $submit, $abstract) {
            $fields = [
                'topic_id' => $data['topic_id'],
                'preferred_type' => PresentationType::from($data['preferred_type']),
                'title' => trim($data['title']),
                'background' => trim($data['background']),
                'methods' => trim($data['methods']),
                'results' => trim($data['results']),
                'conclusions' => trim($data['conclusions']),
                'keywords' => $data['keywords'] ?? null,
            ];

            if ($submit) {
                $fields['status'] = AbstractStatus::Submitted;
                $fields['submitted_at'] = $abstract?->submitted_at ?? now();
            }

            if ($abstract) {
                $abstract->update($fields);
                $abstract->authors()->delete();
            } else {
                $abstract = AbstractSubmission::create($fields + [
                    'edition_id' => $edition->id,
                    'user_id' => $user->id,
                    'status' => AbstractStatus::Draft,
                ]);
            }

            foreach (array_values($data['authors']) as $position => $author) {
                $abstract->authors()->create([
                    'name' => trim($author['name']),
                    'email' => isset($author['email']) ? Str::lower(trim($author['email'])) ?: null : null,
                    'affiliation' => trim($author['affiliation']),
                    'is_presenter' => $position === (int) $data['presenter'],
                    'position' => $position + 1,
                ]);
            }

            return $abstract;
        });

        if ($submit && ! $wasSubmitted) {
            $user->notify(new AbstractSubmitted($abstract));
        }

        return $abstract;
    }

    public function withdraw(AbstractSubmission $abstract): void
    {
        if (! $abstract->status->isEditable() && $abstract->status !== AbstractStatus::RevisionRequested) {
            throw new InvalidArgumentException('This abstract can no longer be withdrawn.');
        }

        $abstract->update(['status' => AbstractStatus::Withdrawn]);
    }

    /** Reviewers who may review this abstract now: not an author, not already reviewing it in this round. */
    public function eligibleReviewers(AbstractSubmission $abstract)
    {
        $authorEmails = $abstract->authors->pluck('email')->filter()->push($abstract->submitter->email)->map(fn ($e) => Str::lower($e));
        $assigned = $abstract->roundReviews()->pluck('reviewer_id');

        return User::role('reviewer')
            ->withCount(['reviewAssignments as open_reviews_count' => fn ($query) => $query->whereNull('completed_at')])
            ->orderBy('last_name')
            ->get()
            ->reject(fn (User $reviewer) => $reviewer->id === $abstract->user_id
                || $authorEmails->contains(Str::lower($reviewer->email))
                || $assigned->contains($reviewer->id))
            ->values();
    }

    /** How many more reviewers the current round can take: never more than two in all. */
    public function openSeats(AbstractSubmission $abstract): int
    {
        return match ($abstract->status) {
            AbstractStatus::Submitted, AbstractStatus::UnderReview => max(0, AbstractSubmission::reviewersNeeded() - $abstract->roundReviews(1)->count()),
            // A replacement for a round-2 reviewer the committee removed.
            AbstractStatus::Revised => max(0, $abstract->reviewersNeededInRound(2) - $abstract->roundReviews(2)->count()),
            default => 0,
        };
    }

    public function assign(AbstractSubmission $abstract, User $reviewer, User $by, ?string $dueOn = null): ReviewAssignment
    {
        $assignment = DB::transaction(function () use ($abstract, $reviewer, $by, $dueOn) {
            $abstract = AbstractSubmission::lockForUpdate()->findOrFail($abstract->id)->load('reviews', 'authors', 'submitter', 'edition');

            if (! $abstract->status->isInReview()) {
                throw new InvalidArgumentException('Reviewers can only be assigned while the abstract is in review.');
            }

            if ($this->openSeats($abstract) === 0) {
                throw new InvalidArgumentException('This abstract already has its '.AbstractSubmission::reviewersNeeded().' reviewers.');
            }

            if (! $this->eligibleReviewers($abstract)->contains('id', $reviewer->id)) {
                throw new InvalidArgumentException('This reviewer cannot review this abstract.');
            }

            $round = $abstract->currentRound();
            $assignment = $abstract->reviews()->create([
                'reviewer_id' => $reviewer->id,
                'round' => $round,
                'assigned_by' => $by->id,
                'due_on' => $dueOn ?? ($round === 2 ? today()->addDays(config('review.second_round_days')) : $abstract->edition->review_deadline),
            ]);

            if ($abstract->status === AbstractStatus::Submitted) {
                $abstract->update(['status' => AbstractStatus::UnderReview]);
            }

            return $assignment;
        });

        $reviewer->notify(new ReviewAssigned($assignment));

        return $assignment;
    }

    public function unassign(ReviewAssignment $assignment): void
    {
        if ($assignment->isComplete()) {
            throw new InvalidArgumentException('A completed review cannot be removed.');
        }

        DB::transaction(function () use ($assignment) {
            $abstract = $assignment->abstract;
            $assignment->delete();

            if ($abstract->status === AbstractStatus::UnderReview && ! $abstract->reviews()->exists()) {
                $abstract->update(['status' => AbstractStatus::Submitted]);
            }
        });

        // Removing the last open round-2 review may leave every remaining review in.
        $this->settle($assignment->abstract);
    }

    /**
     * Rubric points per criterion (see config/review.php), plus the optional technical sub-checks.
     *
     * @param  array{score_originality: int, score_technical: int, score_significance: int, score_clarity: int, technical_checks?: ?list<string>, recommendation: string, comments_for_author: string, comments_for_committee?: ?string}  $data
     */
    public function review(ReviewAssignment $assignment, array $data): void
    {
        if (! $assignment->isOpen()) {
            throw new InvalidArgumentException('This review round is closed.');
        }

        $recommendation = Recommendation::from($data['recommendation']);
        if (! in_array($recommendation, Recommendation::offered($assignment->round), true)) {
            throw new InvalidArgumentException('That recommendation is not available for this review.');
        }

        $assignment->update(collect(Rubric::fields())->mapWithKeys(fn (string $field) => [$field => (int) $data[$field]])->all() + [
            'technical_checks' => array_values(array_unique($data['technical_checks'] ?? [])),
            'recommendation' => $recommendation,
            'comments_for_author' => $data['comments_for_author'],
            'comments_for_committee' => $data['comments_for_committee'] ?? null,
            'completed_at' => $assignment->completed_at ?? now(),
        ]);

        $this->settle($assignment->abstract);
    }

    /**
     * When every reviewer of the round has accepted, accept the abstract
     * without waiting for the committee. Returns true when it did.
     */
    public function settle(AbstractSubmission $abstract): bool
    {
        $accepted = DB::transaction(function () use ($abstract) {
            $abstract = AbstractSubmission::lockForUpdate()->findOrFail($abstract->id)->load('reviews', 'topic');

            if (! $abstract->awaitsDecision()) {
                return null;
            }

            $round = $abstract->roundReviews();
            if ($round->count() < $abstract->reviewersNeededInRound() || ! $round->every(fn (ReviewAssignment $r) => $r->recommendation->isAcceptance())) {
                return null;
            }

            // A poster only if every reviewer said poster.
            $type = $round->every(fn (ReviewAssignment $r) => $r->recommendation === Recommendation::AcceptPoster) && PresentationType::postersEnabled()
                ? PresentationType::Poster
                : PresentationType::Oral;

            $this->accept($abstract, $type, null, automatically: true);

            return $abstract;
        });

        $accepted?->submitter->notify(new AbstractDecided($accepted->fresh()));

        return $accepted !== null;
    }

    /**
     * The committee's decision once every review of the round is in: accept as
     * oral (or poster, when posters are on), reject, or in the first round ask
     * the author to revise. An abstract waiting for its revision can also be
     * rejected, for example when the author misses the deadline.
     */
    public function decide(AbstractSubmission $abstract, string $decision, ?string $note, ?string $revisionDueOn = null): void
    {
        $abstract->loadMissing('reviews', 'topic');

        $waitingForAuthor = $abstract->status === AbstractStatus::RevisionRequested && $decision === 'reject';
        if (! $abstract->awaitsDecision() && ! $waitingForAuthor) {
            throw new InvalidArgumentException($abstract->status->isDecided()
                ? 'This abstract already has a decision.'
                : 'Every review must be in before you decide.');
        }

        if ($decision === 'revise') {
            $this->requestRevision($abstract, $note, $revisionDueOn);

            return;
        }

        DB::transaction(function () use ($abstract, $decision, $note) {
            if ($decision === 'reject') {
                $abstract->update([
                    'status' => AbstractStatus::Rejected,
                    'decision_note' => $note,
                    'decided_at' => now(),
                ]);

                return;
            }

            $type = PresentationType::from($decision);
            if (! in_array($type, PresentationType::decisions(), true)) {
                throw new InvalidArgumentException('Abstracts cannot be accepted as '.strtolower($type->label()).'.');
            }

            $this->accept($abstract, $type, $note);
        });

        $abstract->submitter->notify(new AbstractDecided($abstract->fresh()));
    }

    /** Ask the author for one round of revisions, keeping the current text for comparison. */
    public function requestRevision(AbstractSubmission $abstract, ?string $note, ?string $dueOn = null): void
    {
        if ($abstract->status !== AbstractStatus::UnderReview || ! $abstract->awaitsDecision()) {
            throw new InvalidArgumentException('Revisions can be requested once, after both reviews are in.');
        }

        $abstract->update([
            'status' => AbstractStatus::RevisionRequested,
            'revision_requested_at' => now(),
            'revision_due_on' => $dueOn ?? today()->addDays(config('review.revision_days')),
            'revision_note' => $note,
            'original_version' => $abstract->only(AbstractSubmission::REVISABLE),
        ]);

        $abstract->submitter->notify(new RevisionRequested($abstract->fresh()));
    }

    /** The committee gives the author more time. */
    public function extendRevision(AbstractSubmission $abstract, string $dueOn): void
    {
        if ($abstract->status !== AbstractStatus::RevisionRequested) {
            throw new InvalidArgumentException('This abstract is not waiting for a revision.');
        }

        $abstract->update(['revision_due_on' => $dueOn]);
    }

    /**
     * The author sends the revised version. It goes to the reviewers who did
     * not accept, as a second-round review.
     *
     * @param  array{title: string, background: string, methods: string, results: string, conclusions: string, keywords?: ?string, revision_response: string}  $data
     */
    public function submitRevision(AbstractSubmission $abstract, array $data): void
    {
        $assignments = DB::transaction(function () use ($abstract, $data) {
            $abstract = AbstractSubmission::lockForUpdate()->findOrFail($abstract->id)->load('reviews.reviewer');

            if ($abstract->status !== AbstractStatus::RevisionRequested) {
                throw new InvalidArgumentException('This abstract is not waiting for a revision.');
            }

            $abstract->update([
                'title' => trim($data['title']),
                'background' => trim($data['background']),
                'methods' => trim($data['methods']),
                'results' => trim($data['results']),
                'conclusions' => trim($data['conclusions']),
                'keywords' => $data['keywords'] ?? null,
                'revision_response' => trim($data['revision_response']),
                'revised_at' => now(),
                'status' => AbstractStatus::Revised,
            ]);

            return $abstract->revisionReviewers()->map(fn (ReviewAssignment $first) => $abstract->reviews()->create([
                'reviewer_id' => $first->reviewer_id,
                'round' => 2,
                'assigned_by' => $first->assigned_by,
                'due_on' => today()->addDays(config('review.second_round_days')),
            ]));
        });

        $assignments->each(fn (ReviewAssignment $assignment) => $assignment->reviewer->notify(new ReviewAssigned($assignment)));
    }

    private function accept(AbstractSubmission $abstract, PresentationType $type, ?string $note, bool $automatically = false): void
    {
        $abstract->update([
            'status' => AbstractStatus::Accepted,
            'decision_type' => $type,
            'decision_note' => $note,
            'decided_at' => now(),
            'accepted_automatically' => $automatically,
            'code' => $this->nextCode($abstract, $type),
        ]);
    }

    /** OR-HBR-01: presentation type, topic code, then the next number in that group. */
    private function nextCode(AbstractSubmission $abstract, PresentationType $type): string
    {
        $prefix = $type->codePrefix().'-'.$abstract->topic->code.'-';

        $taken = AbstractSubmission::where('edition_id', $abstract->edition_id)
            ->where('code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('code')
            ->map(fn (string $code) => (int) Str::afterLast($code, '-'))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($taken + 1), 2, '0', STR_PAD_LEFT);
    }
}
