<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ConferenceFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the post-conference certificate gates:
 * - downloads stay locked (with a countdown) until conference.certificates_release_at
 * - after release, downloads require submitted conference feedback
 * - attendance certificates follow recorded check-ins, not payment
 */
class CertificateGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_countdown_shown_and_download_blocked_before_release(): void
    {
        config(['conference.certificates_release_at' => now()->addDay()->toDateTimeString()]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('certificate.index'))
            ->assertOk()
            ->assertSee('Certificates Unlock In')
            ->assertSee('certCountdown', false)
            ->assertDontSee('Download PDF');

        $this->actingAs($user)->get(route('certificate.download.attendance'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_download_requires_feedback_after_release(): void
    {
        config(['conference.certificates_release_at' => now()->subDay()->toDateTimeString()]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('certificate.index'))
            ->assertOk()
            ->assertSee('One Step Left')
            ->assertSee('Submit Feedback to Unlock');

        $this->actingAs($user)->get(route('certificate.download.attendance'))
            ->assertRedirect(route('feedback.create'));
    }

    public function test_feedback_submission_passes_the_gate(): void
    {
        config(['conference.certificates_release_at' => now()->subDay()->toDateTimeString()]);
        $user = User::factory()->create();
        ConferenceFeedback::create(['user_id' => $user->id, 'overall_rating' => 5]);

        // Gates passed; only the attendance requirement remains
        $this->actingAs($user)->get(route('certificate.index'))
            ->assertOk()
            ->assertSee('Almost There')
            ->assertDontSee('Submit Feedback to Unlock');

        $response = $this->actingAs($user)
            ->from(route('certificate.index'))
            ->get(route('certificate.download.attendance'));

        $response->assertRedirect(route('certificate.index'));
        $this->assertStringContainsString('not eligible', session('error'));
    }

    public function test_paid_user_without_recorded_attendance_gets_no_certificate(): void
    {
        config(['conference.certificates_release_at' => now()->subDay()->toDateTimeString()]);
        $user = User::factory()->create(['payment_status' => 'verified']);
        ConferenceFeedback::create(['user_id' => $user->id, 'overall_rating' => 5]);

        // Payment alone is not attendance.
        $this->actingAs($user)->get(route('certificate.index'))
            ->assertOk()
            ->assertDontSee('Download PDF');

        $this->actingAs($user)->get(route('certificate.download.attendance'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_user_scanned_in_every_day_gets_full_attendance(): void
    {
        config(['conference.certificates_release_at' => now()->subDay()->toDateTimeString()]);
        $user = User::factory()->create(['payment_status' => 'verified']);
        ConferenceFeedback::create(['user_id' => $user->id, 'overall_rating' => 4]);
        foreach (range(1, (int) config('conference.total_days')) as $day) {
            Attendance::recordAttendance($user->id, $day);
        }

        $this->actingAs($user)->get(route('certificate.index'))
            ->assertOk()
            ->assertSee('Full Conference Attendance')
            ->assertSee('Download PDF');
    }
}
