<?php

namespace App\Mail;

use App\Models\RapporteurReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RapporteurReportApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(public RapporteurReport $report, public ?string $note = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your session report has been approved — ' . config('conference.short_name') . ' ' . config('conference.year'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rapporteur-report-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
