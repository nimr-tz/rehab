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
     * Reads ADMIN_EMAIL (required) and ADMIN_PASSWORD (optional) through
     * config('app.admin'). Without a password, one is generated and printed once. Safe to run again: an
     * existing admin keeps their password.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = config('app.admin');
        $email = $admin['email'] ?? null;

        if (! $email) {
            throw new RuntimeException('Set ADMIN_EMAIL before running the DeploymentSeeder.');
        }

        $email = Str::lower($email);
        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = $admin['password'] ?: Str::password(16, symbols: false);

            $user = User::create([
                'first_name' => $admin['first_name'],
                'last_name' => $admin['last_name'],
                'email' => $email,
                'phone' => $admin['phone'] ?? '',
                'country' => $admin['country'] ?? 'TZ',
                'password' => $password,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            if (! $admin['password']) {
                $this->command?->warn("Admin {$email} created with password: {$password}");
                $this->command?->warn('Store it now; it is not shown again.');
            }
        }

        $user->assignRole(Role::Admin->value);
    }
}
