<?php

namespace App\Payments\Concerns;

use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared payment behaviour for models implementing App\Payments\Contracts\Payable.
 * The model must have `payment_status` and `payment_reference` columns.
 */
trait HasPayments
{
    public function paymentTransactions(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable')->latest('id');
    }

    public function latestPaymentTransaction(): ?PaymentTransaction
    {
        return $this->paymentTransactions()->first();
    }

    public function transactionAwaitingReview(): ?PaymentTransaction
    {
        return $this->paymentTransactions()
            ->where('status', PaymentTransaction::STATUS_SUBMITTED)
            ->first();
    }

    /**
     * Stable reference the payer quotes on the bank narration or mobile money
     * account field, e.g. RH-U-000123.
     */
    public function ensurePaymentReference(): string
    {
        if (! empty($this->payment_reference)) {
            return $this->payment_reference;
        }

        $reference = sprintf(
            '%s-%s-%06d',
            strtoupper((string) config('payments.reference_prefix', 'RH')),
            $this->paymentReferenceType(),
            $this->getKey()
        );

        $this->forceFill(['payment_reference' => $reference])->saveQuietly();

        return $reference;
    }

    public function isPaymentSettled(): bool
    {
        return in_array($this->payment_status, ['verified', 'waived', 'paid'], true);
    }
}
