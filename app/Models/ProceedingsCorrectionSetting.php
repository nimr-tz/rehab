<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProceedingsCorrectionSetting extends Model
{
    protected $fillable = [
        'is_open',
        'closes_at',
        'updated_by',
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'closes_at' => 'datetime',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
