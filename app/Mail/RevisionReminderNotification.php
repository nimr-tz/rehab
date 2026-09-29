<?php

namespace App\Mail;

use App\Models\AbstractSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RevisionReminderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $revisionMessage;

    public $tries = 3;

    public $timeout = 120;

    public function __construct(AbstractSubmission $abstract, string $message = null)
    {
        $this->abstract = $abstract;
        $this->revisionMessage = $message;

        $this->onQueue('emails-high-priority');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Please Submit Your Revised Abstract - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.revision-reminder-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
