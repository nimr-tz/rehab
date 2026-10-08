<?php

namespace App\Notifications;

use App\Models\FeeWaiver;
use App\Support\Summit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaiverWithdrawn extends Notification
{
    public function __construct(public FeeWaiver $waiver) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $registration = $this->waiver->registration;

        return (new MailMessage)
            ->subject('Your fee waiver has been withdrawn · '.app(Summit::class)->title())
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('The waiver on your registration fee has been withdrawn: '.$this->waiver->revoke_reason)
            ->line('To keep your place, please pay '.$registration->formattedDue().' quoting your reference '.$registration->reference.', then upload the proof in the portal.')
            ->action('Pay your registration fee', route('registration.show'));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Fee waiver withdrawn',
            'body' => 'Please pay '.$this->waiver->registration->formattedDue().' to keep your place.',
            'url' => route('registration.show'),
            'icon' => 'banknotes',
            'tone' => 'warning',
        ];
    }
}
