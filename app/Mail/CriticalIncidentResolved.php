<?php

namespace App\Mail;

use App\Models\SystemLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CriticalIncidentResolved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SystemLog $systemLog,
        public string $resolutionSummary,
        public bool $actionRequired,
        public ?string $actionDetails = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $routeName = data_get($this->systemLog->context, 'request.route_name')
            ?? data_get($this->systemLog->context, 'request.url')
            ?? 'your recent action';

        return new Envelope(
            subject: sprintf('[%s] Issue resolved for %s', config('app.name', config('conference.short_name')), $routeName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.critical-incident-resolved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
