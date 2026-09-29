<?php

namespace App\Http\Controllers;

use App\Models\ConferenceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionManagementController extends Controller
{
    /**
     * Display the sessions assigned to the current user (Chair or Rapporteur)
     */
    public function mySessions()
    {
        $user = Auth::user();
        $sessions = ConferenceSession::where(function($query) use ($user) {
                $query->where('session_chair_id', $user->id)
                      ->orWhere('session_rapporteur_id', $user->id);
            })
            ->where('is_active', true)
            ->with(['abstracts' => function($query) {
                $query->orderBy('session_order', 'asc');
            }])
            ->orderBy('start_time')
            ->get();

        return view('sessions.index', compact('sessions'));
    }

    /**
     * Show details for a specific session
     */
    public function show(ConferenceSession $session)
    {
        $user = Auth::user();

        // Authorization check
        if ($session->session_chair_id !== $user->id && $session->session_rapporteur_id !== $user->id && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access to this session.');
        }

        $session->load(['abstracts' => function($query) {
            $query->orderBy('session_order', 'asc');
        }]);

        $isChair = $session->session_chair_id === $user->id;
        $isRapporteur = $session->session_rapporteur_id === $user->id;

        return view('sessions.show', compact('session', 'isChair', 'isRapporteur'));
    }

    /**
     * Save rapporteur notes for a session
     */
    public function saveReport(Request $request, ConferenceSession $session)
    {
        $user = Auth::user();

        // Only rapporteurs or admins can save notes
        if ($session->session_rapporteur_id !== $user->id && !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Only the assigned rapporteur can save session notes.'
            ], 403);
        }

        $request->validate([
            'rapporteur_notes' => 'required|string'
        ]);

        $session->update([
            'rapporteur_notes' => $request->rapporteur_notes,
            'status' => $request->input('status', $session->status) // Optionally update status
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Session notes saved successfully!'
            ]);
        }

        return redirect()->back()->with('success', 'Session notes saved successfully!');
    }

    /**
     * Confirm a session assignment via email token
     */
    public function confirmAssignment($sessionId, $userId, $role, $token)
    {
        $session = ConferenceSession::findOrFail($sessionId);

        // Validate token
        if ($session->confirmation_token !== $token) {
            abort(403, 'Invalid or expired confirmation token.');
        }

        // Validate user and role
        if ($role === 'chair' && $session->session_chair_id == $userId) {
            $session->update(['chair_confirmed_at' => now()]);
            $message = "Thank you for confirming your role as the Chairperson for the session: {$session->name}.";
        } elseif ($role === 'rapporteur' && $session->session_rapporteur_id == $userId) {
            $session->update(['rapporteur_confirmed_at' => now()]);
            $message = "Thank you for confirming your role as the Rapporteur for the session: {$session->name}.";
        } else {
            abort(403, 'Invalid assignment confirmation.');
        }

        return view('sessions.confirmed', compact('session', 'message'));
    }
}
