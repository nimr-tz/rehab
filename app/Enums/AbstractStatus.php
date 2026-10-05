<?php

namespace App\Enums;

enum AbstractStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::Accepted => 'Accepted',
            self::Rejected => 'Not accepted',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft, self::Withdrawn => 'neutral',
            self::Submitted => 'warning',
            self::UnderReview => 'info',
            self::Accepted => 'success',
            self::Rejected => 'danger',
        };
    }

    /** The author may still edit or withdraw. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Submitted], true);
    }
}
