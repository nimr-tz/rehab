<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Support\Summit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationConfirmed extends Notification
{
    public function __construct(public Registration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summit = app(Summit::class);

        $mail = (new MailMessage)
            ->subject('Your registration is confirmed · '.$summit->title())
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('Your payment has been verified and your registration '.$this->registration->reference.' for the '.$summit->title().' is confirmed.')
            ->line('Your badge is ready to download. Bring it, printed or on your phone, to the registration desk.');

        if ($this->registration->needs_invitation_letter) {
            $mail->line('Your invitation letter for your visa application is also ready in the portal.');
        }

        return $mail->action('Download your badge', route('registration.show'));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Registration confirmed',
            'body' => 'Your badge'.($this->registration->needs_invitation_letter ? ' and invitation letter are' : ' is').' ready to download.',
            'url' => route('registration.badge.show'),
            'icon' => 'check-circle',
            'tone' => 'success',
        ];
    }
}
