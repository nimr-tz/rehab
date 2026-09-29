<?php

namespace Tests\Feature;

use App\Models\ConferenceSession;
use App\Models\GroupMember;
use App\Models\GroupRegistration;
use App\Models\Role;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SessionAttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        $officer = User::factory()->create();
        $officer->roles()->attach(Role::firstOrCreate(['name' => 'registration_officer'], ['display_name' => 'Registration Officer']));

        return $officer;
    }

    private function makeSession(): ConferenceSession
    {
        return ConferenceSession::create([
            'name' => 'Panel: Home-based Rehabilitation',
            'session_type' => 'panel',
            'room_location' => 'Hall A',
            'subtheme' => 'Home-based rehabilitation',
            'schedule_days' => ['2026-09-16'],
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'is_active' => true,
        ]);
    }

    public function test_staff_records_paid_attendee_once_per_session(): void
    {
        $session = $this->makeSession();
        $attendee = User::factory()->create(['payment_status' => 'verified', 'qr_code_token' => 'QR-ABC123']);
        Sanctum::actingAs($this->staff());

        $this->postJson("/api/staff/sessions/{$session->id}/attendance", ['token' => 'QR-ABC123'])
            ->assertCreated()
            ->assertJson(['success' => true, 'session_count' => 1]);

        $this->postJson("/api/staff/sessions/{$session->id}/attendance", ['token' => 'QR-ABC123'])
            ->assertStatus(409)
            ->assertJson(['already_recorded' => true]);

        $this->assertSame(1, SessionAttendance::count());
        $this->assertSame($attendee->id, SessionAttendance::sole()->attendee_id);

        $this->getJson("/api/staff/sessions/{$session->id}/attendance")->assertJson(['count' => 1]);
    }

    public function test_unpaid_attendee_is_refused(): void
    {
        $session = $this->makeSession();
        User::factory()->create(['payment_status' => 'pending', 'qr_code_token' => 'QR-UNPAID']);
        Sanctum::actingAs($this->staff());

        $this->postJson("/api/staff/sessions/{$session->id}/attendance", ['token' => 'QR-UNPAID'])
            ->assertStatus(400);

        $this->assertSame(0, SessionAttendance::count());
    }

    public function test_group_member_badge_is_accepted_when_group_paid(): void
    {
        $session = $this->makeSession();
        $leader = User::factory()->create();
        $group = GroupRegistration::create([
            'leader_user_id' => $leader->id,
            'group_name' => 'Team A',
            'total_amount' => 0,
            'currency' => 'TZS',
            'payment_status' => 'verified',
        ]);
        GroupMember::create([
            'group_registration_id' => $group->id,
            'full_name' => 'Asha Member',
            'registration_category' => 'professional_local',
            'fee_amount' => 0,
            'fee_currency' => 'TZS',
            'qr_token' => 'GM-TESTTOKEN',
        ]);
        Sanctum::actingAs($this->staff());

        $this->postJson("/api/staff/sessions/{$session->id}/attendance", ['token' => 'GM-TESTTOKEN'])
            ->assertCreated()
            ->assertJsonPath('attendee.name', 'Asha Member');
    }

    public function test_unknown_badge_and_non_staff_are_rejected(): void
    {
        $session = $this->makeSession();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/staff/sessions/{$session->id}/attendance", ['token' => 'X'])->assertForbidden();

        Sanctum::actingAs($this->staff());
        $this->postJson("/api/staff/sessions/{$session->id}/attendance", ['token' => 'NOPE'])->assertNotFound();
    }
}
