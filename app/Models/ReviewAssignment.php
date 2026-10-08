<?php

namespace App\Models;

use App\Enums\AbstractStatus;
use App\Enums\Recommendation;
use App\Support\Rubric;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewAssignment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'recommendation' => Recommendation::class,
            'technical_checks' => 'array',
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

    /**
     * The reviewer can still submit or change this review: its round is the
     * one under way and nothing has been decided.
     */
    public function isOpen(): bool
    {
        return match ($this->abstract->status) {
            AbstractStatus::Submitted, AbstractStatus::UnderReview => $this->round === 1,
            AbstractStatus::Revised => $this->round === 2,
            default => false,
        };
    }

    /** Rubric total out of 100, once the review is complete. */
    public function totalScore(): ?int
    {
        if (! $this->isComplete()) {
            return null;
        }

        return (int) collect(Rubric::fields())->sum(fn (string $field) => $this->{$field});
    }

    /** The rubric's verdict band for this review's total. */
    public function band(): ?array
    {
        return Rubric::band($this->totalScore());
    }
}
