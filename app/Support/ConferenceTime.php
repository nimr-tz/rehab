<?php

namespace App\Support;

use Carbon\CarbonInterface;

class ConferenceTime
{
    public static function display(mixed $value, mixed $raw = null): ?string
    {
        foreach ([$raw, $value] as $candidate) {
            if ($candidate instanceof CarbonInterface) {
                return $candidate->format('H:i');
            }

            if ($candidate === null || $candidate === '') {
                continue;
            }

            if (preg_match('/\b([01]\d|2[0-3]):([0-5]\d)\b/', (string) $candidate, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }

    public static function displayWithSeconds(mixed $value, mixed $raw = null, string $fallback = '00:00:00'): string
    {
        $time = self::display($value, $raw);

        return $time ? "{$time}:00" : $fallback;
    }
}
