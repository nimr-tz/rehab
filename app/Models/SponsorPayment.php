<?php

namespace App\Models;

use App\Payments\Concerns\HasPayments;
use App\Payments\Contracts\Payable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsorPayment extends Model implements Payable
{
    use HasFactory;
    use HasPayments;

    protected $fillable = [
        'created_by',
        'payment_verified_by',
        'sponsor_name',
        'package_name',
        'contact_person',
        'contact_email',
        'contact_phone',
        'description',
        'amount',
        'currency',
        'payment_status',
        'payment_reference',
        'paid_amount',
        'paid_currency',
        'payment_verified_at',
        'paid_at',
        'payment_notes',
        'admin_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'payment_verified_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function isVerified(): bool
    {
        return in_array($this->payment_status, ['verified', 'paid'], true);
    }

    public function paymentAmount(): float
    {
        return (float) $this->amount;
    }

    public function paymentCurrency(): string
    {
        return $this->currency ?: 'TZS';
    }

    public function paymentDescription(): string
    {
        return 'Sponsorship — '.$this->sponsor_name;
    }

    public function paymentReferenceType(): string
    {
        return 'S';
    }
}
