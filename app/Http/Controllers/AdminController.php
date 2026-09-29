<?php
// filepath: app/Http/Controllers/AdminController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbstractSubmission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\BlindReviewService;
use App\Models\ReviewConflict;
use App\Services\EmailNotificationService;

class AdminController extends Controller
{
    public function exportReports()
    {
        // For now, return a summary report
        $stats = [
            'total_submissions' => AbstractSubmission::count(),
            'by_status' => AbstractSubmission::groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->get()
                ->pluck('count', 'status'),
            'by_subtheme' => AbstractSubmission::groupBy('subtheme')
                ->selectRaw('subtheme, count(*) as count')
                ->get()
                ->pluck('count', 'subtheme'),
            'acceptance_rate' => $this->calculateAcceptanceRate()
        ];
        
        return view('admin.reports.export', compact('stats'));
    }
    
    // Add this method to handle revised submissions:
    public function reviewRevision(AbstractSubmission $abstract)
    {
        if ($abstract->status !== 'revision_submitted') {
            return redirect()->back()->with('error', 'This abstract is not awaiting revision review.');
        }
        
        // Load all data needed for revision review
        $abstract->load(['user', 'reviewer1', 'reviewer2', 'reviews']);
        
        return view('admin.abstracts.review-revision', compact('abstract'));
    }

    public function processRevision(Request $request, AbstractSubmission $abstract)
    {
        $validated = $request->validate([
            'decision' => 'required|in:accept_revision,reject_revision,needs_more_revision',
            'admin_notes' => 'nullable|string'
        ]);
        
        $newStatus = match($validated['decision']) {
            'accept_revision' => 'accepted', // Accept the revision
            'reject_revision' => 'rejected', // Reject the abstract
            'needs_more_revision' => 'revision', // Needs more revision
        };
        
        $abstract->update([
            'status' => $newStatus,
            'admin_comment' => $validated['admin_notes'],
            'status_changed_at' => now(),
            'status_changed_by' => Auth::id(),
        ]);
        
        $message = match($validated['decision']) {
            'accept_revision' => 'Revision accepted - sent back to reviewers for final evaluation',
            'reject_revision' => 'Revision rejected - abstract has been declined',
            'needs_more_revision' => 'More revisions needed - sent back to author',
        };
        
        return redirect()->back()->with('success', $message);
    }
    
    // User management methods
    // Add these methods to handle the filtered views:
    // Helper methods
    private function calculateAcceptanceRate()
    {
        // ✅ FIXED: Only calculate rate based on SUBMITTED abstracts
        $total = AbstractSubmission::whereNotIn('status', ['draft'])->count();
        if ($total === 0) return 0;
        
        $accepted = AbstractSubmission::where('status', 'accepted')->count();
        
        return round(($accepted / $total) * 100, 1);
    }



    /**
     * Handle AI Assistant Chat
     */
    public function aiChat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = strtolower($request->input('message'));
        $response = "I'm here to help with conference management. You can ask me about statistics, reviews, or emails.";

        // Simple logic for demonstration
        if (str_contains($message, 'hello') || str_contains($message, 'hi')) {
            $response = "Hello! I'm your " . config('conference.short_name') . " Assistant. How can I help you today?";
        } elseif (str_contains($message, 'stat') || str_contains($message, 'count')) {
            $total = AbstractSubmission::count();
            $accepted = AbstractSubmission::where('status', 'accepted')->count();
            $pending = AbstractSubmission::where('status', 'submitted')->count();
            $response = "Current Conference Statistics:\n• Total Abstracts: {$total}\n• Accepted: {$accepted}\n• Pending Review: {$pending}";
        } elseif (str_contains($message, 'review')) {
            $underReview = AbstractSubmission::where('status', 'under_review')->count();
            $response = "There are currently {$underReview} abstracts under review. You can assign reviewers in the 'Abstracts' section.";
        } elseif (str_contains($message, 'email')) {
            $response = "You can send bulk emails and reminders from the Email Management dashboard.";
        } elseif (str_contains($message, 'deadline')) {
            $submissionDeadline = \Carbon\Carbon::parse(config('conference.submission_deadline'))->format('F j, Y');
            $response = "The abstract submission deadline is {$submissionDeadline}. Reviewer deadlines are communicated in assignment emails.";
        }

        return response()->json([
            'success' => true,
            'response' => $response
        ]);
    }
}
