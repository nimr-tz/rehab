<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AbstractSubmission;
use App\Models\User;

class PresentationReminderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $user;
    public $presentationMode;
    public $dashboardUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(AbstractSubmission $abstract)
    {
        $this->abstract = $abstract;
        $this->user = $abstract->user;
        $this->presentationMode = $abstract->presentation_mode ? strtolower($abstract->presentation_mode) : null;
        $this->dashboardUrl = route('presentations.show', $abstract->id);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->presentationMode
            ? 'Reminder: Please Upload Your ' . ucfirst($this->presentationMode) . ' Materials - ' . config('conference.short_name') . ' ' . config('conference.year')
            : 'Reminder: Please Upload Your Presentation Materials - ' . config('conference.short_name') . ' ' . config('conference.year');

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.presentation-reminder',
            with: [
                'user' => $this->user,
                'abstract' => $this->abstract,
                'presentationMode' => $this->presentationMode,
                'dashboardUrl' => $this->dashboardUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
