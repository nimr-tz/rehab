<?php

namespace App\Mail;

use App\Models\AbstractSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommitteeDecisionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $decision;
    public $notes;

    /**
     * Create a new message instance.
     */
    public function __construct(AbstractSubmission $abstract, string $decision, ?string $notes = null)
    {
        $this->abstract = $abstract;
        $this->decision = $decision;
        $this->notes = $notes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->decision) {
            'accepted' => '✅ Abstract Accepted by Committee',
            'rejected' => '❌ Abstract Decision - Thank You for Your Submission',
            'minor_revision_required' => '📝 Minor Revisions Required',
            'major_revision_required' => '📝 Major Revisions Required',
            default => '📋 Committee Decision Update'
        };

        return new Envelope(
            subject: $subject . ' - ' . config('conference.short_name') . ' ' . config('conference.year'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.committee-decision-notification',
            with: [
                'abstract' => $this->abstract,
                'decision' => $this->decision,
                'notes' => $this->notes,
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
