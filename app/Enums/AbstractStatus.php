<?php

namespace App\Enums;

enum AbstractStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case RevisionRequested = 'revision_requested';
    case Revised = 'revised';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::RevisionRequested => 'Revisions requested',
            self::Revised => 'Revised · under review',
            self::Accepted => 'Accepted',
            self::Rejected => 'Not accepted',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft, self::Withdrawn => 'neutral',
            self::Submitted, self::RevisionRequested => 'warning',
            self::UnderReview, self::Revised => 'info',
            self::Accepted => 'success',
            self::Rejected => 'danger',
        };
    }

    /** The author may still edit or withdraw. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Submitted], true);
    }

    /** Waiting on the reviewers or the committee: the first review, or the review of the revision. */
    public function isInReview(): bool
    {
        return in_array($this, [self::Submitted, self::UnderReview, self::Revised], true);
    }

    public function isDecided(): bool
    {
        return in_array($this, [self::Accepted, self::Rejected], true);
    }
}
