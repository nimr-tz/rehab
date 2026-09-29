<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BulkReviewReminderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $reviewer,
        public Collection $pendingReviews,
        public ?Carbon $deadline = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('conference.short_name') . ' ' . config('conference.year') . ': Review Reminder',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.review-reminder',
            with: [
                'reviewer' => $this->reviewer,
                'pendingReviews' => $this->pendingReviews,
                'deadline' => $this->deadline,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
