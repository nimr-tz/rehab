<?php

namespace App\Services;

use App\Models\Registration;
use App\Support\Qr;
use App\Support\Summit;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/** Badges and invitation letters, generated on request from the registration. */
class DocumentService
{
    public function __construct(private Summit $summit) {}

    public function badge(Registration $registration): Response
    {
        $registration->loadMissing('user', 'category');

        return Pdf::loadView('pdf.badge', [
            'registration' => $registration,
            'qr' => Qr::dataUri($registration->qr_token),
            'summit' => $this->summit,
            'logo' => public_path('images/brand/logo-mark.png'),
        ])->setPaper([0, 0, 297.64, 419.53]) // A6 portrait, in points
            ->download('badge-'.$registration->reference.'.pdf');
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
}
