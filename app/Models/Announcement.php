<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'is_urgent',
        'category',
    ];

    protected $casts = [
        'is_urgent' => 'boolean',
    ];
}
