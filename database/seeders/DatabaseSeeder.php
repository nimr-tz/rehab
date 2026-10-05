<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Local development data: the roles, an admin and one participant.
     * Both sign in with the password "password".
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::factory()->create([
            'title' => null,
            'first_name' => 'Portal',
            'last_name' => 'Admin',
            'email' => 'admin@rehab.test',
        ])->assignRole(Role::Admin->value);

        User::factory()->create([
            'title' => 'Dr',
            'first_name' => 'Amina',
            'last_name' => 'Participant',
            'email' => 'participant@rehab.test',
        ])->assignRole(Role::Participant->value);
    }
}
