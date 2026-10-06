<?php

namespace App\Models;

use App\Enums\AbstractStatus;
use App\Enums\PresentationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    protected function casts(): array
    {
        return [
            'status' => AbstractStatus::class,
            'preferred_type' => PresentationType::class,
            'decision_type' => PresentationType::class,
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
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

    /** Average rubric total (out of 100) across completed reviews. */
    public function averageScore(): ?float
    {
        $done = $this->reviews->filter->isComplete();

        return $done->isEmpty() ? null : round($done->avg(fn (ReviewAssignment $review) => $review->totalScore()), 1);
    }
}
