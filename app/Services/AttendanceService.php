<?php

namespace App\Services;

use App\Models\Edition;
use App\Models\ProgrammeSession;
use App\Models\Registration;
use App\Models\SessionAttendance;
use App\Models\User;
use App\Support\ScanResult;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Session attendance, scanned from badges by the staff app, and the CPD points
 * it earns. A scan counts once per person and session, while the session runs,
 * and only for a confirmed (paid or waived) registration.
 */
class AttendanceService
{
    /** The badge's check-in code, or its badge number typed in. */
    public function find(Edition $edition, string $code): ?Registration
    {
        $code = trim($code);

        return $edition->registrations()
            ->where(fn ($q) => $q->where('qr_token', $code)->orWhere('reference', Str::upper($code)))
            ->with('user', 'category')
            ->first();
    }

    public function scan(ProgrammeSession $session, string $code, User $scanner, ?string $device = null): ScanResult
    {
        if (! $session->isScannable()) {
            return new ScanResult(ScanResult::NOT_SCANNABLE, $session->title.' is not a session that is scanned.');
        }

        $registration = $this->find($session->edition, $code);

        if (! $registration) {
            return new ScanResult(ScanResult::NOT_FOUND, 'This badge is not registered for this summit.');
        }
        if (! $registration->isConfirmed()) {
            return new ScanResult(ScanResult::NOT_PAID, $registration->user->name.' has not paid. Send them to the registration desk.', $registration);
        }
        $existing = $registration->attendances()->where('programme_session_id', $session->id)->first();
        if ($existing) {
            return new ScanResult(ScanResult::ALREADY, $registration->user->name.' was already recorded at '.$existing->scanned_at->format('H:i').'.', $registration, $existing);
        }
        if (! $session->isOpenForScanning()) {
            return new ScanResult(ScanResult::CLOSED, $session->title.' runs '.$session->starts_at->format('D j M, H:i').'–'.$session->ends_at->format('H:i').'. Scans count only during the session.', $registration);
        }

        try {
            $attendance = $registration->attendances()->create([
                'programme_session_id' => $session->id,
                'scanned_at' => now(),
                'scanned_by' => $scanner->id,
                'device' => $device ? Str::limit($device, 97) : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Two scanners at once: the other one recorded it.
            $attendance = $registration->attendances()->where('programme_session_id', $session->id)->firstOrFail();

            return new ScanResult(ScanResult::ALREADY, $registration->user->name.' was already recorded.', $registration, $attendance);
        }

        // Attending a session means they have arrived.
        if (! $registration->checked_in_at) {
            $registration->update(['checked_in_at' => now()]);
        }

        return new ScanResult(ScanResult::RECORDED, $registration->user->name.' recorded.', $registration, $attendance);
    }

    /**
     * A participant's CPD: the sessions they attended, by day, with points, out of what the programme offers.
     *
     * @return array{attended: int, points: float, available: float, sessions: int, days: Collection}
     */
    public function summary(Registration $registration): array
    {
        $sessions = $registration->edition->sessions()->get()->filter->isScannable();
        $attended = $registration->attendances()->with('session')->get()
            ->sortBy(fn (SessionAttendance $a) => $a->session->starts_at);

        return [
            'attended' => $attended->count(),
            'points' => round($attended->sum(fn (SessionAttendance $a) => $a->session->points()), 2),
            'available' => round($sessions->sum(fn (ProgrammeSession $s) => $s->points()), 2),
            'sessions' => $sessions->count(),
            'days' => $attended->groupBy(fn (SessionAttendance $a) => $a->session->starts_at->toDateString()),
        ];
    }
}
