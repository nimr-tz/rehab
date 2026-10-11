<?php

namespace Tests\Feature;

use App\Enums\AbstractStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\Registration;
use App\Models\ReviewAssignment;
use App\Models\User;
use App\Notifications\PaymentReminder;
use App\Notifications\ReviewReminder;
use App\Notifications\RevisionReminder;
use App\Services\ReminderService;
use App\Support\BackgroundWork;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Reminders and other work the portal does on its own, without a cron. */
class BackgroundWorkTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Cache::flush();
        $this->travelTo('2027-06-01 09:00');

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'registration_open' => true, 'is_current' => true,
        ]);
        $this->edition->topics()->create(['name' => 'Home-Based Rehabilitation', 'code' => 'HBR', 'sort' => 0]);
        $this->edition->categories()->create(['name' => 'Professional', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        app(Summit::class)->refresh();
    }

    private function abstract(AbstractStatus $status, array $extra = []): AbstractSubmission
    {
        return AbstractSubmission::create($extra + [
            'edition_id' => $this->edition->id,
            'user_id' => User::factory()->create()->assignRole(Role::Participant->value)->id,
            'topic_id' => $this->edition->topics->first()->id,
            'preferred_type' => 'oral', 'title' => 'Peer-led exercise after stroke',
            'background' => 'B', 'methods' => 'M', 'results' => 'R', 'conclusions' => 'C',
            'status' => $status, 'submitted_at' => now(),
        ]);
    }

    private function assignment(string $dueOn, ?string $completedAt = null): ReviewAssignment
    {
        return $this->abstract(AbstractStatus::UnderReview)->reviews()->create([
            'reviewer_id' => User::factory()->create()->assignRole(Role::Reviewer->value)->id,
            'round' => 1, 'due_on' => $dueOn, 'completed_at' => $completedAt,
        ]);
    }

    private function registration(): Registration
    {
        $category = $this->edition->categories->first();

        return Registration::create([
            'edition_id' => $this->edition->id,
            'user_id' => User::factory()->create()->assignRole(Role::Participant->value)->id,
            'registration_category_id' => $category->id,
            'reference' => 'RH27-'.Str::random(6),
            'currency' => 'TZS', 'amount' => 250000,
            'status' => RegistrationStatus::PendingPayment,
            'qr_token' => Str::random(32),
        ]);
    }

    private function sendDue(): int
    {
        return app(ReminderService::class)->sendDue();
    }

    public function test_reviewers_are_reminded_before_the_due_date_and_once_more_when_overdue(): void
    {
        $soon = $this->assignment('2027-06-03');
        $later = $this->assignment('2027-06-20');
        $done = $this->assignment('2027-06-02', completedAt: '2027-05-30 10:00');

        $this->assertSame(1, $this->sendDue());
        Notification::assertSentTo($soon->reviewer, ReviewReminder::class, fn ($n) => ! $n->overdue);
        Notification::assertNotSentTo([$later->reviewer, $done->reviewer], ReviewReminder::class);

        // Not again the next day.
        $this->travel(1)->days();
        $this->assertSame(0, $this->sendDue());

        // Overdue: one more.
        $this->travel(2)->days();
        $this->assertSame(1, $this->sendDue());
        Notification::assertSentTo($soon->reviewer, ReviewReminder::class, fn ($n) => $n->overdue);
        $this->assertSame(0, $this->sendDue());
    }

    public function test_a_review_whose_round_has_closed_is_not_chased(): void
    {
        $assignment = $this->assignment('2027-06-02');
        $assignment->abstract->update(['status' => AbstractStatus::Rejected]);

        $this->assertSame(0, $this->sendDue());
    }

    public function test_authors_are_reminded_before_their_revision_is_due(): void
    {
        $due = $this->abstract(AbstractStatus::RevisionRequested, ['revision_due_on' => '2027-06-04']);
        $notYet = $this->abstract(AbstractStatus::RevisionRequested, ['revision_due_on' => '2027-06-30']);

        $this->assertSame(1, $this->sendDue());
        Notification::assertSentTo($due->submitter, RevisionReminder::class);
        Notification::assertNotSentTo($notYet->submitter, RevisionReminder::class);
        $this->assertSame(0, $this->sendDue());
    }

    public function test_unpaid_registrations_are_reminded_after_a_week_and_before_the_summit(): void
    {
        $registration = $this->registration();
        $submitted = $this->registration();
        $submitted->payments()->create([
            'method' => PaymentMethod::BankTransfer, 'currency' => 'TZS', 'amount' => 250000,
            'transaction_reference' => 'BANK12345', 'payer_name' => 'Payer', 'paid_on' => today(), 'status' => PaymentStatus::Submitted,
        ]);
        $submitted->update(['status' => RegistrationStatus::PaymentSubmitted]);

        $this->assertSame(0, $this->sendDue());

        $this->travel(8)->days();
        $this->assertSame(1, $this->sendDue());
        Notification::assertSentTo($registration->user, PaymentReminder::class, fn ($n) => ! $n->beforeSummit);
        Notification::assertNotSentTo($submitted->user, PaymentReminder::class);
        $this->assertSame(0, $this->sendDue());

        // Two weeks before the summit, once more.
        $this->travelTo('2027-09-02 09:00');
        $this->assertSame(1, $this->sendDue());
        Notification::assertSentTo($registration->user, PaymentReminder::class, fn ($n) => $n->beforeSummit);
        $this->assertSame(0, $this->sendDue());
    }

    public function test_paid_registrations_are_not_reminded(): void
    {
        $this->registration()->update(['status' => RegistrationStatus::Confirmed]);
        $this->travel(8)->days();

        $this->assertSame(0, $this->sendDue());
    }

    public function test_each_task_runs_at_most_once_per_interval(): void
    {
        $this->registration();
        $this->travel(8)->days();

        $first = app(BackgroundWork::class)->run();
        $this->assertSame(1, $first['reminders']);
        $this->assertSame(0, $first['mpesa']);

        $this->assertSame([], app(BackgroundWork::class)->run());

        $this->travel(61)->minutes();
        $this->assertSame(['mpesa' => 0, 'reminders' => 0], app(BackgroundWork::class)->run());
    }

    public function test_the_work_runs_after_a_page_is_sent(): void
    {
        $this->registration();
        $this->travel(8)->days();
        config(['background.after_requests' => true]);

        $this->get(route('home'))->assertOk();
        Notification::assertSentTimes(PaymentReminder::class, 1);

        // Throttled: the next page does not run it again.
        $this->get(route('home'))->assertOk();
        $this->assertSame(1, \DB::table('sent_reminders')->count());
    }

    public function test_the_command_runs_the_same_work(): void
    {
        $this->registration();
        $this->travel(8)->days();

        $this->artisan('portal:background')->expectsOutput('reminders: 1')->assertSuccessful();
    }
}
