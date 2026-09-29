<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AbstractSubmission;
use App\Models\User;

class ReviewReminderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $reviewer;
    public $reviewerPosition;
    public $assignmentDate;
    public $daysSinceAssignment;

    /**
     * Create a new message instance.
     */
    public function __construct(AbstractSubmission $abstract, User $reviewer, $reviewerPosition = 1)
    {
        $this->abstract = $abstract;
        $this->reviewer = $reviewer;
        $this->reviewerPosition = $reviewerPosition;
        $this->assignmentDate = $abstract->assigned_at ?? $abstract->created_at;
        $this->daysSinceAssignment = (int)now()->diffInDays($this->assignmentDate);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('conference.short_name') . " " . config('conference.year') . ": Review Reminder",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.review-reminder-notification',
            with: [
                'abstract' => $this->abstract,
                'reviewer' => $this->reviewer,
                'assignmentDate' => $this->assignmentDate,
                'daysSinceAssignment' => $this->daysSinceAssignment,
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
