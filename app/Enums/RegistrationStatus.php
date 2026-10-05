<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaymentSubmitted = 'payment_submitted';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment',
            self::PaymentSubmitted => 'Payment being verified',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Status pill tone: success, warning, danger, info or neutral. */
    public function tone(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::PaymentSubmitted => 'info',
            self::Confirmed => 'success',
            self::Cancelled => 'neutral',
        };
    }
}
