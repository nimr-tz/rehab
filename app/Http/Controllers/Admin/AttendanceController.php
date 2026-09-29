<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\OnsiteVisitor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    /**
     * Show the attendance scanner page
     */
    public function scanner()
    {
        $today = $this->getCurrentConferenceDay();

        // Get today's attendance stats
        $todayCount = Attendance::where('day', $today)->count();
        $totalRegistered = User::whereIn('payment_status', ['verified', 'waived'])->count();

        // Recent check-ins
        $recentCheckIns = Attendance::with(['user', 'groupMember'])
            ->where('day', $today)
            ->latest('checked_in_at')
            ->take(10)
            ->get();

        return view('registration.attendance.scanner', compact(
            'today',
            'todayCount',
            'totalRegistered',
            'recentCheckIns'
        ));
    }

    /**
     * Process QR code scan for attendance
     */
    public function scan(Request $request)
    {
        $request->validate([
            'qr_token' => 'required|string',
            'day' => 'required|integer|min:1|max:3',
        ]);

        $token = $this->normalizeQrToken($request->qr_token);
        $day = $request->day;

        // Find user by QR token
        $user = User::findByQrToken($token);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code. Attendee not found.',
            ], 404);
        }

        // Check if payment is verified
        if (!in_array($user->payment_status, ['verified', 'waived'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not verified for this user.',
                'user' => [
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'payment_status' => $user->payment_status,
                ],
            ], 400);
        }

        // Check if already checked in today
        if (Attendance::hasAttendance($user->id, $day)) {
            $attendance = Attendance::where('user_id', $user->id)
                ->where('day', $day)
                ->first();

            return response()->json([
                'success' => false,
                'message' => 'Already checked in for Day ' . $day,
                'user' => [
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'affiliation' => $user->affiliation,
                ],
                'attendance' => [
                    'day' => $day,
                    'checked_in_at' => $attendance->checked_in_at->format('g:i A'),
                ],
            ], 409);
        }

        // Record attendance
        $attendance = Attendance::recordAttendance($user->id, $day, Auth::id());
        $user->update([
            'checked_in_at' => $attendance->checked_in_at,
            'checked_in_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully!',
            'user' => [
                'name' => $user->full_name,
                'email' => $user->email,
                'affiliation' => $user->affiliation,
                'is_presenter' => $user->abstractSubmissions()->where('status', 'accepted')->exists(),
            ],
            'attendance' => [
                'day' => $day,
                'day_label' => 'Day ' . $day,
                'checked_in_at' => $attendance->checked_in_at->format('g:i A'),
            ],
        ]);
    }

    protected function normalizeQrToken(string $value): string
    {
        $value = trim($value);

        if (preg_match('~/((?:badge|connect))/([^/?#]+)~', $value, $matches)) {
            return rawurldecode($matches[2]);
        }

        return $value;
    }

    /**
     * Show attendance report
     */
    public function report(Request $request)
    {
        $day = $request->get('day', null);

        $query = Attendance::with(['user', 'groupMember', 'checkedInByUser']);

        if ($day) {
            $query->where('day', $day);
        }

        $attendances = $query->latest('checked_in_at')->paginate(50);

        // Stats per day
        $stats = [
            1 => Attendance::where('day', 1)->count(),
            2 => Attendance::where('day', 2)->count(),
            3 => Attendance::where('day', 3)->count(),
        ];

        $totalRegistered = User::whereIn('payment_status', ['verified', 'waived'])->count();

        // Users with full attendance (all 3 days)
        $fullAttendance = User::whereHas('attendances', function($q) {
            $q->where('day', 1);
        })->whereHas('attendances', function($q) {
            $q->where('day', 2);
        })->whereHas('attendances', function($q) {
            $q->where('day', 3);
        })->count();

        // Use registration layout if accessed from registration routes
        $view = str_starts_with($request->route()->getName(), 'registration.')
            ? 'registration.attendance.report'
            : 'admin.attendance.report';

        return view($view, compact(
            'attendances',
            'stats',
            'totalRegistered',
            'fullAttendance',
            'day'
        ));
    }

    /**
     * Get current conference day (1, 2, or 3)
     * This is a simple implementation - you might want to make this configurable
     */
    protected function getCurrentConferenceDay(): int
    {
        $timezone = config('conference.timezone', config('app.timezone', 'Africa/Dar_es_Salaam'));
        $conferenceStart = \Carbon\Carbon::parse(config('conference.start_date'), $timezone)->startOfDay();
        $totalDays = config('conference.total_days', 3);
        $today = \Carbon\Carbon::now($timezone)->startOfDay();

        // If before conference, show Day 1
        if ($today->lt($conferenceStart)) {
            return 0;
        }

        // Diff in days (0 for same day, 1 for next, etc.)
        $day = (int)$conferenceStart->diffInDays($today) + 1;

        return $day > $totalDays ? 0 : $day;
    }
}
