<?php

namespace App\Mail;

use App\Models\AbstractSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbstractDecisionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $decision;
    public $reviews;
    public $template;

    /**
     * Create a new message instance.
     */
    public function __construct(AbstractSubmission $abstract, string $decision, $reviews, string $template)
    {
        $this->abstract = $abstract;
        $this->decision = $decision;
        $this->reviews = $reviews;
        $this->template = $template;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->decision) {
            'accepted', 'accept_oral', 'accept_poster' => 'Abstract Accepted',
            'reject' => 'Abstract Decision - Thank You for Your Submission',
            'minor_revisions' => 'Minor Revisions Required',
            'major_revisions' => 'Major Revisions Required',
            default => 'Abstract Decision Update',
        };

        return new Envelope(
            subject: $subject . ' - ' . config('conference.short_name') . ' Conference',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->template,
            with: [
                'abstract' => $this->abstract,
                'decision' => $this->decision,
                'reviews' => $this->reviews,
                'user' => $this->abstract->user,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
