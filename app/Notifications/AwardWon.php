<?php

namespace App\Notifications;

use App\Models\AwardEntry;
use App\Support\Summit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells a winner, or the author of a winning abstract, when the award is announced. */
class AwardWon extends Notification
{
    public function __construct(public AwardEntry $entry) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $category = $this->entry->category;
        $place = $category->placeLabel($this->entry->place);
        $summit = app(Summit::class);

        $mail = (new MailMessage)
            ->subject('Congratulations: '.$category->name.' · '.$summit->shortTitle())
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('Congratulations. The '.$summit->title().' has announced its awards, and '
                .($this->entry->user_id === $notifiable->id ? 'you have' : $this->entry->name.' has')
                .' received the '.$category->name.($category->places > 1 ? ' ('.strtolower($place).')' : '').'.');

        if ($this->entry->abstract) {
            $mail->line('Awarded for "'.$this->entry->abstract->title.'" ('.$this->entry->abstract->code.').');
        }

        return $mail->line('Your award certificate is ready to download in the portal.')
            ->action('Download your certificate', route('awards.mine'));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        $category = $this->entry->category;

        return [
            'title' => $category->placeLabel($this->entry->place).' · '.$category->name,
            'body' => $this->entry->abstract ? '"'.$this->entry->abstract->title.'"' : 'Your certificate is ready to download.',
            'url' => route('awards.mine'),
            'icon' => 'star',
            'tone' => 'success',
        ];
    }
}
