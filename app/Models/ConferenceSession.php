<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConferenceSession extends Model
{
    protected $fillable = [
        'name',
        'session_type',
        'subtheme',
        'presentation_type',
        'description',
        'room_location',
        'session_chair',
        'session_chair_email',
        'session_chair_id',
        'session_rapporteur',
        'session_rapporteur_email',
        'session_rapporteur_id',
        'speaker',
        'panelists',
        'schedule_days',
        'start_time',
        'end_time',
        'estimated_duration_minutes',
        'max_abstracts',
        'current_abstracts',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
        'admin_notes',
        'rapporteur_notes',
        'chair_notified_at',
        'rapporteur_notified_at',
        'chair_confirmed_at',
        'rapporteur_confirmed_at',
        'confirmation_token',
        'is_active',
    ];

    protected $casts = [
        'schedule_days' => 'array',
        'is_active' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'chair_notified_at' => 'datetime',
        'rapporteur_notified_at' => 'datetime',
        'chair_confirmed_at' => 'datetime',
        'rapporteur_confirmed_at' => 'datetime',
    ];

    // Relationships
    public function abstracts(): HasMany
    {
        return $this->hasMany(AbstractSubmission::class, 'session_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function speaker(): BelongsTo
    {
        return $this->belongsTo(Speaker::class);
    }

    public function chair(): BelongsTo
    {
        return $this->belongsTo(User::class, 'session_chair_id');
    }

    public function rapporteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'session_rapporteur_id');
    }

    // Accessor to ensure schedule_days is always an array
    public function getScheduleDaysAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    /**
     * Session types that warrant a scientific rapporteur report. Everything else
     * (breaks, meals, arrivals/registration, opening/closing, posters,
     * AGM, and miscellaneous "other" items) is excluded from reporting and coverage.
     */
    public const REPORTABLE_TYPES = ['plenary', 'panel', 'discussion', 'presentation', 'keynote', 'oral'];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Active scientific sessions a rapporteur is expected to report on.
     */
    public function scopeReportable($query)
    {
        return $query->where('is_active', true)
            ->whereIn('session_type', self::REPORTABLE_TYPES);
    }

    public function scopeBySubtheme($query, $subtheme)
    {
        return $query->where('subtheme', $subtheme);
    }

    public function scopeByPresentationType($query, $type)
    {
        return $query->where('presentation_type', $type);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    // Helper methods
    public function canAddAbstract(): bool
    {
        return $this->current_abstracts < $this->max_abstracts;
    }

    public function getAvailableSlots(): int
    {
        return $this->max_abstracts - $this->current_abstracts;
    }

    public function updateAbstractCount(): void
    {
        $this->current_abstracts = $this->abstracts()->count();
        $this->save();
    }

    public function getDurationInHours(): float
    {
        return $this->estimated_duration_minutes / 60;
    }

    public function getScheduleText(): string
    {
        if (!$this->schedule_days) {
            return 'No schedule set';
        }

        $days = implode(', ', $this->schedule_days);
        $time = '';

        if ($this->start_time && $this->end_time) {
            $time = " from {$this->start_time->format('H:i')} to {$this->end_time->format('H:i')}";
        }

        return "Days: {$days}{$time}";
    }

    /**
     * Get the primary date for sorting/grouping, safely handling non-date values like "Day 1"
     */
    public function getSafePrimaryDate(): ?string
    {
        if (empty($this->schedule_days) || empty($this->schedule_days[0])) {
            return null;
        }

        $value = trim((string) $this->schedule_days[0]);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $parsed = \Carbon\Carbon::createFromFormat('Y-m-d', $value);
            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get a display-friendly date or fallback to the raw label
     */
    public function getPrimaryDayLabel(): string
    {
        if (empty($this->schedule_days) || empty($this->schedule_days[0])) {
            return 'Unassigned';
        }

        $value = trim((string) $this->schedule_days[0]);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        try {
            $parsed = \Carbon\Carbon::createFromFormat('Y-m-d', $value);
            return $parsed->format('l, F j, Y');
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getStatusBadge(): string
    {
        $badges = [
            'draft' => '<span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">Draft</span>',
            'scheduled' => '<span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">Scheduled</span>',
            'ongoing' => '<span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Ongoing</span>',
            'completed' => '<span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">Completed</span>',
            'cancelled' => '<span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Cancelled</span>',
        ];

        return $badges[$this->status] ?? '<span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">Unknown</span>';
    }

    public function getCapacityText(): string
    {
        $percentage = $this->max_abstracts > 0 ? ($this->current_abstracts / $this->max_abstracts) * 100 : 0;
        $color = $percentage >= 80 ? 'text-red-600' : ($percentage >= 60 ? 'text-yellow-600' : 'text-green-600');

        return "<span class=\"{$color}\">{$this->current_abstracts}/{$this->max_abstracts}</span>";
    }

    public function isChairConfirmed(): bool
    {
        return !is_null($this->chair_confirmed_at);
    }

    public function isRapporteurConfirmed(): bool
    {
        return !is_null($this->rapporteur_confirmed_at);
    }
}
