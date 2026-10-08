<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\AttendanceService;
use App\Support\Summit;
use Illuminate\Http\JsonResponse;

/** Look up a badge: who it is and whether they may enter, as the desk sees it. */
class AttendeeController extends Controller
{
    public function show(string $code, Summit $summit, AttendanceService $attendance): JsonResponse
    {
        $edition = $summit->edition();
        $registration = $edition ? $attendance->find($edition, $code) : null;
        abort_unless($registration, 404, 'This badge is not registered for this summit.');

        $cpd = $attendance->summary($registration);

        return response()->json([
            'attendee' => self::attendee($registration),
            'cpd' => [
                'sessions_attended' => $cpd['attended'],
                'points' => $cpd['points'],
                'points_available' => $cpd['available'],
            ],
        ]);
    }

    /** The attendee as every app response shows them. */
    public static function attendee(Registration $registration): array
    {
        $registration->loadMissing('user', 'category', 'latestPayment');
        $user = $registration->user;

        return [
            'name' => $registration->displayName(),
            'badge_number' => $registration->reference,
            'role' => $registration->badgeRole(),
            'category' => $registration->category->name,
            'institution' => $user->institution,
            'profession' => $user->profession,
            'country' => $user->countryName(),
            // paid | waived | awaiting_mpesa | with_finance | not_paid
            'payment' => match (true) {
                $registration->isConfirmed() => $registration->isFullyWaived() ? 'waived' : 'paid',
                $registration->latestPayment?->status === PaymentStatus::Pending => 'awaiting_mpesa',
                $registration->latestPayment?->status === PaymentStatus::Submitted => 'with_finance',
                default => 'not_paid',
            },
            'confirmed' => $registration->isConfirmed(),
            'checked_in_at' => $registration->checked_in_at?->toIso8601String(),
            'dietary_needs' => $registration->dietary_needs,
            'accessibility_needs' => $registration->accessibility_needs,
        ];
    }
}
