<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSubmittedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $groupRegistration;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $groupRegistration = null)
    {
        $this->user = $user;
        $this->groupRegistration = $groupRegistration;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->groupRegistration 
            ? "New Group Payment Proof: {$this->groupRegistration->group_name}"
            : "New Payment Proof Submitted - " . config('conference.short_name') . " " . config('conference.year');

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-submitted-admin',
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
