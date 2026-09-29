<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionWindowSetting extends Model
{
    protected $fillable = [
        'override_until',
        'updated_by',
    ];

    protected $casts = [
        'override_until' => 'datetime',
    ];

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
