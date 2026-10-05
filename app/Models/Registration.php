<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'amount' => 'decimal:2',
            'needs_invitation_letter' => 'boolean',
            'confirmed_at' => 'datetime',
            'badge_printed_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RegistrationCategory::class, 'registration_category_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isConfirmed(): bool
    {
        return $this->status === RegistrationStatus::Confirmed;
    }

    /** A new payment can be submitted unless one is pending or the fee is settled. */
    public function canSubmitPayment(): bool
    {
        return $this->status === RegistrationStatus::PendingPayment
            && ! $this->payments()->where('status', PaymentStatus::Submitted)->exists();
    }

    public function formattedAmount(): string
    {
        return $this->currency.' '.number_format((float) $this->amount);
    }

    public function displayName(): string
    {
        return $this->badge_name ?: $this->user->name;
    }
}
