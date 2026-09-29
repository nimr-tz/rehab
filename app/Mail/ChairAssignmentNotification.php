<?php

namespace App\Mail;

use App\Models\ConferenceSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChairAssignmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $session;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(ConferenceSession $session, User $user)
    {
        $this->session = $session;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Scientific Session Chairperson Assignment - ' . $this->session->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.chair-assignment-notification',
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
