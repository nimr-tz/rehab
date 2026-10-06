<?php

namespace App\Enums;

/**
 * How an award is decided. Presentation awards go to accepted abstracts that
 * the committee shortlists and judges score at the summit. Honours go to
 * people, nominated by participants or chosen by the committee.
 */
enum AwardKind: string
{
    case Presentation = 'presentation';
    case Honour = 'honour';

    public function label(): string
    {
        return match ($this) {
            self::Presentation => 'Judged presentation award',
            self::Honour => 'Honour',
        };
    }
}
