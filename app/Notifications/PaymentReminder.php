<?php

namespace App\Notifications;

use App\Models\Registration;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminder extends Notification
{
    public function __construct(public Registration $registration, public bool $beforeSummit = false) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $edition = $this->registration->edition;

        $mail = (new MailMessage)
            ->subject('Your registration fee is still unpaid · '.$edition->short_name.' '.$edition->year)
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('You registered for the '.$edition->name.' '.$edition->year.', but your fee of '.$this->registration->formattedDue().' has not been paid yet.');

        if ($this->beforeSummit && $edition->start_date) {
            $mail->line('The summit starts on '.$edition->start_date->format('j F Y').'. Paying now means your badge is ready and you skip the payment queue at the venue.');
        }

        return $mail
            ->line('Your payment reference is '.$this->registration->reference.'.')
            ->action('Pay the fee', route('registration.show'));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Registration fee unpaid',
            'body' => $this->registration->formattedDue().' · reference '.$this->registration->reference,
            'url' => route('registration.show'),
            'icon' => 'banknotes',
            'tone' => 'warning',
        ];
    }
}
