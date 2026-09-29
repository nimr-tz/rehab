<?php

namespace App\Support;

/**
 * The scientific topics (sub-themes) configured in config/conference.php.
 */
class ConferenceTopics
{
    private const COLORS = ['blue', 'emerald', 'amber', 'rose', 'violet', 'cyan', 'orange', 'teal', 'indigo', 'pink', 'lime', 'sky'];

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(config('conference.subtheme_prefixes', []));
    }

    /**
     * Scope text shown to authors when they choose a topic, if configured.
     */
    public static function description(string $topic): ?string
    {
        return config('conference.subtheme_descriptions', [])[$topic] ?? null;
    }

    /**
     * A Tailwind colour name that stays the same for a topic.
     */
    public static function color(?string $topic): string
    {
        $index = array_search($topic, self::names(), true);

        return $index === false ? 'slate' : self::COLORS[$index % count(self::COLORS)];
    }

    /**
     * @return array<string, string> topic => description ('' when none)
     */
    public static function descriptions(): array
    {
        $out = [];
        foreach (self::names() as $name) {
            $out[$name] = (string) self::description($name);
        }

        return $out;
    }
}
