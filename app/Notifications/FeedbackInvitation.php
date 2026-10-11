<?php

namespace App\Notifications;

use App\Models\Registration;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The day after the summit: thank you, and a few minutes of feedback. */
class FeedbackInvitation extends Notification
{
    public function __construct(public Registration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $edition = $this->registration->edition;

        return (new MailMessage)
            ->subject('Thank you for coming · tell us how it went')
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('Thank you for joining the '.$edition->name.' '.$edition->year.'.')
            ->line('Please take a few minutes to tell us how it went. Your answers are anonymous and shape the next summit.')
            ->line('Your certificate of attendance is ready to download in the portal too.')
            ->action('Give your feedback', route('feedback.create'));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Tell us how it went',
            'body' => 'A few minutes of feedback, anonymous.',
            'url' => route('feedback.create'),
            'icon' => 'clipboard',
            'tone' => 'info',
        ];
    }
}
