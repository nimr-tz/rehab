<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'abstract_submission_id',
        'email_type',
        'recipient_email',
        'subject',
        'status',
        'error_message',
        'sent_at',
        'opened_at',
        'clicked_at',
        'metadata'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Email statuses
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_BOUNCED = 'bounced';
    const STATUS_OPENED = 'opened';
    const STATUS_CLICKED = 'clicked';

    // Email types
    const TYPE_SUBMISSION_CONFIRMATION = 'submission_confirmation';
    const TYPE_REVIEWER_ASSIGNMENT = 'reviewer_assignment';
    const TYPE_STATUS_NOTIFICATION = 'status_notification';
    const TYPE_CODE_ASSIGNMENT = 'code_assignment';
    const TYPE_REVIEW_REMINDER = 'review_reminder';
    const TYPE_REVIEW_COMPLETION = 'review_completion';
    const TYPE_REVIEW_SUBMISSION = 'review_submission';
    const TYPE_BOTH_REVIEWS_COMPLETE = 'both_reviews_complete';
    const TYPE_PROGRESS_UPDATE = 'progress_update';
    const TYPE_REVIEWER_REVOCATION = 'reviewer_revocation';
    const TYPE_TEST_EMAIL = 'test_email';
    const TYPE_DRAFT_SAVED = 'draft_saved';
    const TYPE_ABSTRACT_DELETED = 'abstract_deleted';
    const TYPE_ABSTRACT_UPDATED = 'abstract_updated';
    const TYPE_SUBTHEME_CHANGED = 'subtheme_changed';
    const TYPE_ABSTRACT_WITHDRAWN = 'abstract_withdrawn';
    const TYPE_REVISION_REQUESTED = 'revision_requested';
    const TYPE_REVISION_REMINDER = 'revision_reminder';
    const TYPE_PRESENTATION_UPLOADED = 'presentation_uploaded';
    const TYPE_ACCEPTED_CLARIFICATION = 'accepted_clarification';
    const TYPE_SESSION_ASSIGNMENT = 'session_assignment';
    const TYPE_COMMITTEE_DECISION = 'committee_decision';
    const TYPE_WELCOME_NOTIFICATION = 'welcome_notification';
    const TYPE_STUDENT_ID_VERIFIED = 'student_id_verified';
    const TYPE_STUDENT_ID_REJECTED = 'student_id_rejected';
    const TYPE_PAYMENT_VERIFIED = 'payment_verified';
    const TYPE_CPD_REMINDER = 'cpd_reminder';
    const TYPE_CRITICAL_INCIDENT_RESOLVED = 'critical_incident_resolved';
    const TYPE_CERTIFICATE_READY = 'certificate_ready';
    const TYPE_PRESENTER_CERTIFICATE = 'presenter_certificate';

    /**
     * Build a preview payload for the activity-log modal from stored columns.
     */
    public function getPreviewPayloadAttribute(): array
    {
        $metadata = $this->metadata ?? [];

        // Build readable body lines from metadata keys we commonly store
        $bodyLines = [];
        $skip = ['ip', 'user_agent', 'tracking_id'];
        foreach ($metadata as $key => $value) {
            if (in_array($key, $skip) || is_array($value)) {
                continue;
            }
            $label = ucwords(str_replace('_', ' ', $key));
            $bodyLines[] = "{$label}: {$value}";
        }

        return [
            'subject'        => $this->subject,
            'recipient_name' => $this->user?->full_name,
            'recipient_email'=> $this->recipient_email,
            'email_type'     => str_replace('_', ' ', $this->email_type),
            'status'         => $this->status,
            'sent_at'        => $this->sent_at?->format('M d, Y H:i') ?? $this->created_at?->format('M d, Y H:i'),
            'error_message'  => $this->error_message,
            'abstract_id'    => $this->abstract_submission_id,
            'abstract_title' => $this->abstractSubmission?->title,
            'conference_code'=> $this->abstractSubmission?->conference_code,
            'body_lines'     => $bodyLines,
            'metadata'       => $metadata,
        ];
    }

    /**
     * Get the user that received this email
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the abstract submission related to this email
     */
    public function abstractSubmission()
    {
        return $this->belongsTo(AbstractSubmission::class);
    }

    /**
     * Scope for successful emails
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_OPENED, self::STATUS_CLICKED]);
    }

    /**
     * Scope for failed emails
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_BOUNCED]);
    }

    /**
     * Scope for email activity recorded today.
     */
    public function scopeToday($query)
    {
        $today = Carbon::today();

        return $query->where(function ($q) use ($today) {
            $q->whereDate('sent_at', $today)
                ->orWhere(function ($fallback) use ($today) {
                    $fallback->whereNull('sent_at')
                        ->whereDate('created_at', $today);
                });
        });
    }

    /**
     * Scope for email activity recorded this week.
     */
    public function scopeThisWeek($query)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        return $query->where(function ($q) use ($startOfWeek, $endOfWeek) {
            $q->whereBetween('sent_at', [$startOfWeek, $endOfWeek])
                ->orWhere(function ($fallback) use ($startOfWeek, $endOfWeek) {
                    $fallback->whereNull('sent_at')
                        ->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                });
        });
    }

    /**
     * Get delivery rate percentage
     */
    public static function getDeliveryRate($period = 'today')
    {
        $query = self::query();

        if ($period === 'today') {
            $query->today();
        } elseif ($period === 'week') {
            $query->thisWeek();
        }

        $total = $query->count();
        $successful = $query->successful()->count();

        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    /**
     * Get open rate percentage
     */
    public static function getOpenRate($period = 'today')
    {
        $query = self::query();

        if ($period === 'today') {
            $query->today();
        } elseif ($period === 'week') {
            $query->thisWeek();
        }

        $sent = $query->where('status', '!=', self::STATUS_FAILED)->count();
        $opened = $query->whereIn('status', [self::STATUS_OPENED, self::STATUS_CLICKED])->count();

        return $sent > 0 ? round(($opened / $sent) * 100, 2) : 0;
    }
}
