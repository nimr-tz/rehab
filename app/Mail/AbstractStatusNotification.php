<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AbstractSubmission;

class AbstractStatusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $status; // 'accepted', 'rejected'
    public $message; // Optional custom message

    /**
     * Number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * Number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    public function __construct(AbstractSubmission $abstract, string $status, string $message = null)
    {
        $this->abstract = $abstract;
        $this->status = $status;
        $this->message = $message;
        
        // Very high priority for status notifications
        $this->onQueue('emails-critical');
    }

    public function envelope(): Envelope
    {
        $statusText = ucfirst($this->status);
        return new Envelope(
            subject: "Abstract {$statusText} - " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abstract-status-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
