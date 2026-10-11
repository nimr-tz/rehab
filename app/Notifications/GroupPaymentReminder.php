<?php

namespace App\Notifications;

use App\Models\GroupRegistration;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupPaymentReminder extends Notification
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
            ->subject('Your group\'s fee is still unpaid · '.$this->group->reference)
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('You registered '.$this->group->name.' ('.$count.' '.str('person')->plural($count).'), but the fee of '.$this->group->formattedDue().' has not been paid yet.')
            ->line('Nobody in the group is confirmed until it is paid. Your reference is '.$this->group->reference.'.')
            ->action('Pay for the group', route('groups.show', $this->group));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Group fee unpaid',
            'body' => $this->group->formattedDue().' · reference '.$this->group->reference,
            'url' => route('groups.show', $this->group),
            'icon' => 'banknotes',
            'tone' => 'warning',
        ];
    }
}
