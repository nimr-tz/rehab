<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A set of photos from the summit: a day, a session or an occasion such as the
 * gala dinner. Any photographer can add photos to any album of the edition.
 */
class Album extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'day' => 'date',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ProgrammeSession::class, 'programme_session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function publishedPhotos(): HasMany
    {
        return $this->photos()->published()->inShootingOrder();
    }

    /** A featured photo if there is one, otherwise the first published photo. */
    public function cover(): HasOne
    {
        return $this->hasOne(Photo::class)->ofMany(
            ['is_featured' => 'max', 'id' => 'min'],
            fn (Builder $query) => $query->whereNotNull('published_at'),
        );
    }

    /** The newest upload, for the album tile in the portal while nothing is published. */
    public function latestPhoto(): HasOne
    {
        return $this->hasOne(Photo::class)->latestOfMany();
    }

    /** Albums with at least one published photo. */
    public function scopeVisible(Builder $query): void
    {
        $query->whereHas('photos', fn (Builder $q) => $q->whereNotNull('published_at'));
    }

    public function scopeInProgrammeOrder(Builder $query): void
    {
        $query->orderByRaw('day is null')->orderBy('day')->orderBy('id');
    }

    /** The photographer who created the album can change it; admins can change any album. */
    public function isEditableBy(User $user): bool
    {
        return $user->hasRole(Role::Admin->value) || $this->created_by === $user->id;
    }

    public static function uniqueSlug(Edition $edition, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($edition->year.' '.$title) ?: (string) $edition->year;
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /** The photographers credited on the album, by first name. */
    public function credits(): string
    {
        return User::whereIn('id', $this->publishedPhotos()->reorder()->select('user_id')->distinct())
            ->orderBy('first_name')->get()->map->name->join(', ', ' and ');
    }
}
