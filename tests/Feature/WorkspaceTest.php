<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Edition;
use App\Models\User;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17',
            'registration_open' => true, 'abstracts_open' => true, 'abstract_deadline' => now()->addMonths(6), 'is_current' => true,
        ]);
        app(Summit::class)->refresh();
    }

    private function user(Role ...$roles): User
    {
        return User::factory()->create()->assignRole(array_map(fn (Role $role) => $role->value, $roles));
    }

    public function test_each_role_only_sees_its_own_menu(): void
    {
        $this->actingAs($this->user(Role::Participant))->get(route('dashboard'))->assertOk()
            ->assertSee('My abstracts')->assertDontSee('Assigned abstracts')->assertDontSee('Verification queue')->assertDontSee('Switch role');

        $this->actingAs($this->user(Role::FinanceOfficer))->get(route('dashboard'))->assertOk()
            ->assertSee('Verification queue')->assertDontSee('My abstracts')->assertDontSee('Executive summary')->assertDontSee('Switch role');

        $this->actingAs($this->user(Role::Reviewer))->get(route('dashboard'))->assertOk()
            ->assertSee('Assigned abstracts')->assertDontSee('Decision queue')->assertDontSee('Registration &amp; payment', false);
    }

    public function test_an_admin_starts_in_administration_and_sees_only_its_menu(): void
    {
        $admin = $this->user(Role::Admin);

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.overview'));
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()
            ->assertSee('Users &amp; roles', false)->assertSee('Switch role')
            ->assertDontSee('Verification queue')->assertDontSee('Print queue')->assertDontSee('Decision queue');
    }

    public function test_switching_role_changes_the_menu_and_the_dashboard(): void
    {
        $person = $this->user(Role::Participant, Role::Reviewer);

        $this->actingAs($person)->get(route('dashboard'))->assertSee('Assigned abstracts')->assertDontSee('My abstracts');

        $this->actingAs($person)->post(route('workspace.switch'), ['role' => Role::Participant->value])
            ->assertRedirect(route('dashboard'));
        $this->actingAs($person)->get(route('dashboard'))->assertOk()
            ->assertSee('My abstracts')->assertDontSee('Assigned abstracts');

        $this->actingAs($person)->post(route('workspace.switch'), ['role' => Role::Reviewer->value]);
        $this->actingAs($person)->get(route('dashboard'))->assertSee('Assigned abstracts')->assertDontSee('My abstracts');
    }

    public function test_an_admin_can_switch_into_every_staff_area(): void
    {
        $admin = $this->user(Role::Admin);

        $this->actingAs($admin)->post(route('workspace.switch'), ['role' => Role::FinanceOfficer->value]);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertSee('Verification queue')->assertDontSee('Users &amp; roles', false);

        $this->actingAs($admin)->post(route('workspace.switch'), ['role' => Role::RegistrationOfficer->value]);
        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('desk.index'));

        $this->actingAs($admin)->post(route('workspace.switch'), ['role' => Role::Photographer->value]);
        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('media.albums.index'));

        $this->actingAs($admin)->post(route('workspace.switch'), ['role' => Role::ScientificAdmin->value]);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Decision queue');

        // Admins do not review, judge or register as part of the role.
        $this->actingAs($admin)->post(route('workspace.switch'), ['role' => Role::Reviewer->value])->assertForbidden();
        $this->actingAs($admin)->post(route('workspace.switch'), ['role' => Role::Judge->value])->assertForbidden();
    }

    public function test_nobody_can_switch_into_a_role_they_do_not_hold(): void
    {
        $participant = $this->user(Role::Participant);

        $this->actingAs($participant)->post(route('workspace.switch'), ['role' => Role::Admin->value])->assertForbidden();
        $this->actingAs($participant)->post(route('workspace.switch'), ['role' => Role::FinanceOfficer->value])->assertForbidden();
        $this->actingAs($participant)->post(route('workspace.switch'), ['role' => 'superuser'])->assertSessionHasErrors('role');
        $this->actingAs($participant)->get(route('finance.payments.index'))->assertForbidden();
        $this->actingAs($participant)->get(route('dashboard'))->assertSee('My abstracts');
    }

    public function test_opening_another_roles_screen_moves_the_person_into_that_role(): void
    {
        $person = $this->user(Role::Participant, Role::Reviewer);
        $this->actingAs($person)->post(route('workspace.switch'), ['role' => Role::Participant->value]);

        // From a review notification, say.
        $this->actingAs($person)->get(route('reviews.index'))->assertOk()->assertSee('Assigned abstracts')->assertDontSee('My abstracts');
        $this->actingAs($person)->get(route('dashboard'))->assertSee('Assigned abstracts');

        $this->actingAs($person)->get(route('abstracts.index'))->assertOk()->assertSee('My abstracts')->assertDontSee('Assigned abstracts');
    }

    public function test_a_withdrawn_role_drops_the_person_back_into_one_they_still_hold(): void
    {
        $person = $this->user(Role::Participant, Role::FinanceOfficer);
        $this->actingAs($person)->post(route('workspace.switch'), ['role' => Role::FinanceOfficer->value]);

        $person->removeRole(Role::FinanceOfficer->value);

        $this->actingAs($person->fresh())->get(route('dashboard'))->assertOk()
            ->assertSee('My abstracts')->assertDontSee('Verification queue');
    }

    public function test_search_follows_the_role_being_worked_in(): void
    {
        $admin = $this->user(Role::Admin);

        $this->actingAs($admin)->post(route('workspace.switch'), ['role' => Role::Photographer->value]);
        $this->actingAs($admin)->get(route('dashboard'));
        $this->actingAs($admin)->get(route('search', ['q' => 'anything']))->assertOk()
            ->assertSee('Search sessions…');
    }
}
