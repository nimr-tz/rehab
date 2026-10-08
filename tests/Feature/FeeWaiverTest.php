<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\Edition;
use App\Models\FeeWaiver;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\FeeWaived;
use App\Notifications\WaiverWithdrawn;
use App\Services\DashboardService;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Finance waives registration fees, wholly or in part. Payments are high-risk, so every rule is tested. */
class FeeWaiverTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Storage::fake('local');
        config(['payments.mobile_money.providers' => ['mpesa' => ['label' => 'M-Pesa', 'pay_number' => '000000', 'account_name' => 'Rehab Health']]]);

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'registration_open' => true, 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional (Tanzania)', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        app(Summit::class)->refresh();

        $this->finance = User::factory()->create()->assignRole(Role::FinanceOfficer->value);
    }

    private function registered(): Registration
    {
        $user = User::factory()->create(['first_name' => 'Neema', 'last_name' => 'Mushi'])->assignRole(Role::Participant->value);
        $this->actingAs($user)->post(route('registration.store'), [
            'category' => $this->edition->categories->first()->id,
            'institution' => 'Muhimbili National Hospital', 'profession' => 'Physiotherapist', 'confirm' => '1',
        ])->assertRedirect();

        return $user->registrationFor($this->edition);
    }

    private function waive(Registration $registration, array $data, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->finance)->post(route('finance.waivers.store', $registration), $data);
    }

    private function pay(Registration $registration)
    {
        return $this->actingAs($registration->user)->post(route('registration.payments.store'), [
            'method' => 'mobile_money', 'provider' => 'mpesa', 'transaction_reference' => 'QWE123RTY9',
            'payer_name' => 'Neema Mushi', 'paid_on' => today()->toDateString(),
            'proof' => UploadedFile::fake()->create('slip.pdf', 10, 'application/pdf'),
        ]);
    }

    public function test_finance_finds_a_participant_and_waives_the_whole_fee(): void
    {
        $registration = $this->registered();

        $this->actingAs($this->finance)->get(route('finance.waivers.index', ['q' => 'Mushi']))->assertOk()->assertSee($registration->reference);
        $this->actingAs($this->finance)->get(route('finance.waivers.create', $registration))->assertOk()->assertSee('TZS 250,000');

        $this->waive($registration, ['amount' => '250000', 'reason' => 'speaker', 'note' => 'Keynote, day 2'])->assertRedirect(route('finance.waivers.index'));

        $registration->refresh();
        $this->assertSame(RegistrationStatus::Confirmed, $registration->status);
        $this->assertNotNull($registration->confirmed_at);
        $this->assertTrue($registration->isFullyWaived());
        $this->assertSame(0.0, $registration->amountDue());
        $waiver = FeeWaiver::sole();
        $this->assertSame($this->finance->id, $waiver->granted_by);
        $this->assertSame('Keynote, day 2', $waiver->note);
        Notification::assertSentTo($registration->user, FeeWaived::class);

        // Confirmed with nothing to pay: the badge opens and no payment can be sent.
        $this->actingAs($registration->user)->get(route('registration.badge'))->assertOk();
        $this->actingAs($registration->user)->get(route('registration.show'))->assertSee('Waived by the organisers')->assertSee('Full fee');
        $this->pay($registration)->assertForbidden();
    }

    public function test_a_partial_waiver_leaves_the_rest_to_pay_and_the_payment_confirms_it(): void
    {
        $registration = $this->registered();
        $this->waive($registration, ['amount' => '125000', 'reason' => 'hardship'])->assertRedirect();

        $registration->refresh();
        $this->assertSame(RegistrationStatus::PendingPayment, $registration->status);
        $this->assertSame(125000.0, $registration->amountDue());
        $this->actingAs($registration->user)->get(route('registration.show'))->assertSee('Pay TZS 125,000')->assertSee('To pay');

        // The payment records what is due, and verifying it confirms the registration.
        $this->pay($registration)->assertRedirect();
        $payment = Payment::sole();
        $this->assertSame('125000.00', $payment->amount);
        $this->actingAs($this->finance)->get(route('finance.payments.show', $payment))->assertOk()->assertSee('less a waiver')->assertDontSee('The amount differs from the fee due');
        $this->actingAs($this->finance)->post(route('finance.payments.verify', $payment))->assertRedirect();
        $this->assertSame(RegistrationStatus::Confirmed, $registration->fresh()->status);
    }

    public function test_a_waiver_is_refused_when_the_rules_say_no(): void
    {
        $registration = $this->registered();

        // More than the fee, nothing, or "other" without a note.
        $this->waive($registration, ['amount' => '300000', 'reason' => 'speaker'])->assertSessionHasErrors('amount');
        $this->waive($registration, ['amount' => '0', 'reason' => 'speaker'])->assertSessionHasErrors('amount');
        $this->waive($registration, ['amount' => '50000', 'reason' => 'other'])->assertSessionHasErrors('note');
        $this->waive($registration, ['amount' => '50000', 'reason' => 'bribe'])->assertSessionHasErrors('reason');
        $this->assertSame(0, FeeWaiver::count());

        // One waiver at a time.
        $this->waive($registration, ['amount' => '50000', 'reason' => 'sponsored'])->assertSessionHasNoErrors();
        $this->waive($registration, ['amount' => '100000', 'reason' => 'sponsored'])->assertSessionHasErrors('amount');
        $this->assertSame('50000.00', $registration->fresh()->waived_amount);

        // Not while a payment is waiting for verification.
        $other = $this->registered();
        $this->pay($other)->assertRedirect();
        $this->waive($other, ['amount' => '250000', 'reason' => 'speaker'])->assertSessionHasErrors(['amount' => 'A payment is waiting for verification. Verify or reject it first.']);

        // Not once paid.
        $this->actingAs($this->finance)->post(route('finance.payments.verify', Payment::where('registration_id', $other->id)->sole()));
        $this->waive($other, ['amount' => '250000', 'reason' => 'speaker'])->assertSessionHasErrors('amount');
        $this->actingAs($this->finance)->get(route('finance.waivers.create', $other))->assertRedirect(route('finance.waivers.index'));
    }

    public function test_finance_withdraws_a_waiver_until_the_rest_is_paid_or_the_participant_checks_in(): void
    {
        // A full waiver withdrawn: the full fee is due again.
        $registration = $this->registered();
        $this->waive($registration, ['amount' => '250000', 'reason' => 'committee'])->assertRedirect();
        $waiver = FeeWaiver::sole();

        $this->actingAs($this->finance)->post(route('finance.waivers.withdraw', $waiver), ['revoke_reason' => ''])->assertSessionHasErrors('revoke_reason');
        $this->actingAs($this->finance)->post(route('finance.waivers.withdraw', $waiver), ['revoke_reason' => 'No longer on the committee'])->assertRedirect();

        $registration->refresh();
        $this->assertSame(RegistrationStatus::PendingPayment, $registration->status);
        $this->assertNull($registration->confirmed_at);
        $this->assertSame(250000.0, $registration->amountDue());
        $this->assertSame('No longer on the committee', $waiver->fresh()->revoke_reason);
        $this->assertSame($this->finance->id, $waiver->fresh()->revoked_by);
        Notification::assertSentTo($registration->user, WaiverWithdrawn::class);
        $this->actingAs($this->finance)->post(route('finance.waivers.withdraw', $waiver), ['revoke_reason' => 'Again please'])->assertSessionHasErrors('waiver');

        // A partial waiver whose rest is paid stays.
        $paid = $this->registered();
        $this->waive($paid, ['amount' => '100000', 'reason' => 'sponsored'])->assertRedirect();
        $this->pay($paid)->assertRedirect();
        $partial = FeeWaiver::where('registration_id', $paid->id)->sole();
        $this->actingAs($this->finance)->post(route('finance.waivers.withdraw', $partial), ['revoke_reason' => 'Changed our mind'])
            ->assertSessionHasErrors(['waiver' => 'A payment is waiting for verification. Verify or reject it first.']);
        $this->actingAs($this->finance)->post(route('finance.payments.verify', Payment::where('registration_id', $paid->id)->sole()));
        $this->actingAs($this->finance)->post(route('finance.waivers.withdraw', $partial), ['revoke_reason' => 'Changed our mind'])->assertSessionHasErrors('waiver');
        $this->assertTrue($partial->fresh()->isActive());

        // After check-in, a waiver stays.
        $arrived = $this->registered();
        $this->waive($arrived, ['amount' => '250000', 'reason' => 'organiser'])->assertRedirect();
        $arrived->fresh()->update(['checked_in_at' => now()]);
        $this->actingAs($this->finance)->post(route('finance.waivers.withdraw', FeeWaiver::where('registration_id', $arrived->id)->sole()), ['revoke_reason' => 'Mistake'])
            ->assertSessionHasErrors('waiver');
    }

    public function test_only_finance_and_admins_waive_fees(): void
    {
        $registration = $this->registered();

        foreach ([Role::Participant, Role::Reviewer, Role::Executive, Role::ScientificAdmin] as $role) {
            $user = User::factory()->create()->assignRole($role->value);
            $this->actingAs($user)->get(route('finance.waivers.index'))->assertForbidden();
            $this->waive($registration, ['amount' => '250000', 'reason' => 'speaker'], $user)->assertForbidden();
        }

        $admin = User::factory()->create()->assignRole(Role::Admin->value);
        $this->waive($registration, ['amount' => '250000', 'reason' => 'speaker'], $admin)->assertRedirect();
        $this->assertSame(1, FeeWaiver::count());
    }

    public function test_waivers_reduce_expected_revenue_and_are_exported(): void
    {
        $full = $this->registered();
        $partial = $this->registered();
        $this->registered();
        $this->waive($full, ['amount' => '250000', 'reason' => 'speaker'])->assertRedirect();
        $this->waive($partial, ['amount' => '50000', 'reason' => 'other', 'note' => '=HYPERLINK("http://example.com")'])->assertRedirect();

        // Three fees of 250,000, less 300,000 waived.
        $money = app(DashboardService::class)->executive($this->edition)['money']['TZS'];
        $this->assertEquals(450000, $money['expected']);
        $this->assertEquals(300000, $money['waived']);

        $csv = $this->actingAs($this->finance)->get(route('finance.waivers.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString($full->reference, $csv);
        $this->assertStringContainsString('Invited speaker', $csv);
        // A note that looks like a formula is exported as text.
        $this->assertStringContainsString('\'=HYPERLINK', $csv);
    }
}
