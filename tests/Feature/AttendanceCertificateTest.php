<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\Edition;
use App\Models\ProgrammeSession;
use App\Models\Registration;
use App\Models\User;
use App\Services\AttendanceService;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Certificates of attendance: for everyone recorded at the summit, after it ends, and verifiable by anyone. */
class AttendanceCertificateTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private ProgrammeSession $dayTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->travelTo('2027-09-16 10:00');

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'venue' => 'JNICC', 'city' => 'Dar es Salaam', 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        app(Summit::class)->refresh();

        $this->dayTwo = $this->edition->sessions()->create([
            'title' => 'Stroke rehabilitation', 'kind' => 'parallel', 'starts_at' => '2027-09-16 09:00', 'ends_at' => '2027-09-16 11:00', 'cpd_points' => 2,
        ]);
    }

    private function registration(bool $confirmed = true): Registration
    {
        $user = User::factory()->create(['first_name' => 'Neema', 'last_name' => 'Mushi', 'institution' => 'KCMC'])->assignRole(Role::Participant->value);

        return $this->edition->registrations()->create([
            'user_id' => $user->id, 'registration_category_id' => $this->edition->categories->first()->id,
            'reference' => 'RH27-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT), 'currency' => 'TZS', 'amount' => 250000,
            'status' => $confirmed ? RegistrationStatus::Confirmed : RegistrationStatus::PendingPayment,
            'confirmed_at' => $confirmed ? now() : null, 'qr_token' => 'token-'.$user->id,
        ]);
    }

    private function scanned(Registration $registration): void
    {
        $registration->attendances()->create(['programme_session_id' => $this->dayTwo->id, 'scanned_at' => now()]);
    }

    public function test_the_certificate_comes_after_the_summit_for_those_who_attended(): void
    {
        $attended = $this->registration();
        $this->scanned($attended);
        $absent = $this->registration();

        // During the summit: not yet.
        $this->assertFalse($attended->hasAttendanceCertificate());
        $this->actingAs($attended->user)->get(route('registration.certificate'))->assertForbidden();

        $this->travelTo('2027-09-18 08:00');
        $this->assertTrue($attended->fresh()->hasAttendanceCertificate());
        $this->assertFalse($absent->fresh()->hasAttendanceCertificate());

        $this->actingAs($attended->user)->get(route('registration.show'))->assertSee('Certificate of attendance');
        $this->actingAs($attended->user)->get(route('dashboard'))->assertSee('Ready to download');
        $this->actingAs($attended->user)->get(route('registration.certificate'))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($absent->user)->get(route('registration.certificate'))->assertForbidden();
    }

    public function test_checking_in_at_the_desk_counts_as_attending(): void
    {
        $registration = $this->registration();
        $registration->update(['checked_in_at' => '2027-09-15 08:30']);
        $unpaid = $this->registration(confirmed: false);
        $unpaid->update(['checked_in_at' => '2027-09-15 08:40']);

        $this->travelTo('2027-09-18 08:00');

        $this->assertTrue($registration->fresh()->hasAttendanceCertificate());
        $this->assertFalse($unpaid->fresh()->hasAttendanceCertificate());
    }

    public function test_the_certificate_lists_the_days_attended(): void
    {
        $registration = $this->registration();
        $registration->update(['checked_in_at' => '2027-09-15 08:30']);
        $this->scanned($registration);

        $days = app(AttendanceService::class)->daysAttended($registration->fresh());

        $this->assertSame(['2027-09-15', '2027-09-16'], $days->map->toDateString()->all());
    }

    public function test_anyone_can_verify_a_certificate(): void
    {
        $registration = $this->registration();
        $this->scanned($registration);
        $this->travelTo('2027-09-18 08:00');

        $this->get(route('certificates.verify', [$registration->certificateNumber(), $registration->certificateCode()]))
            ->assertOk()->assertSee('This certificate is genuine')->assertSee('Neema Mushi')->assertSee('16 Sep');

        $this->get(route('certificates.verify', [$registration->certificateNumber(), 'aaaaaaaaaaaa']))->assertOk()->assertSee('We could not find this certificate');
        $this->get(route('certificates.verify', ['AT27-999999', $registration->certificateCode()]))->assertOk()->assertSee('We could not find this certificate');
    }

    public function test_admins_download_a_participants_certificate(): void
    {
        $registration = $this->registration();
        $this->scanned($registration);
        $this->travelTo('2027-09-18 08:00');
        $admin = User::factory()->create()->assignRole(Role::Admin->value);

        $this->actingAs($admin)->get(route('admin.participants.show', $registration))->assertSee(route('admin.participants.certificate', $registration));
        $this->actingAs($admin)->get(route('admin.participants.certificate', $registration))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($registration->user)->get(route('admin.participants.certificate', $registration))->assertForbidden();
    }
}
