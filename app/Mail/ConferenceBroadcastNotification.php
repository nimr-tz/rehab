<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ConferenceBroadcastNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $subject;
    public $messageText;
    public $actionUrl;
    public $actionText;
    public array $attachmentsData;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $subject, string $messageText, string $actionUrl = null, string $actionText = null, array $attachmentsData = [])
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->messageText = $messageText;
        $this->actionUrl = $actionUrl ?? route('dashboard');
        $this->actionText = $actionText ?? 'View Dashboard';
        $this->attachmentsData = $attachmentsData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject . ' - ' . config('conference.short_name') . ' ' . config('conference.year'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.conference-broadcast',
            with: [
                'user' => $this->user,
                'messageText' => $this->messageText,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
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
        return collect($this->attachmentsData)
            ->filter(fn ($attachment) => is_file($attachment['path'] ?? ''))
            ->map(function ($attachment) {
                $mailAttachment = Attachment::fromPath($attachment['path']);

                if (!empty($attachment['name'])) {
                    $mailAttachment = $mailAttachment->as($attachment['name']);
                }

                if (!empty($attachment['mime'])) {
                    $mailAttachment = $mailAttachment->withMime($attachment['mime']);
                }

                return $mailAttachment;
            })
            ->values()
            ->all();
    }
}
