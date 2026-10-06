<?php

namespace App\Services;

use App\Models\AwardEntry;
use App\Models\Registration;
use App\Support\Qr;
use App\Support\Summit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/** Badges, invitation letters and award certificates, generated on request. */
class DocumentService
{
    public function __construct(private Summit $summit) {}

    public function badge(Registration $registration): Response
    {
        return $this->badges(collect([$registration]), 'badge-'.$registration->reference.'.pdf');
    }

    /** Several badges in one PDF, one A6 page each, for the registration desk. */
    public function badges(Collection $registrations, string $filename): Response
    {
        $registrations->each->loadMissing('user', 'category');

        return Pdf::loadView('pdf.badge', [
            'badges' => $registrations->map(fn (Registration $r) => ['registration' => $r, 'qr' => Qr::dataUri($r->qr_token)])->all(),
            'summit' => $this->summit,
            'logo' => public_path('images/brand/logo-mark.png'),
        ])->setPaper([0, 0, 297.64, 419.53]) // A6 portrait, in points
            ->download($filename);
    }

    public function invitationLetter(Registration $registration): Response
    {
        $registration->loadMissing('user', 'category');

        return Pdf::loadView('pdf.invitation-letter', [
            'registration' => $registration,
            'summit' => $this->summit,
            'logo' => public_path('images/brand/logo-mark.png'),
        ])->setPaper('a4')
            ->download('invitation-letter-'.$registration->reference.'.pdf');
    }

    /** A4 landscape certificate for an announced winner. */
    public function awardCertificate(AwardEntry $entry): Response
    {
        $entry->loadMissing('category.edition', 'abstract');

        return Pdf::loadView('pdf.award-certificate', [
            'entry' => $entry,
            'category' => $entry->category,
            'summit' => $this->summit,
            'logo' => public_path('images/brand/logo-mark.png'),
        ])->setPaper('a4', 'landscape')
            ->download('award-certificate-'.$entry->certificateNumber().'.pdf');
    }
}
