<?php

namespace App\Models;

use App\Enums\Recommendation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewAssignment extends Model
{
    protected $guarded = ['id'];

    /** Each scored 1 to 5, for a total out of 20. */
    public const CRITERIA = [
        'score_relevance' => 'Relevance to the summit theme',
        'score_originality' => 'Originality',
        'score_methods' => 'Methods and evidence',
        'score_clarity' => 'Clarity of writing',
    ];

    protected function casts(): array
    {
        return [
            'recommendation' => Recommendation::class,
            'due_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function abstract(): BelongsTo
    {
        return $this->belongsTo(AbstractSubmission::class, 'abstract_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    public function totalScore(): ?int
    {
        if (! $this->isComplete()) {
            return null;
        }

        return (int) collect(array_keys(self::CRITERIA))->sum(fn (string $field) => $this->{$field});
    }
}
