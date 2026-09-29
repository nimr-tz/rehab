<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewHistory extends Model
{
    protected $table = 'review_history';
    
    protected $fillable = [
        'abstract_submission_id',
        'reviewer_id',
        'reviewer_position',
        'action',
        'score',
        'comments',
        'metadata',
        'reason',
        'admin_user_id',
        'action_date'
    ];

    protected $casts = [
        'metadata' => 'array',
        'action_date' => 'datetime',
        'score' => 'decimal:2'
    ];

    public function abstract()
    {
        return $this->belongsTo(AbstractSubmission::class, 'abstract_submission_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
