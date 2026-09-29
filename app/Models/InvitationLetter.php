<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvitationLetter extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'passport_name',
        'passport_number',
        'nationality',
        'date_of_birth',
        'institute',
        'status',
        'agreed_to_terms',
        'rejection_reason',
        'downloaded_at',
        'download_count',
        'issued_at',
        'issued_by',
        'file_path',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'downloaded_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
