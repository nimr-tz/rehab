<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\Edition;
use App\Models\FeedbackResponse;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\FeedbackInvitation;
use App\Services\ReminderService;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/** The feedback survey: once per person who attended, after the summit, anonymous for the organisers. */
class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->travelTo('2027-09-17 16:00');

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        app(Summit::class)->refresh();
    }

    private function registration(bool $attended = true): Registration
    {
        $user = User::factory()->create(['first_name' => 'Neema', 'last_name' => 'Mushi'])->assignRole(Role::Participant->value);

        return $this->edition->registrations()->create([
            'user_id' => $user->id, 'registration_category_id' => $this->edition->categories->first()->id,
            'reference' => 'RH27-'.Str::random(6), 'currency' => 'TZS', 'amount' => 250000,
            'status' => RegistrationStatus::Confirmed, 'confirmed_at' => now(), 'qr_token' => Str::random(32),
            'checked_in_at' => $attended ? '2027-09-15 08:30' : null,
        ]);
    }

    private function answers(array $overrides = []): array
    {
        return array_replace_recursive([
            'ratings' => ['overall' => 5, 'programme' => 4, 'speakers' => 5, 'organisation' => 3, 'venue' => 2, 'portal' => 4],
            'recommend' => 9,
            'next_year' => 'yes',
            'comments' => ['best' => 'The stroke session.', 'improve' => 'Longer lunch breaks.', 'topics' => ''],
        ], $overrides);
    }

    public function test_someone_who_attended_answers_once(): void
    {
        $registration = $this->registration();

        $this->actingAs($registration->user)->get(route('feedback.create'))->assertOk()->assertSee('The summit overall');
        $this->actingAs($registration->user)->get(route('registration.show'))->assertSee('Give your feedback');
        $this->actingAs($registration->user)->post(route('feedback.store'), $this->answers())->assertSessionHas('status');

        $response = FeedbackResponse::sole();
        $this->assertSame(5, $response->ratings['overall']);
        $this->assertSame(9, $response->recommend);
        $this->assertSame(['best' => 'The stroke session.', 'improve' => 'Longer lunch breaks.'], $response->comments);

        $this->actingAs($registration->user)->get(route('feedback.create'))->assertSee('Thank you for your feedback');
        $this->actingAs($registration->user)->post(route('feedback.store'), $this->answers())->assertSessionHasErrors('recommend');
        $this->assertSame(1, FeedbackResponse::count());
    }

    public function test_only_people_who_attended_and_only_while_open(): void
    {
        $absent = $this->registration(attended: false);
        $this->actingAs($absent->user)->post(route('feedback.store'), $this->answers())->assertSessionHasErrors('recommend');

        $attended = $this->registration();
        $this->travelTo('2027-09-10 10:00');
        $this->actingAs($attended->user)->get(route('feedback.create'))->assertSee('It opens on the summit');
        $this->actingAs($attended->user)->post(route('feedback.store'), $this->answers())->assertSessionHasErrors('recommend');

        $this->travelTo('2027-10-18 10:00');
        $this->actingAs($attended->user)->post(route('feedback.store'), $this->answers())->assertSessionHasErrors('recommend');

        $this->assertSame(0, FeedbackResponse::count());
    }

    public function test_every_question_is_checked(): void
    {
        $registration = $this->registration();

        $this->actingAs($registration->user)->post(route('feedback.store'), $this->answers(['ratings' => ['overall' => 7], 'recommend' => 11, 'next_year' => 'often']))
            ->assertSessionHasErrors(['ratings.overall', 'recommend', 'next_year']);
    }

    public function test_those_who_attended_are_invited_the_day_after(): void
    {
        $attended = $this->registration();
        $absent = $this->registration(attended: false);

        $this->assertSame(0, app(ReminderService::class)->sendDue());

        $this->travelTo('2027-09-18 09:00');
        app(ReminderService::class)->sendDue();
        Notification::assertSentTo($attended->user, FeedbackInvitation::class);
        Notification::assertNotSentTo($absent->user, FeedbackInvitation::class);

        app(ReminderService::class)->sendDue();
        Notification::assertSentToTimes($attended->user, FeedbackInvitation::class, 1);
    }

    public function test_admins_see_results_without_names(): void
    {
        foreach ([9, 10, 5] as $i => $score) {
            $registration = $this->registration();
            $this->actingAs($registration->user)->post(route('feedback.store'), $this->answers(['recommend' => $score, 'next_year' => $i === 2 ? 'no' : 'yes']));
        }
        $admin = User::factory()->create()->assignRole(Role::Admin->value);

        $this->actingAs($admin)->get(route('admin.feedback.index'))->assertOk()
            ->assertSee('Net promoter score +33')
            ->assertSee('Longer lunch breaks.')
            ->assertDontSee('Neema Mushi');

        $csv = $this->actingAs($admin)->get(route('admin.feedback.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('The summit overall', $csv);
        $this->assertStringNotContainsString('Neema', $csv);

        $this->actingAs(User::factory()->create()->assignRole(Role::FinanceOfficer->value))->get(route('admin.feedback.index'))->assertForbidden();
    }
}
