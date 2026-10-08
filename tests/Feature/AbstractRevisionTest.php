<?php

namespace Tests\Feature;

use App\Enums\AbstractStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\ReviewAssignment;
use App\Models\User;
use App\Notifications\AbstractDecided;
use App\Notifications\ReviewAssigned;
use App\Notifications\RevisionRequested;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Two reviewers per abstract. Two acceptances accept it automatically; any
 * other combination goes to the committee, which may ask for one revision.
 * The revision goes back only to the reviewers who did not accept.
 */
class AbstractRevisionTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private User $committee;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Notification::fake();

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17',
            'abstracts_open' => true, 'abstract_deadline' => now()->addMonths(6), 'is_current' => true,
        ]);
        $this->edition->topics()->create(['name' => 'Home-Based Rehabilitation', 'code' => 'HBR', 'sort' => 0]);
        app(Summit::class)->refresh();

        $this->committee = $this->user(Role::ScientificAdmin);
        $this->author = $this->user();
    }

    private function user(Role $role = Role::Participant): User
    {
        return User::factory()->create()->assignRole($role->value);
    }

    private function submitted(): AbstractSubmission
    {
        $this->actingAs($this->author)->post(route('abstracts.store'), [
            'action' => 'submit',
            'topic_id' => (string) $this->edition->topics->first()->id,
            'preferred_type' => 'oral',
            'title' => 'Peer-led exercise after stroke',
            'background' => 'Stroke survivors rarely access rehabilitation.',
            'methods' => 'Pilot of weekly groups with forty participants.',
            'results' => 'Walking speed improved.',
            'conclusions' => 'Feasible in communities.',
            'authors' => [['name' => 'Amina Mussa', 'email' => 'amina@example.com', 'affiliation' => 'MNH']],
            'presenter' => '0',
        ])->assertRedirect();

        return AbstractSubmission::sole();
    }

    private function assign(AbstractSubmission $abstract, User $reviewer): ReviewAssignment
    {
        $this->actingAs($this->committee)->post(route('scientific.abstracts.assign', $abstract), ['reviewer_id' => $reviewer->id])
            ->assertSessionHasNoErrors();

        return $abstract->reviews()->where('reviewer_id', $reviewer->id)->latest('id')->first();
    }

    private function review(ReviewAssignment $assignment, string $recommendation, int $points = 60)
    {
        $f = $points / 100;

        return $this->actingAs($assignment->reviewer)->put(route('reviews.update', $assignment), [
            'score_originality' => (int) round(20 * $f), 'score_technical' => (int) round(40 * $f),
            'score_significance' => (int) round(30 * $f), 'score_clarity' => (int) round(10 * $f),
            'recommendation' => $recommendation,
            'comments_for_author' => "Reviewer says {$recommendation}: please report how participants were recruited.",
        ]);
    }

    /** @return array{0: AbstractSubmission, 1: ReviewAssignment, 2: ReviewAssignment} */
    private function reviewed(string $first, string $second): array
    {
        $abstract = $this->submitted();
        $a = $this->assign($abstract, $this->user(Role::Reviewer));
        $b = $this->assign($abstract, $this->user(Role::Reviewer));
        $this->review($a, $first, $first === 'reject' ? 40 : ($first === 'revise' ? 60 : 80))->assertRedirect();
        $this->review($b, $second, $second === 'reject' ? 40 : ($second === 'revise' ? 60 : 80))->assertRedirect();

        return [$abstract->fresh(), $a, $b];
    }

    private function revise(AbstractSubmission $abstract, array $overrides = [])
    {
        return $this->actingAs($this->author)->put(route('abstracts.revision.update', $abstract), $overrides + [
            'title' => 'Peer-led group exercise after stroke in Dodoma',
            'background' => 'Stroke survivors rarely access rehabilitation.',
            'methods' => 'Pilot of weekly groups with forty participants, recruited consecutively at discharge.',
            'results' => 'Walking speed improved by 0.2 m/s (95% CI 0.1 to 0.3).',
            'conclusions' => 'Feasible in communities.',
            'keywords' => 'stroke, community',
            'revision_response' => 'We added how participants were recruited and confidence intervals for the main result.',
        ]);
    }

    public function test_an_abstract_has_exactly_two_reviewers(): void
    {
        $abstract = $this->submitted();
        $this->assign($abstract, $this->user(Role::Reviewer));
        $this->assign($abstract, $this->user(Role::Reviewer));

        $this->actingAs($this->committee)->post(route('scientific.abstracts.assign', $abstract), ['reviewer_id' => $this->user(Role::Reviewer)->id])
            ->assertSessionHasErrors(['reviewer_id' => 'This abstract already has its 2 reviewers.']);
        $this->assertSame(2, $abstract->reviews()->count());

        $this->actingAs($this->committee)->get(route('scientific.abstracts.show', $abstract))->assertOk()->assertDontSee('Assign a reviewer');
    }

    public function test_any_combination_but_two_acceptances_waits_for_the_committee(): void
    {
        foreach ([['accept_oral', 'revise'], ['accept_oral', 'reject'], ['revise', 'reject'], ['reject', 'reject'], ['revise', 'revise']] as [$first, $second]) {
            [$abstract] = $this->reviewed($first, $second);

            $this->assertSame(AbstractStatus::UnderReview, $abstract->status, "{$first} + {$second}");
            $this->assertTrue($abstract->awaitsDecision(), "{$first} + {$second}");
            $this->assertSame(1, AbstractSubmission::awaitingDecision()->count(), "{$first} + {$second}");
            $this->actingAs($this->committee)->get(route('scientific.abstracts.show', $abstract))->assertOk()
                ->assertSee('Request revisions')->assertSee('Accept')->assertSee('Reject');

            $abstract->reviews()->delete();
            $abstract->delete();
        }
    }

    public function test_a_revision_goes_back_only_to_the_reviewer_who_did_not_accept(): void
    {
        [$abstract, $accepted, $wantsChanges] = $this->reviewed('accept_oral', 'revise');

        // The committee asks for a revision. The author's deadline can even pass the submission deadline.
        $this->edition->update(['abstracts_open' => false]);
        $this->actingAs($this->committee)->post(route('scientific.abstracts.decide', $abstract), [
            'decision' => 'revise', 'revision_due_on' => today()->addDays(10)->toDateString(), 'decision_note' => 'Please address both reviews.',
        ])->assertRedirect();

        $abstract->refresh();
        $this->assertSame(AbstractStatus::RevisionRequested, $abstract->status);
        $this->assertSame('Peer-led exercise after stroke', $abstract->originalText('title'));
        Notification::assertSentTo($this->author, RevisionRequested::class);

        // The first round is closed for the reviewers.
        $this->review($wantsChanges, 'accept_oral', 80)->assertSessionHasErrors(['recommendation' => 'This review round is closed.']);

        // The author sees the comments and revises. Someone else cannot.
        $this->actingAs($this->author)->get(route('abstracts.show', $abstract))->assertSee('Revisions requested')->assertSee('please report how participants were recruited');
        $this->actingAs($this->user())->get(route('abstracts.revision.edit', $abstract))->assertNotFound();
        $this->actingAs($this->author)->get(route('abstracts.revision.edit', $abstract))->assertOk()->assertSee('Please address both reviews.');
        $this->revise($abstract, ['revision_response' => ''])->assertSessionHasErrors('revision_response');
        $this->revise($abstract)->assertRedirect(route('abstracts.show', $abstract));

        $abstract->refresh();
        $this->assertSame(AbstractStatus::Revised, $abstract->status);
        $this->assertSame('Peer-led group exercise after stroke in Dodoma', $abstract->title);

        // Only the reviewer who asked for changes gets the revised version.
        $round2 = $abstract->reviews()->where('round', 2)->get();
        $this->assertSame([$wantsChanges->reviewer_id], $round2->pluck('reviewer_id')->all());
        Notification::assertSentTo($wantsChanges->reviewer, ReviewAssigned::class, fn ($n) => $n->assignment->round === 2);
        $this->assertFalse($abstract->reviews()->where('round', 2)->where('reviewer_id', $accepted->reviewer_id)->exists());

        // They see both versions side by side, and cannot ask for a second revision.
        $second = $round2->sole();
        $this->actingAs($wantsChanges->reviewer)->get(route('reviews.edit', $second))->assertOk()
            ->assertSee('What the authors changed')->assertSee('Before the revision')->assertSee('recruited consecutively at discharge')
            ->assertSee('We added how participants were recruited')->assertDontSee('value="revise"', false);
        $this->review($second, 'revise')->assertSessionHasErrors('recommendation');

        // They accept, so the abstract is accepted automatically.
        $this->review($second, 'accept_oral', 80)->assertRedirect();
        $abstract->refresh();
        $this->assertSame(AbstractStatus::Accepted, $abstract->status);
        $this->assertTrue($abstract->accepted_automatically);
        $this->assertSame('OR-HBR-01', $abstract->code);
        Notification::assertSentTo($this->author, AbstractDecided::class);
    }

    public function test_after_a_revision_the_committee_can_only_accept_or_reject(): void
    {
        [$abstract, $a, $b] = $this->reviewed('revise', 'reject');
        $this->actingAs($this->committee)->post(route('scientific.abstracts.decide', $abstract), ['decision' => 'revise'])->assertRedirect();
        $this->assertSame(today()->addDays(14)->toDateString(), $abstract->fresh()->revision_due_on->toDateString());
        $this->revise($abstract)->assertRedirect();

        // Both did not accept, so both review again. They disagree.
        $round2 = $abstract->reviews()->where('round', 2)->get()->keyBy('reviewer_id');
        $this->assertCount(2, $round2);
        $this->review($round2[$a->reviewer_id], 'accept_oral', 80)->assertRedirect();
        $this->assertSame(AbstractStatus::Revised, $abstract->fresh()->status);
        $this->review($round2[$b->reviewer_id], 'reject', 40)->assertRedirect();

        $abstract->refresh();
        $this->assertTrue($abstract->awaitsDecision());
        $this->actingAs($this->committee)->get(route('scientific.abstracts.show', $abstract))->assertOk()
            ->assertSee('Second review · the revised version')->assertDontSee('Request revisions');
        $this->actingAs($this->committee)->post(route('scientific.abstracts.decide', $abstract), ['decision' => 'revise'])->assertSessionHasErrors('decision');

        $this->actingAs($this->committee)->post(route('scientific.abstracts.decide', $abstract), ['decision' => 'reject'])->assertRedirect();
        $this->assertSame(AbstractStatus::Rejected, $abstract->fresh()->status);

        // The author now sees the comments from both rounds.
        $this->actingAs($this->author)->get(route('abstracts.show', $abstract))
            ->assertSee('First review')->assertSee('Review of your revised version');
    }

    public function test_the_committee_extends_or_rejects_a_late_revision_and_the_author_may_withdraw(): void
    {
        [$abstract] = $this->reviewed('accept_oral', 'reject');
        $this->actingAs($this->committee)->post(route('scientific.abstracts.decide', $abstract), ['decision' => 'revise'])->assertRedirect();

        $this->travel(15)->days();
        $this->assertTrue($abstract->fresh()->isRevisionOverdue());
        $this->actingAs($this->committee)->get(route('scientific.abstracts.show', $abstract))->assertSee('The deadline has passed');

        $this->actingAs($this->committee)->put(route('scientific.abstracts.revision-due', $abstract), ['revision_due_on' => today()->addWeek()->toDateString()])
            ->assertRedirect();
        $this->assertFalse($abstract->fresh()->isRevisionOverdue());

        // Accepting is not possible while the revision is awaited; rejecting is.
        $this->actingAs($this->committee)->post(route('scientific.abstracts.decide', $abstract), ['decision' => 'oral'])->assertSessionHasErrors('decision');

        $this->actingAs($this->author)->post(route('abstracts.withdraw', $abstract))->assertRedirect();
        $this->assertSame(AbstractStatus::Withdrawn, $abstract->fresh()->status);
        $this->revise($abstract)->assertForbidden();
    }
}
