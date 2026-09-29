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
use App\Models\AbstractReview;

class ReviewCompletionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $reviewer;
    public $reviewerPosition;
    public $reviewData;
    public $completionDate;

    /**
     * Create a new message instance.
     */
    public function __construct(AbstractSubmission $abstract, User $reviewer, $reviewerPosition = 1)
    {
        $this->abstract = $abstract;
        $this->reviewer = $reviewer;
        $this->reviewerPosition = $reviewerPosition;
        $this->completionDate = now();
        
        // Get review data from abstract_reviews table
        $this->reviewData = AbstractReview::where('abstract_submission_id', $abstract->id)
            ->where('reviewer_id', $reviewer->id)
            ->first();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('conference.short_name') . " " . config('conference.year') . ": Review Submission Confirmation",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.review-completion-notification',
            with: [
                'abstract' => $this->abstract,
                'reviewer' => $this->reviewer,
                'reviewData' => $this->reviewData,
                'completionDate' => $this->completionDate,
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