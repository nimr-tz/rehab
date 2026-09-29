<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>{{ config('conference.short_name',config('conference.short_name')) }} {{ config('conference.year','2026') }} Conference Programme</title>
<style>
@page {
    margin: 5mm 6mm 10mm 6mm;
    size: A4 portrait;
}
* { box-sizing: border-box; }

body {
    font-family: Arial, 'DejaVu Sans', sans-serif;
    font-size: 8.6pt;
    line-height: 1.23;
    color: #1a1a1a;
    margin: 0;
    padding: 0;
}

/* ── Day banner ── */
.day-banner {
    background-color: #ffffff;
    color: #154b5f;
    padding: 2.2mm 4mm;
    margin: 0 -6mm 2mm -6mm;
    font-size: 10.2pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1pt;
    border-left: 4mm solid #154b5f;
    border-bottom: 1.5pt solid #154b5f;
}

/* ── Programme header (top of each content page) ── */
.prog-header {
    text-align: center;
    margin-bottom: 2mm;
    border-bottom: 1pt solid #154b5f;
    padding-bottom: 1.5mm;
}
.prog-header-title { font-size: 10pt; font-weight: bold; color: #152238; }
.prog-header-sub   { font-size: 7.8pt; color: #666; }

/* ── Tables ── */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0.5mm;
    table-layout: fixed;
}
th, td {
    border: 0.5pt solid #9ca3af;
    padding: 1px 2px;
    vertical-align: top;
    word-wrap: break-word;
}
th {
    font-size: 8.2pt;
    font-weight: bold;
    text-align: center;
}

/* Time column */
.col-time {
    width: 13%;
    background-color: #e2e8f0;
    font-weight: bold;
    font-size: 8.6pt;
    text-align: center;
    vertical-align: middle;
}
/* Label column (Chair / Rapporteur) */
.col-label {
    width: 11%;
    background-color: #e2e8f0;
    font-size: 8.1pt;
    font-weight: bold;
    vertical-align: middle;
}

/* Break row */
.row-break td {
    background-color: #86efac;
    text-align: center;
    font-weight: bold;
    font-size: 8.2pt;
    color: #14532d;
}
/* Lunch row */
.row-lunch td {
    background-color: #fde047;
    text-align: center;
    font-weight: bold;
    font-size: 8.2pt;
    color: #713f12;
}
/* Full-width session (plenary / opening / closing / keynote) — content rows stay white */
.row-plenary td {
    background-color: #ffffff;
}
/* Poster / parallel session title banner */
.row-session-banner td {
    background-color: #154b5f;
    color: #ffffff;
    font-weight: bold;
    font-size: 8.2pt;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.8pt;
    padding: 2px 4px;
}
.plenary-title {
    font-size: 8.1pt;
    font-weight: bold;
    text-transform: uppercase;
    color: #154b5f;
}
.plenary-name {
    font-size: 8.2pt;
    font-weight: bold;
    margin-top: 1px;
    line-height: 1.22;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
    color: #1a1a2e;
}
.plenary-meta {
    font-size: 6.8pt;
    color: #475569;
    margin-top: 1px;
}
/* Panelists column */
.col-panelists {
    width: 30%;
    vertical-align: top;
    padding: 2px 4px;
    background-color: #ffffff;
}
.panelists-label {
    font-size: 7.3pt;
    font-weight: bold;
    text-transform: uppercase;
    color: #0c4a6e;
    margin-bottom: 2px;
}
.panelists-list {
    font-size: 7pt;
    line-height: 1.25;
}
.panelist-item {
    margin-bottom: 1px;
}
/* Chair / rapporteur row */
.row-chair td   { background-color: #fde047; font-size: 7.1pt; color: #1a1a1a; }
.row-rapport td { background-color: #fde047; font-size: 7.1pt; color: #1a1a1a; }

/* Parallel session header row — hall names */
.row-parallel-hdr {
    page-break-after: avoid;
}
.row-parallel-hdr td {
    background-color: #93c5fd;
    color: #1e3a5f;
    font-weight: bold;
    font-size: 7.8pt;
    text-transform: uppercase;
    text-align: center;
}
/* Hall header — session descriptions */
.row-halls {
    page-break-after: avoid;
}
.row-halls th {
    background-color: #93c5fd;
    color: #1e3a5f;
    font-size: 7.2pt;
}
/* Abstract Code sub-header row */
.row-codes-hdr td {
    background-color: #93c5fd;
    color: #1e3a5f;
    font-size: 6.8pt;
    font-weight: bold;
    text-align: center;
    vertical-align: middle;
}
/* Session cell inside parallel block — warm orange to match reference */
.cell-session {
    background-color: #fdba74;
    color: #1a1a1a;
}
.session-type-badge {
    font-size: 6.8pt;
    font-weight: bold;
    color: #1e3a8a;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    display: block;
    margin-bottom: 1px;
}
.session-name {
    font-size: 7.4pt;
    font-weight: bold;
    line-height: 1.15;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
/* Abstract code entry */
.row-schedule td {
    font-size: 7.1pt;
    vertical-align: middle;
}
.poster-chunk {
    page-break-inside: avoid;
}
.poster-chunk .row-session-banner td {
    font-size: 9pt;
}
.poster-chunk .row-parallel-hdr td {
    font-size: 8.5pt;
}
.poster-chunk .row-chair td {
    font-size: 8pt;
}
.poster-chunk .row-codes-hdr td {
    font-size: 7.4pt;
}
.poster-chunk .row-schedule td {
    font-size: 8pt;
    padding-top: 0.5px;
    padding-bottom: 0.5px;
}
.code-entry {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #152238;
    text-align: center;
    display: block;
}
.discussion-entry {
    font-style: italic;
    color: #64748b;
    text-align: center;
    display: block;
}
/* Poster screen name header row uses row-parallel-hdr (same as oral hall names) */

/* Abstract index */
.abstract-index {
    page-break-before: always;
}
.index-title {
    background-color: #eef2fb;
    color: #154b5f;
    padding: 2.2mm 4mm;
    margin: 0 -6mm 3mm -6mm;
    font-size: 10pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1pt;
    border-left: 4mm solid #154b5f;
}
.index-subtheme {
    margin: 3mm 0 1mm 0;
    padding: 1mm 3mm;
    background-color: #eef2fb;
    color: #154b5f;
    font-size: 8.9pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    border-left: 2mm solid #154b5f;
    page-break-after: avoid;
}
.index-entries {
    margin-bottom: 2mm;
}
.index-entry {
    padding: 1.1mm 0 1.1mm 3mm;
    border-bottom: 0.3pt solid #e2e8f0;
    page-break-inside: avoid;
}
.index-entry-code {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    font-size: 9.1pt;
    color: #154b5f;
    display: inline;
}
.index-entry-title {
    font-size: 9.1pt;
    color: #1a1a2e;
    font-weight: normal;
    margin-left: 1.5mm;
    display: inline;
}
.index-entry-author {
    font-size: 8.3pt;
    color: #154b5f;
    font-weight: bold;
    font-style: normal;
    display: inline;
}
.index-blank {
    text-align: center;
    color: #64748b;
    padding: 10mm;
}
</style>
</head>
<body>
@php
    $confName      = trim((string) config('conference.name',          config('conference.name')));
    $shortName     = trim((string) config('conference.short_name',    config('conference.short_name')));
    $year          = trim((string) config('conference.year',          '2026'));
    $edition       = trim((string) config('conference.edition',       '33rd'));
    $theme         = trim((string) config('conference.theme',         ''));
    $displayDates  = trim((string) config('conference.display_dates', ''));
    $venue         = trim((string) config('conference.venue',         ''));
    $city          = trim((string) config('conference.city',          ''));
    $country       = trim((string) config('conference.country',       ''));
    $host          = trim((string) config('conference.host',          config('conference.host')));
    $hostShort     = trim((string) config('conference.host_short',    config('conference.host_short')));
    $venueLine     = implode(', ', array_filter(array_unique([$venue, $city, $country])));
    $logoPath      = public_path(config('conference.logo_mark_path'));

    $preferredHalls = array_map('strtoupper', config('conference.halls', []));

    $normalizeHall = function ($hall) {
        return strtoupper(trim((string)($hall ?: 'Hall')));
    };

    $sortHalls = function ($halls, $sessions = null) use ($preferredHalls, $normalizeHall) {
        $sessionByHall = $sessions
            ? collect($sessions)->keyBy(fn($s) => $normalizeHall($s->room_location))
            : collect();

        return collect($halls)
            ->filter()->unique()
            ->sortBy(function ($h) use ($preferredHalls, $normalizeHall, $sessionByHall) {
                $n = $normalizeHall($h);
                $session = $sessionByHall->get($n);
                $typePrefix = '1-';
                // Numeric sort for poster screens (e.g. "Screen #2" before "Screen #10")
                if (preg_match('/screen\s*#?\s*(\d+)/i', $h, $m)) {
                    return $typePrefix . 'Z-' . str_pad((string)(int)$m[1], 4, '0', STR_PAD_LEFT);
                }
                // Prefer halls whose stored name starts with a preferred name
                // (config conference.halls), e.g. "MAIN HALL" starts with "MAIN".
                $i = false;
                foreach ($preferredHalls as $idx => $preferred) {
                    if (str_starts_with($n, $preferred)) { $i = $idx; break; }
                }
                return $typePrefix . ($i === false ? 100 : $i) . '-' . $n;
            })->values();
    };

    $fmtTime = function ($t) {
        if (empty($t) || $t === 'TBD') return 'TBD';
        try { return \Carbon\Carbon::parse($t)->format('H:i'); } catch (\Exception $e) { return (string)$t; }
    };

    $fmtRange = function ($r) use ($fmtTime) {
        $p = explode('-', $r, 2);
        return $fmtTime($p[0] ?? 'TBD') . '–' . $fmtTime($p[1] ?? 'TBD');
    };

    $fmtDate = function ($d) {
        $d = trim((string)$d);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            try { return \Carbon\Carbon::createFromFormat('Y-m-d', $d)->format('l, F j, Y'); } catch (\Exception $e) {}
        }
        return $d ?: 'Unassigned';
    };

    $sessionTypeLabel = function ($session) {
        $t = strtolower((string)$session->session_type);
        $hasAbstracts = (int)($session->abstracts?->count() ?? 0) > 0
            || (int)($session->max_abstracts ?? 0) > 0;

        return match($t) {
            'poster'       => 'Poster Session',
            'plenary'      => 'Plenary Session',
            'keynote'      => 'Keynote Address',
            'opening'      => 'Opening Ceremony',
            'closing'      => 'Closing Ceremony',
            'panel'        => 'Panel Discussion',
            'discussion'   => 'Discussion',
            'meeting'      => 'Meeting',
            'presentation' => $hasAbstracts ? 'Parallel Session' : '',
            default        => '',
        };
    };

    $codeSortKey = function ($code) {
        $code = strtoupper(trim((string)$code));

        if (preg_match('/^(OR|PO)-([A-Z0-9]+)-(\d+)$/', $code, $m)) {
            return sprintf('%s-%s-%05d', $m[1], $m[2], (int)$m[3]);
        }

        return $code ?: 'ZZZZZ';
    };

    $buildTimeline = function ($session) use ($fmtTime, $codeSortKey) {
        $type = strtolower((string)$session->session_type);

        $abstracts = $session->abstracts
            ->sortBy(fn($a) => $codeSortKey($a->conference_code))
            ->values();
        $items = $abstracts->map(fn($a) => [
            'type' => 'abstract',
            'label' => $a->conference_code,
            'title' => $a->title,
        ])->all();

        $entries = $items;

        if (count($entries) > 0 && !in_array($type, ['poster','break','lunch'], true)) {
            $entries[] = ['type' => 'discussion', 'label' => 'Discussion'];
        }
        $start  = $session->start_time ? \Carbon\Carbon::parse($session->start_time) : null;
        $end    = $session->end_time   ? \Carbon\Carbon::parse($session->end_time)   : null;
        $total  = ($start && $end && $end->gt($start)) ? $end->diffInMinutes($start) : count($entries) * 15;
        $minSlot = ($type === 'poster') ? 5 : 10;
        $slotM  = $type === 'poster'
            ? 5
            : max($minSlot, (int)floor($total / max(count($entries), 1)));
        $count  = count($entries);

        return collect($entries)->map(function ($entry, $i) use ($start, $end, $slotM, $count, $fmtTime, $type) {
            if ($start) {
                $s = $start->copy()->addMinutes($slotM * $i);
                $e = ($type !== 'poster' && $end && $i === $count - 1) ? $end->copy() : $s->copy()->addMinutes($slotM);
                $timeStr = $fmtTime($s->format('H:i')) . '–' . $fmtTime($e->format('H:i'));
            } else {
                $timeStr = '';
            }
            return [
                'time' => $timeStr,
                'type' => $entry['type'],
                'label' => $entry['label'],
                'title' => $entry['title'] ?? null,
            ];
        });
    };

    $cleanInlineText = function ($value) {
        return trim(preg_replace('/\s+/', ' ', str_replace('_', ', ', (string)$value)));
    };

    $uniqueList = function ($items) use ($cleanInlineText) {
        $seen = [];
        $clean = [];

        foreach ($items as $item) {
            $value = $cleanInlineText($item);
            $value = trim($value, " \t\n\r\0\x0B.,;:");

            if ($value === '') {
                continue;
            }

            $key = strtolower(preg_replace('/[^a-z0-9]+/i', '', $value));

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $clean[] = $value;
        }

        return $clean;
    };

    $posterScreenTopicLabel = function ($session) use ($cleanInlineText) {
        $abstracts = collect($session->abstracts ?? []);
        if ($abstracts->isEmpty()) {
            return null;
        }

        $subjects = $abstracts
            ->map(function ($abstract) {
                $code = strtoupper(trim((string) $abstract->conference_code));
                if (preg_match('/^(?:OR|PO)-([A-Z0-9]+)-\d+$/', $code, $matches)) {
                    return $matches[1];
                }

                return null;
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(2)
            ->keys()
            ->map(fn ($subject) => \App\Services\SessionTopicDetectionService::codes()[$subject] ?? $subject)
            ->map(fn ($label) => $cleanInlineText($label))
            ->filter()
            ->values();

        return $subjects->isEmpty() ? null : $subjects->implode(' / ');
    };

    $panelTitleAndSpeakerText = function ($name) use ($cleanInlineText) {
        $text = $cleanInlineText($name);
        $lead = '(?:Dr|Prof|Mr|Ms|Mrs|Hon)\.?\s+|(?:COSTECH|TMDA|WHO|UHC|Novo Nordisk)\b|(?:MODERATOR|Moderator)\s*[:\-]';

        foreach ([
            '/^(.*?)(?:\:\s+)(?=' . $lead . ')/i',
            '/^(.*?)(?:\.\s+)(?=' . $lead . ')/i',
            '/^(.*?)(?:\s+)(?=(?:Dr|Prof|Mr|Ms|Mrs|Hon)\.?\s+)/i',
        ] as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $title = trim($matches[1], " \t\n\r\0\x0B:;.,");
                $speakerText = trim(substr($text, strlen($matches[0])));

                return [$title, $speakerText];
            }
        }

        return [$text, ''];
    };

    $mainTitle = function ($session) use ($cleanInlineText, $panelTitleAndSpeakerText) {
        $type = strtolower((string)$session->session_type);
        $name = $cleanInlineText($session->name);

        if ($type === 'panel') {
            return $panelTitleAndSpeakerText($name)[0];
        }

        if (preg_match('/^(.*?)\s+-\s+(.+)$/', $name, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/^(.*?)\s*\(((?:Dr|Prof|Mr|Ms|Mrs|Hon)\.?\s+.*)\)\s*$/i', $name, $matches)) {
            return trim($matches[1]);
        }

        return $name;
    };

    $speakerColumn = function ($session) use ($cleanInlineText, $uniqueList, $panelTitleAndSpeakerText) {
        $panelists = array_filter(array_map($cleanInlineText, preg_split('/\r?\n/', (string)($session->panelists ?? ''))));

        if (!empty($panelists)) {
            return $uniqueList($panelists);
        }

        if (filled($session->speaker)) {
            return $uniqueList(preg_split('/\s*;\s*|\r?\n/', (string)$session->speaker));
        }

        $type = strtolower((string)$session->session_type);
        $name = $cleanInlineText($session->name);

        if ($type === 'panel') {
            [, $speakerText] = $panelTitleAndSpeakerText($name);
            $speakerText = preg_replace('/\b(?:MODERATOR|Moderator)\s*-\s*/', 'Moderator: ', $speakerText);
            $speakers = [];
            $moderatorKeys = [];

            if (preg_match_all('/\b(?:MODERATOR|Moderator)\s*:\s*((?:Dr|Prof|Mr|Ms|Mrs|Hon)\.?\s+[^,;)]+)/i', $speakerText, $moderators)) {
                foreach ($moderators[1] as $moderator) {
                    $moderator = preg_replace('/\s*\(.*/', '', $moderator);
                    $moderatorKeys[strtolower(preg_replace('/[^a-z0-9]+/i', '', $cleanInlineText($moderator)))] = true;
                    $speakers[] = 'Moderator: ' . $moderator;
                }
            }

            if (preg_match_all('/\b(?:Dr|Prof|Mr|Ms|Mrs|Hon)\.?\s+[^,:(]+/i', $speakerText, $matches)) {
                foreach ($matches[0] as $speaker) {
                    $speakerKey = strtolower(preg_replace('/[^a-z0-9]+/i', '', $cleanInlineText($speaker)));

                    if (isset($moderatorKeys[$speakerKey])) {
                        continue;
                    }

                    $speakers[] = preg_replace('/^\s*(?:MODERATOR|Moderator)\s*:\s*/i', 'Moderator: ', $speaker);
                }
            }

            foreach (preg_split('/\s*,\s*/', $speakerText) as $part) {
                $part = trim(preg_replace('/^\(?\s*(?:MODERATOR|Moderator)\s*:\s*/i', '', $part));
                $part = preg_replace('/\s*\([^)]*$/', '', $part);

                if (preg_match('/\b(?:representative|WHO|UHC technical advisor|Philanthropy|Novo Nordisk)\b/i', $part)) {
                    $speakers[] = $part;
                } elseif (preg_match('/^[A-Z][A-Za-z.\' -]{3,40}$/', $part) && !preg_match('/\b(?:Director|Secretary|Officer|Department|Health|Policy|Innovation|Technology)\b/i', $part)) {
                    $speakers[] = $part;
                }
            }

            return $uniqueList($speakers);
        }

        if (preg_match('/\s+-\s+(.+)$/', $name, $matches)) {
            return $uniqueList([$matches[1]]);
        }

        if (preg_match('/\(((?:Dr|Prof|Mr|Ms|Mrs|Hon)\.?\s+.*)\)\s*$/i', $name, $matches)) {
            return $uniqueList([$matches[1]]);
        }

        return [];
    };

    $contentWeight = function ($session) {
        return (int)($session->abstracts?->count() ?? 0)
            + (filled($session->session_chair) ? 1 : 0)
            + (filled($session->session_rapporteur) ? 1 : 0);
    };

    $subthemePrefixes = config('conference.subtheme_prefixes', []);
    $prefixToSubtheme = array_flip($subthemePrefixes); // e.g. 'NCD' => 'Non-Communicable...'

    $abstractIndex = collect($programByDay)
        ->flatMap(fn($slots) => collect($slots)->flatMap(fn($sessions) => collect($sessions)))
        ->flatMap(function ($session) use ($prefixToSubtheme, $subthemePrefixes) {
            return collect($session->abstracts ?? [])->map(function ($abstract) use ($session, $prefixToSubtheme, $subthemePrefixes) {
                $author = \App\Support\TitleFormatter::personName($abstract->author_name ?? '');
                $subtheme = trim((string)($abstract->subtheme ?: $session->subtheme ?? ''));
                // If still blank, derive from conference code prefix (e.g. OR-NCD-01 -> NCD)
                if ($subtheme === '') {
                    $code = trim((string)$abstract->conference_code);
                    if (preg_match('/^(?:OR|PO)-([A-Z]+)-\d+$/i', $code, $m)) {
                        $subtheme = $prefixToSubtheme[strtoupper($m[1])] ?? '';
                    }
                }
                return [
                    'code'     => trim((string)$abstract->conference_code),
                    'title'    => trim((string)$abstract->title),
                    'author'   => $author,
                    'subtheme' => $subtheme ?: 'Other',
                ];
            });

        })
        ->filter(fn($item) => $item['code'] !== '')
        ->unique('code')
        ->sortBy([
            ['subtheme', 'asc'],
            ['code', 'asc'],
        ], SORT_NATURAL | SORT_FLAG_CASE)
        ->groupBy(fn($item) => $item['subtheme'] ?: 'Other');

    $specialTypes    = ['break', 'lunch'];
    $singleTypes     = ['opening', 'closing', 'plenary', 'keynote', 'registration', 'networking', 'panel', 'discussion', 'meeting', 'other'];
@endphp

{{-- ── PROGRAMME CONTENT ── --}}
@forelse($programByDay as $date => $timeSlots)
    @php
        $dayName = $fmtDate($date);
        $dayNumber = $loop->iteration;
        // Poster sessions have a single coordinator for the whole day (not a chair per screen).
        $posterCoordinator = \App\Support\PosterCoordinator::forDayNumber($dayNumber);
    @endphp

    <div class="day-banner"@if(!$loop->first) style="page-break-before:always;"@endif>
        Day {{ $loop->iteration }}: {{ $dayName }}
    </div>

    @foreach($timeSlots as $timeRange => $sessions)
        @php
            $sorted = $sessions->sortBy(function ($s) use ($normalizeHall) {
                return [1, $normalizeHall($s->room_location), $s->sort_order ?? 9999, $s->id];
            })->values();

            // Always split posters out — they render independently even when
            // non-poster sessions share the same time slot.
            $posterSessions  = $sorted
                ->filter(fn($s) => strtolower((string)$s->session_type) === 'poster')
                ->sortByDesc($contentWeight)
                ->unique(fn($s) => strtolower(trim(($s->room_location ?: $s->name) . '|' . $s->name)))
                ->values();
            $nonPosterSorted = $sorted
                ->filter(fn($s) => strtolower((string)$s->session_type) !== 'poster')
                ->sortByDesc($contentWeight)
                ->unique(fn($s) => strtolower(trim($s->session_type . '|' . ($s->room_location ?? '') . '|' . $s->name)))
                ->values();

            $first      = ($nonPosterSorted->first() ?? $posterSessions->first());
            $timeLabel  = $fmtRange($timeRange);
            $types      = $nonPosterSorted->pluck('session_type')->map(fn($t) => strtolower((string)$t));
            $isBreak    = $types->contains(fn($t) => in_array($t, ['break']));
            $isLunch    = $types->contains(fn($t) => in_array($t, ['lunch']));
            $isPoster   = $posterSessions->isNotEmpty();
            $isSingle   = $nonPosterSorted->count() === 1 && in_array(strtolower((string)($nonPosterSorted->first()?->session_type ?? '')), $singleTypes, true);
            $parallelHallCount = $nonPosterSorted->pluck('room_location')->filter()->map(fn($h) => $normalizeHall($h))->unique()->count();
            $isParallel = $nonPosterSorted->count() > 1 && $parallelHallCount > 1;
            $sorted     = $nonPosterSorted; // parallel/single renderer uses $sorted for non-poster sessions
        @endphp

        @if($isBreak || $isLunch)
            <table>
                <tr class="{{ $isLunch ? 'row-lunch' : 'row-break' }}">
                    <td class="col-time" style="width:13%">{{ $timeLabel }}</td>
                    <td>{{ strtoupper($first->name) }}</td>
                </tr>
            </table>
            @continue
        @endif

        @if($isPoster)
            @php
                $posterSorted = $posterSessions->sortBy(function($s) {
                    if (preg_match('/screen\s*#?\s*(\d+)/i', (string)($s->room_location ?? $s->name), $m)) {
                        return (int)$m[1];
                    }
                    return 9999;
                })->values();

                $posterScreenCount = max(1, $posterSorted->count());
                $posterStart = $posterSorted->first()?->start_time
                    ? \Carbon\Carbon::parse($posterSorted->first()->start_time)
                    : null;
                $posterSlotMinutes = 5;
                $posterAbstracts = $posterSorted
                    ->flatMap(fn($s) => collect($s->abstracts ?? []))
                    ->unique('id')
                    ->sortBy(fn($a) => $codeSortKey($a->conference_code))
                    ->values();
                $posterMaxRows = max(1, (int)ceil($posterAbstracts->count() / $posterScreenCount));
                $posterTimelines = $posterSorted->mapWithKeys(fn($s) => [$s->id => collect()]);

                foreach ($posterAbstracts as $index => $abstract) {
                    $screen = $posterSorted[$index % $posterScreenCount];
                    $rowIndex = intdiv($index, $posterScreenCount);
                    $timeStr = $timeLabel;

                    if ($posterStart) {
                        $s = $posterStart->copy()->addMinutes($posterSlotMinutes * $rowIndex);
                        $e = $s->copy()->addMinutes($posterSlotMinutes);
                        $timeStr = $fmtTime($s->format('H:i')) . '–' . $fmtTime($e->format('H:i'));
                    }

                    $posterTimelines[$screen->id]->push([
                        'time' => $timeStr,
                        'type' => 'abstract',
                        'label' => $abstract->conference_code,
                        'title' => $abstract->title,
                    ]);
                }

                // Respect each poster screen's actual session assignments. The earlier
                // fallback distribution is kept only as a safety net for legacy data.
                $assignedPosterTimelines = $posterSorted->mapWithKeys(fn($s) => [$s->id => collect()]);
                $assignedPosterMaxRows = 1;

                foreach ($posterSorted as $screen) {
                    $screenAbstracts = collect($screen->abstracts ?? [])
                        ->unique('id')
                        ->sortBy(fn($a) => $codeSortKey($a->conference_code))
                        ->values();

                    $assignedPosterMaxRows = max($assignedPosterMaxRows, $screenAbstracts->count());

                    foreach ($screenAbstracts as $rowIndex => $abstract) {
                        $timeStr = $timeLabel;

                        if ($posterStart) {
                            $s = $posterStart->copy()->addMinutes($posterSlotMinutes * $rowIndex);
                            $e = $s->copy()->addMinutes($posterSlotMinutes);
                            $timeStr = $fmtTime($s->format('H:i')) . '–' . $fmtTime($e->format('H:i'));
                        }

                        $assignedPosterTimelines[$screen->id]->push([
                            'time' => $timeStr,
                            'type' => 'abstract',
                            'label' => $abstract->conference_code,
                            'title' => $abstract->title,
                        ]);
                    }
                }

                if ($posterSorted->contains(fn($s) => collect($s->abstracts ?? [])->isNotEmpty())) {
                    $posterTimelines = $assignedPosterTimelines;
                    $posterMaxRows = $assignedPosterMaxRows;
                }
            @endphp
            @php
                // Split into groups of 5 screens so each table fits A4 portrait
                $posterChunks = $posterSorted->chunk(5);
                $shortLocFn = function($ps) {
                    $loc = $ps->room_location ?: $ps->name;
                    $short = preg_replace('/^.*?(Screen\s*#?\s*\d+).*$/i', '$1', $loc);
                    if ($short === $loc && preg_match('/(\d+)/', $loc, $m)) {
                        $short = 'Screen #' . $m[1];
                    }
                    return $short;
                };
            @endphp
            @foreach($posterChunks as $chunk)
                @php
                    $chunkCount = $chunk->count();
                    $colWidth   = $chunkCount > 0 ? round(89 / $chunkCount, 1) : 89;
                @endphp
                <table class="poster-chunk" style="table-layout:fixed; width:100%; margin-bottom:1mm;">
                    <tr class="row-session-banner">
                        <td colspan="{{ $chunkCount + 1 }}">POSTER SESSION</td>
                    </tr>
                    {{-- Screen names row — same class as oral hall names (row-parallel-hdr) --}}
                    <tr class="row-parallel-hdr">
                        <td class="col-label" style="width:11%">Screens</td>
                        @foreach($chunk as $ps)
                            <td style="width:{{ $colWidth }}%">{{ $shortLocFn($ps) }}</td>
                        @endforeach
                    </tr>
                    @if($posterCoordinator)
                        {{-- One coordinator for the whole poster session per day (not a chair per screen) --}}
                        <tr class="row-chair">
                            <td class="col-label">Coordinator</td>
                            <td colspan="{{ $chunkCount }}">{{ $posterCoordinator }}</td>
                        </tr>
                    @endif
                    {{-- Abstract Code sub-header --}}
                    <tr class="row-codes-hdr">
                        <td style="width:11%">Time</td>
                        @foreach($chunk as $ps)
                            <td style="width:{{ $colWidth }}%">Abstract Code</td>
                        @endforeach
                    </tr>
                    @for($ri = 0; $ri < $posterMaxRows; $ri++)
                        {{-- Schedule rows — one side-panel time for the whole poster session --}}
                        <tr class="row-schedule">
                            @if($ri === 0)
                                <td class="col-time" style="width:11%" rowspan="{{ $posterMaxRows }}">{{ $timeLabel }}</td>
                            @endif
                            @foreach($chunk as $ps)
                                @php $entry = $posterTimelines[$ps->id][$ri] ?? null; @endphp
                                <td style="text-align:center; width:{{ $colWidth }}%">
                                    @if($entry && $entry['type'] === 'abstract')
                                        <span class="code-entry">{{ $entry['label'] }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endfor
                </table>
            @endforeach
        @endif

        @if($isSingle)
            @php
                $timeline = $buildTimeline($first);
                $speakers = $speakerColumn($first);
                $useSpeakerColumn = !empty($speakers);
                $contentColspan = $useSpeakerColumn ? 2 : 1;
            @endphp
            @php
                $plenaryTypes = ['plenary', 'panel'];
                $effectiveLocationFirst = $first->room_location ?: (in_array(strtolower((string)$first->session_type), $plenaryTypes, true) ? config('conference.plenary_hall') : null);
            @endphp
            <table style="table-layout:fixed; width:100%; page-break-inside:avoid; margin-bottom:0; border-bottom:none;">
                @if($effectiveLocationFirst)
                <tr class="row-parallel-hdr">
                    <td style="width:13%">{{ $timeLabel }}</td>
                    <td colspan="{{ $contentColspan }}">{{ strtoupper($effectiveLocationFirst) }}</td>
                </tr>
                @endif
                <tr class="row-plenary">
                    <td class="col-time" style="width:13%">{{ $effectiveLocationFirst ? '' : $timeLabel }}</td>
                    <td style="width:{{ $useSpeakerColumn ? '57%' : '87%' }}">
                        @if($sessionTypeLabel($first))<div class="plenary-title">{{ $sessionTypeLabel($first) }}</div>@endif
                        <div class="plenary-name">{{ $mainTitle($first) }}</div>
                    </td>
                    @if($useSpeakerColumn)
                        <td class="col-panelists">
                            <div class="panelists-label">{{ strtolower((string)$first->session_type) === 'panel' ? 'PANELISTS/MODERATORS' : 'Speaker(s)' }}</div>
                            <div class="panelists-list">
                                @foreach($speakers as $p)
                                    <div class="panelist-item">{{ $p }}</div>
                                @endforeach
                            </div>
                        </td>
                    @endif
                </tr>
                @if($first->session_chair)
                    <tr class="row-chair">
                        <td class="col-label">Chair</td>
                        <td colspan="{{ $contentColspan }}">{{ $first->session_chair }}</td>
                    </tr>
                @endif
                @if($first->session_rapporteur)
                    <tr class="row-rapport">
                        <td class="col-label">Rapporteur</td>
                        <td colspan="{{ $contentColspan }}">{{ $first->session_rapporteur }}</td>
                    </tr>
                @endif
            </table>
            @if($timeline->isNotEmpty())
            <table style="table-layout:fixed; width:100%; margin-top:0; border-top:none;">
                @foreach($timeline as $entry)
                    <tr class="row-schedule">
                        <td class="col-time" style="width:13%">{{ $entry['time'] ?: $timeLabel }}</td>
                        <td colspan="{{ $contentColspan }}">
                            @if($entry['type'] === 'abstract')
                                <span class="code-entry">{{ $entry['label'] }}</span>
                            @elseif($entry['type'] === 'discussion')
                                <span class="discussion-entry">Discussion</span>
                            @else
                                {{ $entry['label'] }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
            @endif

        @elseif($isParallel)
            @php
                $halls        = $sortHalls($sorted->pluck('room_location'), $sorted);
                $byHall       = $sorted->keyBy(fn($s) => $normalizeHall($s->room_location));
                $timelines    = $halls->mapWithKeys(fn($h) => [
                    $normalizeHall($h) => ($s = $byHall->get($normalizeHall($h))) ? $buildTimeline($s) : collect()
                ]);
                $maxRows      = max(1, $timelines->map->count()->max());
                $hallCount    = $halls->count();
                $hallWidth    = $hallCount > 0 ? round(87 / $hallCount, 1) : 87;
            @endphp
            {{-- Header rows: kept together, won't strand at page bottom --}}
            <table style="table-layout:fixed; width:100%; page-break-inside:avoid; margin-bottom:0; border-bottom:none;">
                <tr class="row-parallel-hdr">
                    <td style="width:11%">{{ $timeLabel }}</td>
                    @foreach($halls as $hall)
                        <td style="width:{{ $hallWidth }}%">{{ strtoupper($hall) }}</td>
                    @endforeach
                </tr>
                <tr class="row-halls">
                    <th style="font-size:6.8pt;">Parallel Sessions</th>
                    @foreach($halls as $hall)
                        @php $s = $byHall->get($normalizeHall($hall)); @endphp
                        <th class="cell-session" style="text-align:left; padding:2px 4px;">
                            @if($s)
                                <span class="session-name">{{ $s->name }}</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
                @if($sorted->contains(fn($s) => filled($s->session_chair)))
                <tr class="row-chair">
                    <td class="col-label" style="font-size:6.8pt;">Chair</td>
                    @foreach($halls as $hall)
                        @php $s = $byHall->get($normalizeHall($hall)); @endphp
                        <td style="font-size:7.1pt;">{{ $s?->session_chair ?: '—' }}</td>
                    @endforeach
                </tr>
                @endif
                @if($sorted->contains(fn($s) => filled($s->session_rapporteur)))
                <tr class="row-rapport">
                    <td class="col-label" style="font-size:6.8pt;">Rapporteur</td>
                    @foreach($halls as $hall)
                        @php $s = $byHall->get($normalizeHall($hall)); @endphp
                        <td style="font-size:7.1pt;">{{ $s?->session_rapporteur ?: '—' }}</td>
                    @endforeach
                </tr>
                @endif
                <tr class="row-codes-hdr">
                    <td style="width:11%">Time</td>
                    @foreach($halls as $hall)
                        <td style="width:{{ $hallWidth }}%">Abstract Code</td>
                    @endforeach
                </tr>
            </table>
            {{-- Schedule rows: separate table so they span pages without repeating the header --}}
            <table style="table-layout:fixed; width:100%; margin-top:0; border-top:none;">
                @for($row = 0; $row < $maxRows; $row++)
                    <tr class="row-schedule">
                        <td class="col-time" style="font-size:7.1pt; width:11%">
                            @php
                                $rowTime = '';
                                foreach ($halls as $h) {
                                    $entry = $timelines->get($normalizeHall($h))?->get($row);
                                    if ($entry && !empty($entry['time'])) { $rowTime = $entry['time']; break; }
                                }
                            @endphp
                            {{ $rowTime ?: ($row === 0 ? $timeLabel : '') }}
                        </td>
                        @foreach($halls as $hall)
                            @php $entry = $timelines->get($normalizeHall($hall))?->get($row); @endphp
                            <td style="text-align:center; vertical-align:middle; font-size:7.1pt; width:{{ $hallWidth }}%">
                                @if($entry)
                                    @if($entry['type'] === 'discussion')
                                        <span class="discussion-entry">Discussion</span>
                                    @else
                                        <span class="code-entry">{{ $entry['label'] }}</span>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endfor
            </table>

        @elseif($sorted->isNotEmpty() && !$isBreak && !$isLunch)
            @foreach($sorted as $session)
                @php
                    $timeline = $buildTimeline($session);
                    $speakers = $speakerColumn($session);
                    $useSpeakerColumn = !empty($speakers);
                    $contentColspan = $useSpeakerColumn ? 2 : 1;
                @endphp
                <table style="page-break-inside:avoid; margin-bottom:0; border-bottom:none;">
                    <tr class="row-plenary">
                        <td class="col-time" style="width:13%">{{ $timeLabel }}</td>
                        <td style="width:{{ $useSpeakerColumn ? '57%' : '87%' }}" @if(!$useSpeakerColumn) colspan="2" @endif>
                            @if($sessionTypeLabel($session))<div class="plenary-title">{{ $sessionTypeLabel($session) }}</div>@endif
                            <div class="plenary-name">{{ $mainTitle($session) }}</div>
                            @php
                                $effectiveLocation = $session->room_location ?: (in_array(strtolower((string)$session->session_type), ['plenary', 'panel'], true) ? config('conference.plenary_hall') : null);
                            @endphp
                            @if($effectiveLocation)
                                <div class="plenary-meta">Venue: {{ $effectiveLocation }}</div>
                            @endif
                        </td>
                        @if($useSpeakerColumn)
                            <td class="col-panelists">
                                <div class="panelists-label">{{ strtolower((string)$session->session_type) === 'panel' ? 'PANELISTS/MODERATORS' : 'Speaker(s)' }}</div>
                                <div class="panelists-list">
                                    @foreach($speakers as $p)
                                        <div class="panelist-item">{{ $p }}</div>
                                    @endforeach
                                </div>
                            </td>
                        @endif
                    </tr>
                    @if($session->session_chair)
                        <tr class="row-chair">
                            <td class="col-label">Chair</td>
                            <td colspan="{{ $contentColspan }}">{{ $session->session_chair }}</td>
                        </tr>
                    @endif
                    @if($session->session_rapporteur)
                        <tr class="row-rapport">
                            <td class="col-label">Rapporteur</td>
                            <td colspan="{{ $contentColspan }}">{{ $session->session_rapporteur }}</td>
                        </tr>
                    @endif
                </table>
                @if($timeline->isNotEmpty())
                <table style="margin-top:0; border-top:none;">
                    @foreach($timeline as $entry)
                        <tr class="row-schedule">
                            <td class="col-time" style="width:13%">{{ $entry['time'] ?: $timeLabel }}</td>
                            <td colspan="{{ $contentColspan }}">
                                @if($entry['type'] === 'abstract')
                                    <span class="code-entry">{{ $entry['label'] }}</span>
                                @elseif($entry['type'] === 'discussion')
                                    <span class="discussion-entry">Discussion</span>
                                @else
                                    {{ $entry['label'] }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
                @endif
            @endforeach
        @endif
    @endforeach

@empty
    <div style="text-align:center; padding:20mm; color:#666;">
        <p style="font-size:11pt; font-weight:bold;">No sessions scheduled yet.</p>
        <p style="font-size:9pt;">Build the programme in the session builder and return here to export.</p>
    </div>
@endforelse

<div class="abstract-index">
    <div class="index-title">Abstract Code Index</div>

    @forelse($abstractIndex as $subtheme => $abstracts)
        @php $abstractList = collect($abstracts); $firstEntry = $abstractList->first(); $restEntries = $abstractList->slice(1); @endphp
        <div style="page-break-inside: avoid;">
            <div class="index-subtheme">{{ $subtheme }}</div>
            @if($firstEntry)
                <div class="index-entries">
                    <div class="index-entry">
                        <span class="index-entry-code">{{ $firstEntry['code'] }}</span>&nbsp;<span class="index-entry-title">{{ $firstEntry['title'] ? \App\Support\TitleFormatter::sentenceCase($firstEntry['title']) : 'Untitled abstract' }}</span>@if($firstEntry['author'])&nbsp;<span class="index-entry-author">— {{ $firstEntry['author'] }}</span>@endif
                    </div>
                </div>
            @endif
        </div>
        @if($restEntries->isNotEmpty())
            <div class="index-entries">
                @foreach($restEntries as $abstract)
                    <div class="index-entry">
                        <span class="index-entry-code">{{ $abstract['code'] }}</span>&nbsp;<span class="index-entry-title">{{ $abstract['title'] ? \App\Support\TitleFormatter::sentenceCase($abstract['title']) : 'Untitled abstract' }}</span>@if($abstract['author'])&nbsp;<span class="index-entry-author">— {{ $abstract['author'] }}</span>@endif
                    </div>
                @endforeach
            </div>
        @endif
    @empty
        <div class="index-blank">No abstract codes available.</div>
    @endforelse
</div>

</body>
</html>
