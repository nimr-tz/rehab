<?php

namespace Tests\Unit;

use App\Support\AbstractBodyFormatter;
use Tests\TestCase;

/**
 * Characterization tests for the abstract-book IMRaD section parser.
 *
 * Each case here corresponds to a real defect fixed in the book's history
 * (text corruption, dropped words, prose split mid-word). The parser is shared
 * by the generated book and the author-facing proceedings-correction preview,
 * so a regression shows up in print.
 */
class AbstractBodyFormatterTest extends TestCase
{
    private function text(string $html): string
    {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function test_empty_body_renders_placeholder(): void
    {
        $this->assertSame('<em>No abstract text provided.</em>', AbstractBodyFormatter::toHtml(''));
        $this->assertSame('<em>No abstract text provided.</em>', AbstractBodyFormatter::toHtml('   '));
        $this->assertSame('<em>No abstract text provided.</em>', AbstractBodyFormatter::toHtml(null));
    }

    public function test_splits_standard_imrad_headings_into_bold_sections(): void
    {
        $html = AbstractBodyFormatter::toHtml(
            'Background: Malaria remains endemic. Methods: We sampled 200 households. '
            .'Results: Prevalence was 12%. Conclusion: Control efforts must continue.'
        );

        $this->assertStringContainsString('<strong>Background:</strong>', $html);
        $this->assertStringContainsString('<strong>Methods:</strong>', $html);
        $this->assertStringContainsString('<strong>Results:</strong>', $html);
        $this->assertStringContainsString('<strong>Conclusion:</strong>', $html);
        $this->assertStringContainsString('Prevalence was 12%', $html);
    }

    public function test_all_caps_headings_are_canonicalised(): void
    {
        $html = AbstractBodyFormatter::toHtml('BACKGROUND: Endemic disease. RESULTS: High burden.');

        $this->assertStringContainsString('<strong>Background:</strong>', $html);
        $this->assertStringContainsString('<strong>Results:</strong>', $html);
        $this->assertStringNotContainsString('BACKGROUND', $html);
    }

    public function test_singular_method_label_is_normalised_to_plural(): void
    {
        $html = AbstractBodyFormatter::toHtml('Method: A cross-sectional survey was used.');

        $this->assertStringContainsString('<strong>Methods:</strong>', $html);
    }

    /** Commit 7eade6d8 — conclusion heading variants all collapse to one form. */
    public function test_conclusion_variants_collapse_to_single_heading(): void
    {
        $variants = [
            'Results: X. Conclusions: Care is needed.',
            'Results: X. Conclusion and Recommendations: Care is needed.',
            'Results: X. CONCLUSION AND RECOMMENDATION: Care is needed.',
            'Results: X. Summary and Conclusion: Care is needed.',
            'Results: X. In conclusion, care is needed.',
        ];

        foreach ($variants as $variant) {
            $html = AbstractBodyFormatter::toHtml($variant);
            $this->assertStringContainsString('<strong>Conclusion:</strong>', $html, "Failed for: {$variant}");
        }
    }

    public function test_materials_and_methods_variants_unify(): void
    {
        foreach (['Materials and Methods: We did X.', 'Material and Method: We did X.', 'MATERIALS AND METHODS: We did X.'] as $variant) {
            $html = AbstractBodyFormatter::toHtml($variant);
            $this->assertStringContainsString('<strong>Materials and Methods:</strong>', $html, "Failed for: {$variant}");
        }
    }

    /** Commit 04177bc1 — labels run together with the preceding word, and semicolon delimiters. */
    public function test_run_together_and_semicolon_delimited_labels_are_recognised(): void
    {
        $runTogether = AbstractBodyFormatter::toHtml('We used a normal approximationResults: Prevalence was high.');
        $this->assertStringContainsString('<strong>Results:</strong>', $runTogether);
        $this->assertStringContainsString('approximation', $this->text($runTogether));

        $semicolon = AbstractBodyFormatter::toHtml('Background; Endemic disease persists. Results; Burden was high.');
        $this->assertStringContainsString('<strong>Background:</strong>', $semicolon);
        $this->assertStringContainsString('<strong>Results:</strong>', $semicolon);
    }

    /**
     * Commit 7715db7e — case-insensitive matching split ordinary prose mid-word
     * ("this study aimed to" became "this study | Study Aim: ed to"). Lowercase
     * words that merely look like labels must never become headings.
     */
    public function test_lowercase_prose_is_never_treated_as_a_heading(): void
    {
        $cases = [
            'This study aimed to describe the burden of disease.' => 'aimed to describe',
            'We analysed focus group discussions across three regions.' => 'focus group discussions',
            'The tool produced personalized recommendations for each clinic.' => 'personalized recommendations',
            'Standard statistical methods were applied throughout.' => 'statistical methods were applied',
        ];

        foreach ($cases as $input => $mustSurvive) {
            $html = AbstractBodyFormatter::toHtml($input);
            $this->assertStringNotContainsString('<strong>', $html, "Unexpected heading for: {$input}");
            $this->assertStringContainsString($mustSurvive, $this->text($html), "Text mangled for: {$input}");
        }
    }

    /** Commit 89a697ba — the parser must never silently delete a word. */
    public function test_no_text_is_lost_when_a_label_word_precedes_a_real_heading(): void
    {
        $html = AbstractBodyFormatter::toHtml('We applied standard statistical methods. Results: Burden was high.');
        $rendered = $this->text($html);

        $this->assertStringContainsString('statistical methods', $rendered);
        $this->assertStringContainsString('Burden was high', $rendered);
    }

    /** Commit 301d9ff4 — a heading with no content folds back rather than vanishing. */
    public function test_heading_without_content_is_folded_back_as_text(): void
    {
        $html = AbstractBodyFormatter::toHtml('Background: Some context. Results: Conclusion: Final word.');
        $rendered = $this->text($html);

        $this->assertStringContainsString('Some context', $rendered);
        $this->assertStringContainsString('Final word', $rendered);
        $this->assertStringContainsString('Results', $rendered);
    }

    public function test_markup_in_body_text_is_escaped(): void
    {
        $html = AbstractBodyFormatter::toHtml('Background: Rates rose <script>alert(1)</script> & fell.');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
    }

    /**
     * Abstracts submitted through the rich-text editor are stored as HTML.
     * Closing block tags carry the paragraph breaks, so they must become real
     * newlines — a bare strip_tags() welds the end of one paragraph onto the
     * next heading ("…generation.Methods:").
     */
    public function test_editor_html_becomes_readable_plain_text(): void
    {
        $stored = '<p><strong>Background:</strong> One health remains a priority for evidence generation.</p>'
            .'<p><strong>Methods:</strong> We conducted a mixed-methods study.</p>'
            .'<p><strong>Results:</strong> The findings identify gaps.</p>';

        $plain = AbstractBodyFormatter::plainText($stored);

        $this->assertStringNotContainsString('<', $plain);
        $this->assertStringNotContainsString('generation.Methods', $plain);
        $this->assertStringContainsString("generation.\n\nMethods:", $plain);

        // …and it still parses into the three sections.
        $labels = array_column(AbstractBodyFormatter::blocks($plain), 'label');
        $this->assertSame(['Background', 'Methods', 'Results'], $labels);
    }

    public function test_line_breaks_and_non_breaking_spaces_are_normalised(): void
    {
        $plain = AbstractBodyFormatter::plainText('<p>Background:&nbsp;Endemic&nbsp;disease.<br>Second line.</p>');

        $this->assertStringNotContainsString("\xc2\xa0", $plain);
        $this->assertStringContainsString('Background: Endemic disease.', $plain);
        $this->assertStringContainsString("\nSecond line.", $plain);
    }

    public function test_render_strips_stored_markup_before_parsing(): void
    {
        $html = AbstractBodyFormatter::render('<p>Background: Endemic disease &amp; poverty.</p>');
        $rendered = $this->text($html);

        $this->assertStringContainsString('<strong>Background:</strong>', $html);
        $this->assertStringContainsString('Endemic disease & poverty', $rendered);
    }

    public function test_unstructured_body_renders_as_a_single_paragraph(): void
    {
        $html = AbstractBodyFormatter::toHtml('A short narrative abstract with no section labels at all.');

        $this->assertStringNotContainsString('<strong>', $html);
        $this->assertStringContainsString('A short narrative abstract', $html);
    }
}
