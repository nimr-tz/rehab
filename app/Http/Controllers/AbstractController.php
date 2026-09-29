<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbstractSubmission;
use App\Services\NotificationService;
use App\Services\EmailNotificationService;
use App\Services\SubmissionWindowService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AbstractController extends Controller
{
    protected $emailService;
    protected $notificationService;
    protected $submissionWindowService;

    public function __construct(
        EmailNotificationService $emailService,
        NotificationService $notificationService,
        SubmissionWindowService $submissionWindowService
    )
    {
        $this->emailService = $emailService;
        $this->notificationService = $notificationService;
        $this->submissionWindowService = $submissionWindowService;
    }
    public function create()
    {
        $user = Auth::user();
        if ($user && $user->isPartofGroup() && $user->groupMember?->groupRegistration->leader_user_id !== $user->id) {
            return redirect()->route('user.dashboard')->with('error', 'Group members do not have abstract submission benefits. This registration type is for participants only. If you are a presenter, please register as an individual.');
        }

        if (!$this->submissionWindowService->isOpenForPublic()) {
            return redirect()->route('user.dashboard')
                ->with('error', $this->submissionWindowService->getClosedMessage('abstract submissions'));
        }

        return view('abstracts.create');
    }

    public function store(Request $request)
    {
        if (!$this->submissionWindowService->isOpenForPublic()) {
            return redirect()->route('user.dashboard')
                ->with('error', $this->submissionWindowService->getClosedMessage('abstract submissions'));
        }

        $request->validate([
            'author_name' => 'required|string|max:255',
            'author_institute' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'subtheme' => 'required|string',
            'keywords' => 'required|string|max:500',
            'presentation_mode' => 'required|in:Oral,Poster',
            'coauthors' => 'nullable|array',
            'coauthors.*.name' => 'nullable|string|max:255',
            'coauthors.*.institute' => 'nullable|string|max:255',
            'action' => 'nullable|in:draft,submit',
        ]);

        // Determine status based on action
        $action = $request->input('action', 'draft');
        $status = $action === 'submit' ? 'submitted' : 'draft';

        // ✅ FIX: Safely handle coauthors data
        $coauthors = $request->input('coauthors', []);

        // Ensure coauthors is an array and filter out empty entries
        if (!is_array($coauthors)) {
            $coauthors = [];
        }

        // Filter out empty coauthor entries
        $coauthors = array_filter($coauthors, function($coauthor) {
            return !empty($coauthor['name']) || !empty($coauthor['institute']);
        });

        // Create the abstract
        $abstract = AbstractSubmission::create([
            'user_id' => Auth::id(),
            'author_name' => $request->author_name,
            'author_institute' => $request->author_institute,
            'title' => $request->title,
            'description' => $request->description,
            'subtheme' => $request->subtheme,
            'keywords' => $request->keywords,
            'presentation_mode' => $request->presentation_mode,
            'include_in_proceedings' => $request->has('include_in_proceedings'),
            'status' => $status,
            'coauthors' => $coauthors, // Store as array, not JSON
            'submitted_at' => $status === 'submitted' ? now() : null,
        ]);

        // Send email confirmation if submitted (not for drafts)
        if ($status === 'submitted') {
            $emailSent = $this->emailService->sendSubmissionConfirmation($abstract);

            if (!$emailSent) {
                Log::warning("Failed to send submission confirmation email for abstract #{$abstract->id}");
            }

            // In-app notifications
            $this->notificationService->createAbstractActionNotification(
                Auth::user(),
                'Abstract Submitted',
                "Your abstract \"{$abstract->title}\" has been successfully submitted.",
                $abstract->id
            );

            $this->notificationService->createAdminNotification(
                'new_submission',
                'New Abstract Submitted',
                "A new abstract has been submitted: \"{$abstract->title}\" by {$abstract->author_name}",
                ['abstract_id' => $abstract->id],
                route('admin.abstracts.view', $abstract->id),
                'high'
            );
        } else {
            // Send draft saved notification
            $emailSent = $this->emailService->sendDraftSavedNotification($abstract);

            if (!$emailSent) {
                Log::warning("Failed to send draft saved email for abstract #{$abstract->id}");
            }

            // In-app notification for draft
            $this->notificationService->createAbstractActionNotification(
                Auth::user(),
                'Draft Saved',
                "Your abstract \"{$abstract->title}\" has been saved as a draft.",
                $abstract->id
            );
        }

        // Different success messages based on action
        $message = $status === 'submitted'
            ? '🎉 Abstract submitted successfully! You will receive email updates on the review process.'
            : '💾 Abstract saved as draft! You can edit and submit it when ready.';

        // ✅ Always redirect to My Abstracts
        return redirect()->route('abstracts.my')->with('success', $message);
    }

    public function show(AbstractSubmission $abstract)
    {
        // Allow admins to view any abstract, otherwise users can only view their own
        if ($abstract->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'You can only view your own abstracts.');
        }

        // Load reviews relationship to ensure it's available in the view
        $abstract->load('reviews');

        return view('abstracts.show', compact('abstract'));
    }

    public function edit(AbstractSubmission $abstract)
    {
        // Ensure user can only edit their own abstracts
        if ($abstract->user_id !== Auth::id()) {
            abort(403, 'You can only edit your own abstracts.');
        }

        // Don't allow editing if under review or decided
        if (in_array($abstract->status, ['under_review', 'accepted', 'rejected'])) {
            return redirect()->route('abstracts.show', $abstract)
                ->with('error', 'This abstract cannot be edited as it is ' . $abstract->status . '.');
        }

        return view('abstracts.edit', compact('abstract'));
    }

    public function update(Request $request, AbstractSubmission $abstract)
    {
        // Ensure user can only update their own abstracts
        if ($abstract->user_id !== Auth::id()) {
            abort(403, 'You can only edit your own abstracts.');
        }

        // Don't allow editing if under review or decided
        if (in_array($abstract->status, ['under_review', 'accepted', 'rejected'])) {
            return redirect()->route('abstracts.show', $abstract)
                ->with('error', 'This abstract cannot be edited as it is ' . $abstract->status . '.');
        }

        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'author_institute' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'subtheme' => 'required|string',
            'keywords' => 'required|string|max:500',
            'presentation_mode' => 'required|in:Poster,Oral',
            'coauthors' => 'nullable|array',
            'coauthors.*.name' => 'required_with:coauthors|string|max:255',
            'coauthors.*.institute' => 'required_with:coauthors|string|max:255',
        ]);

        // ✅ FIX: Handle checkbox and coauthors safely
        $validated['include_in_proceedings'] = $request->has('include_in_proceedings');

        // ✅ FIX: Safely handle coauthors
        $coauthors = $validated['coauthors'] ?? [];
        if (!is_array($coauthors)) {
            $coauthors = [];
        }

        // Filter out empty coauthor entries
        $coauthors = array_filter($coauthors, function($coauthor) {
            return !empty($coauthor['name']) || !empty($coauthor['institute']);
        });

        $validated['coauthors'] = !empty($coauthors) ? json_encode($coauthors) : null;

        // Determine status based on action if provided
        $action = $request->input('action');
        if ($action === 'submit' && $abstract->status === 'draft') {
            if (!$this->submissionWindowService->isOpenForPublic()) {
                return redirect()->route('abstracts.show', $abstract)
                    ->with('error', $this->submissionWindowService->getClosedMessage('abstract submissions'));
            }

            $abstract->status = 'submitted';
            $abstract->submitted_at = now();

            // Send submission confirmation email
            $this->emailService->sendSubmissionConfirmation($abstract);

            // Notify admin
            $this->notificationService->createAdminNotification(
                'new_submission',
                'New Abstract Submitted',
                "An abstract draft has been finalized and submitted: \"{$abstract->title}\" by {$abstract->author_name}",
                ['abstract_id' => $abstract->id],
                route('admin.abstracts.view', $abstract->id),
                'high'
            );
        }

        $abstract->update($validated);

        // Send update notification email
        $emailSent = $this->emailService->sendAbstractUpdatedNotification($abstract);

        if (!$emailSent) {
            Log::warning("Failed to send abstract updated email for abstract #{$abstract->id}");
        }

        // In-app notification
        $notificationTitle = ($action === 'submit' && $abstract->wasChanged('status')) ? 'Abstract Submitted' : 'Abstract Updated';
        $notificationMsg = ($action === 'submit' && $abstract->wasChanged('status'))
            ? "Your abstract \"{$abstract->title}\" has been successfully submitted."
            : "Your abstract \"{$abstract->title}\" has been updated successfully.";

        $this->notificationService->createAbstractActionNotification(
            Auth::user(),
            $notificationTitle,
            $notificationMsg,
            $abstract->id
        );

        $successMsg = ($action === 'submit' && $abstract->wasChanged('status'))
            ? 'Abstract submitted for review!'
            : 'Abstract updated successfully!';

        return redirect()->route('abstracts.show', $abstract)
            ->with('success', $successMsg);
    }

    public function destroy(AbstractSubmission $abstract)
    {
        // Ensure user can only delete their own abstracts
        if ($abstract->user_id !== Auth::id()) {
            abort(403, 'You can only delete your own abstracts.');
        }

        // Only allow deletion for drafts (explicitly block everything else)
        if ($abstract->status !== 'draft') {
            return redirect()->route('abstracts.my')
                ->with('error', 'Only drafts can be deleted.');
        }

        // Store title for email notification before deletion
        $abstractTitle = $abstract->title;
        $user = $abstract->user;

        $abstract->delete();

        // Send deletion notification email
        $emailSent = $this->emailService->sendAbstractDeletedNotification($user, $abstractTitle);

        if (!$emailSent) {
            Log::warning("Failed to send abstract deleted email for user #{$user->id}");
        }

        // In-app notification (for the user, even if they deleted it)
        $this->notificationService->createNotification(
            $user,
            'system_alert',
            'Abstract Deleted',
            "Your draft abstract \"{$abstractTitle}\" was deleted.",
            [],
            null,
            'medium'
        );

        return redirect()->route('abstracts.my')
            ->with('success', 'Abstract deleted successfully!');
    }

    // ✨ ENHANCED: Your submit method with better feedback
    public function submit(AbstractSubmission $abstract)
    {
        // Your security logic is perfect - keeping it exactly
        if ($abstract->user_id !== Auth::id() || $abstract->status !== 'draft') {
            if ($abstract->status !== 'draft') {
                return redirect()->route('abstracts.my')
                    ->with('error', 'This abstract has already been submitted.');
            }
            abort(403);
        }

        if (!$this->submissionWindowService->isOpenForPublic()) {
            return redirect()->route('abstracts.my')
                ->with('error', $this->submissionWindowService->getClosedMessage('abstract submissions'));
        }

        // Add timestamp for submission tracking
        $abstract->update([
            'status' => 'submitted',
            'submitted_at' => now(), // Track when submitted
        ]);

        // Send email confirmation for submission
        $emailSent = $this->emailService->sendSubmissionConfirmation($abstract);

        if (!$emailSent) {
            Log::warning("Failed to send submission confirmation email for abstract #{$abstract->id}");
        }

        // In-app notifications
        $this->notificationService->createAbstractActionNotification(
            Auth::user(),
            'Abstract Submitted',
            "Your abstract \"{$abstract->title}\" has been successfully submitted.",
            $abstract->id
        );

        $this->notificationService->createAdminNotification(
            'new_submission',
            'New Abstract Submitted',
            "An abstract draft has been finalized and submitted: \"{$abstract->title}\" by {$abstract->author_name}",
            ['abstract_id' => $abstract->id],
            route('admin.abstracts.view', $abstract->id),
            'high'
        );

        return redirect()->route('abstracts.my')
            ->with('success', 'Abstract submitted for review! You will be notified when review is complete.');
    }

    // ✨ NEW: Additional methods to complete the workflow
    public function withdraw(AbstractSubmission $abstract)
    {
        // Security check
        if ($abstract->user_id !== Auth::id()) {
            abort(403);
        }

        // ❌ DON'T ALLOW withdrawal if already under review
        if (in_array($abstract->status, ['under_review', 'accepted', 'rejected', 'revision_required'])) {
            return redirect()->route('abstracts.my')
                ->with('error', 'Cannot withdraw: Abstract is already being reviewed or has been decided.');
        }

        // ❌ DON'T ALLOW withdrawal if reviewers are assigned (THIS WAS MISSING!)
        if ($abstract->reviewer_id || $abstract->reviewer_2_id) {
            return redirect()->route('abstracts.my')
                ->with('error', 'Cannot withdraw: Reviewers have been assigned to this abstract. Please contact the conference organizers if you need to make changes.');
        }

        // ❌ DON'T ALLOW withdrawal if status indicates reviewer assignment
        if (in_array($abstract->status, ['reviewer_assigned', 'ready_for_decision'])) {
            return redirect()->route('abstracts.my')
                ->with('error', 'Cannot withdraw: Abstract is already in the review process.');
        }

        // ✅ ALLOW withdrawal for submitted, draft, or other valid statuses
        $oldStatus = $abstract->status;
        $abstract->update(['status' => 'draft']);

        // Send withdrawal notification email for any valid withdrawal
        $emailSent = $this->emailService->sendAbstractWithdrawnNotification($abstract);

        if (!$emailSent) {
            Log::warning("Failed to send abstract withdrawn email for abstract #{$abstract->id}");
        }

        // In-app notification
        $this->notificationService->createAbstractActionNotification(
            Auth::user(),
            'Abstract Withdrawn',
            "Your abstract \"{$abstract->title}\" has been withdrawn and reverted to draft status.",
            $abstract->id
        );

        return redirect()->route('abstracts.my')
            ->with('success', "Abstract withdrawn successfully from '{$oldStatus}' status. You can now edit and resubmit.");
    }

    // ✨ NEW: For handling revision requests (future use)
    // Add this method to handle revision resubmissions:
public function resubmitRevision(Request $request, AbstractSubmission $abstract)
{
    // Security check
    if ($abstract->user_id !== Auth::id()) {
        abort(403);
    }

    // Only allow resubmission if in revision status
    $revisionStatuses = ['revision_required', 'revision_requested', 'revision_submitted', 'revision_under_review'];
    if (!in_array($abstract->status, $revisionStatuses)) {
        return redirect()->route('abstracts.my')
            ->with('error', 'This abstract is not in revision status.');
    }

    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'author_name' => 'required|string|max:255',
        'author_institute' => 'required|string|max:255',
        'subtheme' => 'required|string',
        'keywords' => 'required|string|max:500',
        'presentation_mode' => 'required|in:Oral,Poster',
        'coauthors' => 'nullable|array',
        'coauthors.*.name' => 'required_with:coauthors|string|max:255',
        'coauthors.*.institute' => 'required_with:coauthors|string|max:255',
        'revision_feedback' => 'required|string|min:50',
    ], [
        'revision_feedback.required' => 'Revision notes are compulsory. Please explain the changes you made.',
        'revision_feedback.min' => 'Please provide more detailed revision notes (at least 50 characters).',
    ]);

    // ✅ FIX: Safely handle coauthors
    $coauthors = $validated['coauthors'] ?? [];
    if (!is_array($coauthors)) {
        $coauthors = [];
    }
    $coauthors = array_filter($coauthors, function($coauthor) {
        return !empty($coauthor['name']) || !empty($coauthor['institute']);
    });

    // --- NEW: Determine the next review round ---
    $currentRound = $abstract->revision_round ?? 0;
    $nextRound = $currentRound + 1;

    // --- SMART ROUTING: Only create review records for reviewers who DIDN'T accept ---
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

        if (!$hasAccepted1) {
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

        if (!$hasAccepted2) {
            $reviewersToAssign[] = ['id' => $abstract->reviewer_2_id, 'number' => 2];
        }
    }

    // Create review records only for reviewers who need to re-review
    foreach ($reviewersToAssign as $reviewer) {
        \App\Models\AbstractReview::create([
            'abstract_submission_id' => $abstract->id,
            'reviewer_id' => $reviewer['id'],
            'reviewer_number' => $reviewer['number'],
            'review_round' => $nextRound,
        ]);
    }

    // --- Update the abstract with revisions ---
    $abstract->update([
        'title' => $validated['title'],
        'description' => $validated['description'],
        'author_name' => $validated['author_name'],
        'author_institute' => $validated['author_institute'],
        'subtheme' => $validated['subtheme'],
        'keywords' => $validated['keywords'],
        'presentation_mode' => $validated['presentation_mode'],
        'include_in_proceedings' => $request->has('include_in_proceedings'),
        'coauthors' => !empty($coauthors) ? json_encode($coauthors) : null,
        'status' => 'under_review', // ✅ Go directly to reviewers, not admin
        'revision_feedback' => $validated['revision_feedback'],
        'revision_submitted_at' => now(),
        'revision_round' => $nextRound, // Track revision round
        'resubmitted_at' => now(),
        'updated_at' => now(),
    ]);

    // --- (Optional) Notify reviewers who are assigned to re-review ---
    foreach ($reviewersToAssign as $reviewer) {
        $reviewerUser = \App\Models\User::find($reviewer['id']);
        if ($reviewerUser) {
            $this->emailService->sendRevisionResubmittedNotification($abstract, $reviewerUser, $reviewer['number']);

            // In-app notification for reviewer
            $this->notificationService->createReviewAssignmentNotification(
                $reviewerUser,
                $abstract->title,
                $abstract->id
            );
        }
    }

    // In-app notification for author
    $this->notificationService->createAbstractActionNotification(
        Auth::user(),
        'Revision Submitted',
        "Your revision for \"{$abstract->title}\" has been submitted for re-evaluation.",
        $abstract->id
    );

    // In-app notification for admin
    $this->notificationService->createAdminNotification(
        'revision_submitted',
        'New Revision Submitted',
        "A revision has been submitted for: \"{$abstract->title}\" by {$abstract->author_name}",
        ['abstract_id' => $abstract->id],
        route('admin.abstracts.view', $abstract->id)
    );

    return redirect()->route('abstracts.my')
        ->with('success', 'Revision submitted successfully! It has been sent to ' . count($reviewersToAssign) . ' reviewer(s) for re-evaluation.');
}

    public function my()
    {
        $user = Auth::user();
        if ($user && $user->isPartofGroup() && $user->groupMember?->groupRegistration->leader_user_id !== $user->id) {
            return redirect()->route('user.dashboard')->with('error', 'Group members do not have access to abstract management.');
        }

        // Show only current user's abstracts
        $abstracts = AbstractSubmission::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate user's statistics for the enhanced dashboard
        $revisionStatuses = [
            'revision_required', 'revision_requested', 'revision_submitted', 'revision_under_review'
        ];
        $stats = [
            'total' => $abstracts->count(),
            'draft' => $abstracts->where('status', 'draft')->count(),
            'submitted' => $abstracts->where('status', 'submitted')->count(),
            'under_review' => $abstracts->where('status', 'under_review')->count(),
            'accepted' => $abstracts->where('status', 'accepted')->count(),
            'rejected' => $abstracts->where('status', 'rejected')->count(),
            'revision_requested' => $abstracts->whereIn('status', $revisionStatuses)->count(),
        ];

        // Calculate completion rate
        $stats['completion_rate'] = $stats['total'] > 0 ?
            round((($stats['submitted'] + $stats['under_review'] + $stats['accepted']) / $stats['total']) * 100) : 0;

        return view('abstracts.my', compact('abstracts', 'stats'));
    }
}
