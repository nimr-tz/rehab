<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Certificate Model
 *
 * Tracks all issued certificates with support for:
 * - Full attendance certificates (all 3 days)
 * - Partial attendance/participation certificates (1-2 days)
 * - Oral presentation certificates
 * - Poster presentation certificates
 */
class Certificate extends Model
{
    use HasFactory;

    // Certificate Types
    public const TYPE_ATTENDANCE_FULL = 'attendance_full';

    public const TYPE_ATTENDANCE_PARTIAL = 'attendance_partial';

    public const TYPE_ORAL_PRESENTATION = 'oral_presentation';

    public const TYPE_POSTER_PRESENTATION = 'poster_presentation';

    // Type prefixes for certificate numbers
    public const TYPE_PREFIXES = [
        self::TYPE_ATTENDANCE_FULL => 'ATT',
        self::TYPE_ATTENDANCE_PARTIAL => 'PRT',
        self::TYPE_ORAL_PRESENTATION => 'ORL',
        self::TYPE_POSTER_PRESENTATION => 'PST',
    ];

    // Human-readable type names
    public const TYPE_LABELS = [
        self::TYPE_ATTENDANCE_FULL => 'Certificate of Attendance',
        self::TYPE_ATTENDANCE_PARTIAL => 'Certificate of Participation',
        self::TYPE_ORAL_PRESENTATION => 'Certificate of Oral Presentation',
        self::TYPE_POSTER_PRESENTATION => 'Certificate of Poster Presentation',
    ];

    protected $fillable = [
        'user_id',
        'group_member_id',
        'onsite_visitor_id',
        'holder_name',
        'type',
        'certificate_number',
        'abstract_submission_id',
        'attendance_days',
        'issued_at',
        'first_downloaded_at',
        'download_count',
        'last_verified_at',
        'verification_count',
        'revoked_at',
        'revoked_reason',
        'revoked_by',
        'metadata',
    ];

    protected $casts = [
        'attendance_days' => 'array',
        'metadata' => 'array',
        'issued_at' => 'datetime',
        'first_downloaded_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Official issue date for all certificates: the last day of the conference,
     * regardless of when the certificate record is created or downloaded.
     */
    public static function officialIssueDate(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse(
            config('conference.end_date', '2026-06-11'),
            config('conference.timezone')
        )->setTime(12, 0);
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function abstract(): BelongsTo
    {
        return $this->belongsTo(AbstractSubmission::class, 'abstract_submission_id');
    }

    public function groupMember(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class);
    }

    public function onsiteVisitor(): BelongsTo
    {
        return $this->belongsTo(OnsiteVisitor::class);
    }

    /**
     * The certificate holder as a render-ready object (title, full_name,
     * affiliation), regardless of whether the holder is a registered user
     * or a badge-only attendee (group member, walk-in visitor).
     */
    public function getHolderAttribute(): object
    {
        if ($this->user) {
            return (object) [
                'title' => $this->user->title,
                'full_name' => $this->user->full_name,
                'affiliation' => $this->user->affiliation,
            ];
        }

        $member = $this->groupMember ?? $this->onsiteVisitor;

        return (object) [
            'title' => null,
            'full_name' => $this->holder_name
                ?? $member?->full_name
                ?? $member?->effective_name
                ?? 'Unknown Holder',
            'affiliation' => $member?->institution,
        ];
    }

    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Check if certificate is revoked
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if certificate is valid (not revoked)
     */
    public function isValid(): bool
    {
        return ! $this->isRevoked();
    }

    /**
     * Get the human-readable certificate type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? 'Certificate';
    }

    /**
     * Get formatted attendance days string
     * e.g., "Day 1", "Days 1 & 2", "Days 1, 2 & 3"
     */
    public function getFormattedAttendanceDaysAttribute(): string
    {
        if (! $this->attendance_days || empty($this->attendance_days)) {
            return '';
        }

        $days = $this->attendance_days;
        sort($days);

        if (count($days) === 1) {
            return 'Day '.$days[0];
        }

        if (count($days) === 2) {
            return 'Days '.$days[0].' & '.$days[1];
        }

        $lastDay = array_pop($days);

        return 'Days '.implode(', ', $days).' & '.$lastDay;
    }

    /**
     * Get attendance dates based on conference config and attendance_days
     */
    public function getAttendanceDatesAttribute(): string
    {
        if (! $this->attendance_days || empty($this->attendance_days)) {
            return config('conference.display_dates', '');
        }

        $startDate = \Carbon\Carbon::parse(config('conference.start_date', '2026-06-09'));
        $days = $this->attendance_days;
        sort($days);

        if (count($days) === 1) {
            $date = $startDate->copy()->addDays($days[0] - 1);

            return $date->format('F j, Y');
        }

        // Check if consecutive
        $isConsecutive = true;
        for ($i = 1; $i < count($days); $i++) {
            if ($days[$i] !== $days[$i - 1] + 1) {
                $isConsecutive = false;
                break;
            }
        }

        if ($isConsecutive) {
            $firstDate = $startDate->copy()->addDays($days[0] - 1);
            $lastDate = $startDate->copy()->addDays(end($days) - 1);

            if ($firstDate->month === $lastDate->month) {
                return $firstDate->format('F j').'-'.$lastDate->format('j, Y');
            }

            return $firstDate->format('F j').' - '.$lastDate->format('F j, Y');
        }

        // Non-consecutive days
        $dateStrings = [];
        foreach ($days as $day) {
            $dateStrings[] = $startDate->copy()->addDays($day - 1)->format('F j');
        }
        $lastDate = array_pop($dateStrings);

        return implode(', ', $dateStrings).' & '.$lastDate.', '.$startDate->year;
    }

    /**
     * Check if this is a full attendance certificate
     */
    public function isFullAttendance(): bool
    {
        return $this->type === self::TYPE_ATTENDANCE_FULL;
    }

    /**
     * Check if this is a presenter certificate
     */
    public function isPresenterCertificate(): bool
    {
        return in_array($this->type, [
            self::TYPE_ORAL_PRESENTATION,
            self::TYPE_POSTER_PRESENTATION,
        ]);
    }

    /**
     * Record a download
     */
    public function recordDownload(array $metadata = []): void
    {
        $this->download_count++;

        if (! $this->first_downloaded_at) {
            $this->first_downloaded_at = now();
        }

        if ($metadata) {
            $existingMetadata = $this->metadata ?? [];
            $existingMetadata['downloads'][] = [
                'at' => now()->toIso8601String(),
                'ip' => $metadata['ip'] ?? null,
                'user_agent' => $metadata['user_agent'] ?? null,
            ];
            $this->metadata = $existingMetadata;
        }

        $this->save();
    }

    /**
     * Record a verification
     */
    public function recordVerification(array $metadata = []): void
    {
        $this->verification_count++;
        $this->last_verified_at = now();

        if ($metadata) {
            $existingMetadata = $this->metadata ?? [];
            $existingMetadata['verifications'][] = [
                'at' => now()->toIso8601String(),
                'ip' => $metadata['ip'] ?? null,
            ];
            $this->metadata = $existingMetadata;
        }

        $this->save();
    }

    /**
     * Revoke the certificate
     */
    public function revoke(int $revokedBy, string $reason): void
    {
        $this->revoked_at = now();
        $this->revoked_by = $revokedBy;
        $this->revoked_reason = $reason;
        $this->save();
    }

    /**
     * Generate a unique certificate number, e.g. RHS26-ATT-00001-7KQ2XP.
     *
     * The random suffix keeps numbers unguessable, so the public verification
     * page cannot be used to list every certificate holder.
     */
    public static function generateCertificateNumber(string $type): string
    {
        $prefix = self::TYPE_PREFIXES[$type] ?? 'CRT';
        $conference = strtoupper((string) config('conference.code', 'CONF'));
        $year = substr((string) config('conference.year', date('Y')), -2);

        $lastNumber = self::where('type', $type)->orderBy('id', 'desc')->value('certificate_number');
        $nextSeq = $lastNumber && preg_match('/-(\d{5})(?:-|$)/', $lastNumber, $matches)
            ? (int) $matches[1] + 1
            : 1;

        do {
            $number = $conference.$year.'-'.$prefix.'-'.str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT)
                .'-'.strtoupper(\Illuminate\Support\Str::random(6));
        } while (self::where('certificate_number', $number)->exists());

        return $number;
    }

    /**
     * Find certificate by verification code (public lookup)
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('certificate_number', strtoupper(trim($code)))->first();
    }

    /**
     * Scope: Only valid (non-revoked) certificates
     */
    public function scopeValid($query)
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * Scope: Only attendance certificates
     */
    public function scopeAttendance($query)
    {
        return $query->whereIn('type', [self::TYPE_ATTENDANCE_FULL, self::TYPE_ATTENDANCE_PARTIAL]);
    }

    /**
     * Scope: Only presenter certificates
     */
    public function scopePresenter($query)
    {
        return $query->whereIn('type', [self::TYPE_ORAL_PRESENTATION, self::TYPE_POSTER_PRESENTATION]);
    }
}
