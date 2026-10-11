<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresentationFile extends Model
{
    protected $guarded = ['id'];

    public function abstract(): BelongsTo
    {
        return $this->belongsTo(AbstractSubmission::class, 'abstract_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sizeLabel(): string
    {
        return $this->bytes >= 1048576
            ? number_format($this->bytes / 1048576, 1).' MB'
            : max(1, (int) round($this->bytes / 1024)).' KB';
    }
}
