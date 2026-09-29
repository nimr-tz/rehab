<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Services\ConferenceCodeSyncService;
use Illuminate\Http\Request;

class SubthemeController extends Controller
{
    /**
     * Display subtheme management dashboard.
     */
    public function index()
    {
        // Get all accepted abstracts grouped by subtheme
        $subthemeData = AbstractSubmission::where('status', 'accepted')
            ->with(['user', 'session'])
            ->get()
            ->groupBy('subtheme')
            ->map(function ($abstracts, $subtheme) {
                $oralCount = $abstracts->where('presentation_mode', 'oral')->count();
                $posterCount = $abstracts->where('presentation_mode', 'poster')->count();
                $assignedToSession = $abstracts->whereNotNull('session_id')->count();
                $withCodes = $abstracts->whereNotNull('conference_code')->count();
                
                return [
                    'subtheme' => $subtheme,
                    'total_abstracts' => $abstracts->count(),
                    'oral_count' => $oralCount,
                    'poster_count' => $posterCount,
                    'assigned_to_session' => $assignedToSession,
                    'unassigned_count' => $abstracts->count() - $assignedToSession,
                    'with_codes' => $withCodes,
                    'without_codes' => $abstracts->count() - $withCodes,
                    'abstracts' => $abstracts->sortBy('created_at')
                ];
            });

        return view('admin.subthemes.index', compact('subthemeData'));
    }

    /**
     * Bulk update abstracts (subtheme and presentation mode override).
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'abstract_ids' => 'required|array',
            'abstract_ids.*' => 'exists:abstract_submissions,id',
            'action' => 'required|in:update_subtheme,update_presentation_mode,remove_from_session',
            'new_subtheme' => 'required_if:action,update_subtheme|string|max:255',
            'new_presentation_mode' => 'required_if:action,update_presentation_mode|in:oral,poster'
        ]);

        $updatedCount = 0;
        $abstracts = AbstractSubmission::whereIn('id', $request->abstract_ids)
            ->where('status', 'accepted')
            ->get();

        foreach ($abstracts as $abstract) {
            switch ($request->action) {
                case 'update_subtheme':
                    $abstract->update([
                        'subtheme' => $request->new_subtheme,
                        'session_id' => null // Remove from session if subtheme changes
                    ]);
                    $updatedCount++;
                    break;

                case 'update_presentation_mode':
                    $abstract->update([
                        'presentation_mode' => $request->new_presentation_mode,
                        'session_id' => null // Remove from session if presentation mode changes
                    ]);
                    $updatedCount++;
                    break;

                case 'remove_from_session':
                    if ($abstract->session_id) {
                        $abstract->update(['session_id' => null]);
                        $updatedCount++;
                    }
                    break;
            }
        }

        if ($request->action === 'update_subtheme') {
            app(ConferenceCodeSyncService::class)->resyncAcceptedCodes();
        }

        // Update session counts for affected sessions
        $this->updateSessionCounts();

        $actionName = match($request->action) {
            'update_subtheme' => 'subtheme updated',
            'update_presentation_mode' => 'presentation mode updated',
            'remove_from_session' => 'removed from sessions',
            default => 'updated'
        };

        return redirect()->route('admin.subthemes.index')
            ->with('success', "{$updatedCount} abstracts {$actionName} successfully.");
    }

    /**
     * Generate conference codes for abstracts based on subtheme.
     */
    public function generateCodes(Request $request)
    {
        $request->validate([
            'subtheme' => 'required|string',
            'code_prefix' => 'required|string|max:10',
            'start_number' => 'required|integer|min:1',
            'oral_only' => 'boolean',
            'poster_only' => 'boolean'
        ]);

        $query = AbstractSubmission::where('status', 'accepted')
            ->where('subtheme', $request->subtheme)
            ->whereNull('conference_code');

        // Filter by presentation mode if specified
        if ($request->oral_only) {
            $query->where('presentation_mode', 'oral');
        } elseif ($request->poster_only) {
            $query->where('presentation_mode', 'poster');
        }

        $abstracts = $query->orderBy('created_at')->get();
        $currentNumber = $request->start_number;
        $generatedCount = 0;

        foreach ($abstracts as $abstract) {
            $code = $request->code_prefix . '-' . str_pad($currentNumber, 3, '0', STR_PAD_LEFT);
            
            // Check if code already exists
            if (!AbstractSubmission::where('conference_code', $code)->exists()) {
                $abstract->update([
                    'conference_code' => $code,
                    'code_assigned_by' => Auth::id(),
                    'code_assigned_at' => now()
                ]);
                $generatedCount++;
                $currentNumber++;
            }
        }

        return redirect()->route('admin.subthemes.index')
            ->with('success', "Generated {$generatedCount} conference codes for {$request->subtheme} subtheme.");
    }

    /**
     * Update session counts for all sessions.
     */
    private function updateSessionCounts()
    {
        $sessions = \App\Models\ConferenceSession::all();
        
        foreach ($sessions as $session) {
            $count = $session->abstracts()->count();
            $session->update(['current_abstracts' => $count]);
        }
    }

}
