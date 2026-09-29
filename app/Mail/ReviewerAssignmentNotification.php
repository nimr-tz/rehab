<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AbstractSubmission;
use App\Models\User;

class ReviewerAssignmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $reviewer;
    public $reviewerPosition;
    public $assignmentDate;
    public $assignmentType;
    public $matchType;

    /**
     * Create a new message instance.
     */
    public function __construct(AbstractSubmission $abstract, User $reviewer, $reviewerPosition = 1, array $options = [])
    {
        $this->abstract = $abstract;
        $this->reviewer = $reviewer;
        $this->reviewerPosition = $reviewerPosition;
        $this->assignmentDate = now();
        $this->assignmentType = $options['assignment_type'] ?? 'assigned';
        $this->matchType = $options['match_type'] ?? 'exact';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('conference.short_name') . " " . config('conference.year') . ": New Reviewer Assignment",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reviewer-assignment-notification',
            with: [
                'abstract' => $this->abstract,
                'reviewer' => $this->reviewer,
                'assignmentDate' => $this->assignmentDate,
                'assignmentType' => $this->assignmentType,
                'matchType' => $this->matchType,
            ],
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
