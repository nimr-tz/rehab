<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something a payment pays for: one person's registration, or a group's.
 * Both have a `reference` (quoted on the payment) and a `currency`.
 *
 * The mark* methods run inside the caller's transaction, with the payable
 * locked; notifyPaid() runs after it commits.
 */
interface Payable
{
    /** Who pays, and is emailed about the payment. */
    public function payer(): User;

    /** What is being paid for, for finance: "Professional (Tanzania)", "Group: KCMC (5 people)". */
    public function payableLabel(): string;

    public function amountDue(): float;

    public function formattedDue(): string;

    public function canSubmitPayment(): bool;

    public function payments(): HasMany;

    /** The page where the payer follows their payment. */
    public function paymentUrl(): string;

    public function markPaymentSubmitted(): void;

    public function markPaid(): void;

    public function markUnpaid(): void;

    public function notifyPaid(): void;
}
