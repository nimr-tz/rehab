<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Notifications\GroupConfirmed;
use App\Notifications\RegistrationConfirmed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Several people registered and paid for by one leader. The fee is the sum of
 * each member's category fee, paid at once; paying it confirms every member.
 */
class GroupRegistration extends Model implements Payable
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Registration::class)->orderBy('id');
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

    /** The sum of the members' fees. */
    public function amount(): float
    {
        return round((float) $this->members()->sum('amount'), 2);
    }

    /** Groups have no waivers: what is due is the members' fees. */
    public function amountDue(): float
    {
        return $this->amount();
    }

    public function formattedAmount(): string
    {
        return $this->currency.' '.number_format($this->amount());
    }

    public function formattedDue(): string
    {
        return $this->currency.' '.number_format($this->amountDue());
    }

    /** Members can be added or removed until a payment is sent. */
    public function canChangeMembers(): bool
    {
        return $this->status === RegistrationStatus::PendingPayment
            && ! $this->payments()->whereIn('status', [PaymentStatus::Submitted, PaymentStatus::Pending, PaymentStatus::Verified])->exists();
    }

    public function canSubmitPayment(): bool
    {
        return $this->status === RegistrationStatus::PendingPayment
            && $this->members()->exists()
            && ! $this->payments()->whereIn('status', [PaymentStatus::Submitted, PaymentStatus::Pending])->exists();
    }

    public function pendingGatewayPayment(): ?Payment
    {
        return $this->payments()->where('status', PaymentStatus::Pending)->latest('id')->first();
    }

    public function payer(): User
    {
        return $this->leader;
    }

    public function payableLabel(): string
    {
        $count = $this->members()->count();

        return 'Group: '.$this->name.' ('.$count.' '.str('person')->plural($count).')';
    }

    public function paymentUrl(): string
    {
        return route('groups.show', $this);
    }

    public function markPaymentSubmitted(): void
    {
        $this->update(['status' => RegistrationStatus::PaymentSubmitted]);
        $this->members()->update(['status' => RegistrationStatus::PaymentSubmitted]);
    }

    public function markPaid(): void
    {
        $this->update(['status' => RegistrationStatus::Confirmed, 'confirmed_at' => now()]);
        $this->members()->update(['status' => RegistrationStatus::Confirmed, 'confirmed_at' => now()]);
    }

    public function markUnpaid(): void
    {
        $this->update(['status' => RegistrationStatus::PendingPayment]);
        $this->members()->update(['status' => RegistrationStatus::PendingPayment]);
    }

    /** The leader hears that the group is paid; each member that they are confirmed. */
    public function notifyPaid(): void
    {
        $this->leader->notify(new GroupConfirmed($this->fresh()));
        $this->members()->with('user')->get()->each(fn (Registration $member) => $member->user->notify(new RegistrationConfirmed($member)));
    }
}
