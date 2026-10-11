<?php

namespace App\Models;

use App\Enums\Audience;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'audience' => Audience::class,
            'queued_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    public function isDraft(): bool
    {
        return ! $this->queued_at;
    }

    public function isSending(): bool
    {
        return $this->queued_at && ! $this->finished_at;
    }

    /** The body's paragraphs, split on blank lines. */
    public function paragraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', trim($this->body)))));
    }
}
