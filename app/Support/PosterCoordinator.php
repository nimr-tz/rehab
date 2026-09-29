<?php

namespace App\Support;

use App\Models\ConferenceSession;

/**
 * Resolves the single poster-session coordinator for a given programme day.
 *
 * Poster sessions do not have a chair per screen — one coordinator oversees the
 * whole poster session for the day. The names live in config/conference.php
 * (`poster_coordinators`), keyed by the 1-based programme day number, which is
 * the rank of a date among the distinct dates that have sessions — the same
 * ordering the printed programme uses.
 */
class PosterCoordinator
{
    /** @var list<string>|null Memoised distinct programme dates (ascending). */
    protected static ?array $dates = null;

    /**
     * Coordinator name for a programme day number (1-based), or null.
     */
    public static function forDayNumber(int $day): ?string
    {
        $map = config('conference.poster_coordinators', []);

        return $map[$day] ?? null;
    }

    /**
     * Coordinator name for the programme day a given date (Y-m-d) falls on.
     */
    public static function forDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $index = array_search($date, static::programmeDates(), true);

        return $index === false ? null : static::forDayNumber($index + 1);
    }

    /**
     * Coordinator name for a session, based on its primary scheduled date.
     */
    public static function forSession(ConferenceSession $session): ?string
    {
        return static::forDate($session->getSafePrimaryDate());
    }

    /**
     * Distinct programme dates (Y-m-d), ascending — mirrors how the programme
     * groups and numbers days. Memoised for the lifetime of the request.
     *
     * @return list<string>
     */
    protected static function programmeDates(): array
    {
        if (static::$dates !== null) {
            return static::$dates;
        }

        return static::$dates = ConferenceSession::query()
            ->where('is_active', true)
            ->get()
            ->map(fn (ConferenceSession $session) => $session->getSafePrimaryDate())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Clear the memoised date list (useful in tests).
     */
    public static function flush(): void
    {
        static::$dates = null;
    }
}
