<?php

namespace App\Enums;

enum PaymentStatus: string
{
    // Sent to M-Pesa; waiting for the participant's PIN or for M-Pesa to confirm.
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';
    // M-Pesa declined it: wrong PIN, low balance, a limit. Nothing was paid.
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting M-Pesa',
            self::Submitted => 'Awaiting verification',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::Failed => 'Failed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending, self::Submitted => 'warning',
            self::Verified => 'success',
            self::Rejected, self::Failed => 'danger',
        };
    }
}
