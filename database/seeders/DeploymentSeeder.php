<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Essential data for a fresh deployment: the RBAC roles and the first
 * administrator account.
 *
 * The administrator is taken from the environment:
 *   ADMIN_EMAIL     (required)
 *   ADMIN_FIRST_NAME / ADMIN_LAST_NAME (optional)
 *   ADMIN_PASSWORD  (optional — a random password is generated and printed once)
 *
 * Change the password after first sign-in.
 */
class DeploymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();
        $this->seedAdministrator();
    }

    private function seedRoles(): void
    {
        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Full system access', 'color' => '#dc2626', 'icon' => '🛡️'],
            ['name' => 'scientific_admin', 'display_name' => 'Scientific Coordinator', 'description' => 'Manages abstracts, reviews and the programme', 'color' => '#9333ea', 'icon' => '🧪'],
            ['name' => 'reviewer', 'display_name' => 'Reviewer', 'description' => 'Scientific reviewer', 'color' => '#2563eb', 'icon' => '🔍'],
            ['name' => 'author', 'display_name' => 'Author', 'description' => 'Conference author', 'color' => '#059669', 'icon' => '✏️'],
            ['name' => 'chair', 'display_name' => 'Session Chairperson', 'description' => 'Presides over sessions, manages timing and discussion', 'color' => '#8b5cf6', 'icon' => '🎤'],
            ['name' => 'rapporteur', 'display_name' => 'Rapporteur', 'description' => 'Takes minutes and prepares session reports', 'color' => '#10b981', 'icon' => '📝'],
            ['name' => 'chief_rapporteur', 'display_name' => 'Chief Rapporteur', 'description' => 'Reviews and approves session reports and compiles the conference report', 'color' => '#0ea5e9', 'icon' => '🗂️'],
            ['name' => 'finance_officer', 'display_name' => 'Finance Officer', 'description' => 'Verifies payments and manages finance operations', 'color' => '#0d9488', 'icon' => '💰'],
            ['name' => 'registration_officer', 'display_name' => 'Registration Officer', 'description' => 'Manages badges, check-in and attendance scanning', 'color' => '#7c3aed', 'icon' => '📋'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role + ['is_active' => true]);
        }

        $this->command?->info('Roles created/updated.');
    }

    private function seedAdministrator(): void
    {
        $email = env('ADMIN_EMAIL');

        if (blank($email)) {
            $this->command?->warn('ADMIN_EMAIL is not set — skipping administrator account. Set it and re-run: php artisan db:seed --class=DeploymentSeeder');

            return;
        }

        $existing = User::where('email', $email)->first();
        $password = env('ADMIN_PASSWORD');
        $generated = false;

        if (! $existing && blank($password)) {
            $password = Str::password(20);
            $generated = true;
        }

        $attributes = [
            'first_name' => env('ADMIN_FIRST_NAME', 'System'),
            'last_name' => env('ADMIN_LAST_NAME', 'Administrator'),
            'affiliation' => config('conference.host'),
            'country' => config('conference.country'),
            'student_status' => 'no',
            'email_verified_at' => now(),
        ];

        if (! $existing || filled(env('ADMIN_PASSWORD'))) {
            $attributes['password'] = Hash::make($password);
        }

        $admin = User::updateOrCreate(['email' => $email], $attributes);

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin', true);
        }

        $this->command?->info("Administrator ready: {$email}");

        if ($generated) {
            $this->command?->warn("Generated password (shown once): {$password}");
        }
    }
}
