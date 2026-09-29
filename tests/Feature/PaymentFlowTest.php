<?php

namespace Tests\Feature;

use App\Models\GroupMember;
use App\Models\GroupRegistration;
use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\SponsorPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');

        config([
            'payments.proof_disk' => 'local',
            'payments.registration_fees.professional_local.amount' => 100000,
            'payments.registration_fees.professional_international.amount' => 100,
            'payments.methods.bank_transfer.accounts.TZS.account_number' => '0150000000000',
            'payments.methods.bank_transfer.accounts.TZS.bank_name' => 'Test Bank',
            'payments.methods.mobile_money.providers.mpesa.pay_number' => '555000',
        ]);
    }

    private function participant(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'registration_category' => 'professional_local',
            'payment_status' => 'pending',
        ], $attributes));
    }

    private function financeOfficer(): User
    {
        $officer = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'finance_officer'], ['display_name' => 'Finance Officer']);
        $officer->roles()->attach($role);

        return $officer;
    }

    public function test_payment_page_issues_a_stable_reference(): void
    {
        $user = $this->participant();

        $this->actingAs($user)->get(route('payment.show'))
            ->assertOk()
            ->assertSee(sprintf('RH-U-%06d', $user->id))
            ->assertSee('Test Bank')
            ->assertSee('555000');

        $this->assertSame(sprintf('RH-U-%06d', $user->id), $user->fresh()->payment_reference);
    }

    public function test_bank_transfer_requires_proof(): void
    {
        $user = $this->participant();

        $this->actingAs($user)->post(route('payment.store'), [
            'method' => 'bank_transfer',
            'external_reference' => 'FT2601ABC',
        ])->assertSessionHas('error');

        $this->assertSame(0, PaymentTransaction::count());
        $this->assertSame('pending', $user->fresh()->payment_status);
    }

    public function test_participant_submits_bank_transfer_and_finance_verifies(): void
    {
        $user = $this->participant();
        $officer = $this->financeOfficer();

        $this->actingAs($user)->post(route('payment.store'), [
            'method' => 'bank_transfer',
            'external_reference' => 'FT2601ABC',
            'proof' => UploadedFile::fake()->create('slip.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('payment.show'));

        $transaction = PaymentTransaction::sole();
        $this->assertSame('submitted', $transaction->status);
        $this->assertEquals(100000, (float) $transaction->amount);
        $this->assertSame('TZS', $transaction->currency);
        Storage::disk('local')->assertExists($transaction->proof_path);
        $this->assertSame('submitted', $user->fresh()->payment_status);

        // A second submission while one is under review is refused.
        $this->actingAs($user)->post(route('payment.store'), [
            'method' => 'mobile_money',
            'provider' => 'mpesa',
            'external_reference' => 'QWE123',
        ])->assertSessionHas('error');
        $this->assertSame(1, PaymentTransaction::count());

        $this->actingAs($officer)->post(route('finance.verify', $user), ['notes' => 'Matched statement'])
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('verified', $user->payment_status);
        $this->assertSame('bank_transfer', $user->payment_method);
        $this->assertNotNull($user->qr_code_token);
        $this->assertSame('verified', $transaction->fresh()->status);
        $this->assertSame($officer->id, $transaction->fresh()->reviewed_by);
    }

    public function test_rejected_submission_returns_participant_to_pending(): void
    {
        $user = $this->participant();
        $officer = $this->financeOfficer();

        $this->actingAs($user)->post(route('payment.store'), [
            'method' => 'mobile_money',
            'provider' => 'mpesa',
            'external_reference' => 'QWE123',
        ]);

        $this->actingAs($officer)->post(route('finance.reject', $user), ['notes' => 'Reference not found'])
            ->assertSessionHas('success');

        $this->assertSame('pending', $user->fresh()->payment_status);
        $this->assertSame('rejected', PaymentTransaction::sole()->status);
    }

    public function test_reused_reference_is_flagged_for_finance(): void
    {
        $first = $this->participant();
        $second = $this->participant();

        foreach ([$first, $second] as $payer) {
            $this->actingAs($payer)->post(route('payment.store'), [
                'method' => 'mobile_money',
                'provider' => 'mpesa',
                'external_reference' => 'SAME-REF-1',
            ]);
        }

        $secondTransaction = $second->paymentTransactions()->first();
        $this->assertSame(1, $secondTransaction->duplicateReferences()->count());

        $this->actingAs($this->financeOfficer())->get(route('finance.show', $second))
            ->assertOk()
            ->assertSee('Reference already used');
    }

    public function test_proof_is_private_to_owner_and_finance(): void
    {
        $user = $this->participant();
        $stranger = $this->participant();

        $this->actingAs($user)->post(route('payment.store'), [
            'method' => 'bank_transfer',
            'external_reference' => 'FT2601XYZ',
            'proof' => UploadedFile::fake()->create('slip.pdf', 50, 'application/pdf'),
        ]);
        $transaction = PaymentTransaction::sole();

        $this->actingAs($user)->get(route('payment.proof', $transaction))->assertOk();
        $this->actingAs($stranger)->get(route('payment.proof', $transaction))->assertForbidden();
        $this->actingAs($stranger)->get(route('finance.transactions.proof', $transaction))->assertForbidden();
        $this->actingAs($this->financeOfficer())->get(route('finance.transactions.proof', $transaction))->assertOk();
    }

    public function test_group_payment_verification_activates_members(): void
    {
        $leader = $this->participant();
        $memberAccount = $this->participant(['email' => 'member@example.com']);
        $group = GroupRegistration::create([
            'leader_user_id' => $leader->id,
            'group_name' => 'Team A',
            'total_amount' => 300000,
            'currency' => 'TZS',
            'payment_status' => 'pending',
        ]);
        foreach (['leader@example.com', 'member@example.com', 'third@example.com'] as $email) {
            GroupMember::create([
                'group_registration_id' => $group->id,
                'full_name' => 'Member '.$email,
                'email' => $email,
                'registration_category' => 'professional_local',
                'fee_amount' => 100000,
                'fee_currency' => 'TZS',
            ]);
        }

        $this->actingAs($leader)->post(route('payment.store'), [
            'method' => 'bank_transfer',
            'external_reference' => 'GRP-001',
            'proof' => UploadedFile::fake()->create('slip.pdf', 50, 'application/pdf'),
        ])->assertRedirect(route('payment.show'));

        $transaction = PaymentTransaction::sole();
        $this->assertTrue($transaction->payable->is($group));
        $this->assertEquals(300000, (float) $transaction->amount);

        $this->actingAs($this->financeOfficer())->post(route('finance.group.verify', $group))
            ->assertSessionHas('success');

        $this->assertSame('verified', $group->fresh()->payment_status);
        $this->assertSame('verified', $memberAccount->fresh()->payment_status);
        $this->assertTrue($group->members()->whereNull('qr_token')->doesntExist());
    }

    public function test_finance_records_sponsor_payment(): void
    {
        $officer = $this->financeOfficer();
        $sponsor = SponsorPayment::create([
            'sponsor_name' => 'Acme Health',
            'contact_email' => 'finance@acme.test',
            'contact_phone' => '255700000000',
            'description' => 'Gold sponsorship',
            'amount' => 5000000,
            'currency' => 'TZS',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($officer)->post(route('finance.sponsors.verify', $sponsor), [
            'method' => 'bank_transfer',
            'external_reference' => 'SPN-778',
            'proof' => UploadedFile::fake()->create('slip.pdf', 50, 'application/pdf'),
        ])->assertSessionHas('success');

        $sponsor->refresh();
        $this->assertSame('verified', $sponsor->payment_status);
        $this->assertEquals(5000000, (float) $sponsor->paid_amount);
        $this->assertSame(sprintf('RH-S-%06d', $sponsor->id), $sponsor->payment_reference);
    }

    public function test_payment_page_explains_unpublished_fees(): void
    {
        config(['payments.registration_fees.professional_local.amount' => 0]);
        $user = $this->participant();

        $this->actingAs($user)->get(route('payment.show'))
            ->assertOk()
            ->assertSee('Registration fees have not been published yet.');

        $this->actingAs($user)->post(route('payment.store'), [
            'method' => 'mobile_money',
            'provider' => 'mpesa',
            'external_reference' => 'X1',
        ])->assertSessionHas('error');
    }
}
