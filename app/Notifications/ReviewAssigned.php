<?php

namespace App\Notifications;

use App\Models\ReviewAssignment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewAssigned extends Notification
{
    public function __construct(public ReviewAssignment $assignment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $abstract = $this->assignment->abstract;
        $mail = $this->assignment->round === 2
            ? (new MailMessage)
                ->subject('Revised abstract to review · '.$abstract->blindId())
                ->greeting('Hello '.$notifiable->first_name.',')
                ->line('The authors of abstract '.$abstract->blindId().' have revised it after the first review. Please review the revised version.')
                ->line('You will see the previous and revised text side by side, with the authors\' response. The review stays double-blind.')
            : (new MailMessage)
                ->subject('New abstract to review · '.$abstract->blindId())
                ->greeting('Hello '.$notifiable->first_name.',')
                ->line('The scientific committee has asked you to review abstract '.$abstract->blindId().' under '.$abstract->topic->name.'.')
                ->line('The review is double-blind: you will not see who wrote it.');

        if ($this->assignment->due_on) {
            $mail->line('Please complete it by '.$this->assignment->due_on->format('j F Y').'.');
        }

        return $mail->action('Start the review', route('reviews.edit', $this->assignment));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->assignment->round === 2 ? 'Revised abstract to review' : 'New abstract to review',
            'body' => $this->assignment->abstract->blindId().' · '.$this->assignment->abstract->topic->name,
            'url' => route('reviews.edit', $this->assignment),
            'icon' => 'star',
            'tone' => 'warning',
        ];
    }
}
