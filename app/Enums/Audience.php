<?php

namespace App\Enums;

/** Who an announcement goes to. Everything but "accounts" is this summit's people. */
enum Audience: string
{
    case Registered = 'registered';
    case Confirmed = 'confirmed';
    case Unpaid = 'unpaid';
    case Authors = 'authors';
    case Presenters = 'presenters';
    case Reviewers = 'reviewers';
    case SessionRoles = 'session_roles';
    case Accounts = 'accounts';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Everyone registered',
            self::Confirmed => 'Confirmed participants',
            self::Unpaid => 'Registered but not confirmed',
            self::Authors => 'Everyone who submitted an abstract',
            self::Presenters => 'Presenters of accepted abstracts',
            self::Reviewers => 'Reviewers',
            self::SessionRoles => 'Chairs and rapporteurs',
            self::Accounts => 'Every portal account',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Registered => 'Paid or not, this summit.',
            self::Confirmed => 'Paid or fee waived: they will attend.',
            self::Unpaid => 'Registered, fee not yet paid or verified.',
            self::Authors => 'Submitting authors, whatever the decision.',
            self::Presenters => 'Including presenters without an account.',
            self::Reviewers => 'Everyone with the reviewer role.',
            self::SessionRoles => 'Accepted chairs and rapporteurs.',
            self::Accounts => 'Every verified account, including past years. For example, to say registration has opened.',
        };
    }
}
