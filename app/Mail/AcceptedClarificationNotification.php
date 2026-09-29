<?php

namespace App\Mail;

use App\Models\AbstractSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AcceptedClarificationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AbstractSubmission $abstract)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Presentation Guidance for Your Accepted Abstract - ' . config('conference.short_name') . ' ' . config('conference.year'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.accepted-clarification',
            with: [
                'abstract' => $this->abstract,
                'user' => $this->abstract->user,
                'feedbackItems' => $this->abstract->getAuthorVisibleFeedbackItems(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
