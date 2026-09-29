<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Services\EmailNotificationService;

class StudentVerificationController extends Controller
{
    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }
    /**
     * Display a listing of students for verification.
     */
    public function index()
    {
        $status = request('status', 'pending');

        $query = User::where('student_status', 'yes');

        if ($status !== 'all') {
            $query->where('student_verification_status', $status);
        }

        $students = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'pending' => User::where('student_status', 'yes')->where('student_verification_status', 'pending')->count(),
            'verified' => User::where('student_status', 'yes')->where('student_verification_status', 'verified')->count(),
            'rejected' => User::where('student_status', 'yes')->where('student_verification_status', 'rejected')->count(),
            'total' => User::where('student_status', 'yes')->count(),
        ];

        return view('admin.students.index', compact('students', 'stats', 'status'));
    }

    /**
     * Verify a student.
     */
    public function verify(Request $request, User $user)
    {
        if ($user->student_status !== 'yes') {
            return back()->with('error', 'This user is not registered as a student.');
        }

        $user->update([
            'student_verification_status' => 'verified',
            'student_verified_at' => now(),
            'student_verified_by' => Auth::id(),
            'student_verification_notes' => $request->notes
        ]);

        $this->emailService->sendStudentIDVerified($user);

        return back()->with('success', "Student {$user->full_name} has been verified.");
    }

    /**
     * Reject a student.
     */
    public function reject(Request $request, User $user)
    {
        $request->validate([
            'notes' => 'required|string'
        ]);

        if ($user->student_status !== 'yes' || $user->student_verification_status !== 'pending') {
            return back()->with('error', 'This user is not pending student verification.');
        }

        $user->update([
            'student_verification_status' => 'rejected',
            'student_verified_at' => now(),
            'student_verified_by' => Auth::id(),
            'student_verification_notes' => $request->notes,
        ]);

        $this->emailService->sendStudentIDRejected($user, $request->notes);

        return back()->with('success', "Student {$user->full_name} has been marked for student ID resubmission. Reason: {$request->notes}");
    }
}
