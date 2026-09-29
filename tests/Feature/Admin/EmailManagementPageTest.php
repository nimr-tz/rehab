<?php

namespace Tests\Feature\Admin;

use App\Mail\CriticalErrorAlert;
use App\Mail\CriticalIncidentResolved;
use App\Models\Role;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\CriticalErrorAlertService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class EmailManagementPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $runtimePath = sys_get_temp_dir() . '/conference-email-management-test';
        @mkdir($runtimePath . '/framework/views', 0777, true);
        @mkdir($runtimePath . '/logs', 0777, true);

        config([
            'view.compiled' => $runtimePath . '/framework/views',
            'logging.default' => 'errorlog',
        ]);

        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('abstract_submissions');
        Schema::dropIfExists('group_registrations');
        Schema::dropIfExists('conference_sessions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('payment_status')->nullable();
            $table->boolean('intent_group_leader')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->string('color', 7)->default('#6b7280');
            $table->string('icon', 10)->default('admin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable();
            $table->timestamps();
        });

        Schema::create('system_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('level', 20)->default('error');
            $table->string('channel', 50)->default('system');
            $table->string('source', 100)->nullable();
            $table->string('fingerprint', 64)->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->boolean('resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->foreignId('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('user_notification_email')->nullable();
            $table->string('user_notification_status', 32)->default('not_requested');
            $table->timestamp('user_notification_sent_at')->nullable();
            $table->text('user_notification_error')->nullable();
            $table->boolean('user_action_required')->default(false);
            $table->text('user_action_details')->nullable();
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->unsignedBigInteger('abstract_submission_id')->nullable();
            $table->string('email_type');
            $table->string('recipient_email');
            $table->string('subject');
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('abstract_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('group_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leader_user_id')->constrained('users')->onDelete('cascade');
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });

        Schema::create('conference_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('session_chair_id')->nullable();
            $table->unsignedBigInteger('session_rapporteur_id')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('title')->default('Test notification');
            $table->text('message')->default('Test message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('action_url')->nullable();
            $table->string('priority')->default('medium');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_can_open_system_log_detail_page(): void
    {
        $admin = $this->createAdminUser();
        $systemLog = SystemLog::create([
            'level' => 'error',
            'channel' => 'system',
            'source' => 'RuntimeException',
            'fingerprint' => sha1('detail-page'),
            'message' => 'Critical incident captured for detail page.',
            'context' => ['request' => ['route_name' => 'admin.system-logs.show']],
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.system-logs.show', $systemLog))
            ->assertOk()
            ->assertSee('Error Message')
            ->assertSee('Critical incident captured for detail page.');
    }

    public function test_critical_error_alert_service_sends_email_with_context_and_persists_incident(): void
    {
        Mail::fake();

        config([
            'mail.error_alerts.enabled' => true,
            'mail.error_alerts.mailer' => 'array',
            'mail.error_alerts.to' => ['admin@example.com'],
            'mail.error_alerts.dedupe_minutes' => 15,
        ]);

        $user = User::factory()->create([
            'email' => 'affected@example.com',
        ]);

        $request = Request::create('/abstracts', 'POST', ['contact_email' => 'affected@example.com']);
        $request->headers->set('User-Agent', 'PHPUnit');
        $request->setUserResolver(fn () => $user);

        app(CriticalErrorAlertService::class)->handle(
            new RuntimeException('Dashboard exploded'),
            $request
        );

        Mail::assertSent(CriticalErrorAlert::class, function (CriticalErrorAlert $mail): bool {
            return $mail->context['request']['url'] === 'http://localhost/abstracts'
                && $mail->context['exception']['message'] === 'Dashboard exploded';
        });

        $this->assertDatabaseHas('system_logs', [
            'channel' => 'system',
            'message' => 'Dashboard exploded',
            'user_id' => $user->id,
            'occurrence_count' => 1,
            'resolved' => 0,
        ]);
    }

    public function test_admin_can_resolve_and_notify_user_with_action_required_details(): void
    {
        Mail::fake();

        config([
            'mail.incident_resolutions.mailer' => 'array',
        ]);

        $admin = $this->createAdminUser();
        $user = User::factory()->create([
            'email' => 'affected@example.com',
        ]);

        $systemLog = SystemLog::create([
            'level' => 'error',
            'channel' => 'system',
            'source' => 'RuntimeException',
            'fingerprint' => sha1('incident'),
            'message' => 'Submission failed during payment sync.',
            'context' => [
                'user' => ['email' => 'affected@example.com'],
                'request' => ['route_name' => 'abstracts.store'],
            ],
            'user_id' => $user->id,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'occurrence_count' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.system-logs.show', $systemLog))
            ->patch(route('admin.system-logs.resolve', $systemLog), [
                'resolution_notes' => 'Root cause fixed in payment gateway retry handling.',
                'resolution_summary' => 'Your abstract submission issue has been fixed and the workflow is working again.',
                'notify_user' => '1',
                'notification_email' => 'affected@example.com',
                'action_required' => '1',
                'action_details' => 'Please submit the abstract form again once using the same account.',
            ]);

        $response->assertRedirect(route('admin.system-logs.show', $systemLog));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('system_logs', [
            'id' => $systemLog->id,
            'resolved' => 1,
            'resolved_by' => $admin->id,
            'resolution_summary' => 'Your abstract submission issue has been fixed and the workflow is working again.',
            'user_notification_email' => 'affected@example.com',
            'user_notification_status' => 'sent',
            'user_action_required' => 1,
        ]);

        $this->assertDatabaseHas('email_logs', [
            'user_id' => $user->id,
            'email_type' => 'critical_incident_resolved',
            'recipient_email' => 'affected@example.com',
            'status' => 'sent',
        ]);

        Mail::assertSent(CriticalIncidentResolved::class, function (CriticalIncidentResolved $mail) use ($systemLog): bool {
            return $mail->systemLog->id === $systemLog->id
                && $mail->actionRequired === true
                && $mail->actionDetails === 'Please submit the abstract form again once using the same account.';
        });
    }

    private function createAdminUser(): User
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id, [
            'is_primary' => true,
            'assigned_at' => now(),
        ]);

        return $admin;
    }
}
