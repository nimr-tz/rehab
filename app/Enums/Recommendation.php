<?php

namespace App\Enums;

enum Recommendation: string
{
    case AcceptOral = 'accept_oral';
    case AcceptPoster = 'accept_poster';
    case Reject = 'reject';

    public function label(): string
    {
        return match ($this) {
            self::AcceptOral => 'Accept as oral',
            self::AcceptPoster => 'Accept as poster',
            self::Reject => 'Reject',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::AcceptOral, self::AcceptPoster => 'success',
            self::Reject => 'danger',
        };
    }
}
