<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Responses\PasswordResetLinkResponse;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** @return array<string, mixed> */
    private function registration(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Dr',
            'first_name' => 'Amina',
            'last_name' => 'Mussa',
            'email' => 'Amina.Mussa@Example.com',
            'phone' => '+255 712 345 678',
            'country' => 'TZ',
            'password' => 'rehab2027',
            'password_confirmation' => 'rehab2027',
            'terms' => '1',
        ], $overrides);
    }

    public function test_registration_page_lists_tanzania_first(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create your account')
            ->assertSeeInOrder(['Country', 'Tanzania', 'Afghanistan']);
    }

    public function test_a_participant_can_register_and_must_confirm_their_email(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->registration())
            ->assertRedirect('/dashboard');

        $user = User::sole();
        $this->assertSame('amina.mussa@example.com', $user->email);
        $this->assertSame('Dr Amina Mussa', $user->name);
        $this->assertSame('TZ', $user->country);
        $this->assertTrue($user->hasRole(Role::Participant->value));
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSee('amina.mussa@example.com');
    }

    public function test_registration_validates_the_details(): void
    {
        $this->from(route('register'))
            ->post(route('register'), $this->registration([
                'phone' => 'call me',
                'country' => 'XX',
                'terms' => null,
                'password' => 'short',
                'password_confirmation' => 'short',
            ]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['phone', 'country', 'terms', 'password']);

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_an_email_can_only_register_once_regardless_of_case(): void
    {
        User::factory()->create(['email' => 'amina.mussa@example.com']);

        $this->post(route('register'), $this->registration())
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_the_confirmation_link_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect('/dashboard?verified=1');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get(route('dashboard'))->assertOk()->assertSee('Your account is ready');
    }

    public function test_a_verified_user_can_sign_in_and_out(): void
    {
        $user = User::factory()->create(['email' => 'amina@example.com']);

        $this->post(route('login'), ['email' => 'AMINA@example.com', 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'amina@example.com']);

        $this->from(route('login'))
            ->post(route('login'), ['email' => 'amina@example.com', 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sign_in_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'amina@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), ['email' => 'amina@example.com', 'password' => 'wrong']);
        }

        $this->post(route('login'), ['email' => 'amina@example.com', 'password' => 'password'])
            ->assertStatus(429);
        $this->assertGuest();
    }

    public function test_the_dashboard_requires_sign_in(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_password_can_be_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'amina@example.com']);

        $this->post(route('password.email'), ['email' => 'amina@example.com'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
                ->assertOk()
                ->assertSee('Choose a new password');

            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'newpass2027',
                'password_confirmation' => 'newpass2027',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->post(route('login'), ['email' => 'amina@example.com', 'password' => 'newpass2027'])
            ->assertRedirect('/dashboard');
    }

    public function test_the_reset_form_does_not_reveal_whether_an_email_is_registered(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'amina@example.com']);

        $known = $this->post(route('password.email'), ['email' => 'amina@example.com']);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $known->assertSessionHas('status', PasswordResetLinkResponse::MESSAGE)->assertSessionHasNoErrors();
        $unknown->assertSessionHas('status', PasswordResetLinkResponse::MESSAGE)->assertSessionHasNoErrors();
        Notification::assertCount(1);
    }
}
