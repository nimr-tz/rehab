<?php

namespace App\Notifications;

use App\Models\GroupRegistration;
use App\Support\Summit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The group leader: the payment is verified and every member is confirmed. */
class GroupConfirmed extends Notification
{
    public function __construct(public GroupRegistration $group) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->group->members()->count();

        return (new MailMessage)
            ->subject('Your group is confirmed · '.$this->group->reference)
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('The payment for '.$this->group->name.' ('.$this->group->reference.') has been verified, and all '.$count.' '.str('member')->plural($count).' are confirmed for the '.app(Summit::class)->title().'.')
            ->line('Each member has been emailed and can download their own badge from the portal.')
            ->action('View the group', route('groups.show', $this->group));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Group confirmed',
            'body' => $this->group->name.' · '.$this->group->reference,
            'url' => route('groups.show', $this->group),
            'icon' => 'check-circle',
            'tone' => 'success',
        ];
    }
}
