<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\AttendanceService;
use App\Services\DocumentService;
use App\Support\Summit;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Certificates of attendance: downloaded after the summit, and checked by anyone from the code on them. */
class CertificateController extends Controller
{
    public function mine(Request $request, Summit $summit, DocumentService $documents): Response
    {
        $registration = $request->user()->registrationFor($summit->edition());
        abort_unless($registration?->hasAttendanceCertificate(), 403, 'Your certificate of attendance is available after the summit, once your attendance has been recorded.');

        return $documents->attendanceCertificate($registration);
    }

    /** An admin downloads anyone's certificate, for example to send it by email. */
    public function participant(Registration $registration, DocumentService $documents): Response
    {
        abort_unless($registration->hasAttendanceCertificate(), 403, 'This participant has no certificate of attendance: they were not recorded at the summit, or it has not ended yet.');

        return $documents->attendanceCertificate($registration);
    }

    /** Public: is this certificate genuine? Shows only what the certificate itself shows. */
    public function verify(string $number, string $code, AttendanceService $attendance): View
    {
        $registration = preg_match('/^AT\d{2}-(\d{6})$/', $number, $m) ? Registration::with('user', 'edition')->find((int) $m[1]) : null;
        $valid = $registration
            && $registration->certificateNumber() === $number
            && hash_equals($registration->certificateCode(), $code)
            && $registration->hasAttendanceCertificate();

        return view('certificates.verify', [
            'valid' => $valid,
            'number' => $number,
            'registration' => $valid ? $registration : null,
            'days' => $valid ? $attendance->daysAttended($registration) : collect(),
        ]);
    }
}
