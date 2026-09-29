<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CpdReminderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public string $profileUrl;

    public function __construct(public User $user)
    {
        $this->profileUrl = route('profile.edit');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action Required: Update Your CPD Details — ' . config('conference.short_name') . ' ' . config('conference.year'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cpd-reminder',
            with: [
                'user' => $this->user,
                'profileUrl' => $this->profileUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
