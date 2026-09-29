<?php

namespace App\Support;

/**
 * Renders a free-text abstract body as structured IMRaD sections.
 *
 * Authors submit abstract text with wildly inconsistent section headings, so
 * this parser normalises the common variants and splits the text into labelled
 * blocks. It is deliberately conservative: no matched token is ever discarded,
 * and only the standard IMRaD labels are recognised (see $labels below).
 *
 * Extracted verbatim from the abstract-book Blade so that the author-facing
 * proceedings-correction preview and the generated book share one
 * implementation and cannot drift apart.
 */
class AbstractBodyFormatter
{
    /**
     * Turn raw abstract text into the section markup used in the abstract book.
     */
    public static function toHtml(?string $raw): string
    {
        $blocks = self::blocks($raw);

        if (empty($blocks)) {
            return '<em>No abstract text provided.</em>';
        }

        $html = '';
        foreach ($blocks as $b) {
            if ($b['label'] === null) {
                $html .= '<p class="abstract-section">'.nl2br(e($b['text'])).'</p>';
            } else {
                $html .= '<p class="abstract-section"><strong>'.e($b['label']).':</strong> '
                    .nl2br(e($b['text'])).'</p>';
            }
        }

        return $html;
    }

    /**
     * Parse raw abstract text into ordered section blocks.
     *
     * Each block is ['label' => ?string, 'text' => string]; a null label means
     * unlabelled prose. This is the single parse used by every output format —
     * the book's HTML and the Word proceedings both build from these blocks, so
     * an abstract is sectioned identically wherever it is published.
     *
     * @return array<int, array{label: ?string, text: string}>
     */
    public static function blocks(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        // ── Normalise heading variants before splitting ──────────────────────
        // Authors write the conclusion ~20 different ways: "Conclusions:",
        // "Conclusion and Recommendation(s):", "Summary and Conclusion:",
        // "CONCLUSION AND RECOMMENDATION:", and the sentence opener
        // "In conclusion,". Collapse them all to a single "Conclusion:" so the
        // book reads consistently. The leading word must be capitalised
        // (Conclusion/CONCLUSION) so we never touch lowercase prose like
        // "…reached a conclusion." — only the "and recommendation(s)" tail is
        // case-insensitive. A bare colon is a reliable heading signal.
        // The trailing (?=[A-Z]) also catches run-together headings with no
        // delimiter, e.g. "…opportunity. Conclusion and RecommendationsThese
        // findings…".
        $raw = preg_replace(
            '/(?<![A-Za-z])(?:[Ss]ummary\s+and\s+)?(?:Conclusions?|CONCLUSIONS?)(?:\s+[Aa]nd\s+[Rr]ecommendations?)?(?:\s*[:.]\s*|(?=[A-Z]))/u',
            ' Conclusion: ',
            $raw
        );
        // "In conclusion," / "In summary," sentence opener → Conclusion heading.
        $raw = preg_replace('/(?<![A-Za-z])In\s+(?:conclusion|summary)\s*,\s*/u', ' Conclusion: ', $raw);
        // Unify "Material(s) and Method(s)" (any case) into one heading.
        $raw = preg_replace('/(?<![A-Za-z])Materials?\s*(?:and|&)\s*Methods?\s*[:.]\s*/iu', ' Materials and Methods: ', $raw);

        // Only the standard IMRaD structured-abstract headings are recognised.
        // Short, common words (Aim, Findings, Discussion, Recommendations, Study
        // Setting/Design, …) were matching ordinary prose and chopping it apart:
        //   "this study aimed to…"        → "this study | Study Aim: ed to…"
        //   "personalized recommendations" → "… personalized | Recommendation: s…"
        //   "focus group discussions"      → "focus group | Discussion: s were…"
        // Restricting the set to these labels removes that whole class of damage.
        $labels = [
            'Background', 'Introduction',
            'Objective', 'Objectives',
            'Materials and Methods', 'Materials & Methods',
            'Methodology', 'Methods', 'Method',
            'Results',
            'Conclusion', 'Conclusions',
        ];
        // Match longer labels first (e.g. "Materials and Methods" before "Methods").
        usort($labels, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        // Canonical casing lookup so "BACKGROUND:", "background:" → "Background:".
        $canonical = [];
        foreach ($labels as $l) {
            $canonical[mb_strtolower($l)] = $l;
        }
        // Normalise singular "Method:" to the plural section heading.
        $canonical['method'] = 'Methods';
        // Headings must be capitalised — real structured-abstract headings always
        // are ("Methods:", "RESULTS:", "BackgroundIn…"). Matching case-INsensitively
        // let ordinary lowercase words collide with label names and corrupt the text:
        // "this study aimed to…" became "this study | Study Aim: ed to…", and
        // "…statistical methods. Results:" silently deleted "methods". So we match
        // only Title-case and ALL-CAPS forms (case-sensitively), never lowercase
        // prose. (Under /i, the no-delimiter look-ahead [A-Z] also matched lowercase
        // letters, which is what split "aim|ed" mid-word.)
        $labelForms = array_unique(array_merge($labels, array_map('mb_strtoupper', $labels)));
        usort($labelForms, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $alt = implode('|', array_map(fn ($l) => preg_quote($l, '/'), $labelForms));
        // Pre-normalize: insert a space before section labels that are run-together with
        // a preceding alphanumeric character and are followed by a colon or semicolon.
        // e.g. "approximationResults:" → "approximation Results:"
        $raw = preg_replace('/([a-zA-Z0-9])('.$alt.')(?=\s*[:.;])/u', '$1 $2', $raw);
        // Accept: colon, period, semicolon, or no delimiter (run-together "MethodsA…"
        // or space-separated "Methods For…"). The /u flag keeps [A-Z] strictly upper-case.
        $pattern = '/(?<![a-zA-Z])('.$alt.')(?:\s*[:.;]\s*|\s*(?=[A-Z\p{Lu}]))/u';

        // Split on labels, keeping them: [lead, label, content, label, content, …]
        $parts = preg_split($pattern, $raw, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return [['label' => null, 'text' => $raw]];
        }

        // Build blocks without ever deleting a matched token. If a "heading" has
        // no content after it (e.g. two headings in a row, or a stray match), fold
        // the word back into the previous block as plain text rather than dropping
        // it — guaranteeing no abstract text is silently lost.
        $blocks = [];
        $lead = trim($parts[0] ?? '');
        if ($lead !== '') {
            $blocks[] = ['label' => null, 'text' => $lead];
        }
        for ($i = 1; $i < count($parts); $i += 2) {
            $matched = trim((string) $parts[$i]);
            $label = $canonical[mb_strtolower($matched)] ?? $matched;
            $content = trim((string) ($parts[$i + 1] ?? ''));

            if ($content === '') {
                if (! empty($blocks)) {
                    $blocks[count($blocks) - 1]['text'] .= ' '.$matched;
                } else {
                    $blocks[] = ['label' => null, 'text' => $matched];
                }

                continue;
            }

            $blocks[] = ['label' => $label, 'text' => $content];
        }

        // A parse that yielded nothing still has to render the author's text.
        return ! empty($blocks) ? $blocks : [['label' => null, 'text' => $raw]];
    }

    /**
     * Strip markup/entities from stored abstract text the way the book does
     * before the text reaches the section parser.
     */
    public static function plainText(?string $stored): string
    {
        $text = (string) $stored;

        // Abstracts submitted through the rich-text editor arrive as HTML.
        // Closing block tags carry the paragraph breaks, so turn them into real
        // newlines before stripping — otherwise strip_tags() welds the last word
        // of one paragraph onto the next heading ("…generation.Methods:").
        $text = preg_replace('#<br\s*/?>#i', "\n", $text);
        $text = preg_replace('#</(?:p|div|li|h[1-6]|blockquote)\s*>#i', "\n\n", $text);

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Non-breaking spaces come through as U+00A0 and would otherwise defeat
        // the parser's \s matching.
        $text = str_replace("\xc2\xa0", ' ', $text);

        // Tidy the blank lines the block conversion introduces.
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/ *\n */', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Convenience: stored abstract text straight to section markup.
     */
    public static function render(?string $stored): string
    {
        return self::toHtml(self::plainText($stored));
    }

    /**
     * The IMRaD section labels this formatter recognises, in reading order.
     * Surfaced so author-facing guidance stays in sync with the parser.
     */
    public static function recognisedLabels(): array
    {
        return [
            'Background', 'Introduction', 'Objectives',
            'Materials and Methods', 'Methods', 'Methodology',
            'Results', 'Conclusion',
        ];
    }
}
