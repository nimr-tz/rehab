<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationCategory extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_student' => 'boolean',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function hasFee(): bool
    {
        return $this->amount !== null;
    }

    public function formattedFee(): string
    {
        return $this->hasFee() ? $this->currency.' '.number_format((float) $this->amount) : $this->currency.' —';
    }
}
