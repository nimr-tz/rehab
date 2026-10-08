<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Support\Summit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Someone the registration desk registered at the venue: their account, and how to sign in. */
class RegisteredAtDesk extends Notification
{
    public function __construct(public Registration $registration, private string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summit = app(Summit::class);
        $minutes = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Welcome to the '.$summit->title())
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('The registration desk has registered you for the '.$summit->title().', reference '.$this->registration->reference.'.')
            ->line('We created a portal account for you with this email address. Set a password to sign in: you will find your badge, the programme and, after the summit, your CPD certificate there.')
            ->action('Set your password', route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line("The link works for {$minutes} minutes. After that, choose \"Forgot password\" on the sign-in page and enter this email address.");
    }
}
