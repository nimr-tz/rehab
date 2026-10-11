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

    private function desk(): User
    {
        return User::factory()->create()->assignRole(Role::RegistrationOfficer->value);
    }

    public function test_a_participant_corrects_their_badge_name_and_a_printed_badge_is_printed_again(): void
    {
        $registration = $this->registered();
        $registration->update(['badge_printed_at' => now(), 'badge_batch' => '270901-0900000']);

        $this->actingAs($registration->user)->get(route('registration.badge.show'))->assertSee('Your badge has already been printed');
        $this->actingAs($registration->user)->put(route('registration.badge-name'), ['badge_name' => '  Dr Neema A. Mushi '])->assertRedirect();

        $registration->refresh();
        $this->assertSame('Dr Neema A. Mushi', $registration->displayName());
        $this->assertNull($registration->badge_printed_at);
        $this->assertNull($registration->badge_batch);

        // Empty: back to the account name.
        $this->actingAs($registration->user)->put(route('registration.badge-name'), ['badge_name' => ''])->assertRedirect();
        $this->assertSame('Neema Mushi', $registration->fresh()->displayName());
    }

    public function test_a_collected_badge_stays_marked_as_printed(): void
    {
        $registration = $this->registered();
        $registration->update(['badge_printed_at' => now(), 'checked_in_at' => now()]);

        $this->actingAs($this->desk())->put(route('desk.badge-name', $registration), ['badge_name' => 'Neema A. Mushi'])
            ->assertSessionHas('status', 'Badge name saved.');

        $this->assertSame('Neema A. Mushi', $registration->fresh()->badge_name);
        $this->assertNotNull($registration->fresh()->badge_printed_at);
    }

    public function test_the_desk_corrects_a_name_and_reprints(): void
    {
        $registration = $this->registered();
        $registration->update(['badge_printed_at' => now()]);

        $this->actingAs($this->desk())->put(route('desk.badge-name', $registration), ['badge_name' => 'Neema Mushi-Kweka'])
            ->assertSessionHas('status', 'Badge name changed. Print the badge again.');

        $this->assertNull($registration->fresh()->badge_printed_at);
        $this->actingAs($this->desk())->get(route('desk.show', $registration))->assertSee('Neema Mushi-Kweka')->assertSee('Print badge');
    }

    public function test_a_printed_batch_can_be_printed_again(): void
    {
        $first = $this->registered();
        $second = $this->registered();
        $unpaid = $this->registered(confirmed: false);
        $desk = $this->desk();

        $this->actingAs($desk)->post(route('desk.print-batch'))->assertOk()->assertHeader('content-type', 'application/pdf');

        $batch = $first->fresh()->badge_batch;
        $this->assertNotNull($batch);
        $this->assertSame($batch, $second->fresh()->badge_batch);
        $this->assertNull($unpaid->fresh()->badge_printed_at);

        $this->actingAs($desk)->get(route('desk.queue'))->assertSee('Printed batches')->assertSee('2 badges');
        $this->actingAs($desk)->get(route('desk.reprint-batch', $batch))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($desk)->post(route('desk.print-batch'))->assertNotFound();
    }

    public function test_the_desk_works_on_the_current_summit_only(): void
    {
        $registration = $this->registered();
        $this->edition->update(['is_current' => false]);
        Edition::create(['year' => 2028, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '6th', 'is_current' => true]);
        app(Summit::class)->refresh();
        $desk = $this->desk();

        $this->actingAs($desk)->get(route('desk.show', $registration))->assertNotFound();
        $this->actingAs($desk)->post(route('desk.check-in', $registration))->assertNotFound();
        $this->actingAs($desk)->get(route('desk.badge', $registration))->assertNotFound();
        $this->actingAs($desk)->put(route('desk.badge-name', $registration), ['badge_name' => 'X'])->assertNotFound();
        $this->assertNull($registration->fresh()->checked_in_at);
    }
}
