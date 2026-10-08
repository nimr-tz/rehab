<?php

namespace App\Notifications;

use App\Models\AbstractSubmission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevisionRequested extends Notification
{
    public function __construct(public AbstractSubmission $abstract) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Revisions requested · '.$this->abstract->title)
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('The scientific committee has reviewed your abstract "'.$this->abstract->title.'" and asks you to revise it before a final decision.')
            ->line('The reviewers\' comments are in the portal. Revise the text and tell the reviewers what you changed by '.$this->abstract->revision_due_on->format('j F Y').'.');

        if ($this->abstract->revision_note) {
            $mail->line('Note from the committee: '.$this->abstract->revision_note);
        }

        return $mail->action('Revise your abstract', route('abstracts.revision.edit', $this->abstract));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Revisions requested',
            'body' => '"'.$this->abstract->title.'" · due '.$this->abstract->revision_due_on->format('j M'),
            'url' => route('abstracts.revision.edit', $this->abstract),
            'icon' => 'pencil',
            'tone' => 'warning',
        ];
    }
}
