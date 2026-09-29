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

class BothReviewsCompleteNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $reviewer1;
    public $reviewer2;
    public $averageScore;
    public $finalDecision;
    public $completionDate;

    /**
     * Create a new message instance.
     */
    public function __construct(AbstractSubmission $abstract, User $reviewer1, User $reviewer2, $averageScore, $finalDecision = null)
    {
        $this->abstract = $abstract;
        $this->reviewer1 = $reviewer1;
        $this->reviewer2 = $reviewer2;
        $this->averageScore = $averageScore;
        $this->finalDecision = $finalDecision;
        $this->completionDate = now();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('conference.short_name') . " " . config('conference.year') . ": Both Reviews Complete - Ready for Committee Decision",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.both-reviews-complete-notification',
            with: [
                'abstract' => $this->abstract,
                'reviewer1' => $this->reviewer1,
                'reviewer2' => $this->reviewer2,
                'averageScore' => $this->averageScore,
                'finalDecision' => $this->finalDecision,
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