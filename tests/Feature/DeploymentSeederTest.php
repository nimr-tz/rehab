<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DeploymentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

class DeploymentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_every_role_and_a_verified_admin(): void
    {
        config(['app.admin.email' => 'Chair@RehabHealth.or.tz', 'app.admin.password' => 'initial-secret-1']);

        $this->seed(DeploymentSeeder::class);

        $this->assertSame(count(Role::cases()), RoleModel::count());

        $admin = User::sole();
        $this->assertSame('chair@rehabhealth.or.tz', $admin->email);
        $this->assertTrue($admin->hasRole(Role::Admin->value));
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('initial-secret-1', $admin->password));
    }

    public function test_running_it_again_keeps_the_admin_password(): void
    {
        config(['app.admin.email' => 'chair@rehabhealth.or.tz', 'app.admin.password' => 'initial-secret-1']);
        $this->seed(DeploymentSeeder::class);

        config(['app.admin.password' => 'changed-secret-2']);
        $this->seed(DeploymentSeeder::class);

        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('initial-secret-1', User::sole()->password));
        $this->assertSame(count(Role::cases()), RoleModel::count());
    }
}
