<?php

namespace Tests\Feature;

use App\Enums\Audience;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Edition;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\AnnouncementMessage;
use App\Services\AnnouncementService;
use App\Support\BackgroundWork;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/** Admins email groups of people; the emails go out in batches in the background. */
class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Cache::flush();

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        app(Summit::class)->refresh();

        $this->admin = User::factory()->create()->assignRole(Role::Admin->value);
    }

    private function registered(RegistrationStatus $status): Registration
    {
        $user = User::factory()->create()->assignRole(Role::Participant->value);

        return $this->edition->registrations()->create([
            'user_id' => $user->id, 'registration_category_id' => $this->edition->categories->first()->id,
            'reference' => 'RH27-'.Str::random(6), 'currency' => 'TZS', 'amount' => 250000, 'status' => $status, 'qr_token' => Str::random(32),
        ]);
    }

    private function draft(Audience $audience = Audience::Unpaid)
    {
        return $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'subject' => 'Two weeks to the summit',
            'body' => "The programme is published.\n\nPlease pay your fee before you travel.",
            'audience' => $audience->value,
        ]);
    }

    public function test_an_announcement_goes_to_its_audience_in_batches(): void
    {
        $unpaid = $this->registered(RegistrationStatus::PendingPayment);
        $verifying = $this->registered(RegistrationStatus::PaymentSubmitted);
        $confirmed = $this->registered(RegistrationStatus::Confirmed);

        $this->actingAs($this->admin)->get(route('admin.announcements.create'))->assertOk()->assertSee('Registered but not confirmed');
        $this->draft()->assertRedirect();
        $announcement = Announcement::sole();
        $this->assertTrue($announcement->isDraft());

        $this->actingAs($this->admin)->get(route('admin.announcements.show', $announcement))->assertSee('This goes to')->assertSee('2 people');

        // A test copy to the admin only.
        $this->actingAs($this->admin)->post(route('admin.announcements.test', $announcement))->assertSessionHas('status');
        Notification::assertSentOnDemand(AnnouncementMessage::class, fn ($n, $c, AnonymousNotifiable $to) => $n->test && $to->routes['mail'] === $this->admin->email);

        $this->actingAs($this->admin)->post(route('admin.announcements.send', $announcement))->assertSessionHasErrors('confirm');
        $this->actingAs($this->admin)->post(route('admin.announcements.send', $announcement), ['confirm' => '1'])->assertSessionHas('status');

        $announcement->refresh();
        $this->assertTrue($announcement->isSending() || $announcement->finished_at !== null);
        $this->assertSame(2, $announcement->recipients()->count());

        app(AnnouncementService::class)->sendBatch();

        Notification::assertSentTo([$unpaid->user, $verifying->user], AnnouncementMessage::class);
        Notification::assertNotSentTo($confirmed->user, AnnouncementMessage::class);
        $this->assertNotNull($announcement->fresh()->finished_at);
        $this->assertSame(2, $announcement->recipients()->whereNotNull('sent_at')->count());

        // Sent once only, and no longer editable.
        $this->actingAs($this->admin)->post(route('admin.announcements.send', $announcement), ['confirm' => '1'])->assertSessionHasErrors('confirm');
        $this->actingAs($this->admin)->get(route('admin.announcements.edit', $announcement))->assertForbidden();
    }

    public function test_batches_are_limited_and_continue_in_the_background(): void
    {
        foreach (range(1, 5) as $i) {
            $this->registered(RegistrationStatus::Confirmed);
        }
        $this->draft(Audience::Confirmed);
        $announcement = Announcement::sole();
        app(AnnouncementService::class)->send($announcement);

        $this->assertSame(3, app(AnnouncementService::class)->sendBatch(3));
        $this->assertTrue($announcement->fresh()->isSending());

        $done = app(BackgroundWork::class)->run();
        $this->assertSame(2, $done['announcements']);
        $this->assertNotNull($announcement->fresh()->finished_at);
    }

    public function test_a_failed_email_is_recorded_and_can_be_retried(): void
    {
        $this->registered(RegistrationStatus::Confirmed);
        $this->draft(Audience::Confirmed);
        $announcement = Announcement::sole();
        app(AnnouncementService::class)->send($announcement);

        // The mail server refuses: the failure is recorded against the recipient, and sending finishes.
        $failing = Mockery::mock(ChannelManager::class);
        $failing->shouldReceive('send')->andThrow(new RuntimeException('Mail server unavailable'));
        Notification::swap($failing);
        $this->assertSame(0, app(AnnouncementService::class)->sendBatch());
        Notification::fake();

        $recipient = $announcement->recipients()->sole();
        $this->assertNotNull($recipient->failed_at);
        $this->assertNotNull($announcement->fresh()->finished_at);

        $this->actingAs($this->admin)->get(route('admin.announcements.show', $announcement))->assertSee('1 could not be sent')->assertSee('Mail server unavailable');
        $this->actingAs($this->admin)->post(route('admin.announcements.retry', $announcement))->assertSessionHas('status', '1 failed email will be tried again.');

        $this->assertNull($recipient->fresh()->failed_at);
        $this->assertTrue($announcement->fresh()->isSending());
    }

    public function test_audiences_are_counted_from_live_data(): void
    {
        $recipients = app(AnnouncementService::class)->recipients($this->edition, Audience::Accounts);
        $this->assertTrue($recipients->has(Str::lower($this->admin->email)));
        $this->assertSame(0, app(AnnouncementService::class)->recipients($this->edition, Audience::Presenters)->count());
    }

    public function test_an_empty_audience_cannot_be_sent(): void
    {
        $this->draft(Audience::SessionRoles);

        $this->actingAs($this->admin)->post(route('admin.announcements.send', Announcement::sole()), ['confirm' => '1'])->assertSessionHasErrors('confirm');
        $this->assertTrue(Announcement::sole()->isDraft());
    }

    public function test_only_admins_send_announcements(): void
    {
        $finance = User::factory()->create()->assignRole(Role::FinanceOfficer->value);

        $this->actingAs($finance)->get(route('admin.announcements.index'))->assertForbidden();
        $this->actingAs($finance)->post(route('admin.announcements.store'), ['subject' => 'x', 'body' => 'y', 'audience' => 'accounts'])->assertForbidden();
    }
}
