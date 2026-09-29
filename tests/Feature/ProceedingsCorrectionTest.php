<?php

namespace Tests\Feature;

use App\Models\AbstractSubmission;
use App\Models\RevisionHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\ProceedingsCorrectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Camera-ready corrections: authors fixing their own abstract book entry after
 * acceptance.
 *
 * The load-bearing guarantees here are that a correction never changes the
 * conference code, the status, or any field that drives session scheduling —
 * those are already printed in the programme by the time this window opens.
 */
class ProceedingsCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function acceptedAbstract(User $user, array $overrides = []): AbstractSubmission
    {
        return AbstractSubmission::create(array_merge([
            'user_id' => $user->id,
            'author_name' => 'Jane Mwenda',
            'author_institute' => 'Rehab Health',
            'title' => 'Malaria prevalence in coastal Tanzania',
            'description' => 'Background: Malaria is endemic. Results: Prevalence was 12%.',
            'subtheme' => 'Home-Based Rehabilitation',
            'keywords' => 'malaria, prevalence',
            'presentation_mode' => 'Oral',
            'status' => 'accepted',
            'conference_code' => 'HBR-001',
            'code_is_final' => true,
            'include_in_proceedings' => true,
            'coauthors' => [['name' => 'John Kimaro', 'institute' => 'MUHAS']],
        ], $overrides));
    }

    private function openWindow(): void
    {
        app(ProceedingsCorrectionService::class)->open();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Malaria prevalence in coastal Tanzania',
            'description' => 'Background: Malaria is endemic. Results: Prevalence was 12%.',
            'keywords' => 'malaria, prevalence',
            'author_name' => 'Jane Mwenda',
            'author_institute' => 'Rehab Health',
            'coauthors' => [['name' => 'John Kimaro', 'institute' => 'MUHAS']],
            'include_in_proceedings' => '1',
        ], $overrides);
    }

    // ─── Window gating ───────────────────────────────────────────────────────

    public function test_window_is_closed_by_default(): void
    {
        $this->assertFalse(app(ProceedingsCorrectionService::class)->isOpen());
    }

    public function test_form_is_unreachable_while_the_window_is_closed(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);

        $this->actingAs($user)
            ->get(route('abstracts.proceedings.edit', $abstract))
            ->assertRedirect(route('abstracts.show', $abstract))
            ->assertSessionHas('error');
    }

    public function test_saving_is_rejected_once_the_window_closes(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();
        app(ProceedingsCorrectionService::class)->close();

        $this->actingAs($user)
            ->put(route('abstracts.proceedings.update', $abstract), $this->validPayload(['title' => 'Changed']))
            ->assertRedirect(route('abstracts.show', $abstract))
            ->assertSessionHas('error');

        $this->assertSame('Malaria prevalence in coastal Tanzania', $abstract->fresh()->title);
    }

    public function test_window_auto_closes_after_its_closing_moment(): void
    {
        $service = app(ProceedingsCorrectionService::class);
        $service->open(now()->addHour());
        $this->assertTrue($service->isOpen());

        $this->travel(2)->hours();
        $this->assertFalse($service->isOpen());
    }

    // ─── Access control ──────────────────────────────────────────────────────

    public function test_a_user_cannot_correct_someone_elses_abstract(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $abstract = $this->acceptedAbstract($owner);
        $this->openWindow();

        $this->actingAs($intruder)
            ->get(route('abstracts.proceedings.edit', $abstract))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->put(route('abstracts.proceedings.update', $abstract), $this->validPayload(['title' => 'Hijacked']))
            ->assertForbidden();

        $this->assertSame('Malaria prevalence in coastal Tanzania', $abstract->fresh()->title);
    }

    public function test_only_accepted_abstracts_with_a_code_are_correctable(): void
    {
        $user = User::factory()->create();
        $this->openWindow();

        $underReview = $this->acceptedAbstract($user, ['status' => 'under_review', 'conference_code' => null]);
        $this->actingAs($user)
            ->get(route('abstracts.proceedings.edit', $underReview))
            ->assertRedirect(route('abstracts.show', $underReview))
            ->assertSessionHas('error');

        $noCode = $this->acceptedAbstract($user, ['conference_code' => null]);
        $this->actingAs($user)
            ->get(route('abstracts.proceedings.edit', $noCode))
            ->assertRedirect(route('abstracts.show', $noCode))
            ->assertSessionHas('error');
    }

    // ─── The correction itself ───────────────────────────────────────────────

    public function test_author_can_correct_title_body_keywords_and_authors(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();

        $this->actingAs($user)
            ->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
                'title' => 'Malaria prevalence in coastal Tanzania: a revised analysis',
                'description' => 'Background: Malaria is endemic. Methods: Cross-sectional survey. Results: Prevalence was 14%.',
                'keywords' => 'malaria, prevalence, Tanzania',
                'author_institute' => 'Rehab Health Mwanza Centre',
                'coauthors' => [
                    ['name' => 'John Kimaro', 'institute' => 'MUHAS'],
                    ['name' => 'Asha Salum', 'institute' => 'Rehab Health Mwanza Centre'],
                ],
            ]))
            ->assertRedirect(route('abstracts.show', $abstract))
            ->assertSessionHas('success');

        $fresh = $abstract->fresh();
        $this->assertSame('Malaria prevalence in coastal Tanzania: a revised analysis', $fresh->title);
        $this->assertStringContainsString('Prevalence was 14%', $fresh->description);
        $this->assertSame('malaria, prevalence, Tanzania', $fresh->keywords);
        $this->assertSame('Rehab Health Mwanza Centre', $fresh->author_institute);
        $this->assertCount(2, $fresh->coauthors);
        $this->assertSame('Asha Salum', $fresh->coauthors[1]['name']);
        $this->assertNotNull($fresh->proceedings_corrected_at);
        $this->assertSame($user->id, $fresh->proceedings_corrected_by);
    }

    /**
     * The whole point of the narrow whitelist: an accepted abstract's identity
     * in the printed programme must survive an author edit untouched.
     */
    public function test_correction_never_changes_code_status_subtheme_or_mode(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();

        $this->actingAs($user)->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
            'title' => 'A completely different title about tuberculosis and HIV',
            'description' => 'Background: Tuberculosis is a major concern. Results: Incidence fell.',
            // Smuggled fields — must be ignored, not applied.
            'status' => 'rejected',
            'conference_code' => 'HACK-999',
            'subtheme' => 'Rehabilitation Across the Life Course',
            'presentation_mode' => 'Poster',
            'session_id' => 42,
            'code_is_final' => false,
        ]))->assertRedirect();

        $fresh = $abstract->fresh();
        $this->assertSame('HBR-001', $fresh->conference_code);
        $this->assertSame('accepted', $fresh->status);
        $this->assertSame('Home-Based Rehabilitation', $fresh->subtheme);
        $this->assertSame('Oral', $fresh->presentation_mode);
        $this->assertNull($fresh->session_id);
        $this->assertTrue((bool) $fresh->code_is_final);
    }

    public function test_correction_is_recorded_in_revision_history_with_a_diff(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();

        $this->actingAs($user)->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
            'title' => 'Corrected title',
        ]))->assertRedirect();

        $entry = RevisionHistory::where('abstract_submission_id', $abstract->id)
            ->where('action', 'proceedings_correction')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame(['title'], $entry->metadata['changed_fields']);
        $this->assertSame('Malaria prevalence in coastal Tanzania', $entry->metadata['before']['title']);
        $this->assertSame('Corrected title', $entry->metadata['after']['title']);
        $this->assertSame('HBR-001', $entry->metadata['conference_code']);
    }

    public function test_saving_an_unchanged_form_records_nothing(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();

        $this->actingAs($user)
            ->put(route('abstracts.proceedings.update', $abstract), $this->validPayload())
            ->assertSessionHas('success');

        $this->assertSame(0, RevisionHistory::where('abstract_submission_id', $abstract->id)->count());
        $this->assertNull($abstract->fresh()->proceedings_corrected_at);
    }

    // ─── Co-author storage ───────────────────────────────────────────────────

    /**
     * AbstractSubmission casts 'coauthors' to array. The legacy submission form
     * assigns a json_encode()d string into that cast, which double-encodes and
     * forces the book Blade to decode defensively. Corrections store a real
     * array so the value round-trips cleanly.
     */
    public function test_coauthors_round_trip_as_an_array_not_a_json_string(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();

        $this->actingAs($user)->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
            'coauthors' => [['name' => 'Asha Salum', 'institute' => 'MUHAS']],
        ]))->assertRedirect();

        $fresh = $abstract->fresh();
        $this->assertIsArray($fresh->coauthors);
        $this->assertSame('Asha Salum', $fresh->coauthors[0]['name']);
        $this->assertIsArray(json_decode($fresh->getRawOriginal('coauthors'), true));
    }

    public function test_blank_coauthor_rows_are_discarded_and_legacy_key_is_normalised(): void
    {
        $service = app(ProceedingsCorrectionService::class);

        $normalized = $service->normalizeCoauthors([
            ['name' => 'Asha Salum', 'institute' => 'MUHAS'],
            ['name' => '', 'institute' => ''],
            ['name' => '  John Kimaro  ', 'affiliation' => '  Rehab Health  '],
        ]);

        $this->assertCount(2, $normalized);
        $this->assertSame('John Kimaro', $normalized[1]['name']);
        $this->assertSame('Rehab Health', $normalized[1]['institute']);
        $this->assertArrayNotHasKey('affiliation', $normalized[1]);
    }

    public function test_removing_every_coauthor_is_allowed(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();

        $this->actingAs($user)->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
            'coauthors' => [],
        ]))->assertRedirect();

        $this->assertSame([], $abstract->fresh()->coauthors);
    }

    // ─── Admins acting on an author's behalf ──────────────────────────────────

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Administrator']);
        $admin = User::factory()->create();
        $admin->roles()->attach($role->id, ['is_primary' => true, 'assigned_at' => now()]);

        return $admin;
    }

    /**
     * Admins are needed most after the author window has closed, for the
     * authors who never got round to it — so the window must not bind them.
     */
    public function test_admin_can_open_any_entry_even_after_the_window_closes(): void
    {
        $author = User::factory()->create();
        $abstract = $this->acceptedAbstract($author);
        // Window never opened.

        $this->actingAs($this->makeAdmin())
            ->get(route('abstracts.proceedings.edit', $abstract))
            ->assertOk()
            ->assertSee('Editing on behalf of')
            ->assertSee('HBR-001');
    }

    public function test_admin_correction_is_saved_and_audited_as_on_behalf_of_the_author(): void
    {
        $author = User::factory()->create();
        $abstract = $this->acceptedAbstract($author);
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
                'title' => 'Corrected by the secretariat',
                'include_in_proceedings' => '0',
            ]))
            ->assertRedirect(route('admin.proceedings.entries'));

        $fresh = $abstract->fresh();
        $this->assertSame('Corrected by the secretariat', $fresh->title);
        $this->assertFalse((bool) $fresh->include_in_proceedings);
        $this->assertSame($admin->id, $fresh->proceedings_corrected_by);
        // Ownership never moves to the admin.
        $this->assertSame($author->id, $fresh->user_id);
        $this->assertSame('HBR-001', $fresh->conference_code);

        $entry = RevisionHistory::where('abstract_submission_id', $abstract->id)->first();
        $this->assertSame($admin->id, $entry->user_id);
        $this->assertTrue($entry->metadata['on_behalf_of_author']);
    }

    public function test_an_authors_own_correction_is_not_marked_on_behalf(): void
    {
        $author = User::factory()->create();
        $abstract = $this->acceptedAbstract($author);
        $this->openWindow();

        $this->actingAs($author)->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
            'title' => 'Author fixed it',
        ]))->assertRedirect();

        $entry = RevisionHistory::where('abstract_submission_id', $abstract->id)->first();
        $this->assertFalse($entry->metadata['on_behalf_of_author']);
    }

    public function test_admin_edits_still_cannot_touch_the_code_status_or_scheduling_fields(): void
    {
        $abstract = $this->acceptedAbstract(User::factory()->create());

        $this->actingAs($this->makeAdmin())->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
            'status' => 'rejected',
            'conference_code' => 'HACK-999',
            'subtheme' => 'Something else',
            'presentation_mode' => 'Poster',
        ]))->assertRedirect();

        $fresh = $abstract->fresh();
        $this->assertSame('accepted', $fresh->status);
        $this->assertSame('HBR-001', $fresh->conference_code);
        $this->assertSame('Home-Based Rehabilitation', $fresh->subtheme);
        $this->assertSame('Oral', $fresh->presentation_mode);
    }

    public function test_admin_is_returned_to_the_filtered_list_but_never_off_site(): void
    {
        $abstract = $this->acceptedAbstract(User::factory()->create());
        $admin = $this->makeAdmin();
        $filtered = route('admin.proceedings.entries').'?q=malaria&page=2';

        $this->actingAs($admin)
            ->put(route('abstracts.proceedings.update', $abstract), $this->validPayload(['title' => 'One']) + ['return_to' => $filtered])
            ->assertRedirect($filtered);

        $this->actingAs($admin)
            ->put(route('abstracts.proceedings.update', $abstract), $this->validPayload(['title' => 'Two']) + ['return_to' => 'https://evil.example/phish'])
            ->assertRedirect(route('admin.proceedings.entries'));
    }

    public function test_entries_list_is_admin_only_and_searchable(): void
    {
        $author = User::factory()->create();
        $this->acceptedAbstract($author, ['conference_code' => 'HBR-001', 'title' => 'Malaria in the lake zone']);
        $this->acceptedAbstract($author, ['conference_code' => 'HSS-004', 'title' => 'Health financing reform']);

        $this->actingAs($author)->get(route('admin.proceedings.entries'))->assertForbidden();

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.proceedings.entries', ['q' => 'HSS']))
            ->assertOk()
            ->assertSee('HSS-004')
            ->assertDontSee('HBR-001');
    }

    // ─── Proceedings opt-in ──────────────────────────────────────────────────

    /**
     * The one editable field that changes whether the abstract is published at
     * all, rather than how — it decides inclusion in the Conference Proceedings.
     */
    public function test_author_can_opt_out_of_the_conference_proceedings(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();

        $this->actingAs($user)->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
            'include_in_proceedings' => '0',
        ]))->assertRedirect();

        $this->assertFalse((bool) $abstract->fresh()->include_in_proceedings);

        // …and the proceedings volume drops it.
        $this->assertSame(0, app(\App\Services\ConferenceProceedingsService::class)->stats()['included']);
    }

    public function test_author_can_opt_back_into_the_conference_proceedings(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user, ['include_in_proceedings' => false]);
        $this->openWindow();

        $this->actingAs($user)->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
            'include_in_proceedings' => '1',
        ]))->assertRedirect();

        $this->assertTrue((bool) $abstract->fresh()->include_in_proceedings);
        $this->assertSame(1, app(\App\Services\ConferenceProceedingsService::class)->stats()['included']);
    }

    public function test_opting_out_is_recorded_in_the_audit_trail(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();

        $this->actingAs($user)->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
            'include_in_proceedings' => '0',
        ]))->assertRedirect();

        $entry = RevisionHistory::where('abstract_submission_id', $abstract->id)
            ->where('action', 'proceedings_correction')
            ->first();

        $this->assertNotNull($entry);
        $this->assertContains('include_in_proceedings', $entry->metadata['changed_fields']);
        $this->assertTrue($entry->metadata['before']['include_in_proceedings']);
        $this->assertFalse($entry->metadata['after']['include_in_proceedings']);
    }

    // ─── Validation & preview ────────────────────────────────────────────────

    public function test_required_fields_are_validated(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();

        $this->actingAs($user)
            ->put(route('abstracts.proceedings.update', $abstract), $this->validPayload([
                'title' => '',
                'description' => '',
            ]))
            ->assertSessionHasErrors(['title', 'description']);
    }

    public function test_preview_renders_the_entry_the_way_the_book_will(): void
    {
        $user = User::factory()->create();
        $abstract = $this->acceptedAbstract($user);
        $this->openWindow();

        $response = $this->actingAs($user)->postJson(
            route('abstracts.proceedings.preview', $abstract),
            $this->validPayload([
                'description' => 'Background: Endemic disease. RESULTS: Burden was high.',
                'coauthors' => [['name' => 'John Kimaro', 'institute' => 'MUHAS']],
            ])
        );

        $response->assertOk();
        $data = $response->json();

        $this->assertSame('HBR-001', $data['conference_code']);
        // ALL-CAPS heading canonicalised by the shared formatter.
        $this->assertStringContainsString('<strong>Background:</strong>', $data['body_html']);
        $this->assertStringContainsString('<strong>Results:</strong>', $data['body_html']);
        // Two distinct affiliations → two superscript numbers.
        $this->assertCount(2, $data['affiliations']);
        $this->assertStringContainsString('<sup>1</sup>', $data['authors']);
        $this->assertStringContainsString('<sup>2</sup>', $data['authors']);
    }

    public function test_preview_is_not_available_for_another_users_abstract(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $abstract = $this->acceptedAbstract($owner);
        $this->openWindow();

        $this->actingAs($intruder)
            ->postJson(route('abstracts.proceedings.preview', $abstract), $this->validPayload())
            ->assertForbidden();
    }

    // ─── Admin reporting ─────────────────────────────────────────────────────

    public function test_corrected_since_counts_only_entries_changed_after_the_last_build(): void
    {
        $user = User::factory()->create();
        $service = app(ProceedingsCorrectionService::class);
        $this->openWindow();

        $old = $this->acceptedAbstract($user, ['conference_code' => 'HBR-002']);
        $service->apply($old, $this->validPayload(['title' => 'Older correction']), $user);

        $buildMoment = now()->addMinute();
        $this->travelTo(now()->addMinutes(2));

        $recent = $this->acceptedAbstract($user, ['conference_code' => 'HBR-003']);
        $service->apply($recent, $this->validPayload(['title' => 'Newer correction']), $user);

        $this->assertSame(1, $service->correctedSince($buildMoment));
        $this->assertSame(2, $service->correctedSince(null));
    }
}
