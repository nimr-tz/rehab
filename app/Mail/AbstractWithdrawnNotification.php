<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AbstractSubmission;

class AbstractWithdrawnNotification extends Mailable
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
        
        // ✅ REMOVE: Queuing for immediate delivery in development
        // $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Abstract Withdrawn - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abstract-withdrawn-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
} 