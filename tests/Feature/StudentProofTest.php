<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Enums\StudentProofStatus;
use App\Models\Edition;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\PaymentReminder;
use App\Notifications\StudentProofReviewed;
use App\Services\MpesaPaymentService;
use App\Services\ReminderService;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Student rates need proof of enrolment, approved before anything is paid. Payments are high-risk, so every rule is tested. */
class StudentProofTest extends TestCase
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

        config([
            'mpesa.enabled' => true, 'mpesa.environment' => 'openapi', 'mpesa.api_key' => 'key',
        ]);

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'registration_open' => true, 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        $this->edition->categories()->create(['name' => 'Student', 'currency' => 'TZS', 'amount' => 100000, 'is_student' => true, 'sort' => 1]);
        app(Summit::class)->refresh();

        $this->finance = User::factory()->create()->assignRole(Role::FinanceOfficer->value);
    }

    private function category(string $name): int
    {
        return $this->edition->categories->firstWhere('name', $name)->id;
    }

    private function proof(): UploadedFile
    {
        return UploadedFile::fake()->create('student-id.pdf', 120, 'application/pdf');
    }

    private function register(array $overrides = [])
    {
        $user = User::factory()->create(['first_name' => 'Rehema'])->assignRole(Role::Participant->value);

        return [$user, $this->actingAs($user)->post(route('registration.store'), $overrides + [
            'category' => $this->category('Student'), 'student_proof' => $this->proof(),
            'institution' => 'KCMC University', 'profession' => 'Physiotherapy student', 'confirm' => '1',
        ])];
    }

    private function student(): Registration
    {
        [$user, $response] = $this->register();
        $response->assertRedirect(route('registration.show'));

        return $user->registrationFor($this->edition);
    }

    private function bankPayment(Registration $registration)
    {
        return $this->actingAs($registration->user)->post(route('registration.payments.store'), [
            'method' => 'bank_transfer', 'transaction_reference' => 'FT27123456', 'payer_name' => 'Rehema',
            'paid_on' => today()->toDateString(), 'proof' => UploadedFile::fake()->create('slip.pdf', 10, 'application/pdf'),
        ]);
    }

    public function test_the_student_rate_needs_a_proof(): void
    {
        [$user, $response] = $this->register(['student_proof' => null]);

        $response->assertSessionHasErrors('student_proof');
        $this->assertNull($user->registrationFor($this->edition));
    }

    public function test_nothing_can_be_paid_until_finance_approves_the_proof(): void
    {
        $registration = $this->student();

        $this->assertSame(StudentProofStatus::Pending, $registration->student_status);
        Storage::disk('local')->assertExists($registration->student_proof_path);
        $this->actingAs($registration->user)->get(route('registration.show'))->assertSee('We are checking your student proof');

        $this->assertFalse($registration->canSubmitPayment());
        $this->assertFalse(MpesaPaymentService::availableFor($registration));
        $this->bankPayment($registration)->assertForbidden();
        $this->actingAs($registration->user)->post(route('registration.mpesa.store'), ['mpesa_phone' => '0754123456'])->assertNotFound();

        // Not chased for a fee they cannot pay yet.
        $this->travel(8)->days();
        $this->assertSame(0, app(ReminderService::class)->sendDue());
        Notification::assertNotSentTo($registration->user, PaymentReminder::class);
    }

    public function test_finance_approves_and_the_student_can_pay(): void
    {
        $registration = $this->student();

        $this->actingAs($this->finance)->get(route('finance.students.index'))->assertOk()->assertSee($registration->reference);
        $this->actingAs($this->finance)->get(route('finance.students.show', $registration))->assertOk()->assertSee('Approve the student rate');
        $this->actingAs($this->finance)->get(route('finance.students.proof', $registration))->assertOk();

        $this->actingAs($this->finance)->post(route('finance.students.approve', $registration))->assertRedirect(route('finance.students.index'));

        $registration->refresh();
        $this->assertSame(StudentProofStatus::Approved, $registration->student_status);
        $this->assertSame($this->finance->id, $registration->student_reviewed_by);
        Notification::assertSentTo($registration->user, StudentProofReviewed::class);
        $this->assertTrue($registration->canSubmitPayment());
        $this->assertTrue(MpesaPaymentService::availableFor($registration));
        $this->bankPayment($registration)->assertRedirect(route('registration.show'));
        $this->assertSame('100000.00', $registration->payments()->sole()->amount);

        // Reviewed once only.
        $this->actingAs($this->finance)->post(route('finance.students.reject', $registration), ['student_rejection_reason' => 'Changed our mind'])->assertSessionHasErrors();
        $this->assertSame(StudentProofStatus::Approved, $registration->fresh()->student_status);
    }

    public function test_a_rejected_student_uploads_a_new_proof(): void
    {
        $registration = $this->student();

        $this->actingAs($this->finance)->post(route('finance.students.reject', $registration), ['student_rejection_reason' => ''])->assertSessionHasErrors('student_rejection_reason');
        $this->actingAs($this->finance)->post(route('finance.students.reject', $registration), ['student_rejection_reason' => 'The ID expired in 2025.'])->assertRedirect();

        $registration->refresh();
        $this->assertSame(StudentProofStatus::Rejected, $registration->student_status);
        Notification::assertSentTo($registration->user, StudentProofReviewed::class);
        $this->actingAs($registration->user)->get(route('registration.show'))->assertSee('The ID expired in 2025.')->assertSee('Send the new document');
        $this->bankPayment($registration)->assertForbidden();

        $old = $registration->student_proof_path;
        $this->actingAs($registration->user)->post(route('registration.student-proof'), ['student_proof' => $this->proof()])->assertRedirect(route('registration.show'));

        $registration->refresh();
        $this->assertSame(StudentProofStatus::Pending, $registration->student_status);
        $this->assertNotSame($old, $registration->student_proof_path);
        $this->assertNull($registration->student_rejection_reason);

        // Only after a rejection.
        $this->actingAs($registration->user)->post(route('registration.student-proof'), ['student_proof' => $this->proof()])->assertSessionHasErrors('student_proof');
    }

    public function test_a_rejected_student_can_change_to_the_full_fee(): void
    {
        $registration = $this->student();
        $this->actingAs($this->finance)->post(route('finance.students.reject', $registration), ['student_rejection_reason' => 'Not a student ID.']);

        $this->actingAs($registration->user)->put(route('registration.category'), ['category' => $this->category('Professional')])->assertRedirect(route('registration.show'));

        $registration->refresh();
        $this->assertSame('Professional', $registration->category->name);
        $this->assertSame('250000.00', $registration->amount);
        $this->assertNull($registration->student_status);
        $this->assertTrue($registration->canSubmitPayment());
    }

    public function test_moving_to_the_student_rate_needs_a_proof(): void
    {
        [$user] = $this->register(['category' => $this->category('Professional'), 'student_proof' => null]);
        $registration = $user->registrationFor($this->edition);
        $this->assertNull($registration->student_status);

        $this->actingAs($user)->put(route('registration.category'), ['category' => $this->category('Student')])->assertSessionHasErrors('student_proof');
        $this->assertSame('250000.00', $registration->fresh()->amount);

        $this->actingAs($user)->put(route('registration.category'), ['category' => $this->category('Student'), 'student_proof' => $this->proof()])->assertRedirect();
        $registration->refresh();
        $this->assertSame('100000.00', $registration->amount);
        $this->assertSame(StudentProofStatus::Pending, $registration->student_status);
    }

    public function test_the_category_is_fixed_once_a_payment_is_sent(): void
    {
        $registration = $this->student();
        $this->actingAs($this->finance)->post(route('finance.students.approve', $registration));
        $this->bankPayment($registration)->assertRedirect();

        $this->actingAs($registration->user)->put(route('registration.category'), ['category' => $this->category('Professional')])->assertSessionHasErrors('category');

        $this->assertSame('100000.00', $registration->fresh()->amount);
    }

    public function test_the_desk_sees_the_student_id_in_person(): void
    {
        $desk = User::factory()->create()->assignRole(Role::RegistrationOfficer->value);
        $walkIn = [
            'first_name' => 'Juma', 'last_name' => 'Hassan', 'email' => 'juma@example.org', 'phone' => '+255 754 123 456',
            'country' => 'TZ', 'institution' => 'KCMC', 'profession' => 'Student', 'category' => $this->category('Student'),
        ];

        $this->actingAs($desk)->post(route('desk.register.store'), $walkIn)->assertSessionHasErrors('student_id_seen');
        $this->assertSame(0, Registration::count());

        $this->actingAs($desk)->post(route('desk.register.store'), $walkIn + ['student_id_seen' => '1'])->assertRedirect();
        $walkInRegistration = Registration::sole();
        $this->assertSame(StudentProofStatus::Approved, $walkInRegistration->student_status);
        $this->assertSame($desk->id, $walkInRegistration->student_reviewed_by);
        $this->assertTrue($walkInRegistration->canSubmitPayment());

        // Someone registered online, waiting for finance, shows their ID at the desk.
        $online = $this->student();
        $this->actingAs($desk)->get(route('desk.show', $online))->assertSee('I have seen a valid student ID');
        $this->actingAs($desk)->post(route('desk.student-id', $online))->assertRedirect();
        $this->assertSame(StudentProofStatus::Approved, $online->fresh()->student_status);
    }

    public function test_only_finance_opens_student_proofs(): void
    {
        $registration = $this->student();

        $this->actingAs($registration->user)->get(route('finance.students.proof', $registration))->assertForbidden();
        $this->actingAs($registration->user)->post(route('finance.students.approve', $registration))->assertForbidden();
        $this->assertSame(StudentProofStatus::Pending, $registration->fresh()->student_status);
        $this->assertSame(RegistrationStatus::PendingPayment, $registration->fresh()->status);
    }
}
