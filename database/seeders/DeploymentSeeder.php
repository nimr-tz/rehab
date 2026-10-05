<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class DeploymentSeeder extends Seeder
{
    /**
     * Production setup: the roles and the first administrator.
     *
     * Reads ADMIN_EMAIL (required) and ADMIN_PASSWORD (optional). Without a
     * password, one is generated and printed once. Safe to run again: an
     * existing admin keeps their password.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $email = env('ADMIN_EMAIL');

        if (! $email) {
            throw new RuntimeException('Set ADMIN_EMAIL before running the DeploymentSeeder.');
        }

        $email = Str::lower($email);
        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = env('ADMIN_PASSWORD') ?: Str::password(16, symbols: false);

            $user = User::create([
                'first_name' => env('ADMIN_FIRST_NAME', 'Portal'),
                'last_name' => env('ADMIN_LAST_NAME', 'Admin'),
                'email' => $email,
                'phone' => env('ADMIN_PHONE', ''),
                'country' => env('ADMIN_COUNTRY', 'TZ'),
                'password' => $password,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            if (! env('ADMIN_PASSWORD')) {
                $this->command?->warn("Admin {$email} created with password: {$password}");
                $this->command?->warn('Store it now; it is not shown again.');
            }
        }

        $user->assignRole(Role::Admin->value);
    }
}
