<?php

namespace App\Enums;

enum Recommendation: string
{
    case AcceptOral = 'accept_oral';
    case AcceptPoster = 'accept_poster';
    case Revise = 'revise';
    case Reject = 'reject';

    /**
     * The recommendations reviewers can make: no poster while posters are off,
     * and no further revision when reviewing a revised abstract (round 2).
     *
     * @return list<self>
     */
    public static function offered(int $round = 1): array
    {
        return array_values(array_filter([
            self::AcceptOral,
            PresentationType::postersEnabled() ? self::AcceptPoster : null,
            $round === 1 ? self::Revise : null,
            self::Reject,
        ]));
    }

    public function label(): string
    {
        return match ($this) {
            // Without posters every acceptance is oral, so it is simply "Accept".
            self::AcceptOral => PresentationType::postersEnabled() ? 'Accept as oral' : 'Accept',
            self::AcceptPoster => 'Accept as poster',
            self::Revise => 'Revise',
            self::Reject => 'Reject',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::AcceptOral, self::AcceptPoster => 'success',
            self::Revise => 'warning',
            self::Reject => 'danger',
        };
    }

    public function isAcceptance(): bool
    {
        return in_array($this, [self::AcceptOral, self::AcceptPoster], true);
    }
}
