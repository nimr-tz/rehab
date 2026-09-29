<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>{{ config('conference.short_name') }} {{ config('conference.year') }} Programme and Abstract Book</title>
<style>

/* ═══════════════════════════════════════════
   PAGE SETUP
═══════════════════════════════════════════ */
@page {
    margin: 14mm 13mm 14mm 14mm;
    size: A4 portrait;
}
* { box-sizing: border-box; }
body {
    font-family: Arial, 'DejaVu Sans', sans-serif;
    font-size: 9.5pt;
    line-height: 1.35;
    color: #1a1a2e;
    margin: 0;
    padding: 0;
}

/* ═══════════════════════════════════════════
   FIXED FOOTER
═══════════════════════════════════════════ */
.page-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 14mm;
    border-top: 2pt solid #c89b3c;
    padding: 3mm 0 0 0;
    font-size: 7.5pt;
    color: #666;
    font-family: Arial, 'DejaVu Sans', sans-serif;
}
.footer-left  { float: left; font-style: italic; }
.footer-right { float: right; }
.footer-clear { clear: both; }

/* ═══════════════════════════════════════════
   SECTION HEADINGS
═══════════════════════════════════════════ */
.section-heading {
    font-size: 17pt;
    font-weight: bold;
    color: #152b5e;
    letter-spacing: -0.3pt;
    margin: 0 0 1.5mm 0;
    line-height: 1.15;
}
.section-heading-rule {
    border: none;
    border-top: 2.5pt solid #c89b3c;
    width: 30mm;
    margin: 0 0 4mm 0;
}

/* ═══════════════════════════════════════════
   BODY TEXT
═══════════════════════════════════════════ */
.body-text {
    font-size: 9.5pt;
    line-height: 1.35;
    text-align: justify;
    margin-bottom: 2.5mm;
    color: #1a1a2e;
}

/* ═══════════════════════════════════════════
   CONFERENCE ORGANIZATION
═══════════════════════════════════════════ */
.org-page { page-break-after: always; }

.committee-grid {
    width: 100%;
    border-collapse: collapse;
}
.committee-grid td {
    vertical-align: top;
    width: 50%;
    padding: 0 5mm 6mm 0;
}
.committee-grid td:last-child { padding-right: 0; }

.committee-block {
    background: #f4f6fb;
    border-top: 3pt solid #152b5e;
    padding: 4mm 5mm 4mm 5mm;
}
.committee-block-header {
    font-size: 8.5pt;
    font-weight: bold;
    color: #152b5e;
    text-transform: uppercase;
    letter-spacing: 1pt;
    margin-bottom: 3mm;
}
.committee-list {
    margin: 0;
    padding-left: 5mm;
    list-style-type: decimal;
}
.committee-list li {
    font-size: 9.5pt;
    line-height: 1.5;
    margin-bottom: 0.5mm;
    color: #1a1a2e;
}
.committee-list li em {
    font-size: 8.5pt;
    color: #c89b3c;
    font-style: normal;
    font-weight: bold;
}

/* ═══════════════════════════════════════════
   TABLE OF CONTENTS
═══════════════════════════════════════════ */
.toc-page { page-break-after: always; }

.toc-group-label {
    font-size: 8pt;
    font-weight: bold;
    color: #c89b3c;
    text-transform: uppercase;
    letter-spacing: 1.5pt;
    margin: 5mm 0 2mm 0;
    padding-bottom: 1.5mm;
    border-bottom: 0.5pt solid #e0d5c0;
}
.toc-table {
    width: 100%;
    border-collapse: collapse;
}
.toc-table td {
    padding: 1.5mm 2mm;
    border-bottom: 0.3pt dotted #ccc;
    vertical-align: middle;
}
.toc-row-num {
    width: 10mm;
    font-size: 9pt;
    font-weight: bold;
    color: #c89b3c;
}
.toc-row-text {
    font-size: 9.5pt;
    color: #1a1a2e;
}
.toc-row-right {
    font-size: 8.5pt;
    color: #888;
    text-align: right;
    white-space: nowrap;
    font-style: italic;
    width: 30mm;
}
.toc-indent .toc-row-num { padding-left: 7mm; }

/* ═══════════════════════════════════════════
   CONTENT PAGES (background, foreword, sub themes)
═══════════════════════════════════════════ */
.content-page { page-break-after: always; }


.foreword-signatory {
    margin-top: 10mm;
    border-left: 3pt solid #c89b3c;
    padding-left: 4mm;
}
.foreword-signatory-name {
    font-weight: bold;
    font-size: 11pt;
    color: #152b5e;
}
.foreword-signatory-role {
    font-size: 9.5pt;
    color: #555;
    margin-top: 0.5mm;
    line-height: 1.6;
}

.subtheme-list-entry {
    display: table;
    width: 100%;
    margin-bottom: 3mm;
    padding-bottom: 3mm;
    border-bottom: 0.3pt dotted #ccc;
    page-break-inside: avoid;
}
.subtheme-list-num {
    display: table-cell;
    width: 10mm;
    font-size: 13pt;
    font-weight: bold;
    color: #c89b3c;
    vertical-align: top;
    padding-top: 0.5mm;
}
.subtheme-list-body { display: table-cell; vertical-align: top; }
.subtheme-list-name {
    font-size: 10.5pt;
    font-weight: bold;
    color: #152b5e;
}

/* ═══════════════════════════════════════════
   ABSTRACT SUB-THEME DIVIDER
═══════════════════════════════════════════ */
.theme-section-divider {
    page-break-before: always;
    background-color: #152b5e;
    margin: -14mm -13mm 6mm -14mm;
    padding: 9mm 13mm 7mm 14mm;
}
.theme-section-label {
    font-size: 8pt;
    color: #c89b3c;
    text-transform: uppercase;
    letter-spacing: 2.5pt;
    margin-bottom: 1.5mm;
    font-weight: bold;
}
.theme-section-title {
    font-size: 15pt;
    font-weight: bold;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 2mm;
}
.theme-section-count {
    font-size: 8.5pt;
    color: #a8c5e2;
}
.theme-section-rule {
    border: none;
    border-top: 2pt solid #c89b3c;
    width: 40mm;
    margin: 3mm 0 0 0;
}

/* ═══════════════════════════════════════════
   ABSTRACT ENTRY
═══════════════════════════════════════════ */
.abstract-entry {
    margin-bottom: 3mm;
    padding-bottom: 2mm;
    border-bottom: 0.5pt solid #d5d0c8;
    /* Allow the (often long) body to break across pages so abstracts fill the
       page instead of being pushed wholesale to the next one, which left large
       blank gaps at the bottom of pages. */
    page-break-inside: auto;
}

/* Keep code + title + authors together so a heading is never orphaned at the
   very bottom of a page away from its body. */
.abstract-head {
    page-break-inside: avoid;
}

.abstract-header-row {
    margin-bottom: 1mm;
}

.abstract-code {
    display: inline-block;
    font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
    font-size: 7.5pt;
    font-weight: bold;
    color: #ffffff;
    background-color: #152b5e;
    padding: 0.8mm 4mm;
    letter-spacing: 0.5pt;
    margin-right: 2mm;
    vertical-align: middle;
}
.abstract-mode-badge {
    display: inline-block;
    font-size: 7pt;
    font-weight: bold;
    color: #2e7d32;
    border: 0.8pt solid #2e7d32;
    padding: 0.3mm 3mm;
    vertical-align: middle;
}
.abstract-mode-badge.poster {
    color: #b45309;
    border-color: #b45309;
}

.abstract-title {
    font-size: 10pt;
    font-weight: bold;
    color: #152b5e;
    line-height: 1.25;
    margin: 1mm 0 1mm 0;
}

.abstract-authors {
    font-size: 8.5pt;
    line-height: 1.3;
    margin-bottom: 0.5mm;
    color: #1a1a2e;
}
.aff-sup {
    font-size: 6.5pt;
    vertical-align: super;
    color: #152b5e;
}

.affiliations-block {
    background: #f4f6fb;
    border-left: 2.5pt solid #c89b3c;
    padding: 1mm 3mm;
    margin: 1mm 0 1.5mm 0;
}
.affiliations-header {
    font-size: 7.5pt;
    font-weight: bold;
    color: #152b5e;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    margin-bottom: 1mm;
}
.affiliation-item {
    font-size: 8pt;
    color: #444;
    font-style: italic;
    line-height: 1.25;
    margin-bottom: 0.3mm;
}

.abstract-body {
    font-size: 9pt;
    line-height: 1.3;
    text-align: justify;
    margin: 1mm 0;
    color: #1a1a2e;
}
.abstract-section {
    margin: 0 0 1mm 0;
    text-align: justify;
    line-height: 1.3;
}
.abstract-section:last-child { margin-bottom: 0; }
.abstract-section strong { color: #152b5e; }

.abstract-keywords {
    font-size: 8pt;
    color: #333;
    margin-top: 1mm;
    padding-top: 1mm;
    border-top: 0.3pt dotted #bbb;
}
.abstract-keywords strong {
    font-size: 7.5pt;
    color: #152b5e;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
}

/* ═══════════════════════════════════════════
   CONFERENCE SPEAKERS — biography layout
═══════════════════════════════════════════ */
.speakers-section { page-break-before: always; }

.speaker-type-strip {
    background-color: #152b5e;
    color: #ffffff;
    font-size: 8pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2pt;
    padding: 1.5mm 5mm;
    margin: 3mm 0 3mm 0;
}

/* Each entry uses a floated photo so the bio text wraps beside AND below it.
   Unlike a <table>, this block can break across page boundaries, so a long
   bio splits between pages instead of bumping the whole card and leaving a
   large blank gap at the bottom of the previous page. */
.speaker-bio-entry {
    margin-bottom: 3mm;
    padding-bottom: 2.5mm;
    border-bottom: 0.3pt solid #e8e8e8;
}
.speaker-photo {
    float: left;
    width: 26mm;
    height: 32mm;
    object-fit: cover;
    border: 1.5pt solid #e8e8e8;
    margin: 0 5mm 1.5mm 0;
}
.speaker-bio-initials {
    float: left;
    width: 26mm;
    height: 32mm;
    background-color: #152b5e;
    color: #c89b3c;
    font-weight: bold;
    font-size: 20pt;
    text-align: center;
    line-height: 32mm;
    margin: 0 5mm 1.5mm 0;
}
.speaker-bio-clear { clear: both; height: 0; line-height: 0; font-size: 0; }
.speaker-type-pill {
    display: inline-block;
    font-size: 6.5pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    padding: 0.5mm 3mm;
    color: #fff;
    margin-bottom: 1.5mm;
}
.pill-keynote  { background-color: #c89b3c; }
.pill-plenary  { background-color: #152b5e; }
.pill-panelist { background-color: #1e5f8a; }
.pill-invited  { background-color: #3d7a52; }
.pill-other    { background-color: #666; }

.speaker-bio-name {
    font-size: 11pt;
    font-weight: bold;
    color: #152b5e;
    margin-bottom: 0.5mm;
    line-height: 1.2;
}
.speaker-bio-position {
    font-size: 9pt;
    font-style: italic;
    color: #444;
    line-height: 1.3;
    margin-bottom: 0.5mm;
}
.speaker-bio-affiliation {
    font-size: 9pt;
    color: #333;
    line-height: 1.3;
    margin-bottom: 2mm;
}
.speaker-bio-text {
    font-size: 9.5pt;
    line-height: 1.5;
    text-align: justify;
    color: #1a1a2e;
}
.bio-clearfix {
    clear: both;
    border-bottom: 0.5pt solid #d5d0c8;
    margin-top: 5mm;
    margin-bottom: 5mm;
}

/* ═══════════════════════════════════════════
   AUTHOR INDEX
═══════════════════════════════════════════ */
.author-index-section { page-break-before: always; }

.index-letter-header {
    font-size: 14pt;
    font-weight: bold;
    color: #152b5e;
    border-bottom: 1.5pt solid #c89b3c;
    padding-bottom: 1mm;
    margin-bottom: 2mm;
    margin-top: 5mm;
}
.index-two-col { width: 100%; border-collapse: collapse; }
.index-two-col td { vertical-align: top; width: 50%; padding-right: 5mm; border: none; }
.index-author-row { font-size: 8.5pt; margin-bottom: 1mm; line-height: 1.3; }
.index-author-name { font-weight: bold; color: #1a1a2e; }
.index-author-codes {
    font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
    font-size: 7.5pt;
    color: #666;
    margin-left: 2mm;
}

/* ═══════════════════════════════════════════
   BACK COVER
═══════════════════════════════════════════ */
.back-cover {
    page-break-before: always;
    background-color: #152b5e;
    margin: -22mm -18mm -20mm -22mm;
    padding: 45mm 25mm 30mm 25mm;
    text-align: center;
    min-height: 297mm;
}
.back-cover img {
    width: 32mm;
    display: block;
    margin: 0 auto 8mm auto;
    filter: brightness(0) invert(1);
}
.back-cover-divider {
    border: none;
    border-top: 1.5pt solid #c89b3c;
    width: 40mm;
    margin: 6mm auto;
}
.back-cover-org {
    color: #ffffff;
    font-size: 12pt;
    font-weight: bold;
    letter-spacing: 0.3pt;
    line-height: 1.5;
}
.back-cover-conf {
    color: #a8c5e2;
    font-size: 10pt;
    line-height: 1.6;
    margin-top: 5mm;
}
.back-cover-contact {
    color: #c89b3c;
    font-size: 9pt;
    line-height: 1.7;
    margin-top: 8mm;
}
</style>
</head>
<body>

@php
    $confName     = trim((string) config('conference.name'));
    $shortName    = trim((string) config('conference.short_name'));
    $year         = trim((string) config('conference.year'));
    $edition      = trim((string) config('conference.edition'));
    $theme        = trim((string) config('conference.theme',         ''));
    $displayDates = trim((string) config('conference.display_dates', ''));
    $venue        = trim((string) config('conference.venue',         ''));
    $city         = trim((string) config('conference.city',          ''));
    $country      = trim((string) config('conference.country',       ''));
    $host         = trim((string) config('conference.host'));
    $hostShort    = trim((string) config('conference.host_short'));
    $contactEmail = trim((string) config('conference.contact_email'));
    $venueLine    = implode(', ', array_filter(array_unique([$venue, $city, $country])));
    $logoPath     = public_path(config('conference.logo_mark_path'));

    $totalAbstracts = $abstractsByTheme->flatten()->count();
    $totalThemes    = $abstractsByTheme->keys()->count();
    $totalOral      = $abstractsByTheme->flatten()->where('presentation_mode', 'Oral')->count();
    $totalPoster    = $abstractsByTheme->flatten()->where('presentation_mode', 'Poster')->count();

    $subthemes        = array_keys(config('conference.subtheme_prefixes', []));
    $subthemePrefixes = config('conference.subtheme_prefixes', []);

    $committees = array_filter(config('abstract_book.committees', []));
    $foreword   = config('abstract_book.foreword', []);
    $hasForeword = ! empty($foreword['paragraphs']);

    // Render a structured abstract body: each section label (Introduction,
    // Methods, Results, Conclusion, …) starts its own paragraph with a bold
    // heading, instead of being jammed inline mid-sentence.
    //
    // The parser itself lives in App\Support\AbstractBodyFormatter so that the
    // author-facing proceedings-correction preview renders identically to the book.
    $formatAbstractBody = fn (string $raw): string => \App\Support\AbstractBodyFormatter::toHtml($raw);
@endphp

{{-- The page footer (gold rule + conference/host label + "Page X of Y") is
     drawn during the FPDI cover-assembly step
     (AbstractBookGenerationService::prependCoverPdf). dompdf's position:fixed
     footer mis-rendered mid-page (overlapping body text) and its {PAGE_COUNT}
     placeholder did not survive FPDI re-importing the pages ("###"). Drawing it
     in FPDI gives exact, overlap-free positioning with a correct total count. --}}

{{-- ══════════════════════════════════════════════════════ --}}
{{-- CONFERENCE ORGANIZATION                                 --}}
{{-- ══════════════════════════════════════════════════════ --}}
@if(count($committees) > 0)
<div class=\"org-page\">
    <div class="section-heading">Conference Committees</div>
    <hr class="section-heading-rule">

    @php $committeePairs = array_chunk(array_keys($committees), 2); @endphp

    @foreach($committeePairs as $pair)
        <table class="committee-grid" style="margin-bottom:0;">
            <tbody><tr>
                @foreach($pair as $committeeName)
                    <td>
                        <div class="committee-block">
                            <div class="committee-block-header">{{ $committeeName }}</div>
                            <ol class="committee-list">
                                @foreach($committees[$committeeName] as $member)
                                    <li>
                                        {{ $member['name'] }}
                                        @if(in_array($member['role'], ['Chairperson', 'Secretary']))
                                            <em>&nbsp;({{ $member['role'] }})</em>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </td>
                @endforeach
                @if(count($pair) === 1)<td></td>@endif
            </tr></tbody>
        </table>
        @if(!$loop->last)<div style="height:4mm;"></div>@endif
    @endforeach
</div>
@endif

{{-- ══════════════════════════════════════════════════════ --}}
{{-- TABLE OF CONTENTS                                       --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="toc-page">
    <div class="section-heading">Table of Contents</div>
    <hr class="section-heading-rule">

    <table class="toc-table" cellspacing="0" cellpadding="0">
        <tbody>

        <tr><td colspan="3" class="toc-group-label" style="border-bottom:0.5pt solid #e0d5c0; padding-top:4mm;">Front Matter</td></tr>
        @foreach(array_filter(['Background', $hasForeword ? 'Foreword' : null, 'Sub Themes']) as $fm)
        <tr>
            <td class="toc-row-num">&mdash;</td>
            <td class="toc-row-text">{{ $fm }}</td>
            <td class="toc-row-right">&nbsp;</td>
        </tr>
        @endforeach

        <tr><td colspan="3" class="toc-group-label" style="border-bottom:0.5pt solid #e0d5c0; padding-top:4mm;">Abstracts by Sub Theme</td></tr>
        @php $secN = 0; @endphp
        @foreach($abstractsByTheme as $themeName => $themeAbstracts)
            @php $secN++; @endphp
            <tr>
                <td class="toc-row-num">{{ $secN }}.</td>
                <td class="toc-row-text">{{ $themeName }}</td>
                <td class="toc-row-right">{{ $themeAbstracts->count() }} abstract(s)</td>
            </tr>
        @endforeach

        @if(isset($speakers) && $speakers->count() > 0)
        <tr><td colspan="3" class="toc-group-label" style="border-bottom:0.5pt solid #e0d5c0; padding-top:4mm;">Speakers &amp; Index</td></tr>
        <tr>
            <td class="toc-row-num">&mdash;</td>
            <td class="toc-row-text">Conference Speakers</td>
            <td class="toc-row-right">{{ $speakers->count() }} speaker(s)</td>
        </tr>
        @endif

        </tbody>
    </table>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- BACKGROUND                                              --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="content-page">
    <div class="section-heading">Background</div>
    <hr class="section-heading-rule">

    <p class="body-text">The {{ $edition }} {{ $confName }} ({{ $shortName }} {{ $year }}) is organised by {{ $host }} and takes place at {{ $venueLine }} from {{ $displayDates }}.</p>
    @if($theme !== '')
    <p class="body-text">The theme of this edition is: <em>{{ $theme }}</em>.</p>
    @endif
    <p class="body-text">This book presents the programme and the {{ $totalAbstracts }} accepted abstract(s) across {{ $totalThemes }} sub theme(s): {{ $totalOral }} oral and {{ $totalPoster }} poster presentation(s).</p>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- FOREWORD                                                --}}
{{-- ══════════════════════════════════════════════════════ --}}
@if($hasForeword)
<div class="content-page">
    <div class="section-heading">Foreword</div>
    <hr class="section-heading-rule">

    @foreach($foreword['paragraphs'] as $paragraph)
        <p class="body-text">{{ $paragraph }}</p>
    @endforeach

    @if(filled($foreword['signatory_name'] ?? null))
    <div class="foreword-signatory">
        <div class="foreword-signatory-name">{{ $foreword['signatory_name'] }}</div>
        @if(filled($foreword['signatory_role'] ?? null))
        <div class="foreword-signatory-role">{{ $foreword['signatory_role'] }}</div>
        @endif
    </div>
    @endif
</div>
@endif

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SUB THEMES                                              --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="content-page" style="page-break-after: auto;">
    <div class="section-heading">Sub Themes</div>
    <hr class="section-heading-rule">

    <p class="body-text">The {{ $edition }} {{ $confName }} covers {{ count($subthemes) }} sub themes.</p>

    @foreach($subthemes as $i => $subthemeName)
        <div class="subtheme-list-entry">
            <div class="subtheme-list-num">{{ $i + 1 }}.</div>
            <div class="subtheme-list-body">
                <div class="subtheme-list-name">{{ $subthemeName }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- ABSTRACTS BY SUB THEME                                  --}}
{{-- ══════════════════════════════════════════════════════ --}}
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
            $affMaps = [];
            $nextAff = 1;
            $mainAff = \App\Support\TitleFormatter::affiliation($abstract->author_institute ?? '');
            if ($mainAff !== '') {
                $affMaps[$mainAff] = $nextAff++;
            }
            $coauthors = $abstract->coauthors ?? [];
            if (is_string($coauthors)) {
                $coauthors = json_decode($coauthors, true) ?? [];
            }
            foreach ($coauthors as $co) {
                // Co-authors are stored with an 'institute' key (legacy data may
                // use 'affiliation'); the old code read only 'affiliation', so
                // every co-author was mis-attributed to the main author.
                $coAff = is_array($co) ? \App\Support\TitleFormatter::affiliation($co['institute'] ?? $co['affiliation'] ?? '') : '';
                if ($coAff !== '' && !isset($affMaps[$coAff])) {
                    $affMaps[$coAff] = $nextAff++;
                }
            }
            $abstractText = trim(html_entity_decode(strip_tags((string)($abstract->description ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $abstractHtml = $formatAbstractBody($abstractText);
            $mode = strtolower($abstract->presentation_mode ?? '');
        @endphp

        <div class="abstract-entry">

            <div class="abstract-head">
                <div class="abstract-header-row">
                    <span class="abstract-code">{{ $abstract->conference_code }}</span>
                </div>

                <div class="abstract-title">{{ \App\Support\TitleFormatter::sentenceCase($abstract->title) }}</div>

                <div class="abstract-authors">
                    @if($mainAff !== '')
                        <strong>{{ \App\Support\TitleFormatter::personName($abstract->author_name) }}</strong><span class="aff-sup">{{ $affMaps[$mainAff] }}</span>
                    @else
                        <strong>{{ \App\Support\TitleFormatter::personName($abstract->author_name) }}</strong>
                    @endif
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

            {{-- Affiliations sit OUTSIDE .abstract-head: a long (4–5 line) affiliations
                 block kept inside the un-breakable head made the whole head too tall to
                 fit near a page bottom, so it jumped to the next page and left a large
                 blank gap. Out here it can break naturally while code+title+authors stay
                 together (so a heading is never orphaned). --}}
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

            @php
                $kwValue = '';
                if (!empty($abstract->keywords)) $kwValue = $abstract->keywords;
                elseif (!empty($abstract->subtheme) && $abstract->subtheme !== $themeName) $kwValue = $abstract->subtheme;
            @endphp
            @if($kwValue)
                <div class="abstract-keywords"><strong>Keywords:</strong> {{ \App\Support\TitleFormatter::keywords($kwValue) }}</div>
            @endif

        </div>
    @endforeach

@endforeach

{{-- ══════════════════════════════════════════════════════ --}}
{{-- CONFERENCE SPEAKERS                                     --}}
{{-- ══════════════════════════════════════════════════════ --}}
@if(isset($speakers) && $speakers->count() > 0)
<div class="speakers-section">
    <div class="section-heading">Conference Speakers</div>
    <hr class="section-heading-rule">

    @php
        $spkTypeLabels  = ['keynote'=>'Keynote Speakers','plenary'=>'Plenary Speakers','panelist'=>'Panelists','invited'=>'Invited Speakers'];
        $speakersByType = $speakers->groupBy('type');
        $typeOrder      = ['keynote','plenary','panelist','invited'];
        $otherTypes     = $speakersByType->keys()->diff($typeOrder)->values();
        $orderedTypes   = collect($typeOrder)->merge($otherTypes)->filter(fn($t) => $speakersByType->has($t));
    @endphp

    @foreach($orderedTypes as $type)
        <div class="speaker-type-strip">{{ $spkTypeLabels[$type] ?? ucfirst($type).' Speakers' }}</div>

        @foreach($speakersByType[$type] as $spk)
            @php
                $spkInitial  = strtoupper(mb_substr($spk->name, 0, 1));
                $spkFullName = trim(implode(' ', array_filter([$spk->title ?? '', $spk->name])));
                $spkPhoto    = $spk->pdf_photo_path ?? null;
                $spkBio      = $spk->bio ?? '';
            @endphp
            <div class="speaker-bio-entry">
                @if($spkPhoto)
                    <img class="speaker-photo" src="{{ $spkPhoto }}" alt="{{ $spk->name }}">
                @else
                    <span class="speaker-bio-initials">{{ $spkInitial }}</span>
                @endif
                <div class="speaker-bio-name">{{ $spkFullName }}</div>
                @if($spk->position ?? null)
                    <div class="speaker-bio-position">{{ $spk->position }}</div>
                @endif
                @if($spk->affiliation ?? null)
                    <div class="speaker-bio-affiliation">{{ $spk->affiliation }}</div>
                @endif
                @if($spkBio)
                    <div class="speaker-bio-text" style="margin-top:1.5mm;">{{ $spkBio }}</div>
                @endif
                <div class="speaker-bio-clear"></div>
            </div>
        @endforeach
    @endforeach
</div>
@endif

{{-- Back cover is appended as COVER PAGE.pdf by AbstractBookGenerationService::prependCoverPdf --}}

</body>
</html>
