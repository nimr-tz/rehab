<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\RegisteredAtDesk;
use App\Notifications\RegistrationConfirmed;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/** The registration desk: the registry, walk-ins, payment at the desk, badges and check-in. */
class DeskTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private User $desk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Sleep::fake();
        Cache::flush();

        config([
            'mpesa.enabled' => true,
            'mpesa.environment' => 'sandbox',
            'mpesa.api_key' => 'test-api-key',
            'mpesa.public_key' => preg_replace('/-----[^-]+-----|\s/', '', file_get_contents(base_path('tests/fixtures/mpesa/test-public.pem'))),
        ]);

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'registration_open' => true, 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional (Tanzania)', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        app(Summit::class)->refresh();

        $this->desk = User::factory()->create(['first_name' => 'Rehema', 'last_name' => 'Desk'])->assignRole(Role::RegistrationOfficer->value);
    }

    private function walkIn(array $overrides = [])
    {
        return $this->actingAs($this->desk)->post(route('desk.register.store'), $overrides + [
            'title' => 'Dr', 'first_name' => 'Juma', 'last_name' => 'Hassan', 'email' => 'Juma.Hassan@example.org',
            'phone' => '+255 754 123 456', 'country' => 'TZ', 'institution' => 'KCMC', 'profession' => 'Physiotherapist',
            'category' => $this->edition->categories->first()->id, 'dietary_needs' => 'Vegetarian',
        ]);
    }

    private function fakeMpesa(): void
    {
        Http::fake([
            '*/getSession/' => Http::response(['output_ResponseCode' => 'INS-0', 'output_SessionID' => 'S1']),
            '*/c2bPayment/singleStage/' => Http::response(['output_ResponseCode' => 'INS-0', 'output_TransactionID' => 'DESK123456'], 201),
        ]);
    }

    public function test_a_walk_in_is_registered_paid_by_m_pesa_and_gets_a_badge(): void
    {
        $this->fakeMpesa();

        $response = $this->walkIn();

        $registration = Registration::sole();
        $response->assertRedirect(route('desk.show', $registration));
        $user = $registration->user;
        $this->assertSame('juma.hassan@example.org', $user->email);
        $this->assertSame('KCMC', $user->institution);
        $this->assertTrue($user->hasRole(Role::Participant->value));
        $this->assertSame('Vegetarian', $registration->dietary_needs);
        $this->assertSame(RegistrationStatus::PendingPayment, $registration->status);
        Notification::assertSentTo($user, RegisteredAtDesk::class);

        // No payment, no badge.
        $this->actingAs($this->desk)->get(route('desk.badge', $registration))->assertForbidden();
        $this->actingAs($this->desk)->get(route('desk.show', $registration))->assertOk()
            ->assertSee('Send M-Pesa request')->assertSee('Vegetarian');

        $this->actingAs($this->desk)->postJson(route('desk.mpesa', $registration), ['mpesa_phone' => '0754123456'])
            ->assertOk()->assertJson(['state' => 'confirmed', 'transaction' => 'DESK123456']);

        $payment = Payment::sole();
        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertSame($this->desk->id, $payment->initiated_by);
        $this->assertSame(RegistrationStatus::Confirmed, $registration->fresh()->status);
        Notification::assertSentTo($user, RegistrationConfirmed::class);

        $this->actingAs($this->desk)->get(route('desk.show', $registration))->assertOk()
            ->assertSee('give them their badge')->assertSee('sent from the desk by '.$this->desk->name);
        $this->actingAs($this->desk)->get(route('desk.badge', $registration))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->desk)->post(route('desk.check-in', $registration))->assertSessionHas('status');
        $this->assertNotNull($registration->fresh()->checked_in_at);
    }

    public function test_an_existing_account_is_reused_and_a_registered_one_is_refused(): void
    {
        $existing = User::factory()->create(['email' => 'juma.hassan@example.org'])->assignRole(Role::Participant->value);

        $this->walkIn()->assertRedirect();
        $this->assertSame($existing->id, Registration::sole()->user_id);
        $this->assertSame(1, User::where('email', 'juma.hassan@example.org')->count());
        Notification::assertNotSentTo($existing, RegisteredAtDesk::class);

        $this->walkIn()->assertSessionHasErrors('email');
        $this->assertSame(1, Registration::count());
    }

    public function test_the_registry_shows_where_everyone_stands_and_a_scan_opens_the_person(): void
    {
        $this->walkIn();
        $registration = Registration::sole();

        $this->actingAs($this->desk)->get(route('desk.index'))->assertOk()
            ->assertSee('Juma Hassan')->assertSee('Not paid')->assertSee('No badge yet')->assertSee('Take payment');

        $this->actingAs($this->desk)->get(route('desk.index', ['q' => $registration->qr_token]))->assertRedirect(route('desk.show', $registration));
        $this->actingAs($this->desk)->get(route('desk.index', ['q' => $registration->reference]))->assertRedirect(route('desk.show', $registration));
        $this->actingAs($this->desk)->get(route('desk.index', ['q' => '754 123']))->assertOk()->assertSee('Juma Hassan');
    }

    public function test_unpaid_people_cannot_be_checked_in(): void
    {
        $this->walkIn();
        $registration = Registration::sole();

        $this->actingAs($this->desk)->post(route('desk.check-in', $registration))->assertSessionHasErrors('check_in');
        $this->assertNull($registration->fresh()->checked_in_at);
    }

    public function test_only_desk_staff_use_the_desk(): void
    {
        $participant = User::factory()->create()->assignRole(Role::Participant->value);
        $this->walkIn();
        $registration = Registration::sole();

        $this->actingAs($participant)->get(route('desk.register'))->assertForbidden();
        $this->actingAs($participant)->get(route('desk.show', $registration))->assertForbidden();
        $this->actingAs($participant)->postJson(route('desk.mpesa', $registration), ['mpesa_phone' => '0754123456'])->assertForbidden();
    }
}
