<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>{{ config('conference.short_name',config('conference.short_name')) }} {{ config('conference.year','2026') }} Conference Proceedings</title>
<style>
/* Typography and colours are lifted from the abstract book's stylesheet so an
   abstract reads identically in both volumes. Only the front matter differs:
   the proceedings carries a cover page and then goes straight to the science. */
@page { margin: 14mm 13mm 14mm 14mm; size: A4 portrait; }
* { box-sizing: border-box; }
body {
    font-family: Arial, 'DejaVu Sans', sans-serif;
    font-size: 9.5pt;
    line-height: 1.35;
    color: #1a1a2e;
    margin: 0;
    padding: 0;
}

/* ─── Cover ─────────────────────────────────────────── */
.cover { page-break-after: always; }
.cover-panel {
    background-color: #152b5e;
    margin: 0 -13mm 0 -14mm;
    padding: 14mm 14mm 12mm 14mm;
}
.cover-eyebrow {
    font-size: 8pt; color: #c89b3c; text-transform: uppercase;
    letter-spacing: 2.5pt; font-weight: bold; margin-bottom: 3mm;
}
.cover-title { font-size: 24pt; font-weight: bold; color: #ffffff; line-height: 1.15; margin-bottom: 2mm; }
.cover-year  { font-size: 15pt; font-weight: bold; color: #a8c5e2; }
.cover-rule  { border: none; border-top: 2.5pt solid #c89b3c; width: 40mm; margin: 8mm 0 6mm 0; }
.cover-theme { font-size: 11pt; font-style: italic; color: #444; margin-bottom: 8mm; line-height: 1.4; }
.cover-meta  { font-size: 10pt; color: #1a1a2e; margin-bottom: 1.5mm; }
.cover-host  { font-size: 9pt; color: #666; margin-top: 8mm; }
.cover-count { font-size: 8.5pt; font-style: italic; color: #666; margin-top: 4mm; }

/* ─── Sub theme divider ─────────────────────────────── */
.theme-section-divider {
    page-break-before: always;
    background-color: #152b5e;
    margin: -14mm -13mm 6mm -14mm;
    padding: 9mm 13mm 7mm 14mm;
}
.theme-section-label {
    font-size: 8pt; color: #c89b3c; text-transform: uppercase;
    letter-spacing: 2.5pt; margin-bottom: 1.5mm; font-weight: bold;
}
.theme-section-title { font-size: 15pt; font-weight: bold; color: #ffffff; line-height: 1.2; margin-bottom: 2mm; }
.theme-section-count { font-size: 8.5pt; color: #a8c5e2; }
.theme-section-rule { border: none; border-top: 2pt solid #c89b3c; width: 40mm; margin: 3mm 0 0 0; }

/* ─── Abstract entry ────────────────────────────────── */
.abstract-entry {
    margin-bottom: 3mm;
    padding-bottom: 2mm;
    border-bottom: 0.5pt solid #d5d0c8;
    page-break-inside: auto;
}
/* Keep code + title + authors together so a heading is never orphaned at the
   bottom of a page, away from its body. */
.abstract-head { page-break-inside: avoid; }
.abstract-header-row { margin-bottom: 1mm; }
.abstract-code {
    display: inline-block;
    font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
    font-size: 7.5pt; font-weight: bold; color: #ffffff;
    background-color: #152b5e; padding: 0.8mm 4mm;
    letter-spacing: 0.5pt; margin-right: 2mm; vertical-align: middle;
}
.abstract-title { font-size: 10pt; font-weight: bold; color: #152b5e; line-height: 1.25; margin: 1mm 0; }
.abstract-authors { font-size: 8.5pt; line-height: 1.3; margin-bottom: 0.5mm; color: #1a1a2e; }
.aff-sup { font-size: 6.5pt; vertical-align: super; color: #152b5e; }

.affiliations-block {
    background: #f4f6fb;
    border-left: 2.5pt solid #c89b3c;
    padding: 1mm 3mm;
    margin: 1mm 0 1.5mm 0;
}
.affiliations-header {
    font-size: 7.5pt; font-weight: bold; color: #152b5e;
    text-transform: uppercase; letter-spacing: 0.5pt; margin-bottom: 1mm;
}
.affiliation-item { font-size: 8pt; color: #444; font-style: italic; line-height: 1.25; margin-bottom: 0.3mm; }

.abstract-body { font-size: 9pt; line-height: 1.3; text-align: justify; margin: 1mm 0; color: #1a1a2e; }
.abstract-section { margin: 0 0 1mm 0; text-align: justify; line-height: 1.3; }
.abstract-section:last-child { margin-bottom: 0; }
.abstract-section strong { color: #152b5e; }

.abstract-keywords {
    font-size: 8pt; color: #333; margin-top: 1mm;
    padding-top: 1mm; border-top: 0.3pt dotted #bbb;
}
.abstract-keywords strong {
    font-size: 7.5pt; color: #152b5e;
    text-transform: uppercase; letter-spacing: 0.5pt;
}

.empty-note { font-size: 11pt; font-style: italic; color: #666; margin-top: 20mm; }
</style>
</head>
<body>

@php
    $confName  = trim((string) config('conference.name', config('conference.name')));
    $edition   = trim((string) config('conference.edition', ''));
    $year      = trim((string) config('conference.year', date('Y')));
    $theme     = trim((string) config('conference.theme', ''));
    $dates     = trim((string) config('conference.display_dates', ''));
    $venueLine = implode(', ', array_filter(array_unique([
        trim((string) config('conference.venue', '')),
        trim((string) config('conference.city', '')),
        trim((string) config('conference.country', '')),
    ])));
    $host      = trim((string) config('conference.host', config('conference.host')));

    $totalAbstracts = $abstractsByTheme->flatten()->count();
    $totalThemes    = $abstractsByTheme->keys()->count();
@endphp

{{-- ── Cover ─────────────────────────────────────────── --}}
<div class="cover">
    <div class="cover-panel">
        <div class="cover-eyebrow">Conference Proceedings</div>
        <div class="cover-title">{{ trim($edition.' '.$confName) }}</div>
        <div class="cover-year">{{ $year }}</div>
    </div>

    <hr class="cover-rule">

    @if($theme !== '')
        <div class="cover-theme">{{ $theme }}</div>
    @endif

    @foreach(array_filter([$dates, $venueLine]) as $line)
        <div class="cover-meta">{{ $line }}</div>
    @endforeach

    <div class="cover-host">Hosted by {{ $host }}</div>
    <div class="cover-count">
        {{ $totalAbstracts }} {{ \Illuminate\Support\Str::plural('abstract', $totalAbstracts) }}
        across {{ $totalThemes }} {{ \Illuminate\Support\Str::plural('sub theme', $totalThemes) }}
    </div>
</div>

{{-- ── Abstracts by sub theme ────────────────────────── --}}
@if($abstractsByTheme->isEmpty())
    <div class="empty-note">No abstracts have been marked for inclusion in the conference proceedings.</div>
@endif

@php $secN = 0; @endphp
@foreach($abstractsByTheme as $themeName => $themeAbstracts)
    @php $secN++; @endphp

    <div class="theme-section-divider">
        <div class="theme-section-label">Sub Theme {{ $secN }}</div>
        <div class="theme-section-title">{{ $themeName }}</div>
        <div class="theme-section-count">{{ $themeAbstracts->count() }} Abstract(s)</div>
        <hr class="theme-section-rule">
    </div>

    @foreach($themeAbstracts as $abstract)
        @php
            $affMaps = \App\Support\AbstractGrouping::affiliationMap($abstract);
            $coauthors = \App\Support\AbstractGrouping::coauthors($abstract);
            $mainAff = \App\Support\TitleFormatter::affiliation($abstract->author_institute ?? '');
            $abstractHtml = \App\Support\AbstractBodyFormatter::render($abstract->description);
        @endphp

        <div class="abstract-entry">

            <div class="abstract-head">
                <div class="abstract-header-row">
                    <span class="abstract-code">{{ $abstract->conference_code }}</span>
                </div>

                <div class="abstract-title">{{ \App\Support\TitleFormatter::sentenceCase($abstract->title) }}</div>

                <div class="abstract-authors">
                    <strong>{{ \App\Support\TitleFormatter::personName($abstract->author_name) }}</strong>@if($mainAff !== '' && isset($affMaps[$mainAff]))<span class="aff-sup">{{ $affMaps[$mainAff] }}</span>@endif
                    @foreach($coauthors as $co)
                        @php
                            $coName = is_array($co) ? trim((string)($co['name'] ?? '')) : trim((string)$co);
                            $coAff  = is_array($co) ? \App\Support\TitleFormatter::affiliation($co['institute'] ?? $co['affiliation'] ?? '') : '';
                            $coIdx  = ($coAff !== '' && isset($affMaps[$coAff])) ? $affMaps[$coAff] : null;
                        @endphp
                        @if($coName)
                            , {{ \App\Support\TitleFormatter::personName($coName) }}@if($coIdx)<span class="aff-sup">{{ $coIdx }}</span>@endif
                        @endif
                    @endforeach
                </div>
            </div>

            @if(count($affMaps) > 0)
                <div class="affiliations-block">
                    <div class="affiliations-header">Affiliations</div>
                    @foreach($affMaps as $affName => $affId)
                        @if(trim($affName) !== '')
                            <div class="affiliation-item">{{ $affId }}. {{ \App\Support\TitleFormatter::tidyAffiliation($affName) }}</div>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="abstract-body">{!! $abstractHtml !!}</div>

            @if(!empty($abstract->keywords))
                <div class="abstract-keywords"><strong>Keywords:</strong> {{ \App\Support\TitleFormatter::keywords($abstract->keywords) }}</div>
            @endif

        </div>
    @endforeach
@endforeach

</body>
</html>
