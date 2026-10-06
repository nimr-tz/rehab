<?php

namespace App\Notifications;

use App\Models\PhotoRemovalRequest;
use App\Support\Summit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Emails the person who asked for a photo to be removed with the outcome. */
class PhotoRemovalDecided extends Notification
{
    public function __construct(public PhotoRemovalRequest $removal) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your photo removal request')
            ->greeting('Hello '.$this->removal->name.',');

        if ($this->removal->outcome === 'removed') {
            return $mail
                ->line('You asked us to remove a photo from the "'.$this->removal->album_title.'" album in the summit gallery.')
                ->line('The photo has been taken down and deleted from our servers.');
        }

        return $mail
            ->line('You asked us to remove a photo from the "'.$this->removal->album_title.'" album in the summit gallery.')
            ->line('After reviewing it, the organisers have decided to keep the photo in the gallery.')
            ->line('If you would like to discuss this, write to '.app(Summit::class)->get('contact_email').'.');
    }
}
