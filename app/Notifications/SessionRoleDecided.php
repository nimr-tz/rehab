<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\SessionRoleApplication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionRoleDecided extends Notification
{
    public function __construct(public SessionRoleApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function accepted(): bool
    {
        return $this->application->status === ApplicationStatus::Accepted;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $role = strtolower($this->application->role->label());
        $mail = (new MailMessage)->greeting('Hello '.$notifiable->first_name.',');

        if ($this->accepted()) {
            $session = $this->application->session;
            $mail->subject('You are '.$role.' of '.$session->title)
                ->line('Thank you for offering to help. The scientific committee has made you '.$role.' of:')
                ->line($session->title.', '.$session->starts_at->format('l j F, H:i').'–'.$session->ends_at->format('H:i').($session->hall ? ', '.$session->hall : '').'.')
                ->line($this->application->role->duty());
        } else {
            $mail->subject('Your application to be a '.$role)
                ->line('Thank you for offering to be a '.$role.'. The committee had more offers than sessions, and has not been able to give you one this time.');
        }

        if ($this->application->decision_note) {
            $mail->line('Note from the committee: '.$this->application->decision_note);
        }

        return $mail->action('Open your session roles', route('session-roles.index'));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->accepted() ? 'You are '.strtolower($this->application->role->label()).' of a session' : 'Session role application',
            'body' => $this->accepted() ? $this->application->session->title : 'Not selected this time.',
            'url' => route('session-roles.index'),
            'icon' => $this->accepted() ? 'check-circle' : 'info',
            'tone' => $this->accepted() ? 'success' : 'info',
        ];
    }
}
