<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class RapporteurReport extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_NEEDS_REVISION = 'needs_revision';
    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'user_id',
        'conference_session_id',
        'subtheme',
        'rapporteur2_user_id',
        'presentations',
        'discussion_questions',
        'areas_of_agreement',
        'areas_of_debate',
        'follow_up_issues',
        'scientific_message_1',
        'scientific_message_2',
        'scientific_message_3',
        'most_important_finding',
        'evidence_nature',
        'evidence_status',
        'important_method',
        'main_limitation',
        'recommendations',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'approved_at',
        'chief_feedback',
        'review_history',
    ];

    protected $casts = [
        'presentations'        => 'array',
        'discussion_questions' => 'array',
        'evidence_nature'      => 'array',
        'recommendations'      => 'array',
        'review_history'       => 'array',
        'submitted_at'         => 'datetime',
        'reviewed_at'          => 'datetime',
        'approved_at'          => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rapporteur2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rapporteur2_user_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConferenceSession::class, 'conference_session_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function needsRevision(): bool
    {
        return $this->status === self::STATUS_NEEDS_REVISION;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * True while the author is still allowed to edit their own report.
     */
    public function isEditableByAuthor(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_NEEDS_REVISION], true);
    }

    /**
     * Human label for the current status.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT          => 'Draft',
            self::STATUS_SUBMITTED      => 'Submitted',
            self::STATUS_UNDER_REVIEW   => 'Under Review',
            self::STATUS_NEEDS_REVISION => 'Needs Revision',
            self::STATUS_APPROVED       => 'Approved',
            default                     => ucfirst((string) $this->status),
        };
    }

    /**
     * Append an entry to the review audit trail and persist it.
     */
    public function recordHistory(string $action, ?string $note = null, array $extra = []): void
    {
        $history = $this->review_history ?? [];

        $history[] = array_merge([
            'action'    => $action,
            'note'      => $note,
            'by_id'     => Auth::id(),
            'by_name'   => trim((Auth::user()->title ?? '') . ' ' . (Auth::user()->first_name ?? '') . ' ' . (Auth::user()->last_name ?? '')) ?: (Auth::user()->email ?? 'System'),
            'at'        => now()->toIso8601String(),
        ], $extra);

        $this->review_history = $history;
    }
}
