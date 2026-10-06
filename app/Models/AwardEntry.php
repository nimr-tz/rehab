<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A finalist for a presentation award (a shortlisted abstract, presented by
 * `name`) or a nominee for an honour.
 */
class AwardEntry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['place' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AwardCategory::class, 'award_category_id');
    }

    public function abstract(): BelongsTo
    {
        return $this->belongsTo(AbstractSubmission::class, 'abstract_id');
    }

    /** The recipient's portal account, when they have one. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nominator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AwardScore::class);
    }

    /** Placed by the committee, and announced. */
    public function isWinner(): bool
    {
        return $this->place !== null && $this->category->isAnnounced();
    }

    /** The recipient, or the author who submitted the abstract, sees the entry as theirs. */
    public function belongsToUser(User $user): bool
    {
        return $this->user_id === $user->id || ($this->abstract && $this->abstract->user_id === $user->id);
    }

    /** Judges do not score work they wrote or co-wrote. */
    public function conflictsWith(User $judge): bool
    {
        if (! $this->abstract) {
            return false;
        }

        $this->abstract->loadMissing('authors');

        return $this->abstract->user_id === $judge->id
            || $this->abstract->authors->contains(fn (AbstractAuthor $author) => $author->email && Str::lower($author->email) === Str::lower($judge->email));
    }

    /** Average judges' total, out of AwardRubric::max(). */
    public function averageScore(): ?float
    {
        return $this->scores->isEmpty() ? null : round($this->scores->avg('total'), 1);
    }

    /** "AW27-0042", printed on the certificate. */
    public function certificateNumber(): string
    {
        return 'AW'.$this->category->edition->shortYear().'-'.Str::padLeft((string) $this->id, 4, '0');
    }
}
