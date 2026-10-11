<?php

namespace App\Enums;

/** Proof of enrolment for a student rate. */
enum StudentProofStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Student proof being checked',
            self::Approved => 'Student proof approved',
            self::Rejected => 'Student proof not accepted',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
