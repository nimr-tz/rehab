<?php

namespace App\Services;

use App\Enums\AbstractStatus;
use App\Enums\AwardKind;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\AwardCategory;
use App\Models\AwardEntry;
use App\Models\AwardScore;
use App\Models\Edition;
use App\Models\User;
use App\Notifications\AwardWon;
use App\Support\AwardRubric;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Summit awards: categories, shortlists and nominations, judges' scores, the
 * committee's choice of winners, and the announcement.
 */
class AwardService
{
    /** Adds the suggested categories from config/awards.php that the edition does not have yet. */
    public function addSuggested(Edition $edition): int
    {
        $existing = $edition->awardCategories()->pluck('name')->map(fn (string $name) => Str::lower($name));
        $sort = (int) $edition->awardCategories()->max('sort');
        $added = 0;

        foreach (config('awards.suggested') as $suggestion) {
            if ($existing->contains(Str::lower($suggestion['name']))) {
                continue;
            }

            $edition->awardCategories()->create([
                'name' => $suggestion['name'],
                'description' => $suggestion['description'],
                'kind' => AwardKind::from($suggestion['kind']),
                'presentation_type' => $suggestion['presentation_type'] ?? null,
                'students_only' => $suggestion['students_only'] ?? false,
                'places' => $suggestion['places'],
                // Nominations close a month before the summit, when the dates are known.
                'nominations_close_on' => ($suggestion['nominations'] ?? false)
                    ? ($edition->start_date?->copy()->subMonth() ?? today()->addMonths(3))
                    : null,
                'sort' => $sort += 10,
            ]);
            $added++;
        }

        return $added;
    }

    /** Accepted abstracts that fit the category and are not on its shortlist yet, best reviewed first. */
    public function eligibleAbstracts(AwardCategory $category): Collection
    {
        if (! $category->isPresentation()) {
            return new Collection;
        }

        return AbstractSubmission::query()
            ->where('edition_id', $category->edition_id)
            ->where('status', AbstractStatus::Accepted)
            ->when($category->presentation_type, fn ($q, $type) => $q->where('decision_type', $type))
            ->when($category->students_only, fn ($q) => $q->whereHas('submitter.registrations', fn ($q) => $q
                ->where('edition_id', $category->edition_id)
                ->whereHas('category', fn ($q) => $q->where('is_student', true))))
            ->whereNotIn('id', $category->entries()->whereNotNull('abstract_id')->select('abstract_id'))
            ->with('topic', 'authors', 'reviews', 'edition')
            ->get()
            ->sortByDesc(fn (AbstractSubmission $abstract) => $abstract->averageScore() ?? -1)
            ->values();
    }

    /** Puts accepted abstracts on a presentation award's shortlist. The presenter is the finalist. */
    public function shortlist(AwardCategory $category, array $abstractIds): int
    {
        $this->ensureOpen($category);

        if (! $category->isPresentation()) {
            throw new InvalidArgumentException('Only presentation awards have a shortlist.');
        }

        $eligible = $this->eligibleAbstracts($category)->keyBy('id');
        $chosen = collect($abstractIds)->map(fn ($id) => (int) $id)->unique();

        if ($chosen->isEmpty() || $chosen->contains(fn (int $id) => ! $eligible->has($id))) {
            throw new InvalidArgumentException('Choose accepted abstracts that fit this award.');
        }

        DB::transaction(function () use ($category, $chosen, $eligible) {
            foreach ($chosen as $id) {
                $abstract = $eligible[$id];
                $presenter = $abstract->presenter();
                $category->entries()->create([
                    'abstract_id' => $abstract->id,
                    'user_id' => $this->accountFor($presenter?->email)?->id,
                    'name' => $presenter?->name ?? $abstract->submitter->name,
                    'institution' => $presenter?->affiliation,
                    'email' => $presenter?->email,
                ]);
            }
        });

        return $chosen->count();
    }

    /**
     * A participant nominates someone for an honour.
     *
     * @param  array{name: string, institution?: ?string, email?: ?string, citation: string}  $data
     */
    public function nominate(AwardCategory $category, User $nominator, array $data): AwardEntry
    {
        if (! $category->acceptsNominations()) {
            throw new InvalidArgumentException('Nominations for this award are closed.');
        }

        $name = trim($data['name']);
        $duplicate = $category->entries()->where('nominated_by', $nominator->id)->get()
            ->contains(fn (AwardEntry $entry) => Str::lower($entry->name) === Str::lower($name));

        if ($duplicate) {
            throw new InvalidArgumentException('You have already nominated '.$name.' for this award.');
        }

        return $this->addPerson($category, $data, $nominator);
    }

    /**
     * The committee names a recipient for an honour directly.
     *
     * @param  array{name: string, institution?: ?string, email?: ?string, citation: string}  $data
     */
    public function addRecipient(AwardCategory $category, array $data): AwardEntry
    {
        $this->ensureOpen($category);

        if ($category->isPresentation()) {
            throw new InvalidArgumentException('Presentation awards go to shortlisted abstracts.');
        }

        return $this->addPerson($category, $data);
    }

    public function removeEntry(AwardEntry $entry): void
    {
        $this->ensureOpen($entry->category);

        $entry->delete();
    }

    /** Only users with the judge role can judge. */
    public function syncJudges(AwardCategory $category, array $userIds): void
    {
        $this->ensureOpen($category);

        $judges = User::role(Role::Judge->value)->whereIn('id', $userIds)->pluck('id');

        if ($judges->count() !== count(array_unique($userIds))) {
            throw new InvalidArgumentException('Only people with the awards judge role can judge.');
        }

        $category->judges()->sync($judges);
    }

    public function canJudge(AwardEntry $entry, User $judge): bool
    {
        $category = $entry->category;

        return $category->isPresentation()
            && $category->judges()->whereKey($judge->id)->exists()
            && ! $entry->conflictsWith($judge);
    }

    /**
     * Records or updates a judge's scores for a finalist.
     *
     * @param  array<string, int|string>  $points  criterion => points
     */
    public function score(AwardEntry $entry, User $judge, array $points, ?string $comments): AwardScore
    {
        $this->ensureOpen($entry->category);

        if (! $this->canJudge($entry, $judge)) {
            throw new InvalidArgumentException('You cannot score this finalist.');
        }

        $scores = [];
        foreach (array_keys(AwardRubric::criteria()) as $criterion) {
            $value = filter_var($points[$criterion] ?? null, FILTER_VALIDATE_INT);
            if ($value === false || $value < 1 || $value > AwardRubric::perCriterion()) {
                throw new InvalidArgumentException('Score every criterion from 1 to '.AwardRubric::perCriterion().'.');
            }
            $scores[$criterion] = $value;
        }

        return AwardScore::updateOrCreate(
            ['award_entry_id' => $entry->id, 'judge_id' => $judge->id],
            ['scores' => $scores, 'total' => array_sum($scores), 'comments' => filled($comments) ? trim($comments) : null],
        );
    }

    /**
     * Entries with their average score, best first. Unscored entries come last.
     *
     * @return Collection<int, AwardEntry>
     */
    public function standings(AwardCategory $category): Collection
    {
        return $category->entries()
            ->with('scores.judge', 'abstract.topic', 'abstract.authors', 'abstract.sessions', 'nominator', 'user')
            ->get()
            ->sortBy([
                fn (AwardEntry $a, AwardEntry $b) => ($b->averageScore() ?? -1) <=> ($a->averageScore() ?? -1),
                fn (AwardEntry $a, AwardEntry $b) => $a->id <=> $b->id,
            ])
            ->values();
    }

    /**
     * The committee's choice: entry id => place (1 to the category's places), or empty for no place.
     *
     * @param  array<int|string, int|string|null>  $places
     */
    public function choosePlaces(AwardCategory $category, array $places): void
    {
        $this->ensureOpen($category);

        $chosen = collect($places)->filter(fn ($place) => filled($place))->map(fn ($place) => (int) $place);
        $entryIds = $category->entries()->pluck('id');

        if ($chosen->keys()->contains(fn ($id) => ! $entryIds->contains((int) $id))) {
            throw new InvalidArgumentException('Choose places only for entries in this award.');
        }
        if ($chosen->contains(fn (int $place) => $place < 1 || $place > $category->places)) {
            throw new InvalidArgumentException('This award has '.$category->places.' '.Str::plural('place', $category->places).'.');
        }
        if ($chosen->duplicates()->isNotEmpty()) {
            throw new InvalidArgumentException('Each place can go to only one entry.');
        }

        DB::transaction(function () use ($category, $chosen) {
            $category->entries()->update(['place' => null]);
            foreach ($chosen as $id => $place) {
                $category->entries()->whereKey($id)->update(['place' => $place]);
            }
        });
    }

    /** Publishes the winners on the public awards page and tells each winner. */
    public function announce(AwardCategory $category): void
    {
        $this->ensureOpen($category);

        $winners = $category->winners()->with('user', 'abstract.submitter')->get();
        if ($winners->isEmpty()) {
            throw new InvalidArgumentException('Choose the winners before announcing them.');
        }

        $category->update(['announced_at' => now()]);

        foreach ($winners as $winner) {
            ($winner->user ?? $winner->abstract?->submitter)?->notify(new AwardWon($winner));
        }
    }

    /** Takes the winners off the public page so the committee can correct them. */
    public function withdrawAnnouncement(AwardCategory $category): void
    {
        $category->update(['announced_at' => null]);
    }

    /** @param array{name: string, institution?: ?string, email?: ?string, citation: string} $data */
    private function addPerson(AwardCategory $category, array $data, ?User $nominator = null): AwardEntry
    {
        $email = filled($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null;

        return $category->entries()->create([
            'name' => trim($data['name']),
            'institution' => filled($data['institution'] ?? null) ? trim($data['institution']) : null,
            'email' => $email,
            'user_id' => $this->accountFor($email)?->id,
            'citation' => trim($data['citation']),
            'nominated_by' => $nominator?->id,
        ]);
    }

    private function accountFor(?string $email): ?User
    {
        return $email ? User::where('email', Str::lower($email))->first() : null;
    }

    private function ensureOpen(AwardCategory $category): void
    {
        if ($category->isAnnounced()) {
            throw new InvalidArgumentException('The winners have been announced. Withdraw the announcement to make changes.');
        }
    }
}
