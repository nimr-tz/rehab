<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Photo extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function photographer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    public function scopeInShootingOrder(Builder $query): void
    {
        $query->orderByRaw('taken_at is null')->orderBy('taken_at')->orderBy('id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /** Photographers manage their own photos; admins manage every photo. */
    public function isManageableBy(?User $user): bool
    {
        return $user !== null
            && ($user->hasRole(Role::Admin->value) || ($this->user_id === $user->id && $user->hasRole(Role::Photographer->value)));
    }

    public function url(string $size = 'thumb'): string
    {
        return route('gallery.photos.show', ['photo' => $this, 'size' => $size]);
    }

    public function downloadUrl(): string
    {
        return route('gallery.photos.download', $this);
    }

    /** "rehab-summit-2027-opening-ceremony-142.jpg" */
    public function downloadName(): string
    {
        $album = $this->album;
        $extension = $this->mime === 'image/png' ? 'png' : 'jpg';

        return Str::slug($album->edition->short_name.' '.$album->title).'-'.$this->id.'.'.$extension;
    }

    /** Width and height of the lightbox copy. @return array{0: int, 1: int} */
    public function displaySize(): array
    {
        $scale = min(1, config('gallery.display.size') / max($this->width, $this->height));

        return [max(1, (int) round($this->width * $scale)), max(1, (int) round($this->height * $scale))];
    }

    public function ratio(): float
    {
        return $this->width / max(1, $this->height);
    }

    /** "Opening ceremony · Photo: Neema Mushi" */
    public function caption(): string
    {
        return $this->album->title.($this->photographer ? ' · Photo: '.$this->photographer->name : '');
    }
}
