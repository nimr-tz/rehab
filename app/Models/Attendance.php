<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'group_member_id',
        'onsite_visitor_id',
        'day',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'day' => 'integer',
    ];

    /**
     * The user who attended
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The group member who attended
     */
    public function groupMember(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class);
    }

    public function onsiteVisitor(): BelongsTo
    {
        return $this->belongsTo(OnsiteVisitor::class);
    }

    /**
     * Check if a group member has attendance for a specific day
     */
    public static function hasGroupMemberAttendance(int $groupMemberId, int $day): bool
    {
        return static::where('group_member_id', $groupMemberId)->where('day', $day)->exists();
    }

    /**
     * Record attendance for a group member
     */
    public static function recordGroupMemberAttendance(int $groupMemberId, int $day, ?int $checkedInBy = null): self
    {
        return static::create([
            'group_member_id' => $groupMemberId,
            'day' => $day,
            'checked_in_at' => now(config('conference.timezone', config('app.timezone'))),
            'checked_in_by' => $checkedInBy,
        ]);
    }

    /**
     * The admin who checked them in
     */
    public function checkedInByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /**
     * Get the day label
     */
    public function getDayLabelAttribute(): string
    {
        return match($this->day) {
            0 => 'Demo Day',
            1 => 'Day 1',
            2 => 'Day 2',
            3 => 'Day 3',
            default => 'Day ' . $this->day,
        };
    }

    public function getAttendeeTypeAttribute(): string
    {
        if ($this->group_member_id) {
            return 'group_member';
        }

        if ($this->onsite_visitor_id) {
            return 'onsite_visitor';
        }

        return 'user';
    }

    public function getAttendeeModelAttribute()
    {
        return $this->user
            ?? $this->groupMember
            ?? $this->onsiteVisitor;
    }

    public function getAttendeeNameAttribute(): string
    {
        return $this->attendee_model?->full_name ?? 'Unknown Attendee';
    }

    public function getAttendeeEmailAttribute(): ?string
    {
        return $this->attendee_model?->email;
    }

    public function getAttendeeAffiliationAttribute(): string
    {
        return $this->user?->affiliation
            ?? $this->groupMember?->institution
            ?? $this->onsiteVisitor?->institution
            ?? '-';
    }

    public function getAttendeeInitialsAttribute(): string
    {
        return $this->attendee_model?->initials ?? 'NA';
    }

    public static function hasOnsiteVisitorAttendance(int $visitorId, int $day): bool
    {
        return static::where('onsite_visitor_id', $visitorId)->where('day', $day)->exists();
    }

    public static function recordOnsiteVisitorAttendance(int $visitorId, int $day, ?int $checkedInBy = null): self
    {
        return static::create([
            'onsite_visitor_id' => $visitorId,
            'day' => $day,
            'checked_in_at' => now(config('conference.timezone', config('app.timezone'))),
            'checked_in_by' => $checkedInBy,
        ]);
    }

    /**
     * Check if a user has attendance for a specific day
     */
    public static function hasAttendance(int $userId, int $day): bool
    {
        return static::where('user_id', $userId)->where('day', $day)->exists();
    }

    /**
     * Record attendance for a user
     */
    public static function recordAttendance(int $userId, int $day, ?int $checkedInBy = null): self
    {
        return static::create([
            'user_id' => $userId,
            'day' => $day,
            'checked_in_at' => now(config('conference.timezone', config('app.timezone'))),
            'checked_in_by' => $checkedInBy,
        ]);
    }
}
