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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $abstract = $this->assignment->abstract;
        $mail = (new MailMessage)
            ->subject('New abstract to review · '.$abstract->blindId())
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('The scientific committee has asked you to review abstract '.$abstract->blindId().' under '.$abstract->topic->name.'.')
            ->line('The review is double-blind: you will not see who wrote it.');

        if ($this->assignment->due_on) {
            $mail->line('Please complete it by '.$this->assignment->due_on->format('j F Y').'.');
        }

        return $mail->action('Start the review', route('reviews.edit', $this->assignment));
    }
}
