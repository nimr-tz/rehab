<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AbstractSubmission;

class ConferenceCodeAssignment extends Mailable implements ShouldQueue
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
        
        // High priority for code assignments
        $this->onQueue('emails-high-priority');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Conference Code Assigned - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.conference-code-assignment',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
