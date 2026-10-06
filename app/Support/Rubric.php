<?php

namespace App\Support;

/**
 * The abstract scoring rubric from config/review.php: criteria, their weights,
 * the verdict bands and the words used for each level of a criterion.
 */
class Rubric
{
    /** Descriptive levels by percentage of a criterion's maximum, highest first. */
    public const LEVELS = [
        85 => 'Excellent',
        70 => 'Strong',
        50 => 'Adequate',
        30 => 'Limited',
        0 => 'Weak',
    ];

    /** @return array<string, array{label: string, short: string, max: int, question: string, guidance: list<string>, checks?: array<string, array{label: string, hint: string}>}> */
    public static function criteria(): array
    {
        return config('review.criteria');
    }

    /** @return list<string> */
    public static function fields(): array
    {
        return array_keys(self::criteria());
    }

    public static function max(): int
    {
        return (int) array_sum(array_column(self::criteria(), 'max'));
    }

    /** @return array<string, array{label: string, hint: string}> */
    public static function checks(): array
    {
        return self::criteria()['score_technical']['checks'] ?? [];
    }

    /** @return list<array{key: string, min: int, label: string, tone: string}> */
    public static function bands(): array
    {
        return config('review.bands');
    }

    /** The verdict band for a total out of 100, or null when there is no score. */
    public static function band(int|float|null $total): ?array
    {
        if ($total === null) {
            return null;
        }

        $percent = $total / max(1, self::max()) * 100;

        foreach (self::bands() as $band) {
            if ($percent >= $band['min']) {
                return $band;
            }
        }

        return last(self::bands());
    }

    public static function level(int|float $points, int $max): string
    {
        $percent = $max > 0 ? $points / $max * 100 : 0;

        foreach (self::LEVELS as $min => $label) {
            if ($percent >= $min) {
                return $label;
            }
        }

        return 'Weak';
    }

    /** Text colour for a total, matching its band. */
    public static function scoreClass(int|float|null $total): string
    {
        return match (self::band($total)['tone'] ?? null) {
            'success' => 'text-emerald-700',
            'warning' => 'text-amber-700',
            'danger' => 'text-red-700',
            default => 'text-ink-400',
        };
    }

    /** Everything the scoring screen needs, for Alpine. */
    public static function forScript(): array
    {
        return [
            'criteria' => collect(self::criteria())->map(fn (array $c, string $field) => [
                'field' => $field,
                'label' => $c['label'],
                'short' => $c['short'],
                'max' => $c['max'],
            ])->values()->all(),
            'bands' => self::bands(),
            'levels' => collect(self::LEVELS)->map(fn ($label, $min) => ['min' => $min, 'label' => $label])->values()->all(),
            'max' => self::max(),
            'checks' => array_keys(self::checks()),
        ];
    }
}
