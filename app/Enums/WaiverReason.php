<?php

namespace App\Enums;

/** Why finance waived a registration fee, in whole or in part. */
enum WaiverReason: string
{
    case Speaker = 'speaker';
    case Sponsored = 'sponsored';
    case Organiser = 'organiser';
    case Committee = 'committee';
    case Hardship = 'hardship';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Speaker => 'Invited speaker',
            self::Sponsored => 'Sponsored participant',
            self::Organiser => 'Organising staff',
            self::Committee => 'Committee member',
            self::Hardship => 'Financial hardship',
            self::Other => 'Other',
        };
    }
}
