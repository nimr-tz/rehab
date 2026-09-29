<?php

namespace App\Mail;

use App\Models\AbstractSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewerDecisionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $reviewer;
    public $decision;
    public $adminMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(AbstractSubmission $abstract, User $reviewer, string $decision, string $message = null)
    {
        $this->abstract = $abstract;
        $this->reviewer = $reviewer;
        $this->decision = $decision;
        $this->adminMessage = $message;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->decision) {
            'accept', 'accepted' => '✅ Abstract Accepted - Decision Update',
            'reject', 'rejected' => '❌ Abstract Decision - Decision Update',
            'minor_revision', 'minor_revision_required' => '📝 Minor Revision Requested - Decision Update',
            'major_revision', 'major_revision_required' => '📝 Major Revision Requested - Decision Update',
            default => '📋 Abstract Decision Update'
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
            view: 'emails.reviewer-decision-notification',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

