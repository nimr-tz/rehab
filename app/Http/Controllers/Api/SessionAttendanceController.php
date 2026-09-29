<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConferenceSession;
use App\Models\GroupMember;
use App\Models\OnsiteVisitor;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Staff scanning at the door of a session. Records who attended which
 * session so CPD credit can be calculated from it.
 */
class SessionAttendanceController extends Controller
{
    public function store(Request $request, ConferenceSession $session): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:255',
        ]);

        $attendee = $this->resolveAttendee($validated['token']);

        if (! $attendee) {
            return response()->json(['success' => false, 'message' => 'Badge not recognised.'], 404);
        }

        [$type, $model, $name, $affiliation, $isPaid] = $attendee;

        if (! $isPaid) {
            return response()->json([
                'success' => false,
                'message' => "{$name} does not have a confirmed registration.",
                'attendee' => ['name' => $name, 'affiliation' => $affiliation],
            ], 400);
        }

        $existing = SessionAttendance::where('conference_session_id', $session->id)
            ->where('attendee_type', $type)
            ->where('attendee_id', $model->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'already_recorded' => true,
                'message' => "{$name} was already recorded for this session at ".$existing->scanned_at->format('g:i A').'.',
                'attendee' => ['name' => $name, 'affiliation' => $affiliation],
                'session_count' => $this->sessionCount($session),
            ], 409);
        }

        $attendance = SessionAttendance::create([
            'conference_session_id' => $session->id,
            'attendee_type' => $type,
            'attendee_id' => $model->id,
            'scanned_at' => now(),
            'scanned_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$name} recorded for {$session->name}.",
            'attendee' => ['name' => $name, 'affiliation' => $affiliation],
            'scanned_at' => $attendance->scanned_at->format('g:i A'),
            'session_count' => $this->sessionCount($session),
        ], 201);
    }

    public function summary(ConferenceSession $session): JsonResponse
    {
        return response()->json([
            'session_id' => $session->id,
            'session_name' => $session->name,
            'count' => $this->sessionCount($session),
        ]);
    }

    /**
     * @return array{0:string, 1:\Illuminate\Database\Eloquent\Model, 2:string, 3:?string, 4:bool}|null
     */
    private function resolveAttendee(string $token): ?array
    {
        if ($user = User::findByQrToken($token)) {
            return [SessionAttendance::TYPE_USER, $user, $user->full_name, $user->affiliation, $user->isPaid()];
        }

        $token = $this->normalizeToken($token);

        if ($member = GroupMember::where('qr_token', $token)->with('groupRegistration')->first()) {
            return [SessionAttendance::TYPE_GROUP_MEMBER, $member, $member->full_name, $member->institution, $member->isPaymentVerified()];
        }

        if ($visitor = OnsiteVisitor::where('qr_token', $token)->first()) {
            return [SessionAttendance::TYPE_ONSITE_VISITOR, $visitor, $visitor->effective_name, $visitor->institution, true];
        }

        return null;
    }

    private function normalizeToken(string $token): string
    {
        $token = rawurldecode(trim($token));

        if (preg_match('~/badge/([^/?#]+)~', $token, $matches)) {
            return rawurldecode($matches[1]);
        }

        return $token;
    }

    private function sessionCount(ConferenceSession $session): int
    {
        return SessionAttendance::where('conference_session_id', $session->id)->count();
    }
}
