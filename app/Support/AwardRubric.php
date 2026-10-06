<?php

namespace App\Support;

/** The criteria judges score finalists on, from config/awards.php. */
class AwardRubric
{
    /** @return array<string, array{label: string, hint: string}> */
    public static function criteria(): array
    {
        return config('awards.criteria');
    }

    /** Points for one criterion. */
    public static function perCriterion(): int
    {
        return (int) config('awards.max');
    }

    /** Points for all criteria together. */
    public static function max(): int
    {
        return self::perCriterion() * count(self::criteria());
    }
}
