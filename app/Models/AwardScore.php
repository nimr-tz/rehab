<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One judge's scores for one finalist. */
class AwardScore extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'total' => 'integer',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(AwardEntry::class, 'award_entry_id');
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }
}
