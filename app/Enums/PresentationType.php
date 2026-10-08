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

    /**
     * Decision buttons: value => label, ending with reject. Just "Accept" while
     * oral is the only option.
     *
     * @return array<string, string>
     */
    public static function decisionOptions(bool $short = false): array
    {
        $options = [];
        foreach (self::decisions() as $type) {
            $name = $type === self::Poster ? 'poster' : 'oral';
            $options[$type->value] = match (true) {
                ! self::postersEnabled() => 'Accept',
                $short => ucfirst($name),
                default => 'Accept as '.$name,
            };
        }

        return $options + ['reject' => 'Reject'];
    }

    /** "Accepted as a poster", or just "Accepted" while every abstract is oral. */
    public function acceptedLabel(): string
    {
        if (! self::postersEnabled()) {
            return 'Accepted';
        }

        return 'Accepted as '.($this === self::Poster ? 'a poster' : 'an oral presentation');
    }

    /** Whether the summit has poster presentations (config/review.php). */
    public static function postersEnabled(): bool
    {
        return (bool) config('review.posters');
    }

    /**
     * What an author may ask for when submitting.
     *
     * @return list<self>
     */
    public static function preferences(): array
    {
        return self::postersEnabled() ? self::cases() : [self::Oral];
    }

    /**
     * What an abstract may be accepted as.
     *
     * @return list<self>
     */
    public static function decisions(): array
    {
        return self::postersEnabled() ? [self::Oral, self::Poster] : [self::Oral];
    }

    /** Prefix of the conference code: OR-HBR-01, PO-HBR-01. */
    public function codePrefix(): string
    {
        return $this === self::Poster ? 'PO' : 'OR';
    }
}
