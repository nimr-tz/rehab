<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AbstractSubmission;

class SessionAssignmentNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $abstract;

    /**
     * Number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * Number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    public function __construct(AbstractSubmission $abstract)
    {
        $this->abstract = $abstract;
        
        // High priority for session assignments
        $this->onQueue('emails-high-priority');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Session Assignment - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.session-assignment-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
} 