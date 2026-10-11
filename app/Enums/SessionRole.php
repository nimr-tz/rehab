<?php

namespace App\Enums;

/** What a participant can apply to do in a programme session. */
enum SessionRole: string
{
    case Chair = 'chair';
    case Rapporteur = 'rapporteur';

    public function label(): string
    {
        return match ($this) {
            self::Chair => 'Chair',
            self::Rapporteur => 'Rapporteur',
        };
    }

    public function duty(): string
    {
        return match ($this) {
            self::Chair => 'Introduce the speakers, keep the session to time and lead the questions.',
            self::Rapporteur => 'Follow the session and write a short report of its main points for the summit record.',
        };
    }
}
