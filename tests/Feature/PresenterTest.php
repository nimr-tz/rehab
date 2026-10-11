<?php

namespace Tests\Feature;

use App\Enums\AbstractStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\User;
use App\Notifications\PresenterRegistrationReminder;
use App\Services\PresenterService;
use App\Services\ReminderService;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/** The committee sees whether each accepted abstract's presenter has registered and paid. */
class PresenterTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private User $committee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->travelTo('2027-06-01 10:00');

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'is_current' => true,
        ]);
        $this->edition->topics()->create(['name' => 'Stroke', 'code' => 'STR', 'sort' => 0]);
        $this->edition->categories()->create(['name' => 'Professional', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        app(Summit::class)->refresh();

        $this->committee = User::factory()->create()->assignRole(Role::ScientificAdmin->value);
    }

    /** An accepted abstract; the presenter is the submitter unless another presenter email is given. */
    private function accepted(?string $presenterEmail = null, ?RegistrationStatus $registered = null): AbstractSubmission
    {
        static $n = 0;
        $n++;
        $submitter = User::factory()->create()->assignRole(Role::Participant->value);

        $abstract = AbstractSubmission::create([
            'edition_id' => $this->edition->id, 'user_id' => $submitter->id, 'topic_id' => $this->edition->topics->first()->id,
            'preferred_type' => 'oral', 'title' => "Abstract {$n}", 'background' => 'B', 'methods' => 'M', 'results' => 'R', 'conclusions' => 'C',
            'status' => AbstractStatus::Accepted, 'decision_type' => 'oral', 'code' => sprintf('OR-STR-%02d', $n), 'decided_at' => now(),
        ]);
        $abstract->authors()->create(['name' => $submitter->name, 'email' => $submitter->email, 'affiliation' => 'MNH', 'is_presenter' => ! $presenterEmail, 'position' => 1]);
        if ($presenterEmail) {
            $abstract->authors()->create(['name' => 'Juma Hassan', 'email' => $presenterEmail, 'affiliation' => 'KCMC', 'is_presenter' => true, 'position' => 2]);
        }

        if ($registered) {
            $this->edition->registrations()->create([
                'user_id' => $submitter->id, 'registration_category_id' => $this->edition->categories->first()->id,
                'reference' => 'RH27-'.Str::random(6), 'currency' => 'TZS', 'amount' => 250000, 'status' => $registered, 'qr_token' => Str::random(32),
            ]);
        }

        return $abstract;
    }

    public function test_the_committee_sees_who_has_registered_and_paid(): void
    {
        $confirmed = $this->accepted(registered: RegistrationStatus::Confirmed);
        $unpaid = $this->accepted(registered: RegistrationStatus::PendingPayment);
        $verifying = $this->accepted(registered: RegistrationStatus::PaymentSubmitted);
        $absent = $this->accepted();
        $coAuthor = $this->accepted(presenterEmail: 'juma@example.org', registered: RegistrationStatus::Confirmed);

        $states = app(PresenterService::class)->rows($this->edition)->mapWithKeys(fn ($row) => [$row['abstract']->code => $row['state']]);

        $this->assertSame([
            $confirmed->code => PresenterService::CONFIRMED,
            $unpaid->code => PresenterService::UNPAID,
            $verifying->code => PresenterService::VERIFYING,
            $absent->code => PresenterService::NOT_REGISTERED,
            // The submitter registered, but the co-author presents and has not.
            $coAuthor->code => PresenterService::NOT_REGISTERED,
        ], $states->all());

        $this->actingAs($this->committee)->get(route('scientific.presenters.index'))->assertOk()->assertSee('Juma Hassan')->assertSee('juma@example.org');
        $this->actingAs($this->committee)->get(route('scientific.presenters.index', ['state' => PresenterService::NOT_REGISTERED]))
            ->assertSee($absent->title)->assertDontSee($confirmed->title);
    }

    public function test_presenters_are_reminded_even_without_an_account(): void
    {
        $member = $this->accepted(registered: RegistrationStatus::PendingPayment);
        $guest = $this->accepted(presenterEmail: 'juma@example.org');
        $done = $this->accepted(registered: RegistrationStatus::Confirmed);

        $this->actingAs($this->committee)->post(route('scientific.presenters.remind-all'))->assertSessionHas('status', '2 presenters reminded.');

        Notification::assertSentTo($member->submitter, PresenterRegistrationReminder::class);
        Notification::assertSentOnDemand(PresenterRegistrationReminder::class, fn ($n, $channels, AnonymousNotifiable $to) => $to->routes['mail'] === 'juma@example.org');
        Notification::assertNotSentTo($done->submitter, PresenterRegistrationReminder::class);
        $this->assertNotNull($member->fresh()->presenter_reminded_at);
        $this->assertNull($done->fresh()->presenter_reminded_at);

        $this->actingAs($this->committee)->post(route('scientific.presenters.remind', $done))->assertSessionHasErrors('presenters');
    }

    public function test_unconfirmed_presenters_are_reminded_automatically_two_weeks_after_acceptance(): void
    {
        $abstract = $this->accepted(registered: RegistrationStatus::PendingPayment);

        $this->assertSame(0, app(ReminderService::class)->sendDue());

        $this->travel(15)->days();
        // The unpaid-fee reminder also goes out to the same person; the presenter one once.
        app(ReminderService::class)->sendDue();
        Notification::assertSentToTimes($abstract->submitter, PresenterRegistrationReminder::class, 1);
        app(ReminderService::class)->sendDue();
        Notification::assertSentToTimes($abstract->submitter, PresenterRegistrationReminder::class, 1);
    }

    public function test_the_list_exports_safely(): void
    {
        $abstract = $this->accepted();
        $abstract->update(['title' => '=HYPERLINK("http://evil")']);

        $csv = $this->actingAs($this->committee)->get(route('scientific.presenters.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('Not registered', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_only_the_committee(): void
    {
        $this->actingAs(User::factory()->create()->assignRole(Role::Participant->value))->get(route('scientific.presenters.index'))->assertForbidden();
    }
}
