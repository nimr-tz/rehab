<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OnsiteVisitor extends Model
{
    protected $fillable = [
        'name',
        'print_name',
        'institution',
        'badge_category',
        'notes',
        'qr_token',
        'badge_printed',
        'badge_printed_at',
        'created_by',
    ];

    protected $casts = [
        'badge_printed' => 'boolean',
        'badge_printed_at' => 'datetime',
    ];

    public static array $categories = [
        'invitee' => 'Invitee',
        'vip'     => 'VIP',
        'guest'   => 'Guest',
        'media'   => 'Media',
        'staff'   => 'Staff',
        'speaker' => 'Speaker',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attendance::class, 'onsite_visitor_id');
    }

    public function getOrCreateQrToken(): string
    {
        if (!$this->qr_token) {
            $this->update(['qr_token' => 'ONSITE-' . strtoupper(Str::random(12))]);
        }

        return $this->qr_token;
    }

    public function markBadgePrinted(): void
    {
        $this->update([
            'badge_printed' => true,
            'badge_printed_at' => now(),
        ]);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::$categories[$this->badge_category] ?? ucfirst($this->badge_category);
    }

    public function getEffectiveNameAttribute(): string
    {
        return $this->print_name ?? $this->name;
    }

    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', $this->effective_name, -1, PREG_SPLIT_NO_EMPTY))
            ->take(2)
            ->map(fn($p) => strtoupper(substr($p, 0, 1)))
            ->implode('');
    }
}

