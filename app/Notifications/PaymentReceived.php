<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $registration = $this->payment->registration;

        return (new MailMessage)
            ->subject('We received your payment details · '.$registration->reference)
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('Thank you. We received your payment details for registration '.$registration->reference.'.')
            ->line('Amount: '.$this->payment->formattedAmount().' via '.$this->payment->channel().', reference '.$this->payment->transaction_reference.'.')
            ->line('A finance officer will confirm it, usually within one working day. We will email you as soon as it is verified.')
            ->action('View your registration', route('registration.show'));
    }
}
