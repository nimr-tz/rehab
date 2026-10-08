<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\RegistrationConfirmed;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/** Participants pay with an M-Pesa prompt on their phone. Payments are high-risk, so every rule is tested. */
class MpesaPaymentTest extends TestCase
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
        Sleep::fake();
        Cache::flush();

        config([
            'mpesa.enabled' => true,
            'mpesa.environment' => 'sandbox',
            'mpesa.api_key' => 'test-api-key',
            'mpesa.public_key' => preg_replace('/-----[^-]+-----|\s/', '', file_get_contents(base_path('tests/fixtures/mpesa/test-public.pem'))),
            'mpesa.service_provider_code' => '000000',
            'payments.mobile_money.providers' => ['mpesa' => ['label' => 'M-Pesa', 'pay_number' => '000000', 'account_name' => 'Rehab Health']],
        ]);

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'registration_open' => true, 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional (Tanzania)', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        $this->edition->categories()->create(['name' => 'International', 'currency' => 'USD', 'amount' => 300, 'sort' => 1]);
        app(Summit::class)->refresh();

        $this->finance = User::factory()->create()->assignRole(Role::FinanceOfficer->value);
    }

    private function registered(string $currency = 'TZS'): Registration
    {
        $user = User::factory()->create(['first_name' => 'Neema', 'last_name' => 'Mushi'])->assignRole(Role::Participant->value);
        $this->actingAs($user)->post(route('registration.store'), [
            'category' => $this->edition->categories->firstWhere('currency', $currency)->id,
            'institution' => 'Muhimbili National Hospital', 'profession' => 'Physiotherapist', 'confirm' => '1',
        ])->assertRedirect();

        return $user->registrationFor($this->edition);
    }

    /** Fake M-Pesa: a working session, then the given C2B and status answers. */
    private function fakeMpesa(array $c2b = [], array $query = []): void
    {
        Http::fake([
            '*/getSession/' => Http::response(['output_ResponseCode' => 'INS-0', 'output_SessionID' => 'S1']),
            '*/c2bPayment/singleStage/' => Http::sequence($c2b ?: [$this->c2bSuccess()]),
            '*/queryTransactionStatus/*' => Http::sequence($query),
        ]);
    }

    private function c2bSuccess(string $transactionId = 'QX7TD52KHB'): PromiseInterface
    {
        return Http::response(['output_ResponseCode' => 'INS-0', 'output_ResponseDesc' => 'Request processed successfully', 'output_TransactionID' => $transactionId], 201);
    }

    private function payWithMpesa(Registration $registration, string $phone = '0754 123 456')
    {
        return $this->actingAs($registration->user)->post(route('registration.mpesa.store'), ['mpesa_phone' => $phone]);
    }

    public function test_a_successful_payment_confirms_the_registration_at_once(): void
    {
        $this->fakeMpesa();
        $registration = $this->registered();

        $this->actingAs($registration->user)->get(route('registration.show'))->assertSee('Send payment request');
        $this->payWithMpesa($registration)->assertRedirect(route('registration.show'))->assertSessionHas('status');

        $payment = Payment::sole();
        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertSame('QX7TD52KHB', $payment->transaction_reference);
        $this->assertSame('255754123456', $payment->payer_phone);
        $this->assertSame('mpesa', $payment->provider);
        $this->assertNull($payment->reviewed_by);
        $this->assertSame(RegistrationStatus::Confirmed, $registration->fresh()->status);
        Notification::assertSentTo($registration->user, RegistrationConfirmed::class);

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'c2bPayment/singleStage/')
            && $request['input_Amount'] === '250000'
            && $request['input_CustomerMSISDN'] === '255754123456'
            && $request['input_TransactionReference'] === str_replace('-', '', $registration->reference)
            && $request['input_ThirdPartyConversationID'] === $payment->gateway_reference);

        // Finance sees it as paid, with nothing to verify.
        $this->actingAs($this->finance)->get(route('finance.payments.show', $payment))->assertOk()->assertSee('Paid with an M-Pesa prompt');
        $this->actingAs($this->finance)->get(route('finance.payments.index'))->assertSee('Every submitted payment has been reviewed.');
    }

    public function test_the_amount_is_what_is_due_after_a_waiver(): void
    {
        $this->fakeMpesa();
        $registration = $this->registered();
        $this->actingAs($this->finance)->post(route('finance.waivers.store', $registration), ['amount' => '100000', 'reason' => 'hardship'])->assertRedirect();

        $this->payWithMpesa($registration)->assertRedirect(route('registration.show'));

        $this->assertSame('150000.00', Payment::sole()->amount);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'c2bPayment/singleStage/') && $request['input_Amount'] === '150000');
    }

    public function test_a_declined_payment_fails_and_the_participant_can_try_again(): void
    {
        $this->fakeMpesa(c2b: [
            Http::response(['output_ResponseCode' => 'INS-2006', 'output_ResponseDesc' => 'Insufficient balance'], 422),
            $this->c2bSuccess(),
        ]);
        $registration = $this->registered();

        $this->payWithMpesa($registration)->assertSessionHasErrors(['mpesa_phone' => 'Your M-Pesa balance is too low for this payment. Top up and try again, or pay another way.']);

        $failed = Payment::sole();
        $this->assertSame(PaymentStatus::Failed, $failed->status);
        $this->assertSame('INS-2006', $failed->gateway_code);
        $this->assertSame(RegistrationStatus::PendingPayment, $registration->fresh()->status);
        $this->assertTrue($registration->fresh()->canSubmitPayment());
        Notification::assertNothingSent();

        $this->payWithMpesa($registration)->assertRedirect(route('registration.show'));
        $this->assertSame(RegistrationStatus::Confirmed, $registration->fresh()->status);
        $this->assertSame(1, Payment::where('status', PaymentStatus::Verified)->count());
    }

    public function test_a_timeout_leaves_the_payment_pending_and_blocks_a_second_payment(): void
    {
        $this->fakeMpesa(
            c2b: [Http::response(['output_ResponseCode' => 'INS-9', 'output_ResponseDesc' => 'Request timeout'], 408)],
            query: [Http::response(['output_ResponseCode' => 'INS-0', 'output_ResponseTransactionStatus' => 'Completed', 'output_OriginalTransactionID' => 'LATE123456'])],
        );
        $registration = $this->registered();

        $this->payWithMpesa($registration)->assertRedirect(route('registration.show'));

        $payment = Payment::sole();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->actingAs($registration->user)->get(route('registration.show'))->assertSee('Waiting for M-Pesa')->assertDontSee('Send payment request');

        // No second prompt, no manual payment and no waiver while M-Pesa may still take the money.
        $this->payWithMpesa($registration)->assertSessionHasErrors('mpesa_phone');
        $this->actingAs($registration->user)->post(route('registration.payments.store'), [
            'method' => 'bank_transfer', 'transaction_reference' => 'QWE123RTY9', 'payer_name' => 'Neema Mushi',
            'paid_on' => today()->toDateString(), 'proof' => UploadedFile::fake()->create('slip.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
        $this->actingAs($this->finance)->post(route('finance.waivers.store', $registration), ['amount' => '100000', 'reason' => 'hardship'])->assertSessionHasErrors();
        $this->assertSame(1, Payment::count());

        // The participant entered their PIN late: the check confirms it.
        $this->actingAs($registration->user)->post(route('registration.mpesa.check'))->assertRedirect(route('registration.show'))->assertSessionHas('status');

        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);
        $this->assertSame('LATE123456', $payment->fresh()->transaction_reference);
        $this->assertSame(RegistrationStatus::Confirmed, $registration->fresh()->status);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'queryTransactionStatus/')
            && $request['input_QueryReference'] === $payment->gateway_reference);
    }

    public function test_a_lost_connection_leaves_the_payment_pending(): void
    {
        Http::fake([
            '*/getSession/' => Http::response(['output_ResponseCode' => 'INS-0', 'output_SessionID' => 'S1']),
            '*/c2bPayment/singleStage/' => Http::failedConnection(),
        ]);
        $registration = $this->registered();

        $this->payWithMpesa($registration)->assertRedirect(route('registration.show'));

        $this->assertSame(PaymentStatus::Pending, Payment::sole()->status);
        $this->assertSame(RegistrationStatus::PendingPayment, $registration->fresh()->status);
    }

    public function test_a_payment_m_pesa_never_heard_of_fails_after_five_minutes(): void
    {
        $notFound = fn () => Http::response(['output_ResponseCode' => 'INS-23', 'output_ResponseDesc' => 'Transaction not found. Contact M-Pesa Support'], 400);
        $this->fakeMpesa(
            c2b: [Http::response(['output_ResponseCode' => 'INS-9'], 408)],
            query: [$notFound(), $notFound()],
        );
        $registration = $this->registered();
        $this->payWithMpesa($registration);
        $payment = Payment::sole();

        // Too soon to tell.
        $this->actingAs($registration->user)->post(route('registration.mpesa.check'));
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);

        $this->travel(6)->minutes();
        $this->actingAs($registration->user)->post(route('registration.mpesa.check'))->assertSessionHasErrors('mpesa_phone');

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertTrue($registration->fresh()->canSubmitPayment());
    }

    public function test_a_failed_session_sends_no_prompt(): void
    {
        Http::fake(['*/getSession/' => Http::response(['output_ResponseCode' => 'INS-989', 'output_ResponseDesc' => 'Session Creation Failed'], 400)]);
        $registration = $this->registered();

        $this->payWithMpesa($registration)->assertSessionHasErrors('mpesa_phone');

        $this->assertSame(PaymentStatus::Failed, Payment::sole()->status);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'c2bPayment'));
    }

    public function test_only_m_pesa_numbers_are_accepted(): void
    {
        $this->fakeMpesa();
        $registration = $this->registered();

        foreach (['12345', '0221234567', '254712345678'] as $number) {
            $this->payWithMpesa($registration, $number)->assertSessionHasErrors('mpesa_phone');
        }

        $this->assertSame(0, Payment::count());
        Http::assertNothingSent();
    }

    public function test_m_pesa_is_offered_only_when_enabled_and_for_tzs_fees(): void
    {
        $this->fakeMpesa();
        $usd = $this->registered('USD');
        $this->actingAs($usd->user)->get(route('registration.show'))->assertDontSee('Send payment request');
        $this->payWithMpesa($usd)->assertNotFound();

        config(['mpesa.enabled' => false]);
        $tzs = $this->registered();
        $this->actingAs($tzs->user)->get(route('registration.show'))->assertDontSee('Send payment request');
        $this->payWithMpesa($tzs)->assertNotFound();

        $this->assertSame(0, Payment::count());
        Http::assertNothingSent();
    }

    public function test_the_payment_page_prepares_a_session_in_advance(): void
    {
        $this->fakeMpesa();
        $registration = $this->registered();

        $this->actingAs($registration->user)->get(route('registration.show'))->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'getSession/'));

        // Thirty seconds later the session is active: the payment does not wait or open another.
        $this->travel(31)->seconds();
        $this->payWithMpesa($registration)->assertRedirect(route('registration.show'));
        Http::assertSentCount(2);
        Sleep::assertNeverSlept();
    }

    public function test_the_page_offers_every_configured_channel_with_m_pesa_first(): void
    {
        config(['payments.mobile_money.providers' => [
            'mpesa' => ['label' => 'M-Pesa', 'pay_number' => '000000', 'account_name' => 'Rehab Health'],
            'airtel_money' => ['label' => 'Airtel Money', 'pay_number' => '111111', 'account_name' => 'Rehab Health'],
            'selcom' => ['label' => 'Selcom Pay', 'pay_number' => '222222', 'account_name' => 'Rehab Health'],
        ]]);
        config(['payments.bank_accounts' => ['TZS' => [
            'bank_name' => 'CRDB Bank', 'account_name' => 'Rehab Health', 'account_number' => '0150000', 'branch' => 'Azikiwe', 'swift_code' => 'CORUTZTZ',
        ]]]);
        $this->fakeMpesa();
        $registration = $this->registered();

        $this->actingAs($registration->user)->get(route('registration.show'))->assertOk()
            ->assertSeeInOrder(['M-Pesa', 'Instant: confirmed in about 15 seconds', 'Airtel Money', 'Selcom Pay', 'CRDB Bank'])
            ->assertSee('222222');
    }

    public function test_a_manual_channel_still_goes_to_finance(): void
    {
        config(['payments.mobile_money.providers.airtel_money' => ['label' => 'Airtel Money', 'pay_number' => '111111', 'account_name' => 'Rehab Health']]);
        $registration = $this->registered();

        $this->actingAs($registration->user)->post(route('registration.payments.store'), [
            'channel' => 'airtel_money', 'method' => 'mobile_money', 'provider' => 'airtel_money', 'transaction_reference' => 'AIR123456',
            'payer_name' => 'Neema Mushi', 'paid_on' => today()->toDateString(),
            'proof' => UploadedFile::fake()->create('sms.png', 10, 'image/png'),
        ])->assertRedirect(route('registration.show'));

        $payment = Payment::sole();
        $this->assertSame(PaymentStatus::Submitted, $payment->status);
        $this->assertSame('Airtel Money', $payment->channel());
        $this->assertSame(RegistrationStatus::PaymentSubmitted, $registration->fresh()->status);
    }

    public function test_the_page_pays_in_place_and_reports_the_result(): void
    {
        $this->fakeMpesa();
        $registration = $this->registered();

        $this->actingAs($registration->user)->postJson(route('registration.mpesa.store'), ['mpesa_phone' => '0754123456'])
            ->assertOk()
            ->assertJson(['state' => 'confirmed', 'transaction' => 'QX7TD52KHB']);

        $this->assertSame(RegistrationStatus::Confirmed, $registration->fresh()->status);
    }

    public function test_the_page_reports_a_declined_payment(): void
    {
        $this->fakeMpesa(c2b: [Http::response(['output_ResponseCode' => 'INS-6', 'output_ResponseDesc' => 'Transaction Failed'], 401)]);
        $registration = $this->registered();

        $this->actingAs($registration->user)->postJson(route('registration.mpesa.store'), ['mpesa_phone' => '0754123456'])
            ->assertOk()
            ->assertJson(['state' => 'failed', 'message' => 'The payment was not completed. It may have been cancelled or the PIN was wrong. Please try again.']);

        $this->actingAs($registration->user)->postJson(route('registration.mpesa.store'), ['mpesa_phone' => '123'])
            ->assertUnprocessable()->assertJsonValidationErrors('mpesa_phone');
    }

    public function test_the_page_follows_a_pending_payment_until_m_pesa_answers(): void
    {
        $this->fakeMpesa(
            c2b: [Http::response(['output_ResponseCode' => 'INS-9'], 408)],
            query: [
                Http::response(['output_ResponseCode' => 'INS-23'], 400),
                Http::response(['output_ResponseCode' => 'INS-0', 'output_ResponseTransactionStatus' => 'Completed', 'output_OriginalTransactionID' => 'LATE123456']),
            ],
        );
        $registration = $this->registered();

        $this->actingAs($registration->user)->postJson(route('registration.mpesa.store'), ['mpesa_phone' => '0754123456'])
            ->assertJson(['state' => 'pending']);

        // Asked again within five seconds: M-Pesa is not called again.
        $this->actingAs($registration->user)->getJson(route('registration.mpesa.status'))->assertJson(['state' => 'pending']);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'queryTransactionStatus'));

        $this->travel(6)->seconds();
        $this->actingAs($registration->user)->getJson(route('registration.mpesa.status'))->assertJson(['state' => 'pending']);

        $this->travel(6)->seconds();
        $this->actingAs($registration->user)->getJson(route('registration.mpesa.status'))
            ->assertJson(['state' => 'confirmed', 'transaction' => 'LATE123456']);

        $this->assertSame(RegistrationStatus::Confirmed, $registration->fresh()->status);
    }

    public function test_a_confirmed_registration_cannot_pay_again(): void
    {
        $this->fakeMpesa(c2b: [$this->c2bSuccess(), $this->c2bSuccess('SECOND1234')]);
        $registration = $this->registered();
        $this->payWithMpesa($registration);

        $this->payWithMpesa($registration)->assertNotFound();

        $this->assertSame(1, Payment::count());
    }
}
