<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentTransaction extends Model
{
    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_MOBILE_MONEY = 'mobile_money';

    public const STATUS_PENDING = 'pending';     // gateway push started, awaiting payer

    public const STATUS_SUBMITTED = 'submitted'; // awaiting finance review

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';       // gateway reported failure

    protected $fillable = [
        'method',
        'provider',
        'amount',
        'currency',
        'payer_name',
        'payer_phone',
        'external_reference',
        'proof_path',
        'status',
        'paid_on',
        'submitted_at',
        'submitted_by',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
        'gateway_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'gateway_payload' => 'array',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isAwaitingReview(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function getMethodLabelAttribute(): string
    {
        $label = config("payments.methods.{$this->method}.label", ucwords(str_replace('_', ' ', $this->method)));

        if ($this->provider) {
            $provider = config("payments.methods.{$this->method}.providers.{$this->provider}.label", $this->provider);

            return "{$label} ({$provider})";
        }

        return $label;
    }

    /**
     * Other live (not rejected/failed) transactions quoting the same external
     * reference — a reused bank slip or transaction ID is a red flag.
     */
    public function duplicateReferences()
    {
        if (blank($this->external_reference)) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()
            ->where('id', '!=', $this->id)
            ->whereRaw('LOWER(external_reference) = ?', [strtolower(trim($this->external_reference))])
            ->whereNotIn('status', [self::STATUS_REJECTED, self::STATUS_FAILED]);
    }
}
