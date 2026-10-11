<?php

namespace Tests\Feature;

use App\Enums\AbstractStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\Registration;
use App\Models\User;
use App\Support\Csv;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Spreadsheets for the organisers: participants, needs, abstracts, attendance and CPD points. */
class ExportTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        $this->edition->topics()->create(['name' => 'Stroke', 'code' => 'STR', 'sort' => 0]);
        app(Summit::class)->refresh();

        $this->admin = User::factory()->create()->assignRole(Role::Admin->value);
    }

    private function registration(array $attributes = [], array $user = []): Registration
    {
        $person = User::factory()->create($user)->assignRole(Role::Participant->value);

        return $this->edition->registrations()->create($attributes + [
            'user_id' => $person->id, 'registration_category_id' => $this->edition->categories->first()->id,
            'reference' => 'RH27-'.str_pad((string) $person->id, 6, '0', STR_PAD_LEFT), 'currency' => 'TZS', 'amount' => 250000,
            'status' => RegistrationStatus::Confirmed, 'qr_token' => Str::random(32),
        ]);
    }

    /** @return list<list<string>> */
    private function csv(string $url): array
    {
        $content = $this->actingAs($this->admin)->get($url)->assertOk()->streamedContent();
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        return array_map('str_getcsv', array_filter(explode("\n", trim($content))));
    }

    public function test_participants_export_follows_the_filters(): void
    {
        $this->registration(['dietary_needs' => 'Vegetarian'], ['first_name' => 'Neema']);
        $this->registration(['status' => RegistrationStatus::PendingPayment], ['first_name' => 'Juma']);

        $all = $this->csv(route('admin.participants.export'));
        $this->assertSame('Reference', $all[0][0]);
        $this->assertCount(3, $all);

        $confirmed = $this->csv(route('admin.participants.export', ['status' => 'confirmed']));
        $this->assertCount(2, $confirmed);
        $this->assertSame('Neema', $confirmed[1][2]);
    }

    public function test_the_needs_export_lists_only_people_with_needs(): void
    {
        $this->registration(['dietary_needs' => 'Vegetarian'], ['first_name' => 'Neema']);
        $this->registration(['accessibility_needs' => 'Wheelchair access'], ['first_name' => 'Asha']);
        $this->registration([], ['first_name' => 'Juma']);

        $rows = $this->csv(route('admin.participants.export', ['needs' => 1]));

        $this->assertCount(3, $rows);
        $this->assertSame(['Neema', 'Asha'], [$rows[1][2], $rows[2][2]]);
        $this->assertNotContains('Fee', $rows[0]);
    }

    public function test_abstracts_export(): void
    {
        $author = User::factory()->create()->assignRole(Role::Participant->value);
        AbstractSubmission::create([
            'edition_id' => $this->edition->id, 'user_id' => $author->id, 'topic_id' => $this->edition->topics->first()->id,
            'preferred_type' => 'oral', 'title' => 'Early mobilisation', 'background' => 'B', 'methods' => 'M', 'results' => 'R', 'conclusions' => 'C',
            'status' => AbstractStatus::Accepted, 'decision_type' => 'oral', 'code' => 'OR-STR-01', 'submitted_at' => now(),
        ]);
        AbstractSubmission::create([
            'edition_id' => $this->edition->id, 'user_id' => $author->id, 'topic_id' => $this->edition->topics->first()->id,
            'preferred_type' => 'oral', 'title' => 'A draft', 'background' => '', 'methods' => '', 'results' => '', 'conclusions' => '',
            'status' => AbstractStatus::Draft,
        ]);

        $rows = $this->csv(route('scientific.abstracts.export'));

        $this->assertCount(2, $rows);
        $this->assertSame('OR-STR-01', $rows[1][1]);
    }

    public function test_attendance_and_cpd_exports(): void
    {
        $session = $this->edition->sessions()->create(['title' => 'Stroke', 'kind' => 'parallel', 'starts_at' => '2027-09-15 09:00', 'ends_at' => '2027-09-15 10:30', 'cpd_points' => 1.5]);
        $other = $this->edition->sessions()->create(['title' => 'Spinal cord', 'kind' => 'parallel', 'starts_at' => '2027-09-16 09:00', 'ends_at' => '2027-09-16 10:30', 'cpd_points' => 2]);
        $registration = $this->registration();
        $registration->attendances()->create(['programme_session_id' => $session->id, 'scanned_at' => '2027-09-15 09:10']);
        $registration->attendances()->create(['programme_session_id' => $other->id, 'scanned_at' => '2027-09-16 09:05']);
        $this->registration();

        $attendance = $this->csv(route('scientific.programme.attendance-export'));
        $this->assertCount(3, $attendance);
        $this->assertSame(['Stroke', 'Spinal cord'], [$attendance[1][0], $attendance[2][0]]);

        $cpd = $this->csv(route('scientific.programme.cpd-export'));
        $this->assertCount(2, $cpd);
        $this->assertSame(['2', '3.5'], [$cpd[1][7], $cpd[1][8]]);
    }

    public function test_cells_never_run_as_formulas(): void
    {
        $this->assertSame("'=SUM(A1)", Csv::cell('=SUM(A1)'));
        $this->assertSame("'@cmd", Csv::cell('@cmd'));
        $this->assertSame("'+255 754 123 456", Csv::cell('+255 754 123 456'));
        $this->assertSame('-5', Csv::cell(-5));
        $this->assertSame('250000.00', Csv::cell('250000.00'));
        $this->assertSame('Yes', Csv::cell(true));
        $this->assertSame('', Csv::cell(null));
    }

    public function test_exports_are_for_staff_only(): void
    {
        $participant = $this->registration()->user;

        $this->actingAs($participant)->get(route('admin.participants.export'))->assertForbidden();
        $this->actingAs($participant)->get(route('scientific.abstracts.export'))->assertForbidden();
        $this->actingAs($participant)->get(route('scientific.programme.cpd-export'))->assertForbidden();
    }
}
