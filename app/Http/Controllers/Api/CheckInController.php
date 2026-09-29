<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\OnsiteVisitor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckInController extends Controller
{
    /**
     * Staff Scanner: Lookup attendee by QR token (mobile app).
     * Returns comprehensive data for the scanner UI.
     */
    public function staffLookupQR(string $token)
    {
        $user = User::findByQrToken($token);

        if (!$user) {
            $onsiteResponse = $this->staffLookupOnsiteVisitor($token);
            if ($onsiteResponse) {
                return $onsiteResponse;
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code. Attendee not found.',
            ], 404);
        }

        $currentDay = $this->getCurrentConferenceDay();

        // Check today's attendance
        $todayAttendance = $user->attendances()->where('day', $currentDay)->first();

        // Get presenter status and visibility readiness
        $acceptedAbstracts = $user->abstractSubmissions()->where('status', 'accepted')->get();
        $presentations = $acceptedAbstracts->map(function ($abstract) {
            $mode = $abstract->presentation_mode ?: $abstract->presentation_type;
            $isOral = strtolower((string) $mode) !== 'poster';
            $hasUpload = $isOral
                ? !empty($abstract->oral_presentation_file)
                : !empty($abstract->poster_presentation_file);

            return [
                'id' => $abstract->id,
                'title' => $abstract->title,
                'conference_code' => $abstract->conference_code,
                'presentation_type' => $mode,
                'has_upload' => $hasUpload,
            ];
        });
        $missingPresentations = $presentations->where('has_upload', false)->count();

        $missingItems = [];
        if (blank($user->profile_image)) {
            $missingItems[] = [
                'type' => 'profile_photo',
                'label' => 'Profile photo missing',
                'detail' => 'Upload a clear headshot for the conference app.',
            ];
        }
        if (blank($user->bio)) {
            $missingItems[] = [
                'type' => 'bio',
                'label' => 'Biography missing',
                'detail' => 'Add a short biography for attendee visibility.',
            ];
        }
        if ($missingPresentations > 0) {
            $missingItems[] = [
                'type' => 'presentation_uploads',
                'label' => $missingPresentations . ' presentation upload' . ($missingPresentations === 1 ? '' : 's') . ' missing',
                'detail' => 'Presenter still has accepted presentation material outstanding.',
            ];
        }

        // Build initials
        $names = explode(' ', $user->full_name);
        $initials = count($names) >= 2
            ? strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1))
            : strtoupper(substr($user->full_name, 0, 2));

        return response()->json([
            'success' => true,
            'attendee' => [
                'id' => $user->id,
                'attendee_type' => 'user',
                'name' => $user->full_name,
                'title' => $user->title,
                'initials' => $initials,
                'email' => $user->email,
                'affiliation' => $user->affiliation,
                'country' => $user->country,
                'registration_category' => $user->registration_category,
                'payment_status' => $user->payment_status,
                'is_paid' => $user->isPaid(),
                'is_presenter' => $acceptedAbstracts->isNotEmpty(),
                'profile_image_url' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                'bio' => $user->bio,
                'profile_photo_missing' => blank($user->profile_image),
                'bio_missing' => blank($user->bio),
                'presentations' => $presentations->values()->toArray(),
                'missing_presentations_count' => $missingPresentations,
                'missing_items' => $missingItems,
                'current_day' => $currentDay,
                'current_day_label' => $this->attendanceDayLabel($currentDay),
                'is_demo_day' => $currentDay === 0,
                'attended_today' => $todayAttendance !== null,
                'today_checkin_time' => $todayAttendance?->checked_in_at?->format('g:i A'),
            ],
        ]);
    }

    protected function staffLookupOnsiteVisitor(string $token): ?\Illuminate\Http\JsonResponse
    {
        $visitor = OnsiteVisitor::where('qr_token', $token)->first();

        if (!$visitor) {
            return null;
        }

        $currentDay = $this->getCurrentConferenceDay();
        $todayAttendance = Attendance::where('onsite_visitor_id', $visitor->id)
            ->where('day', $currentDay)
            ->first();

        return response()->json([
            'success' => true,
            'attendee' => [
                'id' => $visitor->id,
                'attendee_type' => 'onsite_visitor',
                'name' => $visitor->effective_name,
                'title' => null,
                'initials' => $visitor->initials,
                'email' => null,
                'affiliation' => $visitor->institution,
                'country' => null,
                'registration_category' => $visitor->badge_category,
                'payment_status' => 'verified',
                'is_paid' => true,
                'is_presenter' => false,
                'profile_image_url' => null,
                'bio' => null,
                'profile_photo_missing' => false,
                'bio_missing' => false,
                'presentations' => [],
                'missing_presentations_count' => 0,
                'missing_items' => [],
                'current_day' => $currentDay,
                'current_day_label' => $this->attendanceDayLabel($currentDay),
                'is_demo_day' => $currentDay === 0,
                'attended_today' => $todayAttendance !== null,
                'today_checkin_time' => $todayAttendance?->checked_in_at?->format('g:i A'),
            ],
        ]);
    }

    /**
     * Staff Scanner: Mark attendance for a specific day (mobile app).
     */
    public function staffMarkAttendance(Request $request)
    {
        $request->validate([
            'attendee_type' => 'nullable|string|in:user,onsite_visitor',
            'attendee_id'   => 'nullable|integer|min:1',
            'user_id'       => 'nullable|integer|min:1',
            'day'           => 'required|integer|min:0|max:' . (int) config('conference.total_days', 3),
        ]);

        $day = (int) $request->day;
        $dayGuard = $this->validateAttendanceDay($day);

        if (!$dayGuard['valid']) {
            return response()->json([
                'success' => false,
                'message' => $dayGuard['message'],
                'current_day' => $dayGuard['current_day'],
                'current_day_label' => $this->attendanceDayLabel($dayGuard['current_day']),
            ], 422);
        }

        $attendeeType = $request->input('attendee_type', 'user');
        $attendeeId   = (int) ($request->input('attendee_id') ?? $request->input('user_id'));

        if ($attendeeType === 'onsite_visitor') {
            return $this->markOnsiteVisitorAttendance($attendeeId, $day);
        }

        // ── Regular user path (unchanged) ───────────────────────────────────
        $user = User::find($attendeeId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Attendee not found.'], 404);
        }

        if (!$user->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not verified. Please verify payment before marking attendance.',
            ], 400);
        }

        $existingAttendance = $user->attendances()->where('day', $day)->first();
        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'message' => "Already marked present for {$this->attendanceDayLabel($day)} at " . $existingAttendance->checked_in_at->format('g:i A'),
            ], 400);
        }

        $attendance = Attendance::recordAttendance($user->id, $day, Auth::id());
        $this->syncUserCheckInSnapshot($user);

        return response()->json([
            'success' => true,
            'message' => "{$user->full_name} marked present for {$this->attendanceDayLabel($day)}!",
            'attendance' => [
                'day' => $day,
                'day_label' => $this->attendanceDayLabel($day),
                'checked_in_at' => $attendance->checked_in_at->format('g:i A'),
            ],
        ]);
    }

    protected function markOnsiteVisitorAttendance(int $visitorId, int $day): \Illuminate\Http\JsonResponse
    {
        $visitor = OnsiteVisitor::find($visitorId);
        if (!$visitor) {
            return response()->json(['success' => false, 'message' => 'Walk-in visitor not found.'], 404);
        }

        $existing = Attendance::where('onsite_visitor_id', $visitorId)->where('day', $day)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Already marked present for {$this->attendanceDayLabel($day)} at " . $existing->checked_in_at->format('g:i A'),
            ], 400);
        }

        $attendance = Attendance::recordOnsiteVisitorAttendance($visitorId, $day, Auth::id());

        return response()->json([
            'success' => true,
            'message' => "{$visitor->effective_name} marked present for {$this->attendanceDayLabel($day)}!",
            'attendance' => [
                'day' => $day,
                'day_label' => $this->attendanceDayLabel($day),
                'checked_in_at' => $attendance->checked_in_at->format('g:i A'),
            ],
        ]);
    }

    /**
     * Get current conference day (1, 2, or 3)
     */
    protected function getCurrentConferenceDay(): int
    {
        $timezone = config('conference.timezone', config('app.timezone', 'Africa/Dar_es_Salaam'));
        $conferenceStart = \Carbon\Carbon::parse(config('conference.start_date'), $timezone)->startOfDay();
        $totalDays = config('conference.total_days', 3);
        $today = \Carbon\Carbon::now($timezone)->startOfDay();

        if ($today->lt($conferenceStart)) {
            return 0;
        }

        $day = (int)$conferenceStart->diffInDays($today) + 1;
        return $day > $totalDays ? 0 : $day;
    }

    protected function attendanceDayLabel(int $day): string
    {
        return $day === 0 ? 'Demo Day' : "Day {$day}";
    }

    protected function validateAttendanceDay(int $day): array
    {
        $currentDay = $this->getCurrentConferenceDay();

        if ($day === $currentDay) {
            return ['valid' => true, 'current_day' => $currentDay, 'message' => null];
        }

        if ($currentDay === 0) {
            return [
                'valid' => false,
                'current_day' => $currentDay,
                'message' => 'Real attendance is locked outside the conference dates. Use Demo Day for testing.',
            ];
        }

        return [
            'valid' => false,
            'current_day' => $currentDay,
            'message' => "Attendance can only be marked for {$this->attendanceDayLabel($currentDay)} today.",
        ];
    }

    protected function syncUserCheckInSnapshot(User $user): void
    {
        $latestAttendance = Attendance::where('user_id', $user->id)
            ->latest('checked_in_at')
            ->first();

        $user->update([
            'checked_in_at' => $latestAttendance?->checked_in_at,
            'checked_in_by' => $latestAttendance?->checked_in_by,
        ]);
    }
}
