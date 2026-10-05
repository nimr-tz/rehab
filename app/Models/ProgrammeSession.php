<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProgrammeSession extends Model
{
    protected $guarded = ['id'];

    public const KINDS = [
        'plenary' => 'Plenary',
        'parallel' => 'Parallel',
        'posters' => 'Posters',
        'panel' => 'Panel',
        'break' => 'Break',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function abstracts(): BelongsToMany
    {
        return $this->belongsToMany(AbstractSubmission::class, 'programme_session_abstract', 'programme_session_id', 'abstract_id')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? ucfirst($this->kind);
    }

    public function kindClasses(): string
    {
        return match ($this->kind) {
            'plenary' => 'text-ember-600',
            'parallel' => 'text-brand-700',
            'posters' => 'text-olive-700',
            'panel' => 'text-sun-700',
            default => 'text-ink-500',
        };
    }
}
