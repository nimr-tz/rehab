<?php

namespace Tests\Feature;

use App\Enums\AbstractStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\AbstractDecided;
use App\Notifications\PaymentRejected;
use App\Notifications\RegistrationConfirmed;
use App\Notifications\ReviewAssigned;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SummitFlowTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

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
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17',
            'registration_open' => true, 'abstracts_open' => true, 'abstract_deadline' => now()->addMonths(6), 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional (Tanzania)', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        $this->edition->categories()->create(['name' => 'Professional (International)', 'currency' => 'USD', 'amount' => 250, 'sort' => 1]);
        $this->edition->topics()->create(['name' => 'Home-Based Rehabilitation', 'code' => 'HBR', 'sort' => 0]);
        app(Summit::class)->refresh();
    }

    private function user(Role ...$roles): User
    {
        return User::factory()->create()->assignRole(array_map(fn (Role $role) => $role->value, $roles ?: [Role::Participant]));
    }

    private function register(User $user): Registration
    {
        $this->actingAs($user)->post(route('registration.store'), [
            'category' => $this->edition->categories->first()->id,
            'institution' => 'Muhimbili National Hospital',
            'profession' => 'Physiotherapist',
            'needs_invitation_letter' => '1',
            'passport_number' => 'AB1234567',
            'confirm' => '1',
        ])->assertRedirect(route('registration.show'));

        return $user->registrationFor($this->edition);
    }

    private function pay(User $user): Payment
    {
        $this->actingAs($user)->post(route('registration.payments.store'), [
            'method' => 'mobile_money', 'provider' => 'mpesa', 'transaction_reference' => 'QWE123RTY9',
            'payer_name' => $user->name, 'paid_on' => today()->toDateString(),
            'proof' => UploadedFile::fake()->create('slip.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('registration.show'));

        return Payment::latest('id')->first();
    }

    public function test_a_participant_registers_with_a_reference_and_the_fee_snapshot(): void
    {
        $registration = $this->register($user = $this->user());

        $this->assertMatchesRegularExpression('/^RH27-\d{6}$/', $registration->reference);
        $this->assertSame(RegistrationStatus::PendingPayment, $registration->status);
        $this->assertSame('250000.00', $registration->amount);
        $this->assertSame('Muhimbili National Hospital', $user->fresh()->institution);

        // Once only.
        $this->actingAs($user)->post(route('registration.store'), ['category' => $this->edition->categories->first()->id])->assertStatus(409);
    }

    public function test_payment_verification_confirms_the_registration_and_unlocks_documents(): void
    {
        $registration = $this->register($user = $this->user());
        $payment = $this->pay($user);

        $this->assertSame(RegistrationStatus::PaymentSubmitted, $registration->fresh()->status);
        Storage::disk('local')->assertExists($payment->proof_path);
        $this->actingAs($user)->get(route('registration.badge'))->assertForbidden();

        $finance = $this->user(Role::FinanceOfficer);
        $this->actingAs($finance)->get(route('finance.payments.proof', $payment))->assertOk();
        $this->actingAs($finance)->post(route('finance.payments.verify', $payment))->assertRedirect();

        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);
        $this->assertSame($finance->id, $payment->fresh()->reviewed_by);
        $this->assertTrue($registration->fresh()->isConfirmed());
        Notification::assertSentTo($user, RegistrationConfirmed::class);

        $badge = $this->actingAs($user)->get(route('registration.badge'));
        $badge->assertOk();
        $this->assertStringStartsWith('%PDF', $badge->getContent());
        $this->actingAs($user)->get(route('registration.letter'))->assertOk();

        // A payment is reviewed once.
        $this->expectException(\InvalidArgumentException::class);
        $this->withoutExceptionHandling()->actingAs($finance)->post(route('finance.payments.verify', $payment));
    }

    public function test_a_rejected_payment_lets_the_participant_pay_again(): void
    {
        $registration = $this->register($user = $this->user());
        $payment = $this->pay($user);

        $this->actingAs($user)->post(route('registration.payments.store'), [])->assertForbidden(); // one pending at a time

        $this->actingAs($this->user(Role::FinanceOfficer))
            ->post(route('finance.payments.reject', $payment), ['rejection_reason' => 'Reference not on statement'])
            ->assertRedirect();

        $this->assertSame(PaymentStatus::Rejected, $payment->fresh()->status);
        $this->assertSame(RegistrationStatus::PendingPayment, $registration->fresh()->status);
        Notification::assertSentTo($user, PaymentRejected::class);

        $this->pay($user);
        $this->assertSame(2, $registration->payments()->count());
    }

    public function test_mobile_money_is_refused_for_usd_fees(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post(route('registration.store'), [
            'category' => $this->edition->categories->last()->id, 'institution' => 'X', 'profession' => 'Y', 'confirm' => '1',
        ]);

        $this->actingAs($user)->post(route('registration.payments.store'), [
            'method' => 'mobile_money', 'provider' => 'mpesa', 'transaction_reference' => 'QWE123RTY9',
            'payer_name' => 'X', 'paid_on' => today()->toDateString(),
            'proof' => UploadedFile::fake()->create('slip.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('method');
    }

    /** @return array<string, string> */
    private function abstractForm(array $overrides = []): array
    {
        return array_merge([
            'action' => 'submit',
            'topic_id' => (string) $this->edition->topics->first()->id,
            'preferred_type' => 'oral',
            'title' => 'Peer-led exercise after stroke',
            'background' => 'Stroke survivors rarely access rehabilitation.',
            'methods' => 'Pilot of weekly groups with forty participants.',
            'results' => 'Walking speed improved.',
            'conclusions' => 'Feasible in communities.',
            'authors' => [['name' => 'Amina Mussa', 'email' => 'amina@example.com', 'affiliation' => 'MNH'], ['name' => 'Co Author', 'email' => 'co@example.com', 'affiliation' => 'KCMC']],
            'presenter' => '1',
        ], $overrides);
    }

    public function test_abstracts_respect_the_word_limit_and_drafts_can_be_incomplete(): void
    {
        $author = $this->user();

        $this->actingAs($author)->post(route('abstracts.store'), $this->abstractForm(['results' => str_repeat('word ', 301)]))
            ->assertSessionHasErrors('background');

        $this->actingAs($author)->post(route('abstracts.store'), $this->abstractForm(['action' => 'draft', 'methods' => '', 'results' => '']))
            ->assertRedirect();
        $draft = AbstractSubmission::sole();
        $this->assertSame(AbstractStatus::Draft, $draft->status);
        $this->assertSame('Co Author', $draft->presenter()->name);

        // Someone else cannot see it.
        $this->actingAs($this->user())->get(route('abstracts.show', $draft))->assertNotFound();
    }

    public function test_the_review_is_double_blind_and_the_decision_assigns_a_code(): void
    {
        $author = $this->user();
        $this->actingAs($author)->post(route('abstracts.store'), $this->abstractForm())->assertRedirect();
        $abstract = AbstractSubmission::sole();

        $committee = $this->user(Role::ScientificAdmin);
        $reviewer = $this->user(Role::Reviewer);
        $coAuthorReviewer = User::factory()->create(['email' => 'co@example.com'])->assignRole(Role::Reviewer->value);

        // A co-author cannot review their own abstract.
        $this->expectsAssignmentRefused($committee, $abstract, $coAuthorReviewer);

        $this->actingAs($committee)->post(route('scientific.abstracts.assign', $abstract), ['reviewer_id' => $reviewer->id])->assertRedirect();
        $this->assertSame(AbstractStatus::UnderReview, $abstract->fresh()->status);
        Notification::assertSentTo($reviewer, ReviewAssigned::class);

        $assignment = $abstract->reviews()->sole();
        $this->actingAs($reviewer)->get(route('reviews.edit', $assignment))
            ->assertOk()->assertSee($abstract->title)->assertDontSee('Amina Mussa')->assertDontSee('amina@example.com');
        $this->actingAs($this->user(Role::Reviewer))->get(route('reviews.edit', $assignment))->assertNotFound();

        $this->actingAs($reviewer)->put(route('reviews.update', $assignment), [
            'score_relevance' => 5, 'score_originality' => 4, 'score_methods' => 4, 'score_clarity' => 5,
            'recommendation' => 'accept_oral', 'comments_for_author' => 'Clear and practical; please add confidence intervals.',
        ])->assertRedirect(route('reviews.index'));
        $this->assertSame(18, $assignment->fresh()->totalScore());

        $this->actingAs($committee)->post(route('scientific.abstracts.decide', $abstract), ['decision' => 'oral'])->assertRedirect();
        $abstract->refresh();
        $this->assertSame(AbstractStatus::Accepted, $abstract->status);
        $this->assertSame('OR-HBR-01', $abstract->code);
        Notification::assertSentTo($author, AbstractDecided::class);

        $this->actingAs($author)->get(route('abstracts.show', $abstract))->assertSee('OR-HBR-01')->assertSee('confidence intervals');
    }

    private function expectsAssignmentRefused(User $committee, AbstractSubmission $abstract, User $reviewer): void
    {
        try {
            $this->withoutExceptionHandling()->actingAs($committee)->post(route('scientific.abstracts.assign', $abstract), ['reviewer_id' => $reviewer->id]);
            $this->fail('A co-author was allowed to review.');
        } catch (\InvalidArgumentException) {
            $this->assertSame(0, $abstract->reviews()->count());
        } finally {
            $this->withExceptionHandling();
        }
    }

    public function test_each_area_is_limited_to_its_roles(): void
    {
        $participant = $this->user();
        $reviewer = $this->user(Role::Reviewer);
        $finance = $this->user(Role::FinanceOfficer);

        foreach (['admin.overview', 'admin.settings.edit', 'finance.payments.index', 'scientific.abstracts.index', 'desk.index', 'reviews.index'] as $route) {
            $this->actingAs($participant)->get(route($route))->assertForbidden();
        }
        $this->actingAs($reviewer)->get(route('finance.payments.index'))->assertForbidden();
        $this->actingAs($reviewer)->get(route('registration.show'))->assertForbidden();
        $this->actingAs($finance)->get(route('scientific.abstracts.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('finance.payments.index'))->assertOk();
    }

    public function test_an_admin_can_change_the_summit_settings(): void
    {
        $admin = $this->user(Role::Admin);
        $category = $this->edition->categories->first();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'venue' => 'Arusha International Conference Centre', 'city' => 'Arusha', 'country' => 'Tanzania',
            'start_date' => '2027-10-06', 'end_date' => '2027-10-08',
            'registration_open' => '1', 'abstracts_open' => '0',
            'categories' => [['id' => $category->id, 'name' => $category->name, 'currency' => 'TZS', 'amount' => '300000', 'is_student' => '0']],
            'topics' => [['id' => $this->edition->topics->first()->id, 'name' => 'Home-Based Rehabilitation', 'code' => 'hbr'], ['id' => '', 'name' => 'New topic', 'code' => 'new']],
        ])->assertRedirect(route('admin.settings.edit'));

        $this->edition->refresh();
        $this->assertSame('Arusha', $this->edition->city);
        $this->assertFalse($this->edition->abstracts_open);
        $this->assertSame(1, $this->edition->categories()->count()); // the unused USD category was removed
        $this->assertSame('300000.00', $category->fresh()->amount);
        $this->assertSame(['HBR', 'NEW'], $this->edition->topics()->pluck('code')->all());

        $this->get(route('home'))->assertSee('6–8 October 2027')->assertSee('Arusha International Conference Centre');
    }
}
