<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Awaiting verification',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Submitted => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
        };
    }
}
