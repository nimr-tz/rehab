<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevisionHistory extends Model
{
    use HasFactory;

    protected $table = 'revision_history';

    protected $fillable = [
        'abstract_submission_id',
        'action',
        'user_id',
        'revision_round',
        'feedback',
        'author_response',
        'admin_notes',
        'revision_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the abstract submission this history belongs to
     */
    public function abstractSubmission()
    {
        return $this->belongsTo(AbstractSubmission::class);
    }

    /**
     * Get the user who performed this action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get history for a specific abstract
     */
    public function scopeForAbstract($query, $abstractId)
    {
        return $query->where('abstract_submission_id', $abstractId);
    }

    /**
     * Scope to get history for a specific revision round
     */
    public function scopeForRound($query, $round)
    {
        return $query->where('revision_round', $round);
    }

    /**
     * Get formatted action description
     */
    public function getActionDescriptionAttribute()
    {
        $descriptions = [
            'revision_requested' => 'Revision requested',
            'revision_submitted' => 'Revision submitted by author',
            'admin_approved' => 'Revision approved by admin',
            'admin_rejected' => 'Revision rejected by admin',
            'sent_to_reviewers' => 'Sent to reviewers for final evaluation',
            'reviewer_completed' => 'Reviewer completed final evaluation',
            'final_accepted' => 'Final acceptance after revision',
            'final_rejected' => 'Final rejection after revision',
        ];

        return $descriptions[$this->action] ?? ucwords(str_replace('_', ' ', $this->action));
    }

    /**
     * Create a new revision history entry
     */
    public static function createEntry(array $data)
    {
        return self::create([
            'abstract_submission_id' => $data['abstract_id'],
            'action' => $data['action'],
            'user_id' => $data['user_id'] ?? null,
            'revision_round' => $data['round'] ?? 1,
            'feedback' => $data['feedback'] ?? null,
            'author_response' => $data['author_response'] ?? null,
            'admin_notes' => $data['admin_notes'] ?? null,
            'revision_type' => $data['revision_type'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }
}
