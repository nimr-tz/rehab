<?php

namespace App\Support;

use League\ISO3166\ISO3166;

class Countries
{
    /** Listed first, ahead of the alphabetical list. */
    public const HOME = 'TZ';

    /** @var array<string, string>|null */
    private static ?array $options = null;

    /**
     * Alpha-2 code => name, with Tanzania first and the rest alphabetical.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        if (self::$options !== null) {
            return self::$options;
        }

        $all = collect((new ISO3166)->all())
            ->mapWithKeys(fn (array $country) => [$country['alpha2'] => self::shortName($country['name'])])
            ->sort();

        return self::$options = [self::HOME => $all[self::HOME]] + $all->except(self::HOME)->all();
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::options());
    }

    public static function name(?string $code): ?string
    {
        return $code ? (self::options()[strtoupper($code)] ?? null) : null;
    }

    /** "Tanzania, United Republic of" reads better as "Tanzania". */
    private static function shortName(string $name): string
    {
        return [
            'Tanzania, United Republic of' => 'Tanzania',
            'Congo (Democratic Republic of the)' => 'DR Congo',
            'Iran (Islamic Republic of)' => 'Iran',
            'Korea (Republic of)' => 'South Korea',
            "Korea (Democratic People's Republic of)" => 'North Korea',
            'Bolivia (Plurinational State of)' => 'Bolivia',
            'Venezuela (Bolivarian Republic of)' => 'Venezuela',
            'Moldova (Republic of)' => 'Moldova',
            'Micronesia (Federated States of)' => 'Micronesia',
            'Palestine, State of' => 'Palestine',
            'United Kingdom of Great Britain and Northern Ireland' => 'United Kingdom',
            'United States of America' => 'United States',
        ][$name] ?? $name;
    }
}
