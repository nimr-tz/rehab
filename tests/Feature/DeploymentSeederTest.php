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

    protected function tearDown(): void
    {
        putenv('ADMIN_EMAIL');
        putenv('ADMIN_PASSWORD');
        parent::tearDown();
    }

    public function test_it_creates_every_role_and_a_verified_admin(): void
    {
        putenv('ADMIN_EMAIL=Chair@RehabHealth.or.tz');
        putenv('ADMIN_PASSWORD=initial-secret-1');

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
        putenv('ADMIN_EMAIL=chair@rehabhealth.or.tz');
        putenv('ADMIN_PASSWORD=initial-secret-1');
        $this->seed(DeploymentSeeder::class);

        putenv('ADMIN_PASSWORD=changed-secret-2');
        $this->seed(DeploymentSeeder::class);

        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('initial-secret-1', User::sole()->password));
        $this->assertSame(count(Role::cases()), RoleModel::count());
    }
}
