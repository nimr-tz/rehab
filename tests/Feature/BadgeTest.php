<?php

namespace Tests\Feature;

use App\Enums\AbstractStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\Edition;
use App\Models\Registration;
use App\Models\Topic;
use App\Models\User;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** After payment the badge is the participant's summit ID, on screen and in the PDF. */
class BadgeTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

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
        $this->edition->categories()->create(['name' => 'Student', 'currency' => 'TZS', 'amount' => 50000, 'sort' => 1, 'is_student' => true]);
        app(Summit::class)->refresh();
    }

    private function registered(string $category = 'Professional (Tanzania)', bool $confirmed = true): Registration
    {
        $user = User::factory()->create([
            'first_name' => 'Neema', 'last_name' => 'Mushi', 'profession' => 'Physiotherapist', 'institution' => 'Muhimbili National Hospital',
        ])->assignRole(Role::Participant->value);
        $this->actingAs($user)->post(route('registration.store'), [
            'category' => $this->edition->categories->firstWhere('name', $category)->id,
            'institution' => 'Muhimbili National Hospital', 'profession' => 'Physiotherapist', 'confirm' => '1',
        ])->assertRedirect();

        $registration = $user->registrationFor($this->edition);
        if ($confirmed) {
            $registration->update(['status' => RegistrationStatus::Confirmed, 'confirmed_at' => now()]);
        }

        return $registration->fresh();
    }

    public function test_the_role_on_the_badge(): void
    {
        $this->assertSame('Participant', $this->registered()->badgeRole());
        $this->assertSame('Student', $this->registered('Student')->badgeRole());

        $presenter = $this->registered();
        $presenter->user->abstracts()->create([
            'edition_id' => $this->edition->id, 'topic_id' => Topic::create(['edition_id' => $this->edition->id, 'name' => 'Stroke', 'code' => 'STR'])->id,
            'preferred_type' => 'oral', 'title' => 'Early mobilisation after stroke', 'background' => 'B', 'methods' => 'M', 'results' => 'R', 'conclusions' => 'C',
            'status' => AbstractStatus::Accepted,
        ]);
        $this->assertSame('Presenter', $presenter->badgeRole());
    }

    public function test_a_confirmed_participant_sees_their_badge_as_their_id(): void
    {
        $registration = $this->registered();

        $this->actingAs($registration->user)->get(route('registration.show'))->assertOk()
            ->assertSee('Neema Mushi, Participant')
            ->assertSee('Physiotherapist')
            ->assertSee('Muhimbili National Hospital')
            ->assertSee($registration->reference)
            ->assertSee('You are going to the')
            ->assertSee('Download badge (PDF)');

        $this->actingAs($registration->user)->get(route('dashboard'))->assertOk()->assertSee('Neema Mushi, Participant');
        $this->actingAs($registration->user)->get(route('registration.badge'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_before_payment_the_badge_is_locked(): void
    {
        $registration = $this->registered(confirmed: false);

        $this->actingAs($registration->user)->get(route('registration.badge.show'))->assertOk()
            ->assertSee('Neema Mushi, Participant')
            ->assertDontSee('alt="Check-in code"', false);
        $this->actingAs($registration->user)->get(route('registration.badge'))->assertForbidden();
    }
}
