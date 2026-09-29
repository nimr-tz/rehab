<?php

namespace App\Support;

class TitleFormatter
{
    /**
     * Acronyms and proper nouns that must keep their canonical capitalization
     * after a title is converted to sentence case. Order longer/compound terms
     * before their substrings (e.g. COVID-19 before COVID).
     */
    private const TERMS = [
        // Compound / hyphenated first
        'SARS-CoV-2', 'COVID-19', 'MDR-TB', 'XDR-TB', 'TB/HIV', 'HIV/AIDS',
        'SARS-CoV', 'PEPFAR', 'GeneXpert',
        // Diseases / pathogens / clinical
        'HIV', 'AIDS', 'TB', 'COVID', 'SARS', 'DNA', 'RNA', 'PCR', 'qPCR',
        'ART', 'ARV', 'ARVs', 'AMR', 'NCD', 'NCDs', 'STI', 'STIs', 'UTI',
        'CD4', 'ELISA', 'PrEP', 'PEP', 'MTCT', 'PMTCT', 'EID', 'IPC', 'WASH',
        'ANC', 'MCH', 'RMNCH', 'SRH', 'SRHR', 'GBV', 'SGBV', 'BMI', 'BP',
        'HBV', 'HCV', 'HPV', 'EBV', 'MDR', 'XDR', 'IPT', 'IPTp', 'LAM',
        'RDT', 'RDTs', 'POC', 'QoL', 'WHO', 'AFB',
        // Institutions / agencies
        'NIMR', 'MUHAS', 'UDSM', 'UDOM', 'KCMC', 'CDC', 'USAID', 'EDCTP',
        'TMDA', 'TFDA', 'NHIF', 'CHF', 'PORALG', 'MoH', 'FELTP', 'IHI',
        'WHO', 'UNICEF', 'UNAIDS', 'UNFPA', 'GAVI', 'AKU',
        // Places / proper nouns
        'Tanzania', 'Tanzanian', 'Zanzibar', 'Africa', 'African',
        'Sub-Saharan', 'Mwanza', 'Mbeya', 'Dodoma', 'Arusha', 'Kilimanjaro',
        'Kigoma', 'Tabora', 'Tanga', 'Morogoro', 'Mtwara', 'Iringa', 'Geita',
        'Dar es Salaam', 'Uganda', 'Kenya', 'Rwanda', 'Burundi', 'Zambia',
        'Malawi', 'Nigeria', 'Ghana', 'Ethiopia', 'Mozambique',
    ];

    /**
     * Harmonize an abstract/presentation title to sentence case.
     *
     * Only "shouty" titles (mostly UPPERCASE) are transformed — titles the
     * author already wrote in mixed case are left exactly as submitted, since
     * their capitalization was intentional. Acronyms and proper nouns are
     * restored to their canonical form so terms like HIV or Tanzania are never
     * mangled into "Hiv" or "tanzania".
     */
    public static function sentenceCase(?string $title): string
    {
        $title = trim((string) $title);

        if ($title === '' || ! self::isShouty($title)) {
            return $title;
        }

        // Lowercase everything, then capitalize the first letter of each sentence.
        $result = mb_strtolower($title, 'UTF-8');
        $result = preg_replace_callback(
            '/(^|[.!?:]\s+)(\p{L})/u',
            fn (array $m): string => $m[1].mb_strtoupper($m[2], 'UTF-8'),
            $result
        );

        // Restore acronyms / proper nouns (case-insensitive, whole-token match).
        foreach (self::TERMS as $term) {
            $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($term, '/').'(?![\p{L}\p{N}])/iu';
            $result = preg_replace($pattern, $term, $result) ?? $result;
        }

        return $result;
    }

    /**
     * Title-case a person's name for display, e.g. "dr. sara munir" -> "Dr. Sara
     * Munir". Honorifics and post-nominals are normalized; name particles
     * (de, van, von, …) stay lowercase unless they lead the name. Display only —
     * the stored value is never changed.
     */
    public static function personName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $honorifics = [
            'dr' => 'Dr.', 'prof' => 'Prof.', 'mr' => 'Mr.', 'mrs' => 'Mrs.',
            'ms' => 'Ms.', 'miss' => 'Miss', 'sr' => 'Sr.', 'jr' => 'Jr.',
        ];
        $postNominals = ['phd', 'md', 'msc', 'bsc', 'mph', 'mba', 'rn', 'mbchb'];
        $particles = [
            'de', 'da', 'di', 'del', 'della', 'der', 'den', 'van', 'von',
            'la', 'le', 'du', 'bin', 'al', 'el', 'dos', 'das', 'ter',
        ];

        $words = preg_split('/\s+/', $name) ?: [];
        $out = [];
        foreach ($words as $idx => $word) {
            $lower = mb_strtolower($word, 'UTF-8');
            $bare = rtrim($lower, '.');

            if (isset($honorifics[$bare])) {
                $out[] = $honorifics[$bare];
            } elseif (in_array($bare, $postNominals, true)) {
                $out[] = mb_strtoupper($bare, 'UTF-8');
            } elseif ($idx > 0 && in_array($lower, $particles, true)) {
                $out[] = $lower;
            } else {
                $out[] = self::capWord($word);
            }
        }

        return implode(' ', $out);
    }

    /**
     * Tidy an affiliation string for display: a single space after commas and
     * collapsed whitespace. Casing is left untouched to avoid mangling proper
     * nouns. Display only.
     */
    public static function tidyAffiliation(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s*,\s*/', ', ', $text) ?? $text;

        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }

    /**
     * Canonical display form of an affiliation (tidied). Display only.
     */
    public static function affiliation(?string $text): string
    {
        $text = self::tidyAffiliation($text);
        if ($text === '') {
            return '';
        }

        return $text;
    }

    /**
     * Normalize a keyword list to a clean "; "-separated string, stripping stray
     * spaces and empty entries. Display only.
     */
    public static function keywords(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        $separator = str_contains($text, ';') ? ';' : ',';
        $parts = preg_split('/\s*'.preg_quote($separator, '/').'\s*/', $text) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));

        return implode('; ', $parts);
    }

    /**
     * Capitalize a single name token, handling hyphens and apostrophes
     * (e.g. "o'brien" -> "O'Brien", "smith-jones" -> "Smith-Jones").
     */
    private static function capWord(string $word): string
    {
        return preg_replace_callback(
            '/\p{L}+/u',
            fn (array $m): string => mb_strtoupper(mb_substr($m[0], 0, 1, 'UTF-8'), 'UTF-8')
                .mb_strtolower(mb_substr($m[0], 1, null, 'UTF-8'), 'UTF-8'),
            $word
        ) ?? $word;
    }

    /**
     * A title is "shouty" when at least 80% of its letters are uppercase.
     */
    private static function isShouty(string $title): bool
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $title);
        $total = mb_strlen((string) $letters, 'UTF-8');

        if ($total < 4) {
            return false;
        }

        $upper = preg_replace('/[^\p{Lu}]/u', '', $title);

        return (mb_strlen((string) $upper, 'UTF-8') / $total) >= 0.8;
    }
}
