<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $guarded = ['id'];

    /** The four figure colours of the logo, cycled by position. */
    private const ACCENTS = ['ember', 'coral', 'olive', 'sun'];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function abstracts(): HasMany
    {
        return $this->hasMany(AbstractSubmission::class);
    }

    public function accent(): string
    {
        return self::ACCENTS[$this->sort % count(self::ACCENTS)];
    }

    /** Chip classes, written out in full so Tailwind finds them. */
    public function chipClasses(): string
    {
        return match ($this->accent()) {
            'ember' => 'bg-ember-50 text-ember-700',
            'coral' => 'bg-coral-50 text-coral-700',
            'olive' => 'bg-olive-50 text-olive-700',
            'sun' => 'bg-sun-50 text-sun-700',
        };
    }

    public function borderClass(): string
    {
        return match ($this->accent()) {
            'ember' => 'border-ember-500',
            'coral' => 'border-coral-400',
            'olive' => 'border-olive-700',
            'sun' => 'border-sun-400',
        };
    }
}
