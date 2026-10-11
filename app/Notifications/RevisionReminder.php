<?php

namespace App\Notifications;

use App\Models\AbstractSubmission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevisionReminder extends Notification
{
    public function __construct(public AbstractSubmission $abstract) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Revision due soon · '.$this->abstract->title)
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('A reminder that the revised version of "'.$this->abstract->title.'" is due on '.$this->abstract->revision_due_on->format('j F Y').'.')
            ->line('The reviewers\' comments are in the portal. Send the revised text with a short note on what you changed.')
            ->action('Revise your abstract', route('abstracts.revision.edit', $this->abstract));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Revision due soon',
            'body' => '"'.$this->abstract->title.'" · due '.$this->abstract->revision_due_on->format('j M'),
            'url' => route('abstracts.revision.edit', $this->abstract),
            'icon' => 'clock',
            'tone' => 'warning',
        ];
    }
}
