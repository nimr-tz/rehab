<?php

namespace App\Support;

use App\Models\AbstractSubmission;
use Illuminate\Support\Collection;

/**
 * Groups accepted abstracts into the sub theme sections used by every published
 * volume.
 *
 * Shared by the abstract book (PDF) and the conference proceedings (Word) so the
 * two documents order and section their abstracts identically — the proceedings
 * differs only in which abstracts it selects, never in how they are arranged.
 */
class AbstractGrouping
{
    /**
     * Group by canonical sub theme, then order within each section: oral before
     * poster, then by conference code.
     */
    public static function bySubtheme(Collection $abstracts): Collection
    {
        $canonical = array_keys(config('conference.subtheme_prefixes', []));

        return $abstracts
            ->groupBy(fn (AbstractSubmission $abstract) => self::normalizeSubtheme(trim((string) $abstract->subtheme), $canonical))
            ->sortKeys()
            ->map(fn ($group) => $group->sortBy([
                [fn ($a) => strtolower((string) $a->presentation_mode) === 'oral' ? 0 : 1, 'asc'],
                [fn ($a) => preg_replace('/-\d+$/', '', (string) $a->conference_code), 'asc'],
                ['conference_code', 'asc'],
            ]));
    }

    /**
     * Map a free-text sub theme onto the canonical list.
     *
     * Authors and importers introduce spelling and punctuation drift, so an
     * exact match is tried first and then a similarity match; anything below the
     * threshold lands in a catch-all section rather than creating a near-duplicate
     * heading.
     */
    public static function normalizeSubtheme(string $subtheme, array $canonical): string
    {
        if (! $subtheme) {
            return 'Unspecified Subtheme';
        }

        // Exact match first
        if (in_array($subtheme, $canonical)) {
            return $subtheme;
        }

        // Fuzzy match — find canonical with highest similarity
        $best = null;
        $bestScore = 0;
        foreach ($canonical as $c) {
            similar_text(strtolower($subtheme), strtolower($c), $pct);
            if ($pct > $bestScore) {
                $bestScore = $pct;
                $best = $c;
            }
        }

        // Only remap if similarity is high enough (>= 70%)
        return ($bestScore >= 70 && $best) ? $best : 'Unspecified Subtheme';
    }

    /**
     * Number each distinct affiliation for superscript markers: the presenting
     * author's institute first, then each new co-author institute in order.
     *
     * @return array<string, int> affiliation name => superscript number
     */
    public static function affiliationMap(AbstractSubmission $abstract): array
    {
        $map = [];
        $next = 1;

        $main = TitleFormatter::affiliation($abstract->author_institute ?? '');
        if ($main !== '') {
            $map[$main] = $next++;
        }

        foreach (self::coauthors($abstract) as $coauthor) {
            // Co-authors are stored with an 'institute' key; legacy rows use
            // 'affiliation'. Reading only one of them mis-attributes every
            // co-author to the presenting author.
            $affiliation = TitleFormatter::affiliation($coauthor['institute'] ?? $coauthor['affiliation'] ?? '');
            if ($affiliation !== '' && ! isset($map[$affiliation])) {
                $map[$affiliation] = $next++;
            }
        }

        return $map;
    }

    /**
     * Co-authors as a plain array, tolerating the legacy double-encoded string.
     *
     * @return array<int, array<string, string>>
     */
    public static function coauthors(AbstractSubmission $abstract): array
    {
        $coauthors = $abstract->coauthors ?? [];

        if (is_string($coauthors)) {
            $coauthors = json_decode($coauthors, true) ?: [];
        }

        return is_array($coauthors) ? $coauthors : [];
    }
}
