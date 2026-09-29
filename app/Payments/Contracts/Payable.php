<?php

namespace App\Payments\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Something a fee is paid against: an individual registration (User),
 * a group registration or a sponsor invoice.
 */
interface Payable
{
    public function paymentTransactions(): MorphMany;

    public function paymentAmount(): float;

    public function paymentCurrency(): string;

    public function paymentDescription(): string;

    /** Single letter used in the payment reference (U, G, S). */
    public function paymentReferenceType(): string;

    public function ensurePaymentReference(): string;

    public function isPaymentSettled(): bool;

    public function transactionAwaitingReview(): ?\App\Models\PaymentTransaction;
}
