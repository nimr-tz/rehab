<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejected extends Notification
{
    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We could not verify your payment · '.$this->payment->registration->reference)
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('We could not verify the payment you submitted (reference '.$this->payment->transaction_reference.').')
            ->line('Reason: '.$this->payment->rejection_reason)
            ->line('Please check the details and submit the payment again from your registration page.')
            ->action('Submit the payment again', route('registration.show'));
    }
}
