<?php

namespace App\Support;

/**
 * Chart colours, taken from the brand ramps and checked with the dataviz
 * validator (lightness band, chroma floor, colour-blind and normal-vision
 * separation in this order). Assign in this fixed order; never cycle past it.
 * A fifth category folds into "Other" or the chart becomes a bar list.
 */
class Palette
{
    /** brand-500, ember-600, sun-500, coral-600 */
    public const CATEGORICAL = ['#1b7fa3', '#bd520a', '#d69a00', '#d9574b'];

    public const OTHER = '#949cab'; // ink-400

    /** Single-hue sequential ramp (brand), light to dark. */
    public const SEQUENTIAL = ['#eff8fb', '#d7edf4', '#b0dbe9', '#7cc0d7', '#3f9dbe', '#1b7fa3', '#0b6688', '#024f6d'];

    public static function categorical(int $index): string
    {
        return self::CATEGORICAL[$index] ?? self::OTHER;
    }

    /** Background and text for a heatmap cell holding $value out of $max. */
    public static function heat(int|float $value, int|float $max): array
    {
        if ($value <= 0 || $max <= 0) {
            return ['#f6f8fa', '#949cab'];
        }

        $step = (int) min(count(self::SEQUENTIAL) - 1, max(1, round($value / $max * (count(self::SEQUENTIAL) - 1))));

        return [self::SEQUENTIAL[$step], $step >= 4 ? '#ffffff' : '#043f57'];
    }
}
