<?php

namespace App\Models;

use App\Enums\AbstractStatus;
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
            'waived_amount' => 'decimal:2',
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
            && ! $this->payments()->whereIn('status', [PaymentStatus::Submitted, PaymentStatus::Pending])->exists();
    }

    /** An M-Pesa payment still waiting for the participant's PIN or M-Pesa's answer. */
    public function pendingGatewayPayment(): ?Payment
    {
        return $this->payments()->where('status', PaymentStatus::Pending)->latest('id')->first();
    }

    public function waivers(): HasMany
    {
        return $this->hasMany(FeeWaiver::class)->latest('id');
    }

    public function activeWaiver(): HasOne
    {
        return $this->hasOne(FeeWaiver::class)->whereNull('revoked_at')->latestOfMany();
    }

    /** The fee of the category, before any waiver. */
    public function formattedAmount(): string
    {
        return $this->currency.' '.number_format((float) $this->amount);
    }

    /** What the participant still has to pay: the fee less any waiver. */
    public function amountDue(): float
    {
        return max(0, round((float) $this->amount - (float) $this->waived_amount, 2));
    }

    public function formattedDue(): string
    {
        return $this->currency.' '.number_format($this->amountDue());
    }

    public function isWaived(): bool
    {
        return (float) $this->waived_amount > 0;
    }

    public function isFullyWaived(): bool
    {
        return $this->isWaived() && $this->amountDue() <= 0;
    }

    public function displayName(): string
    {
        return $this->badge_name ?: $this->user->name;
    }

    /** The ribbon on the badge: presenters of an accepted abstract, students, everyone else. */
    public function badgeRole(): string
    {
        $presenting = $this->user->abstracts()
            ->where('edition_id', $this->edition_id)
            ->where('status', AbstractStatus::Accepted)
            ->exists();

        return match (true) {
            $presenting => 'Presenter',
            (bool) $this->category?->is_student => 'Student',
            default => 'Participant',
        };
    }
}
