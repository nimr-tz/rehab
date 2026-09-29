<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAttendance extends Model
{
    public const TYPE_USER = 'user';

    public const TYPE_GROUP_MEMBER = 'group_member';

    public const TYPE_ONSITE_VISITOR = 'onsite_visitor';

    protected $fillable = [
        'conference_session_id',
        'attendee_type',
        'attendee_id',
        'scanned_at',
        'scanned_by',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConferenceSession::class, 'conference_session_id');
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function attendee(): ?Model
    {
        return match ($this->attendee_type) {
            self::TYPE_USER => User::find($this->attendee_id),
            self::TYPE_GROUP_MEMBER => GroupMember::find($this->attendee_id),
            self::TYPE_ONSITE_VISITOR => OnsiteVisitor::find($this->attendee_id),
            default => null,
        };
    }
}
