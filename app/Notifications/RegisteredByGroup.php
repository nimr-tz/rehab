<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Support\Summit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Someone a group leader registered: who registered them, and (for a new account) how to sign in. */
class RegisteredByGroup extends Notification
{
    public function __construct(public Registration $registration, private ?string $token = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summit = app(Summit::class);
        $group = $this->registration->group;

        $mail = (new MailMessage)
            ->subject('You are registered for the '.$summit->title())
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line($group->leader->name.' has registered you for the '.$summit->title().' as part of '.$group->name.', reference '.$this->registration->reference.'.')
            ->line('They pay the fee for the whole group. You will receive an email when it is confirmed, and can then download your badge.');

        if (! $this->token) {
            return $mail->action('Open the portal', route('registration.show'));
        }

        $minutes = config('auth.passwords.users.expire');

        return $mail
            ->line('We created a portal account for you with this email address. Set a password to sign in.')
            ->action('Set your password', route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line("The link works for {$minutes} minutes. After that, choose \"Forgot password\" on the sign-in page and enter this email address.");
    }
}
