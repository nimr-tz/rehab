<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\Edition;
use App\Models\GroupRegistration;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\GroupConfirmed;
use App\Notifications\GroupPaymentReminder;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentRejected;
use App\Notifications\PaymentReminder;
use App\Notifications\RegisteredByGroup;
use App\Notifications\RegistrationConfirmed;
use App\Services\ReminderService;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/** A leader registers several people and pays once for all of them. Payments are high-risk, so every rule is tested. */
class GroupRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private User $leader;

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

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'registration_open' => true, 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional (Tanzania)', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        $this->edition->categories()->create(['name' => 'Allied professional', 'currency' => 'TZS', 'amount' => 150000, 'sort' => 1]);
        $this->edition->categories()->create(['name' => 'International', 'currency' => 'USD', 'amount' => 300, 'sort' => 2]);
        $this->edition->categories()->create(['name' => 'Student', 'currency' => 'TZS', 'amount' => 50000, 'is_student' => true, 'sort' => 3]);
        app(Summit::class)->refresh();

        $this->leader = User::factory()->create(['first_name' => 'Halima'])->assignRole(Role::Participant->value);
        $this->finance = User::factory()->create()->assignRole(Role::FinanceOfficer->value);
    }

    private function category(string $name): int
    {
        return $this->edition->categories->firstWhere('name', $name)->id;
    }

    private function member(string $email, string $category = 'Professional (Tanzania)', array $extra = []): array
    {
        return $extra + [
            'first_name' => ucfirst(strtok($email, '@')), 'last_name' => 'Mrema', 'email' => $email, 'phone' => '+255 754 000 111',
            'country' => 'TZ', 'institution' => 'KCMC', 'profession' => 'Physiotherapist', 'category' => $this->category($category),
        ];
    }

    private function createGroup(array $members, ?string $leaderCategory = null)
    {
        return $this->actingAs($this->leader)->post(route('groups.store'), [
            'name' => 'KCMC Physiotherapy', 'leader_category' => $leaderCategory ? $this->category($leaderCategory) : '',
            'members' => $members, 'confirm' => '1',
        ]);
    }

    private function group(): GroupRegistration
    {
        return GroupRegistration::sole();
    }

    /** A member who has set their password from the welcome email, which verifies it. */
    private function signedUp(string $email): User
    {
        $user = User::where('email', $email)->sole();
        $user->markEmailAsVerified();

        return $user;
    }

    private function bankPayment(GroupRegistration $group, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->leader)->post(route('groups.payments.store', $group), [
            'method' => 'bank_transfer', 'transaction_reference' => 'FT27123456', 'payer_name' => 'KCMC Finance',
            'paid_on' => today()->toDateString(), 'proof' => UploadedFile::fake()->create('slip.pdf', 20, 'application/pdf'),
        ]);
    }

    public function test_a_leader_registers_a_group_and_everyone_gets_a_registration(): void
    {
        $existing = User::factory()->create(['email' => 'amina@example.org'])->assignRole(Role::Participant->value);

        $this->createGroup([
            $this->member('juma@example.org'),
            $this->member('AMINA@example.org', 'Allied professional'),
        ], leaderCategory: 'Professional (Tanzania)')->assertRedirect(route('groups.show', $this->group()));

        $group = $this->group();
        $this->assertSame('RH27-G'.str_pad((string) $group->id, 4, '0', STR_PAD_LEFT), $group->reference);
        $this->assertSame('TZS', $group->currency);
        $this->assertSame(3, $group->members()->count());
        $this->assertSame(650000.0, $group->amountDue());
        $this->assertSame(RegistrationStatus::PendingPayment, $group->status);

        // A new account for the new email, the existing account reused, and the leader registered too.
        $juma = User::where('email', 'juma@example.org')->sole();
        $this->assertTrue($juma->hasRole(Role::Participant->value));
        $this->assertSame($group->id, $juma->registrationFor($this->edition)->group_registration_id);
        $this->assertSame($group->id, $existing->registrationFor($this->edition)->group_registration_id);
        $this->assertSame($group->id, $this->leader->registrationFor($this->edition)->group_registration_id);

        Notification::assertSentTo($juma, RegisteredByGroup::class);
        Notification::assertSentTo($existing, RegisteredByGroup::class);
        Notification::assertNotSentTo($this->leader, RegisteredByGroup::class);

        $this->actingAs($this->leader)->get(route('groups.show', $group))->assertOk()->assertSee('Pay TZS 650,000')->assertSee($group->reference);
    }

    public function test_a_group_pays_in_one_currency_and_takes_no_student_rates(): void
    {
        $this->createGroup([$this->member('juma@example.org'), $this->member('ana@example.org', 'International')])->assertSessionHasErrors('members');
        $this->createGroup([$this->member('juma@example.org', 'Student')])->assertSessionHasErrors('members');

        $this->assertSame(0, GroupRegistration::count());
        $this->assertSame(0, Registration::count());
        $this->assertFalse(User::where('email', 'juma@example.org')->exists());
    }

    public function test_someone_already_registered_cannot_join_and_nothing_is_created(): void
    {
        $this->createGroup([$this->member('juma@example.org')])->assertRedirect();
        $this->leader = User::factory()->create()->assignRole(Role::Participant->value);

        $this->createGroup([$this->member('neema@example.org'), $this->member('juma@example.org')])->assertSessionHasErrors('members');

        $this->assertSame(1, GroupRegistration::count());
        $this->assertFalse(User::where('email', 'neema@example.org')->exists());
    }

    public function test_members_cannot_pay_for_themselves(): void
    {
        $this->createGroup([$this->member('juma@example.org')]);
        $juma = $this->signedUp('juma@example.org');
        $registration = $juma->registrationFor($this->edition);

        $this->assertFalse($registration->canSubmitPayment());
        $this->actingAs($juma)->get(route('registration.show'))->assertSee('Paid for by KCMC Physiotherapy');
        $this->actingAs($juma)->post(route('registration.payments.store'), [
            'method' => 'bank_transfer', 'transaction_reference' => 'FT27999999', 'payer_name' => 'Juma',
            'paid_on' => today()->toDateString(), 'proof' => UploadedFile::fake()->create('slip.pdf', 10, 'application/pdf'),
        ])->assertForbidden();

        // Nor can finance waive one member's fee inside a group.
        $this->actingAs($this->finance)->post(route('finance.waivers.store', $registration), ['amount' => '250000', 'reason' => 'hardship'])->assertSessionHasErrors();
        $this->assertSame(RegistrationStatus::PendingPayment, $registration->fresh()->status);
    }

    public function test_one_payment_for_the_group_confirms_everyone_once_verified(): void
    {
        $this->createGroup([$this->member('juma@example.org'), $this->member('amina@example.org', 'Allied professional')]);
        $group = $this->group();
        $this->signedUp('juma@example.org');
        $this->signedUp('amina@example.org');

        $this->bankPayment($group)->assertRedirect(route('groups.show', $group));

        $payment = Payment::sole();
        $this->assertSame($group->id, $payment->group_registration_id);
        $this->assertNull($payment->registration_id);
        $this->assertSame('400000.00', $payment->amount);
        $this->assertSame(RegistrationStatus::PaymentSubmitted, $group->fresh()->status);
        $this->assertSame([RegistrationStatus::PaymentSubmitted], $group->members()->get()->pluck('status')->unique()->values()->all());
        Notification::assertSentTo($this->leader, PaymentReceived::class);

        // Fixed while finance verifies, and no second payment.
        $this->assertFalse($group->fresh()->canChangeMembers());
        $this->actingAs($this->leader)->post(route('groups.members.store', $group), $this->member('neema@example.org'))->assertSessionHasErrors('member');
        $this->bankPayment($group->fresh())->assertForbidden();

        $this->actingAs($this->finance)->get(route('finance.payments.index'))->assertSee('KCMC Physiotherapy (2 people)')->assertSee($group->reference);
        $this->actingAs($this->finance)->get(route('finance.payments.show', $payment))->assertOk()->assertSee('Verifying this payment confirms all 2 people');
        $this->actingAs($this->finance)->post(route('finance.payments.verify', $payment))->assertRedirect();

        $group->refresh();
        $this->assertSame(RegistrationStatus::Confirmed, $group->status);
        $this->assertNotNull($group->confirmed_at);
        $group->members()->with('user')->get()->each(function (Registration $member) {
            $this->assertSame(RegistrationStatus::Confirmed, $member->status);
            $this->assertNotNull($member->confirmed_at);
            Notification::assertSentTo($member->user, RegistrationConfirmed::class);
            $this->actingAs($member->user)->get(route('registration.badge'))->assertOk();
        });
        Notification::assertSentTo($this->leader, GroupConfirmed::class);
    }

    public function test_a_rejected_group_payment_reopens_the_group(): void
    {
        $this->createGroup([$this->member('juma@example.org')]);
        $group = $this->group();
        $this->bankPayment($group);

        $this->actingAs($this->finance)->post(route('finance.payments.reject', Payment::sole()), ['rejection_reason' => 'Not on the statement.'])->assertRedirect();

        $group->refresh();
        $this->assertSame(RegistrationStatus::PendingPayment, $group->status);
        $this->assertSame(RegistrationStatus::PendingPayment, $group->members()->sole()->status);
        Notification::assertSentTo($this->leader, PaymentRejected::class);
        $this->assertTrue($group->canChangeMembers());
        $this->assertTrue($group->canSubmitPayment());
    }

    public function test_a_group_pays_by_m_pesa(): void
    {
        config([
            'mpesa.enabled' => true, 'mpesa.environment' => 'sandbox', 'mpesa.api_key' => 'test-api-key',
            'mpesa.public_key' => preg_replace('/-----[^-]+-----|\s/', '', file_get_contents(base_path('tests/fixtures/mpesa/test-public.pem'))),
            'mpesa.service_provider_code' => '000000',
        ]);
        Http::fake([
            '*/getSession/' => Http::response(['output_ResponseCode' => 'INS-0', 'output_SessionID' => 'S1']),
            '*/c2bPayment/singleStage/' => Http::response(['output_ResponseCode' => 'INS-0', 'output_TransactionID' => 'GRP1234567'], 201),
        ]);
        $this->createGroup([$this->member('juma@example.org'), $this->member('amina@example.org')]);
        $group = $this->group();

        // Members never get an M-Pesa prompt of their own.
        $juma = $this->signedUp('juma@example.org');
        $this->actingAs($juma)->post(route('registration.mpesa.store'), ['mpesa_phone' => '0754123456'])->assertNotFound();

        $this->actingAs($this->leader)->post(route('groups.mpesa.store', $group), ['mpesa_phone' => '0754 123 456'])->assertRedirect(route('groups.show', $group));

        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), 'c2bPayment/singleStage/')
            && $r['input_Amount'] === '500000'
            && $r['input_TransactionReference'] === str_replace('-', '', $group->reference));
        $this->assertSame(PaymentStatus::Verified, Payment::sole()->status);
        $this->assertSame(RegistrationStatus::Confirmed, $group->fresh()->status);
        $this->assertSame(2, $group->members()->where('status', RegistrationStatus::Confirmed)->count());
        Notification::assertSentTo($juma, RegistrationConfirmed::class);
    }

    public function test_the_leader_adds_and_removes_people_before_paying(): void
    {
        $this->createGroup([$this->member('juma@example.org')]);
        $group = $this->group();

        $this->actingAs($this->leader)->post(route('groups.members.store', $group), $this->member('amina@example.org', 'Allied professional'))->assertSessionHasNoErrors();
        $this->assertSame(400000.0, $group->fresh()->amountDue());

        // Same currency only.
        $this->actingAs($this->leader)->post(route('groups.members.store', $group), $this->member('ana@example.org', 'International'))->assertSessionHasErrors('member');

        $juma = Registration::whereHas('user', fn ($q) => $q->where('email', 'juma@example.org'))->sole();
        $this->actingAs($this->leader)->delete(route('groups.members.destroy', [$group, $juma]))->assertSessionHasNoErrors();
        $this->assertModelMissing($juma);
        $this->assertSame(150000.0, $group->fresh()->amountDue());

        // Taken out, Juma can register by themselves.
        $this->assertNull(User::where('email', 'juma@example.org')->sole()->registrationFor($this->edition));
    }

    public function test_only_the_leader_sees_and_pays_for_the_group(): void
    {
        $this->createGroup([$this->member('juma@example.org')]);
        $group = $this->group();
        $juma = $this->signedUp('juma@example.org');

        $this->actingAs($juma)->get(route('groups.show', $group))->assertNotFound();
        $this->bankPayment($group, as: $juma)->assertNotFound();
        $this->assertSame(0, Payment::count());
    }

    public function test_the_leader_is_reminded_and_members_are_not(): void
    {
        $this->createGroup([$this->member('juma@example.org')]);
        $juma = User::where('email', 'juma@example.org')->sole();

        $this->travel(8)->days();
        app(ReminderService::class)->sendDue();

        Notification::assertSentTo($this->leader, GroupPaymentReminder::class);
        Notification::assertNotSentTo($juma, PaymentReminder::class);
        app(ReminderService::class)->sendDue();
        Notification::assertSentToTimes($this->leader, GroupPaymentReminder::class, 1);
    }
}
