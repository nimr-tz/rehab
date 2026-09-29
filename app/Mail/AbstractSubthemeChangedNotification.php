<?php

namespace App\Mail;

use App\Models\AbstractSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbstractSubthemeChangedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AbstractSubmission $abstract,
        public string $oldSubtheme,
        public string $newSubtheme
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Abstract Subtheme Updated - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abstract-subtheme-changed-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
