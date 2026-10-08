<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProgrammeSession;
use App\Models\SessionAttendance;
use App\Services\AttendanceService;
use App\Support\ScanResult;
use App\Support\Summit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The programme as the scanning app sees it, and scanning badges at a session. */
class SessionController extends Controller
{
    public function __construct(private Summit $summit) {}

    /** The summit's sessions, optionally for one day (?date=2027-09-15), with live attendance counts. */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        $sessions = $this->summit->edition()?->sessions()
            ->when($request->query('date'), fn ($q, $date) => $q->whereDate('starts_at', $date))
            ->withCount('attendances')
            ->get() ?? collect();

        return response()->json([
            'data' => $sessions->map(fn (ProgrammeSession $session) => $this->session($session))->values(),
        ]);
    }

    /** Who has been scanned at a session, newest first. */
    public function attendance(ProgrammeSession $session): JsonResponse
    {
        $this->ensureCurrent($session);

        $attendances = $session->attendances()->with('registration.user')->latest('scanned_at')->get();

        return response()->json([
            'session' => $this->session($session->loadCount('attendances')),
            'data' => $attendances->map(fn (SessionAttendance $a) => [
                'name' => $a->registration->displayName(),
                'badge_number' => $a->registration->reference,
                'scanned_at' => $a->scanned_at->toIso8601String(),
            ])->values(),
        ]);
    }

    /** Record a badge at a session. Always 200 with an outcome, except for invalid input. */
    public function scan(Request $request, ProgrammeSession $session, AttendanceService $attendance): JsonResponse
    {
        $this->ensureCurrent($session);

        $data = $request->validate(['code' => ['required', 'string', 'max:100']]);

        $result = $attendance->scan($session, $data['code'], $request->user(), $request->user()->currentAccessToken()?->name);

        return response()->json([
            'outcome' => $result->outcome,
            'counted' => $result->counted(),
            'message' => $result->message,
            'attendee' => $result->registration ? AttendeeController::attendee($result->registration) : null,
            'scanned_at' => $result->attendance?->scanned_at->toIso8601String(),
            'session' => $this->session($session->loadCount('attendances')),
        ], $result->outcome === ScanResult::NOT_FOUND ? 404 : 200);
    }

    private function ensureCurrent(ProgrammeSession $session): void
    {
        abort_unless($session->edition_id === $this->summit->edition()?->id, 404);
    }

    private function session(ProgrammeSession $session): array
    {
        return [
            'id' => $session->id,
            'title' => $session->title,
            'kind' => $session->kind,
            'hall' => $session->hall,
            'starts_at' => $session->starts_at->toIso8601String(),
            'ends_at' => $session->ends_at->toIso8601String(),
            'cpd_points' => $session->points(),
            'scannable' => $session->isScannable(),
            'open_now' => $session->isOpenForScanning(),
            'attendance_count' => $session->attendances_count ?? $session->attendances()->count(),
        ];
    }
}
