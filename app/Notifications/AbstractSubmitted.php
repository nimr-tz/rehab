<?php

namespace App\Notifications;

use App\Models\AbstractSubmission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbstractSubmitted extends Notification
{
    public function __construct(public AbstractSubmission $abstract) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Abstract received · '.$this->abstract->title)
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('We received your abstract "'.$this->abstract->title.'".')
            ->line('It will be reviewed double-blind by the scientific committee. You can still edit or withdraw it until review starts.')
            ->action('View your abstract', route('abstracts.show', $this->abstract));
    }
}
