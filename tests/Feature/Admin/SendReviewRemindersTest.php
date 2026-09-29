<?php

namespace Tests\Feature\Admin;

use App\Models\AbstractSubmission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendReviewRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_bulk_review_reminders_without_server_error(): void
    {
        Mail::fake();
        Queue::fake();

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $reviewerRole = Role::firstOrCreate(
            ['name' => 'reviewer'],
            ['display_name' => 'Reviewer']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id, [
            'is_primary' => true,
            'assigned_at' => now(),
        ]);

        $reviewer = User::factory()->create();
        $reviewer->roles()->attach($reviewerRole->id, [
            'is_primary' => true,
            'assigned_at' => now(),
        ]);

        $author = User::factory()->create();

        $abstract = AbstractSubmission::factory()->create([
            'user_id' => $author->id,
            'status' => 'under_review',
            'reviewer_id' => $reviewer->id,
            'reviewer_2_id' => null,
            'reviewer_1_score' => null,
            'assigned_at' => now()->subDays(5),
        ]);

        $response = $this
            ->from(route('admin.emails.index'))
            ->actingAs($admin)
            ->post(route('admin.emails.send-review-reminders'), [
                'deadline' => now()->toDateString(),
            ]);

        $response
            ->assertRedirect(route('admin.emails.index'))
            ->assertSessionHas('success', 'Review reminders queued: 1 queued, 0 failed to queue');

        // Reminders go through the logged-email queue (database connection).
        $log = \App\Models\EmailLog::where('email_type', \App\Models\EmailLog::TYPE_REVIEW_REMINDER)->sole();
        $this->assertSame($reviewer->email, $log->recipient_email);
        Queue::assertPushed(\App\Jobs\SendLoggedEmail::class, 1);
    }
}
