<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbstractReviewerExclusion extends Model
{
    protected $fillable = [
        'abstract_submission_id',
        'reviewer_id',
        'created_by',
        'reason',
    ];

    public function abstract()
    {
        return $this->belongsTo(AbstractSubmission::class, 'abstract_submission_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
