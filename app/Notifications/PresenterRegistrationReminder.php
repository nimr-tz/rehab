<?php

namespace App\Notifications;

use App\Models\AbstractSubmission;
use App\Services\PresenterService;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The presenter of an accepted abstract has not yet registered or paid. */
class PresenterRegistrationReminder extends Notification
{
    public function __construct(public AbstractSubmission $abstract, public string $name, public string $state) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $edition = $this->abstract->edition;
        $mail = (new MailMessage)
            ->subject('Please register to present '.$this->abstract->code)
            ->greeting('Hello '.$this->name.',')
            ->line('You are the presenter of "'.$this->abstract->title.'" ('.$this->abstract->code.'), accepted for the '.$edition->name.' '.$edition->year.'.');

        if ($session = $this->abstract->sessions->first()) {
            $mail->line('It is scheduled in '.$session->title.', '.$session->starts_at->format('l j F, H:i').'.');
        }

        $mail->line($this->state === PresenterService::NOT_REGISTERED
            ? 'Presenters register for the summit like every participant. Please register and pay the fee so that your presentation stays in the programme.'
            : 'Your registration fee has not been paid yet. Please pay it so that your presentation stays in the programme.');

        return $mail->action($this->state === PresenterService::NOT_REGISTERED ? 'Register for the summit' : 'Pay the fee', $notifiable instanceof AnonymousNotifiable ? route('register') : route('registration.show'));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Register to present '.$this->abstract->code,
            'body' => $this->state === PresenterService::NOT_REGISTERED ? 'Please register for the summit.' : 'Please pay your registration fee.',
            'url' => route('registration.show'),
            'icon' => 'ticket',
            'tone' => 'warning',
        ];
    }
}
