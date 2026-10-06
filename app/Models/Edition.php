<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'abstract_deadline' => 'date',
            'review_deadline' => 'date',
            'session_role_deadline' => 'date',
            'presentation_deadline' => 'date',
            'registration_open' => 'boolean',
            'abstracts_open' => 'boolean',
            'is_current' => 'boolean',
        ];
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(RegistrationCategory::class)->orderBy('sort');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('sort');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function abstracts(): HasMany
    {
        return $this->hasMany(AbstractSubmission::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ProgrammeSession::class)->orderBy('starts_at');
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function awardCategories(): HasMany
    {
        return $this->hasMany(AwardCategory::class)->ordered();
    }

    /** Abstract submission is open when switched on and the deadline has not passed. */
    public function acceptsAbstracts(): bool
    {
        return $this->abstracts_open
            && (! $this->abstract_deadline || today()->lte($this->abstract_deadline));
    }

    public function shortYear(): string
    {
        return substr((string) $this->year, -2);
    }
}
