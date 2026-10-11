<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_on' => 'date',
            'reviewed_at' => 'datetime',
            'gateway_checked_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(GroupRegistration::class, 'group_registration_id');
    }

    /** What this payment pays for: one registration, or a group's. */
    public function payable(): Payable
    {
        return $this->registration ?? $this->group;
    }

    /** The desk officer who sent the M-Pesa request, if it was not the participant. */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function formattedAmount(): string
    {
        return $this->currency.' '.number_format((float) $this->amount);
    }

    /** "Bank transfer" or the mobile money operator, e.g. "M-Pesa". */
    public function channel(): string
    {
        if ($this->method === PaymentMethod::MobileMoney && $this->provider) {
            return config("payments.mobile_money.providers.{$this->provider}.label", $this->provider);
        }

        return $this->method->label();
    }
}
