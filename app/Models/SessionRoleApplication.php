<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionRoleApplication extends Model
{
    public const ROLE_CHAIR = 'chair';
    public const ROLE_RAPPORTEUR = 'rapporteur';

    /**
     * Topics an applicant can choose from.
     *
     * @return list<string>
     */
    public static function themes(): array
    {
        return array_keys(config('conference.subtheme_prefixes', []));
    }

    /**
     * Last moment to apply as a chair or rapporteur (app timezone).
     */
    public static function deadline(): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::parse(config('conference.session_role_deadline'), config('app.timezone'));
    }

    protected $fillable = [
        'user_id',
        'role_requested',
        'preferred_theme',
        'attendance_confirmed',
        'notes',
        'status',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'attendance_confirmed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role_requested) {
            self::ROLE_CHAIR => 'Session Chair',
            self::ROLE_RAPPORTEUR => 'Rapporteur',
            default => ucfirst((string) $this->role_requested),
        };
    }
}
