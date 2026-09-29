<?php

namespace App\Mail;

use App\Models\GroupRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GroupPaymentVerifiedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $groupRegistration;
    public $notes;

    /**
     * Create a new message instance.
     */
    public function __construct(GroupRegistration $groupRegistration, $notes = null)
    {
        $this->groupRegistration = $groupRegistration;
        $this->notes = $notes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Group Payment Verified - ' . config('conference.short_name') . ' ' . config('conference.year'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.group-payment-verified',
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
