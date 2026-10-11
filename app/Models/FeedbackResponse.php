<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackResponse extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'ratings' => 'array',
            'comments' => 'array',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
