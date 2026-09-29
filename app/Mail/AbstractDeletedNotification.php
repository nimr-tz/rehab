<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class AbstractDeletedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $abstractTitle;

    /**
     * Number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * Number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    public function __construct(User $user, string $abstractTitle)
    {
        $this->user = $user;
        $this->abstractTitle = $abstractTitle;
        
        // ✅ REMOVE: Queuing for immediate delivery in development
        // $this->onQueue('emails-low-priority');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Abstract Deleted - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abstract-deleted-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
} 