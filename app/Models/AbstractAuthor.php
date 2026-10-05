<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbstractAuthor extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_presenter' => 'boolean'];
    }

    public function abstract(): BelongsTo
    {
        return $this->belongsTo(AbstractSubmission::class, 'abstract_id');
    }
}
