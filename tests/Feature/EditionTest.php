<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Edition;
use App\Models\User;
use App\Services\EditionService;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** One summit a year: the next one starts fresh, and last year's records stay with last year. */
class EditionTest extends TestCase
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
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'venue' => 'JNICC', 'country' => 'Tanzania',
            'registration_open' => true, 'abstracts_open' => true, 'is_current' => true,
        ]);
        $this->edition->categories()->create(['name' => 'Professional', 'currency' => 'TZS', 'amount' => 250000, 'sort' => 0]);
        $this->edition->categories()->create(['name' => 'Student', 'currency' => 'TZS', 'amount' => 50000, 'is_student' => true, 'sort' => 1]);
        $this->edition->topics()->create(['name' => 'Home-Based Rehabilitation', 'code' => 'HBR', 'sort' => 0]);
        app(Summit::class)->refresh();

        $this->admin = User::factory()->create()->assignRole(Role::Admin->value);
    }

    private function register(): User
    {
        $user = User::factory()->create()->assignRole(Role::Participant->value);
        $this->actingAs($user)->post(route('registration.store'), [
            'category' => $this->edition->categories->first()->id,
            'institution' => 'MNH', 'profession' => 'Physiotherapist', 'confirm' => '1',
        ])->assertRedirect();

        return $user;
    }

    private function startNext(array $overrides = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.settings.next'), $overrides + [
            'next_year' => '2028', 'next_name' => 'Rehabilitation Summit', 'next_short_name' => 'Rehab Summit', 'next_ordinal' => '6th',
            'copy_categories' => '1', 'copy_topics' => '1', 'confirm_next' => '1',
        ]);
    }

    public function test_the_next_summit_starts_fresh_and_last_year_keeps_its_records(): void
    {
        $user = $this->register();

        $this->actingAs($this->admin)->get(route('admin.settings.edit'))->assertOk()
            ->assertSee('Start the 2028 summit')->assertSee('value="6th"', false);

        $this->startNext()->assertRedirect(route('admin.settings.edit'))->assertSessionHas('status');

        $next = Edition::where('year', 2028)->sole();
        $this->assertTrue($next->is_current);
        $this->assertFalse($this->edition->fresh()->is_current);
        $this->assertSame(1, Edition::where('is_current', true)->count());

        // Copied: fees and topics. Not copied: dates, venue, open switches.
        $this->assertSame(['Professional', 'Student'], $next->categories->pluck('name')->all());
        $this->assertSame('250000.00', $next->categories->first()->amount);
        $this->assertTrue($next->categories->last()->is_student);
        $this->assertSame(['HBR'], $next->topics->pluck('code')->all());
        $this->assertNull($next->start_date);
        $this->assertNull($next->venue);
        $this->assertFalse($next->registration_open);
        $this->assertFalse($next->abstracts_open);

        // Last year's registration stays with last year; the participant is not registered for 2028.
        $this->assertSame(1, $this->edition->registrations()->count());
        app(Summit::class)->refresh();
        $this->assertNull($user->fresh()->registrationFor(app(Summit::class)->edition()));
        $this->actingAs($user)->get(route('registration.show'))->assertOk();
    }

    public function test_nothing_is_copied_unless_asked(): void
    {
        $this->startNext(['copy_categories' => '0', 'copy_topics' => '0'])->assertRedirect();

        $next = Edition::where('year', 2028)->sole();
        $this->assertSame(0, $next->categories()->count());
        $this->assertSame(0, $next->topics()->count());
    }

    public function test_starting_the_next_summit_must_be_confirmed_and_the_year_must_be_new(): void
    {
        $this->startNext(['confirm_next' => '0'])->assertSessionHasErrors('confirm_next');
        $this->startNext(['next_year' => '2027'])->assertSessionHasErrors('next_year');

        $this->assertSame(1, Edition::count());
        $this->assertTrue($this->edition->fresh()->is_current);
    }

    public function test_the_year_of_a_summit_with_registrations_cannot_be_changed(): void
    {
        $settings = fn (int $year) => $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'year' => $year, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'categories' => $this->edition->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'currency' => $c->currency, 'amount' => $c->amount])->all(),
            'topics' => [['id' => $this->edition->topics->first()->id, 'name' => 'Home-Based Rehabilitation', 'code' => 'HBR']],
        ]);

        // Before anyone registers, a typo in the year can still be fixed.
        $settings(2026)->assertSessionHasNoErrors();
        $this->assertSame(2026, $this->edition->fresh()->year);
        $settings(2027)->assertSessionHasNoErrors();

        $this->register();
        $this->actingAs($this->admin)->get(route('admin.settings.edit'))->assertSee('Fixed: people have registered.');
        $settings(2028)->assertSessionHasErrors('year');
        $this->assertSame(2027, $this->edition->fresh()->year);
    }

    public function test_an_admin_can_go_back_to_another_year(): void
    {
        $this->startNext()->assertRedirect();

        $this->actingAs($this->admin)->put(route('admin.settings.current', $this->edition))->assertRedirect(route('admin.settings.edit'));

        $this->assertTrue($this->edition->fresh()->is_current);
        $this->assertSame(1, Edition::where('is_current', true)->count());
    }

    public function test_only_admins_start_a_summit(): void
    {
        $finance = User::factory()->create()->assignRole(Role::FinanceOfficer->value);

        $this->actingAs($finance)->post(route('admin.settings.next'), ['next_year' => '2028'])->assertForbidden();
        $this->actingAs($finance)->put(route('admin.settings.current', $this->edition))->assertForbidden();
        $this->assertSame(1, Edition::count());
    }

    public function test_the_next_ordinal(): void
    {
        $this->assertSame('6th', EditionService::nextOrdinal('5th'));
        $this->assertSame('2nd', EditionService::nextOrdinal('1st'));
        $this->assertSame('3rd', EditionService::nextOrdinal('2nd'));
        $this->assertSame('11th', EditionService::nextOrdinal('10th'));
        $this->assertSame('21st', EditionService::nextOrdinal('20th'));
        $this->assertSame('', EditionService::nextOrdinal('Fifth'));
    }
}
