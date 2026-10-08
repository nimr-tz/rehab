<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\Edition;
use App\Models\ProgrammeSession;
use App\Models\Registration;
use App\Models\SessionAttendance;
use App\Models\User;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Session attendance from the staff app, and the CPD points it earns. */
class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private User $desk;

    private ProgrammeSession $morning;

    private ProgrammeSession $afternoon;

    private ProgrammeSession $lunch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'registration_open' => true, 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional (Tanzania)', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        app(Summit::class)->refresh();

        $session = fn (string $title, string $kind, string $day, string $from, string $to, ?float $points) => $this->edition->sessions()->create([
            'title' => $title, 'kind' => $kind, 'starts_at' => "{$day} {$from}", 'ends_at' => "{$day} {$to}", 'cpd_points' => $points,
        ]);
        $this->morning = $session('Opening plenary', 'plenary', '2027-09-15', '09:00', '10:30', 1.5);
        $this->lunch = $session('Lunch', 'break', '2027-09-15', '12:30', '13:30', null);
        $this->afternoon = $session('Stroke rehabilitation', 'parallel', '2027-09-16', '14:00', '15:30', 2);

        $this->desk = User::factory()->create()->assignRole(Role::RegistrationOfficer->value);
    }

    private function registration(bool $confirmed = true): Registration
    {
        $user = User::factory()->create(['first_name' => 'Neema', 'last_name' => 'Mushi'])->assignRole(Role::Participant->value);

        return $this->edition->registrations()->create([
            'user_id' => $user->id, 'registration_category_id' => $this->edition->categories->first()->id,
            'reference' => 'RH27-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT), 'currency' => 'TZS', 'amount' => 250000,
            'status' => $confirmed ? RegistrationStatus::Confirmed : RegistrationStatus::PendingPayment,
            'confirmed_at' => $confirmed ? now() : null, 'qr_token' => 'token-'.$user->id,
        ]);
    }

    private function scan(ProgrammeSession $session, string $code)
    {
        return $this->postJson(route('api.v1.sessions.scan', $session), ['code' => $code]);
    }

    public function test_staff_sign_in_to_the_app_and_others_cannot(): void
    {
        $this->desk->update(['password' => 'secret-password']);
        $participant = User::factory()->create(['password' => 'secret-password'])->assignRole(Role::Participant->value);

        $this->postJson(route('api.v1.login'), ['email' => $this->desk->email, 'password' => 'secret-password', 'device_name' => 'Hall A phone'])
            ->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
        $this->postJson(route('api.v1.login'), ['email' => $this->desk->email, 'password' => 'wrong', 'device_name' => 'x'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson(route('api.v1.login'), ['email' => $participant->email, 'password' => 'secret-password', 'device_name' => 'x'])
            ->assertUnprocessable();

        $this->getJson(route('api.v1.sessions.index'))->assertUnauthorized();
        Sanctum::actingAs($participant);
        $this->getJson(route('api.v1.sessions.index'))->assertForbidden();
    }

    public function test_a_badge_is_recorded_once_per_session_during_the_session(): void
    {
        Sanctum::actingAs($this->desk);
        $registration = $this->registration();

        // Before the session starts.
        $this->travelTo('2027-09-15 08:55');
        $this->scan($this->morning, $registration->qr_token)->assertOk()->assertJson(['outcome' => 'session_closed', 'counted' => false]);

        $this->travelTo('2027-09-15 09:20');
        $this->scan($this->morning, $registration->qr_token)->assertOk()
            ->assertJson(['outcome' => 'recorded', 'counted' => true, 'attendee' => ['badge_number' => $registration->reference, 'payment' => 'paid']])
            ->assertJsonPath('session.attendance_count', 1);
        $this->assertNotNull($registration->fresh()->checked_in_at);

        // Scanned again, or by its badge number: still once.
        $this->scan($this->morning, $registration->reference)->assertOk()->assertJson(['outcome' => 'already_recorded', 'counted' => true]);
        $this->assertSame(1, SessionAttendance::count());
        $this->assertSame($this->desk->id, SessionAttendance::sole()->scanned_by);

        // After it ends.
        $this->travelTo('2027-09-15 10:31');
        $this->scan($this->morning, $registration->qr_token)->assertJson(['outcome' => 'already_recorded']);
        $this->scan($this->lunch, $registration->qr_token)->assertJson(['outcome' => 'not_scannable']);
    }

    public function test_unpaid_and_unknown_badges_are_not_recorded(): void
    {
        Sanctum::actingAs($this->desk);
        $unpaid = $this->registration(confirmed: false);
        $this->travelTo('2027-09-15 09:20');

        $this->scan($this->morning, $unpaid->qr_token)->assertOk()->assertJson(['outcome' => 'not_paid', 'counted' => false, 'attendee' => ['payment' => 'not_paid']]);
        $this->scan($this->morning, 'not-a-badge')->assertNotFound()->assertJson(['outcome' => 'not_found']);
        $this->assertSame(0, SessionAttendance::count());
    }

    public function test_cpd_points_add_up_per_session_across_the_days(): void
    {
        Sanctum::actingAs($this->desk);
        $registration = $this->registration();

        $this->travelTo('2027-09-15 09:30');
        $this->scan($this->morning, $registration->qr_token)->assertJson(['outcome' => 'recorded']);
        $this->travelTo('2027-09-16 14:10');
        $this->scan($this->afternoon, $registration->qr_token)->assertJson(['outcome' => 'recorded']);

        $this->getJson(route('api.v1.attendees.show', $registration->qr_token))->assertOk()
            ->assertJson(['cpd' => ['sessions_attended' => 2, 'points' => 3.5, 'points_available' => 3.5]]);

        $this->actingAs($registration->user)->get(route('dashboard'))->assertOk()
            ->assertSee('of 3.5 points')->assertSee('2 of 2 sessions attended');
        $this->actingAs($this->desk)->get(route('desk.show', $registration))->assertOk()
            ->assertSee('Stroke rehabilitation')->assertSee('3.5 of 3.5 CPD points');
    }

    public function test_the_app_lists_sessions_and_who_attended(): void
    {
        Sanctum::actingAs($this->desk);
        $registration = $this->registration();
        $this->travelTo('2027-09-15 09:30');
        $this->scan($this->morning, $registration->qr_token);

        $this->getJson(route('api.v1.sessions.index', ['date' => '2027-09-15']))->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Opening plenary')
            ->assertJsonPath('data.0.open_now', true)
            ->assertJsonPath('data.0.attendance_count', 1)
            ->assertJsonPath('data.1.scannable', false);

        $this->getJson(route('api.v1.sessions.attendance', $this->morning))->assertOk()
            ->assertJsonPath('data.0.badge_number', $registration->reference);
    }

    public function test_the_committee_sets_cpd_points_in_the_programme(): void
    {
        $committee = User::factory()->create()->assignRole(Role::ScientificAdmin->value);

        $this->actingAs($committee)->get(route('scientific.programme.index'))->assertOk()->assertSee('Opening plenary')->assertSee('Stroke rehabilitation');

        $this->actingAs($committee)->put(route('scientific.programme.update', $this->morning), [
            'title' => 'Opening plenary', 'kind' => 'plenary', 'starts_at' => '2027-09-15T09:00', 'ends_at' => '2027-09-15T10:30', 'cpd_points' => '2.5',
        ])->assertRedirect(route('scientific.programme.index'));
        $this->assertSame('2.50', $this->morning->fresh()->cpd_points);

        $this->actingAs($committee)->post(route('scientific.programme.store'), [
            'title' => 'Closing', 'kind' => 'plenary', 'starts_at' => '2027-09-17T16:00', 'ends_at' => '2027-09-17T15:00',
        ])->assertSessionHasErrors('ends_at');

        // A session with attendance keeps it: it cannot be deleted.
        $registration = $this->registration();
        $registration->attendances()->create(['programme_session_id' => $this->afternoon->id, 'scanned_at' => now()]);
        $this->actingAs($committee)->delete(route('scientific.programme.destroy', $this->afternoon))->assertSessionHasErrors('session');
        $this->assertNotNull($this->afternoon->fresh());
        $this->actingAs($committee)->delete(route('scientific.programme.destroy', $this->lunch))->assertRedirect();
        $this->assertNull($this->lunch->fresh());

        $this->actingAs($this->desk)->get(route('scientific.programme.index'))->assertForbidden();
    }
}
