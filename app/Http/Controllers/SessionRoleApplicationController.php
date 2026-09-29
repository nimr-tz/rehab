<?php

namespace App\Http\Controllers;

use App\Models\AbstractSubmission;
use App\Models\SessionRoleApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SessionRoleApplicationController extends Controller
{
    public function create()
    {
        if (!$this->userHasAbstractSubmission()) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Session Chair and Rapporteur applications are available to authors with abstract submissions.');
        }

        $deadline = SessionRoleApplication::deadline();
        $application = SessionRoleApplication::where('user_id', Auth::id())->first();

        return view('session-role-applications.create', [
            'application' => $application,
            'deadline' => $deadline,
            'roles' => [
                SessionRoleApplication::ROLE_CHAIR => 'Session Chair',
                SessionRoleApplication::ROLE_RAPPORTEUR => 'Rapporteur',
            ],
            'themes' => SessionRoleApplication::themes(),
            'isOpen' => now()->lte($deadline),
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->userHasAbstractSubmission()) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Session Chair and Rapporteur applications are available to authors with abstract submissions.');
        }

        $deadline = SessionRoleApplication::deadline();

        if (now()->gt($deadline)) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Applications for Session Chair and Rapporteur roles closed on April 30, 2026.');
        }

        $validated = $request->validate([
            'role_requested' => ['required', Rule::in([SessionRoleApplication::ROLE_CHAIR, SessionRoleApplication::ROLE_RAPPORTEUR])],
            'preferred_theme' => ['required', 'string', Rule::in(SessionRoleApplication::themes())],
            'attendance_confirmed' => 'accepted',
            'notes' => 'nullable|string|max:1000',
        ], [
            'attendance_confirmed.accepted' => 'Please confirm that you are sure you can attend the conference.',
        ]);

        SessionRoleApplication::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'role_requested' => $validated['role_requested'],
                'preferred_theme' => $validated['preferred_theme'],
                'attendance_confirmed' => true,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]
        );

        return redirect()->route('user.dashboard')
            ->with('success', 'Your Session Chair/Rapporteur application has been submitted.');
    }

    private function userHasAbstractSubmission(): bool
    {
        return AbstractSubmission::where('user_id', Auth::id())->exists();
    }
}
