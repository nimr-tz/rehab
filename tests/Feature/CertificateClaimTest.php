<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\GroupMember;
use App\Models\OnsiteVisitor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CertificateClaimTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['conference.certificates_release_at' => now()->subDay()->format('Y-m-d H:i:s')]);

        Schema::dropIfExists('certificates');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('onsite_visitors');
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('affiliation')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('qr_code_token')->nullable();
            $table->timestamps();
        });

        Schema::create('group_members', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('group_registration_id')->nullable();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('institution')->nullable();
            $table->string('qr_token', 100)->unique()->nullable();
            $table->boolean('checked_in')->default(false);
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
        });

        Schema::create('onsite_visitors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('print_name')->nullable();
            $table->string('institution')->nullable();
            $table->string('badge_category')->default('invitee');
            $table->string('qr_token', 100)->unique()->nullable();
            $table->boolean('badge_printed')->default(false);
            $table->timestamp('badge_printed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('group_member_id')->nullable();
            $table->unsignedBigInteger('onsite_visitor_id')->nullable();
            $table->integer('day');
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedBigInteger('checked_in_by')->nullable();
            $table->timestamps();
        });

        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('group_member_id')->nullable();
            $table->unsignedBigInteger('onsite_visitor_id')->nullable();
            $table->string('holder_name')->nullable();
            $table->string('type', 50);
            $table->string('certificate_number', 50)->unique();
            $table->unsignedBigInteger('abstract_submission_id')->nullable();
            $table->json('attendance_days')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('first_downloaded_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_verified_at')->nullable();
            $table->unsignedInteger('verification_count')->default(0);
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('conference_feedback');
        Schema::create('conference_feedback', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('badge_code', 64)->nullable();
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('participant_type')->nullable();
            $table->integer('overall_rating')->nullable();
            $table->boolean('would_present_again')->default(false);
            $table->timestamps();
        });
    }

    /** Record badge-keyed feedback so the claim gate passes. */
    private function giveFeedbackForBadge(string $code): void
    {
        \App\Models\ConferenceFeedback::create([
            'badge_code' => $code,
            'participant_type' => 'attendee',
            'overall_rating' => 5,
        ]);
    }

    public function test_shows_the_public_claim_page(): void
    {
        $this->get(route('certificate.claim'))
            ->assertOk()
            ->assertSee('Claim Your Certificate');
    }

    public function test_walk_in_visitor_with_attendance_can_download_certificate(): void
    {
        $visitor = OnsiteVisitor::create([
            'name' => 'Test Visitor',
            'badge_category' => 'guest',
            'qr_token' => 'ONSITE-TESTTOKEN1234',
        ]);
        Attendance::recordOnsiteVisitorAttendance($visitor->id, 1);
        $this->giveFeedbackForBadge('ONSITE-TESTTOKEN1234');

        $response = $this->post(route('certificate.claim.download'), [
            'code' => 'ONSITE-TESTTOKEN1234',
        ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'Certificate_Participation_Test_Visitor',
            (string) $response->headers->get('content-disposition')
        );

        $certificate = Certificate::where('onsite_visitor_id', $visitor->id)->first();
        $this->assertNotNull($certificate);
        $this->assertSame(Certificate::TYPE_ATTENDANCE_PARTIAL, $certificate->type);
        $this->assertSame('Test Visitor', $certificate->holder_name);
        $this->assertNull($certificate->user_id);
    }

    public function test_group_member_with_full_attendance_gets_full_certificate(): void
    {
        $member = GroupMember::create([
            'full_name' => 'Group Person',
            'qr_token' => 'GM-FULLATTEND123',
        ]);
        foreach ([1, 2, 3] as $day) {
            Attendance::recordGroupMemberAttendance($member->id, $day);
        }
        $this->giveFeedbackForBadge('GM-FULLATTEND123');

        $this->post(route('certificate.claim.download'), ['code' => 'GM-FULLATTEND123'])->assertOk();

        $certificate = Certificate::where('group_member_id', $member->id)->first();
        $this->assertSame(Certificate::TYPE_ATTENDANCE_FULL, $certificate->type);
    }

    public function test_accepts_a_scanned_badge_url_instead_of_a_raw_token(): void
    {
        $visitor = OnsiteVisitor::create([
            'name' => 'Url Visitor',
            'badge_category' => 'guest',
            'qr_token' => 'ONSITE-URLTOKEN56789',
        ]);
        Attendance::recordOnsiteVisitorAttendance($visitor->id, 2);
        $this->giveFeedbackForBadge('ONSITE-URLTOKEN56789');

        $this->post(route('certificate.claim.download'), [
            'code' => 'https://summit.rehabhealth.or.tz/badge/ONSITE-URLTOKEN56789',
        ])->assertOk();
    }

    public function test_badge_with_no_recorded_attendance_is_refused(): void
    {
        OnsiteVisitor::create([
            'name' => 'Unscanned Visitor',
            'badge_category' => 'guest',
            'qr_token' => 'ONSITE-NOATTENDANCE1',
        ]);
        $this->giveFeedbackForBadge('ONSITE-NOATTENDANCE1');

        $this->from(route('certificate.claim'))
            ->post(route('certificate.claim.download'), ['code' => 'ONSITE-NOATTENDANCE1'])
            ->assertRedirect(route('certificate.claim'))
            ->assertSessionHas('error');

        $this->assertSame(0, Certificate::count());
    }

    public function test_registered_delegate_can_claim_with_their_badge(): void
    {
        $user = \App\Models\User::create([
            'title' => 'Dr.',
            'first_name' => 'Delegate',
            'last_name' => 'Person',
            'email' => 'delegate@example.com',
            'password' => bcrypt('secret'),
            'qr_code_token' => 'RH26-DELEGATE12345',
        ]);
        foreach ([1, 2, 3] as $day) {
            Attendance::recordAttendance($user->id, $day);
        }

        // Feedback tied to their account (submitted while logged in) counts
        \App\Models\ConferenceFeedback::create([
            'user_id' => $user->id,
            'participant_type' => 'attendee',
            'overall_rating' => 4,
        ]);

        $response = $this->post(route('certificate.claim.download'), [
            'code' => 'RH26-DELEGATE12345',
        ]);

        $response->assertOk();

        $certificate = Certificate::where('user_id', $user->id)->first();
        $this->assertNotNull($certificate);
        $this->assertSame(Certificate::TYPE_ATTENDANCE_FULL, $certificate->type);
    }

    public function test_claim_without_feedback_redirects_to_feedback_form(): void
    {
        OnsiteVisitor::create([
            'name' => 'No Feedback Visitor',
            'badge_category' => 'guest',
            'qr_token' => 'ONSITE-NOFEEDBACK123',
        ]);

        $this->post(route('certificate.claim.download'), ['code' => 'ONSITE-NOFEEDBACK123'])
            ->assertRedirect(route('feedback.create', ['badge' => 'ONSITE-NOFEEDBACK123']))
            ->assertSessionHas('error');

        $this->assertSame(0, Certificate::count());
    }

    public function test_delegate_without_feedback_is_also_gated(): void
    {
        \App\Models\User::create([
            'first_name' => 'Gated',
            'last_name' => 'Delegate',
            'email' => 'gated@example.com',
            'password' => bcrypt('secret'),
            'qr_code_token' => 'RH26-GATED1234567',
        ]);

        $this->post(route('certificate.claim.download'), ['code' => 'RH26-GATED1234567'])
            ->assertRedirect(route('feedback.create', ['badge' => 'RH26-GATED1234567']))
            ->assertSessionHas('error');
    }

    public function test_badge_feedback_submission_links_badge_and_returns_to_claim(): void
    {
        $visitor = OnsiteVisitor::create([
            'name' => 'Loop Visitor',
            'badge_category' => 'guest',
            'qr_token' => 'ONSITE-FEEDBACKLOOP1',
        ]);
        Attendance::recordOnsiteVisitorAttendance($visitor->id, 1);

        $this->post(route('feedback.store'), [
            'badge_code' => 'ONSITE-FEEDBACKLOOP1',
            'overall_rating' => 5,
        ])->assertRedirect(route('certificate.claim', ['code' => 'ONSITE-FEEDBACKLOOP1', 'autodownload' => 1]));

        $this->assertTrue(
            \App\Models\ConferenceFeedback::where('badge_code', 'ONSITE-FEEDBACKLOOP1')->exists()
        );

        // And the claim now succeeds
        $this->post(route('certificate.claim.download'), ['code' => 'ONSITE-FEEDBACKLOOP1'])
            ->assertOk();
    }

    public function test_onsite_visitor_badge_page_shows_certificate_button(): void
    {
        $this->withoutVite();

        OnsiteVisitor::create([
            'name' => 'Badge Page Visitor',
            'badge_category' => 'guest',
            'qr_token' => 'ONSITE-BADGEPAGE1234',
        ]);

        $this->get('/badge/ONSITE-BADGEPAGE1234')
            ->assertOk()
            ->assertSee('Badge Page Visitor')
            ->assertSee('Get My Certificate');
    }

    public function test_rejects_an_unknown_badge_code(): void
    {
        $this->from(route('certificate.claim'))
            ->post(route('certificate.claim.download'), ['code' => 'GM-DOESNOTEXIST99'])
            ->assertRedirect(route('certificate.claim'))
            ->assertSessionHas('error');
    }

    public function test_claimed_certificate_verifies_publicly_without_a_user_account(): void
    {
        $visitor = OnsiteVisitor::create([
            'name' => 'Verified Visitor',
            'badge_category' => 'guest',
            'qr_token' => 'ONSITE-VERIFYTOKEN12',
        ]);
        Attendance::recordOnsiteVisitorAttendance($visitor->id, 1);
        $this->giveFeedbackForBadge('ONSITE-VERIFYTOKEN12');

        $this->post(route('certificate.claim.download'), ['code' => 'ONSITE-VERIFYTOKEN12'])->assertOk();

        $certificate = Certificate::where('onsite_visitor_id', $visitor->id)->first();

        $this->get(route('certificate.verify', $certificate->certificate_number))
            ->assertOk()
            ->assertSee('Verified Visitor');
    }

    public function test_certificate_numbers_are_not_sequentially_guessable(): void
    {
        $number = Certificate::generateCertificateNumber(Certificate::TYPE_ATTENDANCE_FULL);

        $this->assertMatchesRegularExpression('/^RHS\d{2}-[A-Z]+-00001-[A-Z0-9]{6}$/', $number);
        $this->assertNotSame($number, Certificate::generateCertificateNumber(Certificate::TYPE_ATTENDANCE_FULL));
    }

    public function test_name_based_claim_route_is_gone(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('certificate.claim.name'));
    }
}
