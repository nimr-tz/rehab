<?php

namespace Tests\Feature\Admin;

use App\Models\AbstractSubmission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end coverage for the admin abstract update route
 * (App\Http\Controllers\Admin\AbstractManagementController::update).
 *
 * Subtheme names containing commas previously broke the comma-delimited
 * `in:` validation rule and produced "The selected subtheme is invalid".
 */
class AbstractUpdateSubthemeValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
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

    private function payloadFor(string $subtheme): array
    {
        return [
            'author_name'      => 'Jane Doe',
            'author_institute' => 'Rehab Health',
            'title'            => 'A study of something important',
            'description'      => 'A sufficiently long description of the abstract content.',
            'subtheme'         => $subtheme,
        ];
    }

    public function test_admin_can_update_abstract_to_each_configured_subtheme(): void
    {
        $admin = $this->makeAdmin();
        $author = User::factory()->create();

        $subthemes = array_keys(config('conference.subtheme_prefixes', []));
        $this->assertNotEmpty($subthemes, 'Expected configured subthemes.');

        foreach ($subthemes as $subtheme) {
            $abstract = AbstractSubmission::factory()->create([
                'user_id'  => $author->id,
                'status'   => 'submitted',
                'subtheme' => 'Stale placeholder subtheme',
            ]);

            $response = $this->actingAs($admin)
                ->from(route('admin.abstracts.edit', $abstract))
                ->put(route('admin.abstracts.update', $abstract), $this->payloadFor($subtheme));

            $response->assertSessionHasNoErrors();
            $response->assertRedirect(route('admin.abstracts.index'));

            $this->assertSame(
                $subtheme,
                $abstract->fresh()->subtheme,
                "Subtheme should persist for: {$subtheme}"
            );
        }
    }

    public function test_comma_containing_subthemes_are_accepted(): void
    {
        $admin = $this->makeAdmin();
        $author = User::factory()->create();

        $commaSubthemes = array_values(array_filter(
            array_keys(config('conference.subtheme_prefixes', [])),
            fn (string $s) => str_contains($s, ',')
        ));

        $this->assertNotEmpty($commaSubthemes, 'Expected at least one comma-containing subtheme to guard against.');

        foreach ($commaSubthemes as $subtheme) {
            $abstract = AbstractSubmission::factory()->create([
                'user_id'  => $author->id,
                'status'   => 'submitted',
                'subtheme' => 'Stale placeholder subtheme',
            ]);

            $this->actingAs($admin)
                ->put(route('admin.abstracts.update', $abstract), $this->payloadFor($subtheme))
                ->assertSessionHasNoErrors();

            $this->assertSame($subtheme, $abstract->fresh()->subtheme);
        }
    }

    public function test_unknown_subtheme_is_still_rejected(): void
    {
        $admin = $this->makeAdmin();
        $author = User::factory()->create();

        $abstract = AbstractSubmission::factory()->create([
            'user_id'  => $author->id,
            'status'   => 'submitted',
            'subtheme' => array_key_first(config('conference.subtheme_prefixes', [])),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.abstracts.update', $abstract), $this->payloadFor('Totally Made Up Subtheme'))
            ->assertSessionHasErrors('subtheme');
    }
}
