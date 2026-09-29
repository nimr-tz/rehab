<?php

namespace Tests\Feature;

use App\Models\AbstractSubmission;
use App\Models\Role;
use App\Models\User;
use App\Services\ConferenceProceedingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Conference Proceedings volume (PDF).
 *
 * Separate from the abstract book: abstracts only, and only those whose author
 * opted in. Everything about how an individual abstract is presented must match
 * the book — same grouping, same author/affiliation numbering, same IMRaD
 * sectioning — so those are asserted against the rendered document text.
 */
class ConferenceProceedingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id, [
            'is_primary' => true,
            'assigned_at' => now(),
        ]);

        return $admin;
    }

    private function abstract(array $overrides = []): AbstractSubmission
    {
        return AbstractSubmission::create(array_merge([
            'author_name' => 'Jane Mwenda',
            'author_institute' => 'Rehab Health',
            'title' => 'Malaria prevalence in coastal Tanzania',
            'description' => 'Background: Malaria remains endemic. Methods: A cross-sectional survey. Results: Prevalence was 12.4%.',
            'subtheme' => 'Home-Based Rehabilitation',
            'keywords' => 'malaria, prevalence',
            'presentation_mode' => 'Oral',
            'status' => 'accepted',
            'conference_code' => 'HBR-001',
            'include_in_proceedings' => true,
            'coauthors' => [['name' => 'John Kimaro', 'institute' => 'MUHAS']],
        ], $overrides));
    }

    /**
     * All visible text in the volume, in document order. Asserted against the
     * HTML dompdf typesets, rather than against the PDF's compressed streams.
     */
    private function documentText(): string
    {
        $html = app(ConferenceProceedingsService::class)->html();

        // Drop <style> wholesale so CSS never counts as visible text.
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $html);

        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    // ─── Selection ───────────────────────────────────────────────────────────

    public function test_only_opted_in_abstracts_are_included(): void
    {
        $this->abstract(['conference_code' => 'HBR-001', 'title' => 'Included abstract']);
        $this->abstract(['conference_code' => 'HBR-002', 'title' => 'Excluded abstract', 'include_in_proceedings' => false]);

        $text = $this->documentText();

        $this->assertStringContainsString('Included abstract', $text);
        $this->assertStringNotContainsString('Excluded abstract', $text);
    }

    public function test_unaccepted_and_uncoded_abstracts_are_excluded(): void
    {
        $this->abstract(['conference_code' => 'HBR-001', 'title' => 'Accepted and coded']);
        $this->abstract(['conference_code' => 'HBR-009', 'title' => 'Still under review', 'status' => 'under_review']);
        $this->abstract(['conference_code' => null, 'title' => 'Accepted but uncoded']);

        $text = $this->documentText();

        $this->assertStringContainsString('Accepted and coded', $text);
        $this->assertStringNotContainsString('Still under review', $text);
        $this->assertStringNotContainsString('Accepted but uncoded', $text);
    }

    public function test_stats_report_inclusion_and_opt_out_counts(): void
    {
        $this->abstract(['conference_code' => 'HBR-001']);
        $this->abstract(['conference_code' => 'HBR-002']);
        $this->abstract(['conference_code' => 'HBR-003', 'include_in_proceedings' => false]);
        $this->abstract(['conference_code' => 'HBR-004', 'status' => 'rejected']);

        $stats = app(ConferenceProceedingsService::class)->stats();

        $this->assertSame(2, $stats['included']);
        $this->assertSame(1, $stats['excluded']);
        $this->assertSame(3, $stats['total_accepted']);
    }

    public function test_an_empty_volume_still_produces_a_valid_document(): void
    {
        $this->abstract(['include_in_proceedings' => false]);

        $text = $this->documentText();

        $this->assertStringContainsString('No abstracts have been marked for inclusion', $text);
    }

    // ─── Presentation matches the abstract book ──────────────────────────────

    public function test_titles_and_author_names_are_normalised_like_the_book(): void
    {
        $this->abstract([
            'title' => 'MALARIA PREVALENCE IN COASTAL TANZANIA',
            'author_name' => 'jane mwenda',
            'coauthors' => [['name' => 'john kimaro', 'institute' => 'MUHAS']],
        ]);

        $text = $this->documentText();

        // Shouty titles become sentence case; acronyms and place names survive.
        $this->assertStringContainsString('Malaria prevalence in coastal Tanzania', $text);
        $this->assertStringNotContainsString('MALARIA PREVALENCE IN COASTAL', $text);
        $this->assertStringContainsString('Jane Mwenda', $text);
        $this->assertStringContainsString('John Kimaro', $text);
    }

    public function test_imrad_sections_are_parsed_the_same_way_as_the_book(): void
    {
        $this->abstract([
            // Heading variants the book's parser normalises: ALL-CAPS, and the
            // "In conclusion," sentence opener.
            'description' => 'Background: Endemic disease. METHODS: Household survey. Results: Burden was high. In conclusion, action is needed.',
        ]);

        $text = $this->documentText();

        $this->assertStringContainsString('Background: ', $text);
        $this->assertStringContainsString('Methods: ', $text);
        $this->assertStringContainsString('Results: ', $text);
        $this->assertStringContainsString('Conclusion: ', $text);
        $this->assertStringNotContainsString('METHODS', $text);
    }

    public function test_distinct_affiliations_are_numbered_once_each(): void
    {
        $this->abstract([
            'author_institute' => 'Rehab Health',
            'coauthors' => [
                ['name' => 'John Kimaro', 'institute' => 'MUHAS'],
                // Same affiliation as the presenting author — must reuse marker 1.
                ['name' => 'Asha Salum', 'institute' => 'Rehab Health'],
            ],
        ]);

        $text = $this->documentText();

        // .affiliations-header is uppercased by CSS at render time, so the
        // markup itself carries the title-case word.
        $this->assertStringContainsString('Affiliations', $text);
        $this->assertStringContainsString('1. Rehab Health', $text);
        $this->assertStringContainsString('2. MUHAS', $text);
        $this->assertStringNotContainsString('3. ', $text);
    }

    public function test_legacy_coauthor_affiliation_key_is_still_honoured(): void
    {
        $this->abstract([
            'coauthors' => [['name' => 'John Kimaro', 'affiliation' => 'Ifakara Health Institute']],
        ]);

        $text = $this->documentText();

        $this->assertStringContainsString('2. Ifakara Health Institute', $text);
    }

    public function test_abstracts_are_grouped_into_numbered_subtheme_sections(): void
    {
        $this->abstract(['conference_code' => 'HBR-001', 'subtheme' => 'Home-Based Rehabilitation']);
        $this->abstract([
            'conference_code' => 'RIN-001',
            'subtheme' => 'Research and Innovation',
        ]);

        $text = $this->documentText();

        // .theme-section-label is a letter-spaced gold eyebrow above the sub theme
        // title — not a "Sub Theme N: Title" run-on. CSS uppercases it in print.
        $this->assertStringContainsString('Sub Theme 1', $text);
        $this->assertStringContainsString('Sub Theme 2', $text);
        $this->assertStringContainsString('Home-Based Rehabilitation', $text);
        $this->assertStringContainsString('Research and Innovation', $text);
    }

    public function test_conference_codes_and_keywords_appear(): void
    {
        $this->abstract(['conference_code' => 'HBR-042', 'keywords' => 'malaria, prevalence']);

        $text = $this->documentText();

        $this->assertStringContainsString('HBR-042', $text);
        // .abstract-keywords strong is uppercased and letter-spaced by CSS.
        $this->assertStringContainsString('Keywords:', $text);
    }

    // ─── Document mechanics ──────────────────────────────────────────────────

    public function test_volume_opens_with_a_cover_page_and_no_table_of_contents(): void
    {
        $this->abstract();

        $text = $this->documentText();

        $this->assertStringContainsString('Conference Proceedings', $text);
        $this->assertStringContainsString('1 abstract across 1 sub theme', $text);
        // Straight from cover to abstracts.
        $this->assertStringNotContainsString('Table of Contents', $text);
    }

    /**
     * The proceedings must look like the abstract book. These are the book's own
     * stylesheet values: navy #152b5e sub theme panel and code chip, gold
     * #c89b3c rules and left edge, #f4f6fb affiliations panel, #a8c5e2 count.
     */
    public function test_volume_uses_the_abstract_books_palette(): void
    {
        $this->abstract();

        $html = app(ConferenceProceedingsService::class)->html();

        foreach ([
            '#152b5e' => 'navy panels and code chip',
            '#c89b3c' => 'gold rules',
            '#f4f6fb' => 'affiliations panel',
            '#a8c5e2' => 'pale blue count',
        ] as $hex => $what) {
            $this->assertTrue(str_contains($html, $hex), "Missing the book's {$what} colour {$hex}");
        }

        // The book's structural classes, so page-break behaviour matches too.
        foreach (['theme-section-divider', 'abstract-code', 'affiliations-block', 'abstract-body', 'aff-sup'] as $class) {
            $this->assertTrue(str_contains($html, $class), "Missing the book's .{$class}");
        }

        $this->assertTrue(str_contains($html, 'text-align: justify'), 'Abstract body is not justified');
    }

    /**
     * The sub theme divider is a full-bleed navy band: it must reach past the
     * page margins, which the book does with negative margins.
     */
    public function test_subtheme_divider_bleeds_to_the_page_edges(): void
    {
        $this->abstract();

        $html = app(ConferenceProceedingsService::class)->html();

        $this->assertTrue(
            (bool) preg_match('/\.theme-section-divider\s*\{[^}]*margin:\s*-14mm\s+-13mm/s', $html),
            'The sub theme divider does not bleed past the page margins'
        );
        $this->assertTrue(
            (bool) preg_match('/\.theme-section-divider\s*\{[^}]*page-break-before:\s*always/s', $html),
            'Each sub theme should start on a new page'
        );
    }

    public function test_it_renders_a_real_pdf(): void
    {
        $this->abstract();

        $pdf = app(ConferenceProceedingsService::class)->pdf();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_download_serves_a_pdf_with_the_conference_filename(): void
    {
        $this->abstract();

        $response = $this->actingAs($this->makeAdmin())->get(route('admin.conference-program.proceedings.download'));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Conference-Proceedings', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));
    }
}
