<?php

namespace App\Models;

use App\Enums\AwardKind;
use App\Enums\PresentationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An award at one edition of the summit, such as Best Poster. The committee
 * chooses up to three places, then announces the winners.
 */
class AwardCategory extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'kind' => AwardKind::class,
            'presentation_type' => PresentationType::class,
            'students_only' => 'boolean',
            'places' => 'integer',
            'nominations_close_on' => 'date',
            'announced_at' => 'datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AwardEntry::class);
    }

    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'award_category_judge')->orderBy('last_name');
    }

    /** Places 1 to 3, in order. */
    public function winners(): HasMany
    {
        return $this->entries()->whereNotNull('place')->orderBy('place');
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('id');
    }

    public function scopeAnnounced(Builder $query): void
    {
        $query->whereNotNull('announced_at');
    }

    public function isPresentation(): bool
    {
        return $this->kind === AwardKind::Presentation;
    }

    public function isAnnounced(): bool
    {
        return $this->announced_at !== null;
    }

    /** Participants can nominate people for an honour until its closing date. */
    public function acceptsNominations(): bool
    {
        return $this->kind === AwardKind::Honour
            && ! $this->isAnnounced()
            && $this->nominations_close_on
            && today()->lte($this->nominations_close_on);
    }

    /** "Oral presentations · students only", or who decides an honour. */
    public function eligibility(): string
    {
        if (! $this->isPresentation()) {
            return $this->nominations_close_on ? 'Nominated by participants' : 'Chosen by the organising committee';
        }

        $type = match ($this->presentation_type) {
            PresentationType::Oral => 'Oral presentations',
            PresentationType::Poster => 'Posters',
            default => PresentationType::postersEnabled() ? 'Oral presentations and posters' : 'Oral presentations',
        };

        return $type.($this->students_only ? ' by students' : '');
    }

    /** "First place", or "Winner" when there is only one. */
    public function placeLabel(int $place): string
    {
        if ($this->places === 1) {
            return 'Winner';
        }

        return match ($place) {
            1 => 'First place',
            2 => 'Second place',
            default => 'Third place',
        };
    }

    /**
     * Scores entered, and scores expected: every assigned judge scores every
     * finalist, except finalists they wrote or co-wrote.
     *
     * @return array{done: int, expected: int}
     */
    public function scoringProgress(): array
    {
        $this->loadMissing('judges', 'entries.scores', 'entries.abstract.authors');
        $done = 0;
        $expected = 0;

        foreach ($this->entries as $entry) {
            foreach ($this->judges as $judge) {
                if (! $entry->conflictsWith($judge)) {
                    $expected++;
                    $done += $entry->scores->contains('judge_id', $judge->id) ? 1 : 0;
                }
            }
        }

        return ['done' => $done, 'expected' => $expected];
    }

    /** Where the award stands, for the committee: [key, label, tone]. */
    public function stage(): array
    {
        $this->loadMissing('entries');
        $decided = $this->entries->whereNotNull('place')->isNotEmpty();

        return match (true) {
            $this->isAnnounced() => ['announced', 'Announced', 'success'],
            $decided => ['decided', 'Winners chosen', 'info'],
            $this->acceptsNominations() => ['nominations', 'Nominations open', 'info'],
            $this->entries->isEmpty() => $this->isPresentation() ? ['shortlisting', 'Shortlisting', 'neutral'] : ['deciding', 'Choose a recipient', 'warning'],
            $this->isPresentation() && $this->scoringIncomplete() => ['judging', 'Judging', 'warning'],
            default => ['deciding', 'Ready to decide', 'warning'],
        };
    }

    private function scoringIncomplete(): bool
    {
        ['done' => $done, 'expected' => $expected] = $this->scoringProgress();

        return $expected === 0 || $done < $expected;
    }
}
