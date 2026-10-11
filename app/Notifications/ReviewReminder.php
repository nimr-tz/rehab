<?php

namespace App\Notifications;

use App\Models\ReviewAssignment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewReminder extends Notification
{
    public function __construct(public ReviewAssignment $assignment, public bool $overdue = false) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $abstract = $this->assignment->abstract;
        $due = $this->assignment->due_on->format('j F Y');

        return (new MailMessage)
            ->subject(($this->overdue ? 'Review overdue · ' : 'Review due soon · ').$abstract->blindId())
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line($this->overdue
                ? 'Your review of abstract '.$abstract->blindId().' was due on '.$due.'. The committee cannot decide on it until your review is in.'
                : 'A reminder that your review of abstract '.$abstract->blindId().' is due on '.$due.'.')
            ->line('If you cannot review it, please tell the scientific committee so they can ask someone else.')
            ->action('Open the review', route('reviews.edit', $this->assignment));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->overdue ? 'Review overdue' : 'Review due soon',
            'body' => $this->assignment->abstract->blindId().' · due '.$this->assignment->due_on->format('j M'),
            'url' => route('reviews.edit', $this->assignment),
            'icon' => 'clock',
            'tone' => $this->overdue ? 'danger' : 'warning',
        ];
    }
}
