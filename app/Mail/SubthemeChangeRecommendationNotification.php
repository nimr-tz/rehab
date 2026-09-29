<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AbstractSubmission;
use App\Models\User;

class SubthemeChangeRecommendationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $abstract;
    public $reviewer;
    public $reviewerPosition;
    public $suggestedSubtheme;

    public function __construct(AbstractSubmission $abstract, User $reviewer, int $reviewerPosition, string $suggestedSubtheme)
    {
        $this->abstract = $abstract;
        $this->reviewer = $reviewer;
        $this->reviewerPosition = $reviewerPosition;
        $this->suggestedSubtheme = $suggestedSubtheme;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Subtheme Change Recommendation - Abstract #{$this->abstract->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subtheme-change-recommendation-notification',
            with: [
                'abstract' => $this->abstract,
                'reviewer' => $this->reviewer,
                'reviewerPosition' => $this->reviewerPosition,
                'suggestedSubtheme' => $this->suggestedSubtheme,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
