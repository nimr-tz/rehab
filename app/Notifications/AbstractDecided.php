<?php

namespace App\Notifications;

use App\Enums\AbstractStatus;
use App\Models\AbstractSubmission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbstractDecided extends Notification
{
    public function __construct(public AbstractSubmission $abstract) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $accepted = $this->abstract->status === AbstractStatus::Accepted;

        $mail = (new MailMessage)
            ->subject(($accepted ? 'Abstract accepted' : 'Decision on your abstract').' · '.$this->abstract->title)
            ->greeting('Hello '.$notifiable->first_name.',');

        if ($accepted) {
            $mail->line('Congratulations. Your abstract "'.$this->abstract->title.'" has been accepted as a '.strtolower($this->abstract->decision_type->label()).'.')
                ->line('Its conference code is '.$this->abstract->code.'. Please use it in all correspondence and on your slides or poster.');
        } else {
            $mail->line('Thank you for submitting "'.$this->abstract->title.'". After review, the scientific committee was not able to accept it this year.');
        }

        if ($this->abstract->decision_note) {
            $mail->line('Note from the committee: '.$this->abstract->decision_note);
        }

        return $mail->line('The reviewers\' comments are available in the portal.')
            ->action('View the decision', route('abstracts.show', $this->abstract));
    }
}
