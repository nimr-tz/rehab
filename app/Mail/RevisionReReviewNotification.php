<?php

namespace App\Mail;

use App\Models\AbstractSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RevisionReReviewNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $reviewer;
    public $reviewerPosition;

    public function __construct(AbstractSubmission $abstract, User $reviewer, $reviewerPosition = 1)
    {
        $this->abstract = $abstract;
        $this->reviewer = $reviewer;
        $this->reviewerPosition = $reviewerPosition;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⏰ URGENT: Revision Re-Review Required (24h) - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.revision-rereview-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
