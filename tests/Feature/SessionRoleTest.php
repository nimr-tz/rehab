<?php

namespace Tests\Feature;

use App\Enums\AbstractStatus;
use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\ProgrammeSession;
use App\Models\SessionRoleApplication;
use App\Models\User;
use App\Notifications\SessionRoleDecided;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Participants apply to chair or report a session; the committee decides; rapporteurs report. */
class SessionRoleTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private User $committee;

    private ProgrammeSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Storage::fake('local');
        $this->travelTo('2027-06-01 10:00');

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'session_role_deadline' => '2027-07-31', 'is_current' => true,
        ]);
        $this->edition->topics()->create(['name' => 'Stroke', 'code' => 'STR', 'sort' => 0]);
        app(Summit::class)->refresh();

        $this->committee = User::factory()->create()->assignRole(Role::ScientificAdmin->value);
        $this->session = $this->edition->sessions()->create([
            'title' => 'Oral session: Stroke', 'kind' => 'parallel', 'starts_at' => '2027-09-15 11:00', 'ends_at' => '2027-09-15 12:30', 'chair' => 'To be confirmed',
        ]);
    }

    private function participant(): User
    {
        return User::factory()->create()->assignRole(Role::Participant->value);
    }

    private function apply(User $user, string $role = 'chair')
    {
        return $this->actingAs($user)->post(route('session-roles.store'), [
            'role' => $role, 'topics' => [$this->edition->topics->first()->id],
            'experience' => 'I have chaired the stroke track at our national conference for three years.',
        ]);
    }

    public function test_the_home_page_link_leads_to_the_applications(): void
    {
        $this->get(route('home'))->assertSee(route('session-roles.index'));
        $this->get(route('session-roles.index'))->assertRedirect(route('login'));
        $this->actingAs($this->participant())->get(route('session-roles.index'))->assertOk()
            ->assertSee('Apply to be a chair')->assertSee('Apply to be a rapporteur')->assertSee('31 July 2027');
    }

    public function test_a_participant_applies_once_per_role_before_the_deadline(): void
    {
        $user = $this->participant();

        $this->apply($user)->assertRedirect(route('session-roles.index'));
        $this->apply($user)->assertSessionHasErrors('experience');
        $this->apply($user, 'rapporteur')->assertRedirect();
        $this->assertSame(2, SessionRoleApplication::where('user_id', $user->id)->count());

        // Withdrawn while pending.
        $application = SessionRoleApplication::where('user_id', $user->id)->where('role', 'rapporteur')->sole();
        $this->actingAs($user)->delete(route('session-roles.destroy', $application))->assertRedirect();
        $this->assertModelMissing($application);

        $this->travelTo('2027-08-01 09:00');
        $this->actingAs($user)->get(route('session-roles.index'))->assertSee('Applications are closed');
        $this->apply($this->participant())->assertSessionHasErrors('experience');
    }

    public function test_the_committee_accepts_with_a_session_and_the_programme_names_them(): void
    {
        $chair = $this->participant();
        $this->apply($chair);
        $application = SessionRoleApplication::sole();

        $this->actingAs($this->committee)->get(route('scientific.session-roles.index'))->assertOk()->assertSee($chair->name);
        $this->actingAs($this->committee)->post(route('scientific.session-roles.accept', $application), ['session_id' => $this->session->id, 'note' => 'Please arrive early.'])
            ->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Accepted, $application->status);
        $this->assertSame($this->session->id, $application->programme_session_id);
        $this->assertSame($chair->id, $this->session->fresh()->chair_user_id);
        $this->assertSame($chair->name, $this->session->fresh()->chair);
        Notification::assertSentTo($chair, SessionRoleDecided::class);
        $this->get(route('programme'))->assertSee('Chair: '.$chair->name);
        $this->actingAs($chair)->get(route('session-roles.index'))->assertSee('Oral session: Stroke')->assertSee('Please arrive early.');

        // One chair per session.
        $other = $this->participant();
        $this->apply($other);
        $second = SessionRoleApplication::where('user_id', $other->id)->sole();
        $this->actingAs($this->committee)->post(route('scientific.session-roles.accept', $second), ['session_id' => $this->session->id])
            ->assertSessionHasErrors('application_'.$second->id);

        // Released: the session has no chair, and the application is open again.
        $this->actingAs($this->committee)->post(route('scientific.session-roles.release', $application))->assertRedirect();
        $this->assertNull($this->session->fresh()->chair_user_id);
        $this->assertNull($this->session->fresh()->chair);
        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
    }

    public function test_the_committee_declines(): void
    {
        $user = $this->participant();
        $this->apply($user, 'rapporteur');
        $application = SessionRoleApplication::sole();

        $this->actingAs($this->committee)->post(route('scientific.session-roles.decline', $application))->assertRedirect();

        $this->assertSame(ApplicationStatus::Declined, $application->fresh()->status);
        Notification::assertSentTo($user, SessionRoleDecided::class);
        $this->actingAs($user)->get(route('session-roles.index'))->assertSee('Not selected');
    }

    public function test_the_chair_can_download_the_slides_of_their_session(): void
    {
        $chair = $this->participant();
        $this->apply($chair);
        $this->actingAs($this->committee)->post(route('scientific.session-roles.accept', SessionRoleApplication::sole()), ['session_id' => $this->session->id]);

        $abstract = AbstractSubmission::create([
            'edition_id' => $this->edition->id, 'user_id' => $this->participant()->id, 'topic_id' => $this->edition->topics->first()->id,
            'preferred_type' => 'oral', 'title' => 'Early mobilisation', 'background' => 'B', 'methods' => 'M', 'results' => 'R', 'conclusions' => 'C',
            'status' => AbstractStatus::Accepted, 'decision_type' => 'oral', 'code' => 'OR-STR-01',
        ]);
        Storage::disk('local')->put('presentations/slides.pdf', 'slides');
        $abstract->presentationFile()->create(['path' => 'presentations/slides.pdf', 'original_name' => 'slides.pdf', 'bytes' => 6]);

        $this->actingAs($chair)->get(route('presentations.download', $abstract))->assertNotFound();

        $this->session->abstracts()->attach($abstract->id, ['position' => 1]);
        $this->actingAs($chair)->get(route('session-roles.index'))->assertSee('OR-STR-01');
        $this->actingAs($chair)->get(route('presentations.download', $abstract))->assertOk();
    }

    public function test_the_rapporteur_reports_once_the_session_has_started(): void
    {
        $rapporteur = $this->participant();
        $this->apply($rapporteur, 'rapporteur');
        $this->actingAs($this->committee)->post(route('scientific.session-roles.accept', SessionRoleApplication::sole()), ['session_id' => $this->session->id]);
        $report = str_repeat('The session agreed that early mobilisation after stroke should start within 48 hours. ', 2);

        $this->actingAs($rapporteur)->get(route('session-roles.index'))->assertSee('once the session has started');
        $this->actingAs($rapporteur)->put(route('session-roles.report', $this->session), ['report' => $report])->assertSessionHasErrors('report');

        $this->travelTo('2027-09-15 12:00');
        $this->actingAs($this->participant())->put(route('session-roles.report', $this->session), ['report' => $report])->assertSessionHasErrors('report');
        $this->actingAs($rapporteur)->put(route('session-roles.report', $this->session), ['report' => $report])->assertSessionHasNoErrors();

        $this->assertSame(trim($report), $this->session->fresh()->rapporteur_report);
        $this->get(route('programme'))->assertSeeText("Rapporteur's report")->assertSee('within 48 hours');
    }

    public function test_renaming_the_chair_by_hand_unlinks_the_accepted_person(): void
    {
        $chair = $this->participant();
        $this->apply($chair);
        $this->actingAs($this->committee)->post(route('scientific.session-roles.accept', SessionRoleApplication::sole()), ['session_id' => $this->session->id]);

        $this->actingAs($this->committee)->put(route('scientific.programme.update', $this->session), [
            'title' => 'Oral session: Stroke', 'kind' => 'parallel', 'starts_at' => '2027-09-15 11:00', 'ends_at' => '2027-09-15 12:30',
            'chair' => 'Prof Invited Guest', 'rapporteur' => '',
        ])->assertRedirect();

        $this->assertSame('Prof Invited Guest', $this->session->fresh()->chair);
        $this->assertNull($this->session->fresh()->chair_user_id);
    }

    public function test_only_the_committee_decides(): void
    {
        $user = $this->participant();
        $this->apply($user);

        $this->actingAs($user)->post(route('scientific.session-roles.accept', SessionRoleApplication::sole()), ['session_id' => $this->session->id])->assertForbidden();
        $this->assertSame(ApplicationStatus::Pending, SessionRoleApplication::sole()->status);
    }
}
