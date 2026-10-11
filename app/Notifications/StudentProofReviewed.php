<?php

namespace App\Notifications;

use App\Enums\StudentProofStatus;
use App\Models\Registration;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentProofReviewed extends Notification
{
    public function __construct(public Registration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function approved(): bool
    {
        return $this->registration->student_status === StudentProofStatus::Approved;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Hello '.$notifiable->first_name.',');

        if ($this->approved()) {
            return $mail
                ->subject('Student rate approved · '.$this->registration->reference)
                ->line('We have checked your proof of enrolment, and the student rate applies to your registration.')
                ->line('You can now pay the fee of '.$this->registration->formattedDue().' with reference '.$this->registration->reference.'.')
                ->action('Pay the fee', route('registration.show'));
        }

        return $mail
            ->subject('We could not accept your student proof · '.$this->registration->reference)
            ->line('We could not accept the proof of enrolment you uploaded.')
            ->line('Reason: '.$this->registration->student_rejection_reason)
            ->line('Upload another document, or change to a category that does not need one.')
            ->action('Update your registration', route('registration.show'));
    }

    /** Shown under the bell in the portal. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->approved() ? 'Student rate approved' : 'Student proof not accepted',
            'body' => $this->approved() ? 'You can now pay '.$this->registration->formattedDue().'.' : (string) $this->registration->student_rejection_reason,
            'url' => route('registration.show'),
            'icon' => $this->approved() ? 'check-circle' : 'x-circle',
            'tone' => $this->approved() ? 'success' : 'danger',
        ];
    }
}
