<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedSessionRolePerson extends Model
{
    protected $fillable = [
        'source_sheet',
        'subtheme',
        'subtheme_key',
        'role',
        'source_name',
        'normalized_name',
        'institution',
        'matched_user_id',
        'match_confidence',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function matchedUser()
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
