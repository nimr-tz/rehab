<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiStaffAttendanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('abstract_submissions');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('onsite_visitors');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('affiliation')->nullable();
            $table->string('country')->nullable();
            $table->string('profile_image')->nullable();
            $table->text('bio')->nullable();
            $table->string('registration_category')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedBigInteger('checked_in_by')->nullable();
            $table->string('qr_code_token')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('assigned_at')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->unsignedBigInteger('group_member_id')->nullable();
            $table->unsignedBigInteger('onsite_visitor_id')->nullable();
            $table->integer('day');
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedBigInteger('checked_in_by')->nullable();
            $table->timestamps();
        });

        Schema::create('onsite_visitors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('print_name')->nullable();
            $table->string('institution')->nullable();
            $table->string('badge_category')->default('invitee');
            $table->text('notes')->nullable();
            $table->string('qr_token', 100)->unique()->nullable();
            $table->boolean('badge_printed')->default(false);
            $table->timestamp('badge_printed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('abstract_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('status')->default('draft');
            $table->string('title')->nullable();
            $table->string('conference_code')->nullable();
            $table->string('presentation_type')->nullable();
            $table->string('presentation_mode')->nullable();
            $table->string('oral_presentation_file')->nullable();
            $table->string('poster_presentation_file')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_registration_officer_can_mark_and_lookup_mobile_attendance(): void
    {
        config(['conference.start_date' => now()->toDateString(), 'conference.total_days' => 3]);

        $officerRole = Role::create(['name' => 'registration_officer']);
        $officer = $this->makeUser('officer@example.com', 'verified', 'OFFICER-QR');
        $officer->roles()->attach($officerRole->id, [
            'is_primary' => true,
            'assigned_at' => now(),
        ]);

        $attendee = $this->makeUser('attendee@example.com', 'verified', 'RH26-TESTTOKEN');

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/staff/mark-attendance', [
                'user_id' => $attendee->id,
                'day' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $attendee->id,
            'day' => 1,
            'checked_in_by' => $officer->id,
        ]);
        $this->assertNotNull($attendee->fresh()->checked_in_at);

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/staff/lookup-qr/RH26-TESTTOKEN')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendee.attended_today', true)
            ->assertJsonPath('attendee.profile_photo_missing', true)
            ->assertJsonPath('attendee.bio_missing', true)
            ->assertJsonCount(2, 'attendee.missing_items');

        $this->assertNotNull($attendee->fresh()->checked_in_at);
    }

    public function test_staff_attendance_uses_demo_day_before_conference_and_blocks_real_days(): void
    {
        config(['conference.start_date' => now()->addDay()->toDateString(), 'conference.total_days' => 3]);

        $officerRole = Role::create(['name' => 'registration_officer']);
        $officer = $this->makeUser('officer2@example.com', 'verified', 'OFFICER2-QR');
        $officer->roles()->attach($officerRole->id, [
            'is_primary' => true,
            'assigned_at' => now(),
        ]);

        $attendee = $this->makeUser('attendee2@example.com', 'verified', 'RH26-DEMO');

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/staff/lookup-qr/RH26-DEMO')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendee.current_day', 0)
            ->assertJsonPath('attendee.current_day_label', 'Demo Day')
            ->assertJsonPath('attendee.is_demo_day', true);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/staff/mark-attendance', [
                'user_id' => $attendee->id,
                'day' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/staff/mark-attendance', [
                'user_id' => $attendee->id,
                'day' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.day', 0)
            ->assertJsonPath('attendance.day_label', 'Demo Day');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $attendee->id,
            'day' => 0,
        ]);
        $this->assertFalse($attendee->fresh()->hasAttendedConference());
    }

    public function test_staff_attendance_day_uses_conference_timezone_not_server_timezone(): void
    {
        config([
            'conference.start_date' => '2026-06-09',
            'conference.total_days' => 3,
            'conference.timezone' => 'Africa/Dar_es_Salaam',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-08 22:30:00', 'UTC'));

        $officerRole = Role::create(['name' => 'registration_officer']);
        $officer = $this->makeUser('officer3@example.com', 'verified', 'OFFICER3-QR');
        $officer->roles()->attach($officerRole->id, [
            'is_primary' => true,
            'assigned_at' => now(),
        ]);

        $attendee = $this->makeUser('attendee3@example.com', 'verified', 'RH26-TZ');

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/staff/lookup-qr/RH26-TZ')
            ->assertOk()
            ->assertJsonPath('attendee.current_day', 1)
            ->assertJsonPath('attendee.current_day_label', 'Day 1')
            ->assertJsonPath('attendee.is_demo_day', false);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/staff/mark-attendance', [
                'user_id' => $attendee->id,
                'day' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.day', 1);
    }

    public function test_registration_officer_can_lookup_and_mark_walkin_visitor(): void
    {
        config(['conference.start_date' => now()->toDateString(), 'conference.total_days' => 3]);

        $officerRole = Role::create(['name' => 'registration_officer']);
        $officer = $this->makeUser('officer4@example.com', 'verified', 'OFFICER4-QR');
        $officer->roles()->attach($officerRole->id, [
            'is_primary' => true,
            'assigned_at' => now(),
        ]);

        $visitor = \App\Models\OnsiteVisitor::create([
            'name' => 'Walk In Guest',
            'institution' => 'Ministry of Health',
            'badge_category' => 'vip',
            'qr_token' => 'ONSITE-WALKINTEST',
            'created_by' => $officer->id,
        ]);

        // Lookup with the already-printed ONSITE- token resolves to the walk-in.
        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/staff/lookup-qr/ONSITE-WALKINTEST')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendee.attendee_type', 'onsite_visitor')
            ->assertJsonPath('attendee.id', $visitor->id)
            ->assertJsonPath('attendee.name', 'Walk In Guest')
            ->assertJsonPath('attendee.is_paid', true);

        // Marking attendance routes through the onsite-visitor path.
        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/staff/mark-attendance', [
                'attendee_type' => 'onsite_visitor',
                'attendee_id' => $visitor->id,
                'day' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.day', 1);

        $this->assertDatabaseHas('attendances', [
            'onsite_visitor_id' => $visitor->id,
            'user_id' => null,
            'day' => 1,
            'checked_in_by' => $officer->id,
        ]);

        // Second scan on the same day is rejected as a duplicate.
        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/staff/mark-attendance', [
                'attendee_type' => 'onsite_visitor',
                'attendee_id' => $visitor->id,
                'day' => 1,
            ])
            ->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    private function makeUser(string $email, string $paymentStatus, string $qrToken): User
    {
        return User::create([
            'first_name' => ucfirst(strtok($email, '@')),
            'last_name' => 'Tester',
            'email' => $email,
            'password' => Hash::make('password'),
            'affiliation' => 'Rehab Health',
            'registration_category' => 'professional_local',
            'payment_status' => $paymentStatus,
            'qr_code_token' => $qrToken,
        ]);
    }
}
