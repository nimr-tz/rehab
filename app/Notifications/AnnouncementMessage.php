<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** An announcement from the organisers. People with an account also see it under the bell. */
class AnnouncementMessage extends Notification
{
    public function __construct(public Announcement $announcement, public string $name, public bool $test = false) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(($this->test ? '[Test] ' : '').$this->announcement->subject)
            ->greeting('Hello '.$this->name.',');

        foreach ($this->announcement->paragraphs() as $paragraph) {
            $mail->line($paragraph);
        }

        return $mail->action('Open the portal', route('dashboard'));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->announcement->subject,
            'body' => Str::limit(implode(' ', $this->announcement->paragraphs()), 140),
            'url' => route('dashboard'),
            'icon' => 'bell',
            'tone' => 'info',
        ];
    }
}
