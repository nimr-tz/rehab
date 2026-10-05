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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

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
        if (! $abstract->status->isEditable()) {
            throw new InvalidArgumentException('This abstract can no longer be withdrawn.');
        }

        $abstract->update(['status' => AbstractStatus::Withdrawn]);
    }

    /** Reviewers who may review this abstract: not an author, not already assigned. */
    public function eligibleReviewers(AbstractSubmission $abstract)
    {
        $authorEmails = $abstract->authors->pluck('email')->filter()->push($abstract->submitter->email)->map(fn ($e) => Str::lower($e));
        $assigned = $abstract->reviews->pluck('reviewer_id');

        return User::role('reviewer')
            ->withCount(['reviewAssignments as open_reviews_count' => fn ($query) => $query->whereNull('completed_at')])
            ->orderBy('last_name')
            ->get()
            ->reject(fn (User $reviewer) => $reviewer->id === $abstract->user_id
                || $authorEmails->contains(Str::lower($reviewer->email))
                || $assigned->contains($reviewer->id))
            ->values();
    }

    public function assign(AbstractSubmission $abstract, User $reviewer, User $by, ?string $dueOn = null): ReviewAssignment
    {
        if (! $this->eligibleReviewers($abstract)->contains('id', $reviewer->id)) {
            throw new InvalidArgumentException('This reviewer cannot review this abstract.');
        }

        if (! in_array($abstract->status, [AbstractStatus::Submitted, AbstractStatus::UnderReview], true)) {
            throw new InvalidArgumentException('Only submitted abstracts can be sent for review.');
        }

        $assignment = DB::transaction(function () use ($abstract, $reviewer, $by, $dueOn) {
            $assignment = $abstract->reviews()->create([
                'reviewer_id' => $reviewer->id,
                'assigned_by' => $by->id,
                'due_on' => $dueOn ?? $abstract->edition->review_deadline,
            ]);

            $abstract->update(['status' => AbstractStatus::UnderReview]);

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
    }

    /**
     * @param  array{score_relevance: int, score_originality: int, score_methods: int, score_clarity: int, recommendation: string, comments_for_author: string, comments_for_committee?: ?string}  $data
     */
    public function review(ReviewAssignment $assignment, array $data): void
    {
        if (in_array($assignment->abstract->status, [AbstractStatus::Accepted, AbstractStatus::Rejected, AbstractStatus::Withdrawn], true)) {
            throw new InvalidArgumentException('A decision has already been made on this abstract.');
        }

        $assignment->update([
            'score_relevance' => $data['score_relevance'],
            'score_originality' => $data['score_originality'],
            'score_methods' => $data['score_methods'],
            'score_clarity' => $data['score_clarity'],
            'recommendation' => Recommendation::from($data['recommendation']),
            'comments_for_author' => $data['comments_for_author'],
            'comments_for_committee' => $data['comments_for_committee'] ?? null,
            'completed_at' => $assignment->completed_at ?? now(),
        ]);
    }

    /** Accept as oral or poster (assigning the conference code), or reject. */
    public function decide(AbstractSubmission $abstract, string $decision, ?string $note): void
    {
        if (! in_array($abstract->status, [AbstractStatus::Submitted, AbstractStatus::UnderReview], true)) {
            throw new InvalidArgumentException('This abstract already has a decision.');
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
            $abstract->update([
                'status' => AbstractStatus::Accepted,
                'decision_type' => $type,
                'decision_note' => $note,
                'decided_at' => now(),
                'code' => $this->nextCode($abstract, $type),
            ]);
        });

        $abstract->submitter->notify(new AbstractDecided($abstract->fresh()));
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
