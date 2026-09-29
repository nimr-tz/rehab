<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Services\CriticalIncidentResolutionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SystemLogController extends Controller
{
    /**
     * Display the system logs dashboard.
     */
    public function index(Request $request)
    {
        $query = SystemLog::with('user')->latest();

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Filter by channel
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        // Filter by resolved status
        if ($request->filled('status')) {
            if ($request->status === 'unresolved') {
                $query->where('resolved', false);
            } elseif ($request->status === 'resolved') {
                $query->where('resolved', true);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(25)->appends($request->query());

        // Stats
        $stats = [
            'total' => SystemLog::count(),
            'unresolved' => SystemLog::unresolved()->count(),
            'resolved' => SystemLog::where('resolved', true)->count(),
            'errors_today' => SystemLog::where('level', 'error')->whereDate('created_at', today())->count(),
        ];

        return view('admin.system-logs.index', compact('logs', 'stats'));
    }

    /**
     * View log details.
     */
    public function show(SystemLog $systemLog)
    {
        $systemLog->load(['user', 'resolver']);
        return view('admin.system-logs.show', compact('systemLog'));
    }

    /**
     * Mark a log as resolved.
     */
    public function resolve(Request $request, SystemLog $systemLog, CriticalIncidentResolutionService $resolutionService)
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
            'resolution_summary' => 'nullable|string|max:1000',
            'notify_user' => 'nullable|boolean',
            'notification_email' => 'nullable|email:rfc',
            'action_required' => 'nullable|boolean',
            'action_details' => 'nullable|string|max:2000',
        ]);

        if ($request->boolean('notify_user')
            && $resolutionService->resolveNotificationEmail($systemLog, $request->input('notification_email')) === null) {
            throw ValidationException::withMessages([
                'notify_user' => 'This incident does not have a captured user email. Provide a valid notification email to send a resolution notice.',
            ]);
        }

        $result = $resolutionService->resolve($systemLog, [
            'resolution_notes' => $request->input('resolution_notes'),
            'resolution_summary' => $request->input('resolution_summary'),
            'notify_user' => $request->boolean('notify_user'),
            'notification_email' => $request->input('notification_email'),
            'action_required' => $request->boolean('action_required'),
            'action_details' => $request->input('action_details'),
        ], $request->user());

        $message = 'Log entry marked as resolved.';

        if ($result['status'] === SystemLog::USER_NOTIFICATION_SENT) {
            $message .= ' User resolution email sent.';
        } elseif ($result['status'] === SystemLog::USER_NOTIFICATION_FAILED) {
            $message .= ' User resolution email failed: ' . ($result['error'] ?? 'unknown mail error');
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Bulk resolve logs.
     */
    public function bulkResolve(Request $request)
    {
        $request->validate([
            'log_ids' => 'required|array',
            'log_ids.*' => 'exists:system_logs,id',
        ]);

        SystemLog::whereIn('id', $request->log_ids)->update([
            'resolved' => true,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'resolution_notes' => 'Bulk resolved by admin.',
        ]);

        return redirect()->back()->with('success', count($request->log_ids) . ' log(s) marked as resolved.');
    }

    /**
     * Clear all resolved logs.
     */
    public function clearResolved()
    {
        $count = SystemLog::where('resolved', true)->count();
        SystemLog::where('resolved', true)->delete();

        return redirect()->back()->with('success', "{$count} resolved log entries cleared.");
    }
}
