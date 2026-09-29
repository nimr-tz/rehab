<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AbstractSubmission;

class DraftSavedNotification extends Mailable
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
        // $this->onQueue('emails-low-priority');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Abstract Draft Saved - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.draft-saved-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
} 