<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CpdRequestExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_cpd_request_totals_and_download_link(): void
    {
        $admin = $this->createAdminUser();

        User::factory()->create([
            'professional_board' => 'Medical Council of Tanganyika',
            'registration_number' => 'MCT-12345',
        ]);

        User::factory()->create([
            'professional_board' => 'Nursing and Midwifery Council',
            'registration_number' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.emails.index'))
            ->assertOk()
            ->assertSee('CPD Requests')
            ->assertSee('>2<', false)
            ->assertSee('participants entered CPD details.')
            ->assertSee('1 complete · 1 incomplete.')
            ->assertSee(route('admin.emails.export-cpd-requests'), false);
    }

    public function test_admin_can_export_complete_and_incomplete_cpd_requests_with_attendance(): void
    {
        $admin = $this->createAdminUser();

        $complete = User::factory()->create([
            'first_name' => 'Asha',
            'last_name' => 'Mushi',
            'email' => 'asha@example.com',
            'professional_board' => 'Medical Council of Tanganyika',
            'registration_number' => 'MCT-12345',
        ]);

        Attendance::create([
            'user_id' => $complete->id,
            'day' => 1,
            'checked_in_at' => now(),
        ]);
        Attendance::create([
            'user_id' => $complete->id,
            'day' => 3,
            'checked_in_at' => now(),
        ]);

        User::factory()->create([
            'first_name' => 'Neema',
            'last_name' => 'Juma',
            'email' => 'neema@example.com',
            'professional_board' => 'Nursing and Midwifery Council',
            'registration_number' => null,
        ]);

        User::factory()->create([
            'first_name' => 'No',
            'last_name' => 'Request',
            'email' => 'no-request@example.com',
            'professional_board' => null,
            'registration_number' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.emails.export-cpd-requests'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Asha Mushi', $csv);
        $this->assertStringContainsString('MCT-12345', $csv);
        $this->assertStringContainsString('"Day 1, Day 3"', $csv);
        $this->assertStringContainsString('Neema Juma', $csv);
        $this->assertStringContainsString('Incomplete - missing registration number', $csv);
        $this->assertStringNotContainsString('no-request@example.com', $csv);
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
