<?php

namespace App\Mail;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateReadyNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public User $user,
        public Certificate $certificate,
    ) {
        $this->onQueue('emails-high-priority');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Certificate of Attendance is Ready — ' . config('conference.short_name') . ' ' . config('conference.year'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.certificate-ready',
            with: [
                'certificateNumber' => $this->certificate->certificate_number,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
