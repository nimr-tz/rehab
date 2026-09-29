<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AbstractSubmission;

class RevisionRequestedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $revisionMessage;

    /**
     * Number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * Number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    public function __construct(AbstractSubmission $abstract, string $message = null)
    {
        $this->abstract = $abstract;
        $this->revisionMessage = $message;
        
        // High priority for revision requests
        $this->onQueue('emails-high-priority');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Revision Requested - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.revision-requested-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
} 