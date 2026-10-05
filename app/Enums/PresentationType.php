<?php

namespace App\Enums;

enum PresentationType: string
{
    case Oral = 'oral';
    case Poster = 'poster';
    case Either = 'either';

    public function label(): string
    {
        return match ($this) {
            self::Oral => 'Oral presentation',
            self::Poster => 'Poster',
            self::Either => 'Oral or poster',
        };
    }

    /** Prefix of the conference code: OR-HBR-01, PO-HBR-01. */
    public function codePrefix(): string
    {
        return $this === self::Poster ? 'PO' : 'OR';
    }
}
