<?php

namespace App\Notifications;

use App\Models\FeeWaiver;
use App\Support\Summit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeeWaived extends Notification
{
    public function __construct(public FeeWaiver $waiver) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summit = app(Summit::class);
        $registration = $this->waiver->registration;
        $mail = (new MailMessage)->greeting('Hello '.$notifiable->first_name.',');

        if ($registration->isFullyWaived()) {
            $mail->subject('Your registration fee has been waived · '.$summit->title())
                ->line('The organisers have waived your registration fee for the '.$summit->title().'. There is nothing to pay, and your registration '.$registration->reference.' is confirmed.')
                ->line('Your badge is ready to download. Bring it, printed or on your phone, to the registration desk.');

            if ($registration->needs_invitation_letter) {
                $mail->line('Your invitation letter for your visa application is also ready in the portal.');
            }

            return $mail->action('Download your badge', route('registration.show'));
        }

        return $mail->subject('Your registration fee has been reduced · '.$summit->title())
            ->line('The organisers have waived '.$this->waiver->formattedAmount().' of your registration fee for the '.$summit->title().'.')
            ->line('You now pay '.$registration->formattedDue().' instead of '.$registration->formattedAmount().'. Quote your reference '.$registration->reference.' with the payment, then upload the proof in the portal.')
            ->action('Pay the rest', route('registration.show'));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        $registration = $this->waiver->registration;

        return $registration->isFullyWaived()
            ? ['title' => 'Registration fee waived', 'body' => 'Your registration is confirmed. Your badge is ready to download.', 'url' => route('registration.badge.show'), 'icon' => 'check-circle', 'tone' => 'success']
            : ['title' => 'Registration fee reduced', 'body' => 'You now pay '.$registration->formattedDue().'.', 'url' => route('registration.show'), 'icon' => 'banknotes', 'tone' => 'info'];
    }
}
