<?php

namespace Tests\Feature;

use App\Enums\AbstractStatus;
use App\Enums\AwardKind;
use App\Enums\PresentationType;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\AwardCategory;
use App\Models\Edition;
use App\Models\User;
use App\Notifications\AwardWon;
use App\Services\AwardService;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

class AwardsTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Notification::fake();

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        $this->edition->categories()->create(['name' => 'Student', 'currency' => 'TZS', 'amount' => 100000, 'is_student' => true, 'sort' => 1]);
        $this->edition->topics()->create(['name' => 'Home-Based Rehabilitation', 'code' => 'HBR', 'sort' => 0]);
        app(Summit::class)->refresh();
    }

    private function user(Role ...$roles): User
    {
        return User::factory()->create()->assignRole(array_map(fn (Role $role) => $role->value, $roles ?: [Role::Participant]));
    }

    /** An accepted abstract presented by its submitter, with an optional co-author. */
    private function accepted(User $author, PresentationType $type = PresentationType::Oral, bool $student = false, ?User $coAuthor = null): AbstractSubmission
    {
        static $n = 0;
        $n++;

        $this->edition->registrations()->firstOrCreate(['user_id' => $author->id], [
            'registration_category_id' => $this->edition->categories->firstWhere('is_student', $student)->id,
            'reference' => 'RS27-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'currency' => 'TZS', 'amount' => 100000,
            'status' => RegistrationStatus::Confirmed, 'qr_token' => 'token-'.$n,
        ]);

        $abstract = AbstractSubmission::create([
            'edition_id' => $this->edition->id, 'user_id' => $author->id, 'topic_id' => $this->edition->topics->first()->id,
            'preferred_type' => $type, 'decision_type' => $type, 'status' => AbstractStatus::Accepted,
            'title' => 'Study number '.$n, 'background' => 'B', 'methods' => 'M', 'results' => 'R', 'conclusions' => 'C',
            'code' => $type->codePrefix().'-HBR-'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
        ]);
        $abstract->authors()->create(['name' => $author->name, 'email' => $author->email, 'affiliation' => 'MNH', 'is_presenter' => true, 'position' => 1]);
        if ($coAuthor) {
            $abstract->authors()->create(['name' => $coAuthor->name, 'email' => $coAuthor->email, 'affiliation' => 'KCMC', 'is_presenter' => false, 'position' => 2]);
        }

        return $abstract;
    }

    private function award(array $overrides = []): AwardCategory
    {
        return $this->edition->awardCategories()->create($overrides + [
            'name' => 'Best Oral Presentation', 'kind' => AwardKind::Presentation, 'presentation_type' => PresentationType::Oral, 'places' => 3,
        ]);
    }

    private function scores(int $each): array
    {
        return ['scores' => ['science' => $each, 'impact' => $each, 'delivery' => $each, 'discussion' => $each], 'comments' => 'Clear.'];
    }

    public function test_the_committee_adds_the_suggested_awards_once(): void
    {
        $committee = $this->user(Role::ScientificAdmin);

        $this->actingAs($committee)->get(route('committee.awards.index'))->assertOk()->assertSee('Add the suggested awards');
        $this->actingAs($committee)->post(route('committee.awards.suggested'))->assertRedirect(route('committee.awards.index'));
        $this->actingAs($committee)->post(route('committee.awards.suggested'))->assertSessionHas('status', 'This summit already has every suggested award.');

        // Best Poster is left out while posters are off.
        $this->assertSame(count(config('awards.suggested')) - 1, $this->edition->awardCategories()->count());
        $champion = $this->edition->awardCategories()->where('name', 'Rehabilitation Champion Award')->sole();
        $this->assertSame('2027-08-15', $champion->nominations_close_on->toDateString()); // a month before the summit
        $this->assertTrue($champion->acceptsNominations());

        $this->actingAs($committee)->get(route('committee.awards.index'))->assertOk()
            ->assertSee('Best Oral Presentation')->assertDontSee('Best Poster')->assertSee('Shortlisting');
    }

    public function test_a_poster_award_needs_posters_switched_on(): void
    {
        $committee = $this->user(Role::Admin);
        $form = ['name' => 'Best Poster', 'kind' => 'presentation', 'presentation_type' => 'poster', 'students_only' => '0', 'places' => '2'];

        $this->actingAs($committee)->post(route('committee.awards.store'), $form)->assertSessionHasErrors('presentation_type');
        $this->assertSame(0, AwardCategory::count());

        config(['review.posters' => true]);
        $this->actingAs($committee)->post(route('committee.awards.suggested'))->assertRedirect();
        $this->assertTrue($this->edition->awardCategories()->where('name', 'Best Poster')->exists());
    }

    public function test_the_committee_creates_and_edits_an_award(): void
    {
        config(['review.posters' => true]);
        $committee = $this->user(Role::Admin);

        $this->actingAs($committee)->post(route('committee.awards.store'), [
            'name' => 'Best Poster', 'kind' => 'presentation', 'presentation_type' => 'poster', 'students_only' => '0', 'places' => '2',
            'nominations_close_on' => '2027-08-01', 'description' => 'For the clearest poster.',
        ])->assertRedirect();
        $award = AwardCategory::sole();
        $this->assertSame(PresentationType::Poster, $award->presentation_type);
        $this->assertNull($award->nominations_close_on); // only honours take nominations

        $this->actingAs($committee)->put(route('committee.awards.update', $award), [
            'name' => 'Best Poster', 'kind' => 'presentation', 'places' => '4',
        ])->assertSessionHasErrors('places');
    }

    public function test_only_accepted_abstracts_that_fit_the_award_can_be_shortlisted(): void
    {
        $committee = $this->user(Role::ScientificAdmin);
        $oral = $this->accepted($this->user());
        $poster = $this->accepted($this->user(), PresentationType::Poster);
        $studentOral = $this->accepted($this->user(), student: true);
        $rejected = $this->accepted($this->user());
        $rejected->update(['status' => AbstractStatus::Rejected]);

        $award = $this->award();
        $this->actingAs($committee)->get(route('committee.awards.show', $award))
            ->assertOk()->assertSee($oral->title)->assertSee($studentOral->title)->assertDontSee($poster->title)->assertDontSee($rejected->title);

        $this->actingAs($committee)->post(route('committee.awards.entries.store', $award), ['abstracts' => [$poster->id]])->assertSessionHasErrors('entries');
        $this->actingAs($committee)->post(route('committee.awards.entries.store', $award), ['abstracts' => [$oral->id]])->assertSessionHasNoErrors();

        $entry = $award->entries()->sole();
        $this->assertSame($oral->submitter->name, $entry->name);
        $this->assertSame($oral->user_id, $entry->user_id);

        // A student award only offers abstracts from people registered as students.
        $students = $this->award(['name' => 'Student Research Award', 'presentation_type' => null, 'students_only' => true, 'places' => 1]);
        $this->assertSame([$studentOral->id], app(AwardService::class)->eligibleAbstracts($students)->pluck('id')->all());
    }

    public function test_judges_score_only_their_awards_and_never_their_own_work(): void
    {
        $judge = $this->user(Role::Judge);
        $coAuthorJudge = $this->user(Role::Judge);
        $otherJudge = $this->user(Role::Judge);
        $abstract = $this->accepted($this->user(), coAuthor: $coAuthorJudge);
        $award = $this->award();
        $awards = app(AwardService::class);
        $awards->shortlist($award, [$abstract->id]);
        $awards->syncJudges($award, [$judge->id, $coAuthorJudge->id]);
        $entry = $award->entries()->sole();

        // Only people with the judge role can be assigned.
        $this->expectsRefusal(fn () => $awards->syncJudges($award, [$this->user()->id]));

        $this->actingAs($judge)->get(route('judging.index'))->assertOk()->assertSee($abstract->title);
        $this->actingAs($judge)->get(route('judging.edit', $entry))->assertOk()->assertSee('Scientific quality');
        $this->actingAs($judge)->put(route('judging.update', $entry), $this->scores(11))->assertSessionHasErrors('scores.science');
        $this->actingAs($judge)->put(route('judging.update', $entry), $this->scores(8))->assertRedirect(route('judging.index'));
        $this->assertSame(32, $entry->scores()->sole()->total);

        // Scoring again replaces the judge's scores.
        $this->actingAs($judge)->put(route('judging.update', $entry), $this->scores(9))->assertRedirect();
        $this->assertSame(36, $entry->scores()->sole()->total);

        // A co-author never scores it, and a judge of another award cannot reach it.
        $this->actingAs($coAuthorJudge)->get(route('judging.edit', $entry))->assertForbidden();
        $this->actingAs($coAuthorJudge)->put(route('judging.update', $entry), $this->scores(10))->assertSessionHasErrors('scores');
        $this->actingAs($otherJudge)->get(route('judging.edit', $entry))->assertNotFound();
        $this->assertSame(1, $entry->scores()->count());

        // The co-author's missing score does not hold the award in judging.
        $this->assertSame(['done' => 1, 'expected' => 1], $award->fresh()->scoringProgress());
        $this->assertSame('deciding', $award->fresh()->stage()[0]);
    }

    public function test_the_ranking_follows_the_average_score_and_each_place_goes_to_one_finalist(): void
    {
        $committee = $this->user(Role::ScientificAdmin);
        [$judgeA, $judgeB] = [$this->user(Role::Judge), $this->user(Role::Judge)];
        $first = $this->accepted($this->user());
        $second = $this->accepted($this->user());
        $award = $this->award(['places' => 2]);
        $awards = app(AwardService::class);
        $awards->shortlist($award, [$first->id, $second->id]);
        $awards->syncJudges($award, [$judgeA->id, $judgeB->id]);
        [$entryFirst, $entrySecond] = [$award->entries()->where('abstract_id', $first->id)->sole(), $award->entries()->where('abstract_id', $second->id)->sole()];

        $this->actingAs($judgeA)->put(route('judging.update', $entrySecond), $this->scores(6));
        $this->actingAs($judgeB)->put(route('judging.update', $entrySecond), $this->scores(7));
        $this->actingAs($judgeA)->put(route('judging.update', $entryFirst), $this->scores(9));
        $this->actingAs($judgeB)->put(route('judging.update', $entryFirst), $this->scores(8));

        $standings = $awards->standings($award);
        $this->assertSame([$entryFirst->id, $entrySecond->id], $standings->pluck('id')->all());
        $this->assertSame(34.0, $standings->first()->averageScore());

        $this->actingAs($committee)->put(route('committee.awards.places', $award), ['places' => [$entryFirst->id => 1, $entrySecond->id => 1]])
            ->assertSessionHasErrors('places');
        $this->actingAs($committee)->put(route('committee.awards.places', $award), ['places' => [$entryFirst->id => 3]])
            ->assertSessionHasErrors('places');
        $this->actingAs($committee)->put(route('committee.awards.places', $award), ['places' => [$entryFirst->id => 1, $entrySecond->id => 2]])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $entryFirst->fresh()->place);
        $this->assertSame(2, $entrySecond->fresh()->place);
        $this->assertSame('Second place', $award->placeLabel(2));
    }

    public function test_announcing_publishes_the_winners_and_tells_them(): void
    {
        $committee = $this->user(Role::ScientificAdmin);
        $winner = $this->user();
        $abstract = $this->accepted($winner);
        $award = $this->award(['places' => 1]);
        $awards = app(AwardService::class);
        $awards->shortlist($award, [$abstract->id]);
        $entry = $award->entries()->sole();

        $this->actingAs($committee)->post(route('committee.awards.announce', $award))->assertSessionHasErrors('places');
        $awards->choosePlaces($award, [$entry->id => 1]);

        $this->get(route('awards.index'))->assertOk()->assertSee('Best Oral Presentation')->assertDontSee($winner->name);

        $this->actingAs($committee)->post(route('committee.awards.announce', $award))->assertSessionHasNoErrors();
        $this->assertTrue($award->fresh()->isAnnounced());
        Notification::assertSentTo($winner, AwardWon::class);

        $this->get(route('awards.index'))->assertSee($winner->name)->assertSee($abstract->title);
        $this->actingAs($winner)->get(route('dashboard'))->assertSee('Download certificate');

        // Nothing changes once announced, until the announcement is withdrawn.
        $this->expectsRefusal(fn () => $awards->choosePlaces($award->fresh(), []));
        $this->actingAs($committee)->delete(route('committee.awards.withdraw', $award))->assertRedirect();
        $this->assertFalse($award->fresh()->isAnnounced());
        $this->get(route('awards.index'))->assertDontSee($winner->name);
    }

    public function test_only_winners_download_their_certificate_after_the_announcement(): void
    {
        $committee = $this->user(Role::ScientificAdmin);
        $winner = $this->user();
        $finalist = $this->user();
        $award = $this->award(['places' => 1]);
        $awards = app(AwardService::class);
        $awards->shortlist($award, [$this->accepted($winner)->id, $this->accepted($finalist)->id]);
        $winning = $award->entries()->where('user_id', $winner->id)->sole();
        $other = $award->entries()->where('user_id', $finalist->id)->sole();
        $awards->choosePlaces($award, [$winning->id => 1]);

        $this->actingAs($winner)->get(route('awards.certificate', $winning))->assertNotFound(); // not announced yet
        $this->actingAs($winner)->get(route('awards.mine'))->assertOk()->assertSee('Shortlisted');

        $awards->announce($award);

        $this->actingAs($winner)->get(route('awards.certificate', $winning))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($finalist)->get(route('awards.certificate', $winning))->assertNotFound();
        $this->actingAs($finalist)->get(route('awards.certificate', $other))->assertNotFound();
        $this->actingAs($finalist)->get(route('awards.mine'))->assertSee('Finalist');
        $this->actingAs($committee)->get(route('committee.awards.entries.certificate', [$award, $winning]))->assertOk();
    }

    public function test_participants_nominate_people_while_nominations_are_open(): void
    {
        $participant = $this->user();
        $nominee = $this->user();
        $champion = $this->award([
            'name' => 'Rehabilitation Champion Award', 'kind' => AwardKind::Honour, 'presentation_type' => null, 'places' => 1,
            'nominations_close_on' => today()->addMonth(),
        ]);
        $form = ['award_category_id' => $champion->id, 'name' => $nominee->name, 'email' => strtoupper($nominee->email),
            'citation' => 'Runs weekly therapy groups for children with disabilities across six villages.'];

        $this->actingAs($participant)->get(route('awards.mine'))->assertOk()->assertSee('Nominate someone');
        $this->actingAs($participant)->post(route('awards.nominate'), ['citation' => 'Too short'] + $form)->assertSessionHasErrors('citation');
        $this->actingAs($participant)->post(route('awards.nominate'), $form)->assertRedirect(route('awards.mine'));
        $this->actingAs($participant)->post(route('awards.nominate'), $form)->assertSessionHasErrors('name');

        $nomination = $champion->entries()->sole();
        $this->assertSame($participant->id, $nomination->nominated_by);
        $this->assertSame($nominee->id, $nomination->user_id); // matched by email

        // Nominations are confidential: the nominee does not see them.
        $this->actingAs($nominee)->get(route('awards.mine'))->assertSee('No awards yet')->assertDontSee('Your nominations');
        $this->actingAs($participant)->get(route('awards.mine'))->assertSee('Your nominations')->assertSee('With the committee');

        $champion->update(['nominations_close_on' => today()->subDay()]);
        $this->actingAs($this->user())->post(route('awards.nominate'), $form)->assertSessionHasErrors('name');
    }

    public function test_awards_areas_are_limited_to_their_roles(): void
    {
        $participant = $this->user();
        $judge = $this->user(Role::Judge);
        $award = $this->award();

        $this->actingAs($participant)->get(route('committee.awards.index'))->assertForbidden();
        $this->actingAs($participant)->get(route('judging.index'))->assertForbidden();
        $this->actingAs($judge)->get(route('committee.awards.show', $award))->assertForbidden();
        $this->actingAs($judge)->get(route('dashboard'))->assertRedirect(route('judging.index'));
        $this->actingAs($judge)->get(route('judging.index'))->assertOk()->assertSee('No awards assigned to you yet');
        $this->actingAs($this->user(Role::ScientificAdmin))->get(route('committee.awards.show', $award))->assertOk();
    }

    public function test_the_public_page_and_home_page_list_the_awards(): void
    {
        $this->get(route('awards.index'))->assertOk()->assertSee('Awards to be announced');

        $this->award(['description' => 'For the best talks.']);
        $this->get(route('awards.index'))->assertOk()->assertSee('Best Oral Presentation')->assertSee('For the best talks.')
            ->assertSee('Winners will be announced at the closing ceremony.');
        $this->get(route('home'))->assertOk()->assertSee('Recognising excellence')->assertSee('Best Oral Presentation');
    }

    private function expectsRefusal(callable $action): void
    {
        try {
            $action();
            $this->fail('The action should have been refused.');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
    }
}
