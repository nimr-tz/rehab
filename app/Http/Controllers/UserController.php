<?php

namespace App\Http\Controllers;

use App\Models\AbstractSubmission;
use App\Models\SessionRoleApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    /**
     * Display the user dashboard
     */
    public function dashboard()
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return redirect('/login');
            }

            // Move GroupMember check from view to controller for better performance
            $groupMember = \App\Models\GroupMember::where('email', $user->email)
                ->with('groupRegistration')
                ->first();

            // RESTRICTION: Individual Group Members (not leaders) cannot access full dashboard
            if ($groupMember && $groupMember->groupRegistration->leader_user_id !== $user->id) {
                return view('user.group-member-status', [
                    'member' => $groupMember,
                    'group' => $groupMember->groupRegistration,
                ]);
            }

            // Get user's abstracts with essential relationships
            $userAbstracts = AbstractSubmission::where('user_id', $user->id)
                ->with(['session', 'reviews' => function ($q) {
                    $q->where('status', 'submitted');
                }])
                ->get();

            // Statistics for header
            $stats = [
                'total_submissions' => $userAbstracts->count(),
                'under_review' => $userAbstracts->whereIn('status', [
                    'submitted',
                    'under_review',
                    'ready_for_decision',
                    'revision_submitted',
                    'revision_review',
                ])->count(),
                'accepted' => $userAbstracts->where('status', 'accepted')->count(),
                'rejected' => $userAbstracts->where('status', 'rejected')->count(),
            ];

            // Action required grouping
            $actionRequired = $userAbstracts->whereIn('status', [
                'minor_revision_required', 'major_revision_required', 'revision_required', 'revision_requested',
            ]);

            // Filter pending presentations
            $pendingPresentations = $userAbstracts->filter(function ($abstract) {
                return $abstract->status === 'accepted' && $abstract->requiresPresentationUpload();
            });

            $inProgress = $userAbstracts->whereIn('status', [
                'draft', 'submitted', 'under_review', 'ready_for_decision',
                'revision_submitted', 'revision_review',
            ]);

            $completed = $userAbstracts->whereIn('status', ['accepted', 'rejected']);

            $stats['action_required'] = $actionRequired->count() + $pendingPresentations->count();
            $stats['revision_required'] = $actionRequired->count();

            $sessionRoleApplicationDeadline = SessionRoleApplication::deadline();
            $sessionRoleApplicationOpen = $userAbstracts->isNotEmpty() && now()->lte($sessionRoleApplicationDeadline);
            $sessionRoleApplication = Schema::hasTable('session_role_applications')
                ? SessionRoleApplication::where('user_id', $user->id)->first()
                : null;

            // Camera-ready corrections: which of this user's abstracts they can
            // still fix before the proceedings volume is published.
            $corrections = app(\App\Services\ProceedingsCorrectionService::class);
            $proceedings = [
                'is_open' => $corrections->isOpen(),
                'closes_at' => $corrections->closesAt(),
                'abstracts' => $userAbstracts
                    ->filter(fn ($abstract) => $corrections->isEligible($abstract))
                    ->sortBy('conference_code')
                    ->values(),
            ];

            return view('user.dashboard', compact(
                'stats',
                'actionRequired',
                'inProgress',
                'completed',
                'pendingPresentations',
                'groupMember',
                'sessionRoleApplication',
                'sessionRoleApplicationOpen',
                'sessionRoleApplicationDeadline',
                'proceedings'
            ));
        } catch (\Exception $e) {
            Log::error('Error in UserController::dashboard: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Show revision interface for an abstract
     */
    public function showRevisionInterface(AbstractSubmission $abstract)
    {
        $user = Auth::user();

        // Ensure the abstract belongs to the user and is in revision status
        if ($abstract->user_id !== $user->id) {
            abort(403, 'Unauthorized access to abstract.');
        }

        $validRevisionStatuses = ['minor_revision_required', 'major_revision_required', 'revision_requested', 'revision_required'];
        if (! in_array($abstract->status, $validRevisionStatuses)) {
            return redirect()->route('user.dashboard')
                ->with('error', 'This abstract is not currently in revision status.');
        }

        // Get all reviews for this abstract
        $reviews = $abstract->reviews()->with('reviewer')->get();

        // Get revision feedback from admin (stored in admin_comment)
        $revisionFeedback = $abstract->admin_comment;
        $revisionDeadline = $abstract->revision_deadline;
        $revisionRequestedAt = $abstract->revision_requested_at;

        return view('user.revision-interface', compact('abstract', 'reviews', 'revisionFeedback', 'revisionDeadline', 'revisionRequestedAt'));
    }

    /**
     * Handle abstract resubmission after revision
     */
    public function resubmitAbstract(Request $request, AbstractSubmission $abstract)
    {
        $user = Auth::user();

        // Ensure the abstract belongs to the user and is in revision status
        if ($abstract->user_id !== $user->id) {
            abort(403, 'Unauthorized access to abstract.');
        }

        // Ensure the abstract belongs to the user and is in a revision-required status
        $validRevisionStatuses = ['minor_revision_required', 'major_revision_required', 'revision_required', 'revision_requested'];
        if (! in_array($abstract->status, $validRevisionStatuses)) {
            Log::warning("User {$user->id} attempted to revise abstract {$abstract->id} with invalid status: {$abstract->status}");

            return redirect()->route('user.dashboard')
                ->with('error', 'This abstract is not currently in revision status.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'author_name' => 'required|string|max:255',
            'author_institute' => 'required|string|max:255',
            'subtheme' => 'required|string',
            'revision_feedback' => 'required|string|min:50|max:2000',
        ], [
            'revision_feedback.required' => 'Please describe the changes you made in response to the feedback.',
            'revision_feedback.min' => 'Your revision notes must be at least 50 characters to adequately explain your changes.',
        ]);

        try {
            // Increment revision round
            $currentRound = $abstract->revision_round ?? 0;
            $nextRound = $currentRound + 1;

            // Update the abstract - go to under_review (to reviewers, not admin)
            $abstract->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'author_name' => $validated['author_name'],
                'author_institute' => $validated['author_institute'],
                'subtheme' => $validated['subtheme'],
                'revision_feedback' => $validated['revision_feedback'],
                'status' => 'under_review', // Go to reviewers, not admin
                'revision_submitted_at' => now(),
                'revision_round' => $nextRound,
            ]);

            Log::info("Abstract {$abstract->id} resubmitted by user {$user->id} after revision (round {$nextRound})");

            // SMART ROUTING: Only assign re-review to reviewers who DIDN'T accept
            $previousRoundReviews = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                ->where('review_round', $currentRound)
                ->where('status', 'submitted')
                ->get();

            $emailService = app(\App\Services\EmailNotificationService::class);
            $reviewersToNotify = [];

            // Check Reviewer 1
            if ($abstract->reviewer_id) {
                // Check if Reviewer 1 has EVER accepted this abstract
                $hasAccepted1 = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                    ->where('reviewer_id', $abstract->reviewer_id)
                    ->where('status', 'submitted')
                    ->whereIn('recommendation', ['accept'])
                    ->exists();

                // Only assign if they have NOT accepted yet
                if (! $hasAccepted1) {
                    // Create new review record for this round
                    \App\Models\AbstractReview::create([
                        'abstract_submission_id' => $abstract->id,
                        'reviewer_id' => $abstract->reviewer_id,
                        'reviewer_number' => 1,
                        'review_round' => $nextRound,
                        'is_revision_review' => true,
                        'status' => 'draft',
                    ]);
                    $reviewersToNotify[] = ['user' => $abstract->reviewer1, 'position' => 1];
                } else {
                    Log::info("Reviewer 1 (ID: {$abstract->reviewer_id}) skipped - already accepted in round {$currentRound}");
                }
            }

            // Check Reviewer 2
            if ($abstract->reviewer_2_id) {
                // Check if Reviewer 2 has EVER accepted this abstract
                $hasAccepted2 = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                    ->where('reviewer_id', $abstract->reviewer_2_id)
                    ->where('status', 'submitted')
                    ->whereIn('recommendation', ['accept'])
                    ->exists();

                // Only assign if they have NOT accepted yet
                if (! $hasAccepted2) {
                    // Create new review record for this round
                    \App\Models\AbstractReview::create([
                        'abstract_submission_id' => $abstract->id,
                        'reviewer_id' => $abstract->reviewer_2_id,
                        'reviewer_number' => 2,
                        'review_round' => $nextRound,
                        'is_revision_review' => true,
                        'status' => 'draft',
                    ]);
                    $reviewersToNotify[] = ['user' => $abstract->reviewer2, 'position' => 2];
                } else {
                    Log::info("Reviewer 2 (ID: {$abstract->reviewer_2_id}) skipped - already accepted in round {$currentRound}");
                }
            }

            // Send notifications only to reviewers who need to re-review
            foreach ($reviewersToNotify as $reviewer) {
                if ($reviewer['user']) {
                    $emailService->sendRevisionResubmittedNotification($abstract, $reviewer['user'], $reviewer['position']);
                }
            }

            $notifiedCount = count($reviewersToNotify);

            return redirect()->route('user.dashboard')
                ->with('success', "Abstract resubmitted successfully. {$notifiedCount} reviewer(s) have been notified for re-review.");

        } catch (\Exception $e) {
            Log::error("Failed to resubmit abstract {$abstract->id}: ".$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to resubmit abstract. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show revision request and upload form for an abstract
     */
    public function showRevisionForm($id)
    {
        $user = Auth::user();
        $abstract = AbstractSubmission::where('id', $id)->where('user_id', $user->id)->with(['reviews.reviewer'])->firstOrFail();
        if (! in_array($abstract->status, ['revision', 'minor_revision', 'major_revision', 'revision_required'])) {
            return redirect()->back()->with('error', 'This abstract is not currently in revision.');
        }

        return view('user.abstracts.revision-edit', compact('abstract'));
    }

    /**
     * Handle upload of revised abstract and author response
     */
    public function submitRevision(Request $request, $id)
    {
        $user = Auth::user();
        $abstract = AbstractSubmission::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        if (! in_array($abstract->status, ['revision', 'minor_revision', 'major_revision', 'revision_required'])) {
            return redirect()->back()->with('error', 'This abstract is not currently in revision.');
        }
        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'author_institute' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'subtheme' => 'required|string',
            'presentation_mode' => 'required|string',
            'coauthors' => 'nullable|array',
            'coauthors.*.name' => 'nullable|string|max:255',
            'coauthors.*.institute' => 'nullable|string|max:255',
            'include_in_proceedings' => 'nullable|boolean',
            'revision_file' => 'nullable|file|mimes:pdf,doc,docx',
            'author_response' => 'required|string|min:50|max:2000',
        ], [
            'author_response.required' => 'Revision notes are compulsory. Please explain the changes you made.',
            'author_response.min' => 'Please provide more detailed revision notes (at least 50 characters).',
        ]);

        // Update abstract with revision data
        $updateData = [
            'author_name' => $validated['author_name'],
            'author_institute' => $validated['author_institute'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'subtheme' => $validated['subtheme'],
            'presentation_mode' => $validated['presentation_mode'],
            'coauthors' => isset($validated['coauthors']) ? json_encode($validated['coauthors']) : null,
            'include_in_proceedings' => isset($validated['include_in_proceedings']) ? (bool) $validated['include_in_proceedings'] : false,
            'status' => 'under_review', // ✅ Go directly to review, skip admin
            'revision_submitted_at' => now(),
            'revision_feedback' => $validated['author_response'] ?? null,
        ];
        if ($request->hasFile('revision_file')) {
            $path = $request->file('revision_file')->store('revisions', 'public');
            $updateData['revision_file'] = $path;
        }
        $abstract->update($updateData);

        // ✅ SMART ROUTING: Only create review records for reviewers who DIDN'T accept
        $currentRound = $abstract->revision_round ?? 0; // Current round (default to 0 for initial)
        $nextRound = $currentRound + 1; // Increment for the new revision

        // Update the abstract's revision round
        $abstract->update(['revision_round' => $nextRound]);

        // Get previous round reviews to check who accepted
        $previousRoundReviews = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
            ->where('review_round', $currentRound)
            ->where('status', 'submitted')
            ->get();

        // Build list of reviewers who need to re-review
        $reviewersToAssign = [];

        if ($abstract->reviewer_id) {
            // Check if Reviewer 1 has EVER accepted
            $hasAccepted1 = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                ->where('reviewer_id', $abstract->reviewer_id)
                ->where('status', 'submitted')
                ->whereIn('recommendation', ['accept', 'accept_oral', 'accept_poster'])
                ->exists();

            if (! $hasAccepted1) {
                $reviewersToAssign[] = ['id' => $abstract->reviewer_id, 'number' => 1];
            }
        }

        if ($abstract->reviewer_2_id) {
            // Check if Reviewer 2 has EVER accepted
            $hasAccepted2 = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                ->where('reviewer_id', $abstract->reviewer_2_id)
                ->where('status', 'submitted')
                ->whereIn('recommendation', ['accept', 'accept_oral', 'accept_poster'])
                ->exists();

            if (! $hasAccepted2) {
                $reviewersToAssign[] = ['id' => $abstract->reviewer_2_id, 'number' => 2];
            }
        }

        // Create review records only for reviewers who need to re-review (FOR THE NEXT ROUND)
        foreach ($reviewersToAssign as $reviewer) {
            // Create new review record for the NEXT ROUND
            \App\Models\AbstractReview::create([
                'abstract_submission_id' => $abstract->id,
                'reviewer_id' => $reviewer['id'],
                'reviewer_number' => $reviewer['number'],
                'review_round' => $nextRound, // ✅ Use the next round
                'is_revision_review' => true,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Notify reviewer of revision
            $reviewerUser = \App\Models\User::find($reviewer['id']);
            if ($reviewerUser) {
                // Send email notification
                $emailService = new \App\Services\EmailNotificationService;
                // You can implement sendRevisionResubmittedNotification if needed
            }
        }

        return redirect()->route('user.dashboard')->with('success', 'Revision submitted successfully and sent directly to reviewers for re-evaluation.');
    }

}
