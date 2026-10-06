<?php

namespace App\Notifications;

use App\Models\PhotoRemovalRequest;
use Illuminate\Notifications\Notification;

/** Tells admins, under the bell, that someone asked for a photo to come down. */
class PhotoRemovalRequested extends Notification
{
    public function __construct(public PhotoRemovalRequest $removal) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Photo removal requested',
            'body' => $this->removal->name.' asked for a photo in "'.$this->removal->album_title.'" to be taken down.',
            'url' => route('media.removal-requests.index'),
            'icon' => 'eye-slash',
            'tone' => 'warning',
        ];
    }
}
