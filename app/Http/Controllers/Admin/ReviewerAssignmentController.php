<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\User;
use App\Services\BlindReviewService;
use App\Services\EmailNotificationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewerAssignmentController extends Controller
{
    protected $blindReviewService;
    protected $emailService;
    protected $notificationService;
    protected $assignmentService;

    public function __construct(
        BlindReviewService $blindReviewService,
        EmailNotificationService $emailService,
        NotificationService $notificationService,
        \App\Services\ReviewAssignmentService $assignmentService
    ) {
        $this->blindReviewService = $blindReviewService;
        $this->emailService = $emailService;
        $this->notificationService = $notificationService;
        $this->assignmentService = $assignmentService;
    }

    /**
     * Display reviewer assignment interface
     */
    /**
     * Display reviewer assignment interface
     */
    public function index(Request $request)
    {
        // Get filter parameter (default to 'unassigned')
        $filter = $request->get('filter', 'unassigned');

        // Base query - only SUBMITTED abstracts (not drafts)
        $query = AbstractSubmission::with(['reviewer1', 'reviewer2'])
            ->whereNotIn('status', ['draft']); // Exclude drafts

        // Subtheme filter and sorting
        $selectedSubtheme = $request->get('subtheme');
        $sort = $request->get('sort', 'created_desc');
        $search = $request->get('search');

        if (!empty($selectedSubtheme) && $selectedSubtheme !== 'all') {
            $query->where('subtheme', $selectedSubtheme);
        }

        // Search by ID or title
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        // Apply filters
        switch ($filter) {
            case 'unassigned':
                // Abstracts missing either reviewer (not fully assigned)
                $query->where(function($q) {
                    $q->whereNull('reviewer_id')->orWhereNull('reviewer_2_id');
                });
                break;

            case 'assigned':
                // Both reviewers assigned, no reviews submitted yet
                $query->whereNotNull('reviewer_id')
                      ->whereNotNull('reviewer_2_id')
                      ->whereDoesntHave('reviews', function($q) {
                          $q->where('status', 'submitted');
                      });
                break;

            case 'in_review':
                // Both reviewers assigned, only ONE review submitted (partial completion)
                $query->whereNotNull('reviewer_id')
                      ->whereNotNull('reviewer_2_id')
                      ->has('reviews', '=', 1)
                      ->whereHas('reviews', function($q) {
                          $q->where('status', 'submitted');
                      });
                break;

            case 'reviewed':
                // Both reviewers assigned, BOTH reviews submitted (complete)
                $query->whereNotNull('reviewer_id')
                      ->whereNotNull('reviewer_2_id')
                      ->has('reviews', '>=', 2)
                      ->whereHas('reviews', function($q) {
                          $q->where('status', 'submitted');
                      }, '>=', 2);
                break;

            case 'all':
            default:
                // Show all SUBMITTED abstracts (drafts already excluded above)
                break;
        }

        // Apply sorting
        switch ($sort) {
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'subtheme_asc':
                $query->orderBy('subtheme', 'asc')->orderBy('created_at', 'desc');
                break;
            case 'created_desc':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Paginate results - default to 10 per page
        $perPage = $request->get('per_page', 10);
        $abstracts = $query->paginate($perPage)->withQueryString();

        // Safety redirect: If user is on a page that no longer has results (e.g. due to filtering)
        // redirect them back to page 1 to avoid showing a blank "empty" state.
        if ($abstracts->count() == 0 && $abstracts->total() > 0 && $request->get('page') > 1) {
            $params = $request->all();
            $params['page'] = 1;
            return redirect()->route('admin.abstracts.assign-reviewers-page', $params);
        }

        // Add review completion status to each abstract
        $abstracts->getCollection()->transform(function($abstract) {
            // Get reviews for this abstract
            $reviews = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                ->where('status', 'submitted')
                ->get();

            // Check if reviewer 1 has completed
            $abstract->reviewer1_completed = $abstract->reviewer_id && $reviews->where('reviewer_id', $abstract->reviewer_id)->count() > 0;

            // Check if reviewer 2 has completed
            $abstract->reviewer2_completed = $abstract->reviewer_2_id && $reviews->where('reviewer_id', $abstract->reviewer_2_id)->count() > 0;

            return $abstract;
        });

        // Build subtheme options
        $subthemes = AbstractSubmission::whereNotIn('status', ['draft'])
            ->whereNotNull('subtheme')
            ->select('subtheme')
            ->distinct()
            ->orderBy('subtheme')
            ->pluck('subtheme');

        // Get available reviewers with optimized workload calculation
        $availableReviewers = User::whereHas('roles', function($query) {
                $query->where('name', 'reviewer');
            })
            ->withCount([
                'reviewedAbstracts1' => function($q) { $q->whereNotIn('status', ['draft']); },
                'reviewedAbstracts2' => function($q) { $q->whereNotIn('status', ['draft']); }
            ])
            ->get()
            ->map(function($user) {
                $user->workload = $user->reviewed_abstracts1_count + $user->reviewed_abstracts2_count;
                return $user;
            });

        // Calculate statistics - all abstracts (not filtered by current view filter)
        $statsQuery = AbstractSubmission::whereNotIn('status', ['draft']);
        if (!empty($selectedSubtheme) && $selectedSubtheme !== 'all') {
            $statsQuery->where('subtheme', $selectedSubtheme);
        }
        $allAbstracts = $statsQuery->select('id', 'reviewer_id', 'reviewer_2_id')->get();
        // Load review counts more efficiently
        $reviewCounts = \App\Models\AbstractReview::whereIn('abstract_submission_id', $allAbstracts->pluck('id'))
            ->where('status', 'submitted')
            ->selectRaw('abstract_submission_id, count(*) as count')
            ->groupBy('abstract_submission_id')
            ->pluck('count', 'abstract_submission_id');

        $stats = [
            'total' => $allAbstracts->count(),
            'unassigned' => 0,
            'assigned' => 0,
            'in_review' => 0,
            'reviewed' => 0,
        ];

        foreach ($allAbstracts as $a) {
            $count = $reviewCounts[$a->id] ?? 0;
            if (!$a->reviewer_id || !$a->reviewer_2_id) {
                $stats['unassigned']++;
            } elseif ($count === 0) {
                $stats['assigned']++;
            } elseif ($count === 1) {
                $stats['in_review']++;
            } elseif ($count >= 2) {
                $stats['reviewed']++;
            }
        }

        $stats['percentage'] = $stats['total'] > 0 ? round((($stats['assigned'] + $stats['in_review'] + $stats['reviewed']) / $stats['total']) * 100) : 0;

        $reviewerWorkload = $availableReviewers;

        return view('admin.abstracts.assign-reviewers-new', [
            'abstracts' => $abstracts,
            'availableReviewers' => $availableReviewers,
            'stats' => $stats,
            'filter' => $filter,
            'subthemes' => $subthemes,
            'selectedSubtheme' => $selectedSubtheme,
            'sort' => $sort,
            'search' => $search,
            'perPage' => $perPage,
            'reviewerWorkload' => $reviewerWorkload
        ]);
    }

    /**
     * Assign a single reviewer to an abstract
     */
    public function assignReviewer(Request $request, AbstractSubmission $abstract)
    {
        $validated = $request->validate([
            'reviewer_id' => 'nullable|exists:users,id',
            'reviewer_position' => 'required|in:1,2',
            'override_conflicts' => 'boolean',
            'force_reassign' => 'boolean'
        ]);

        $reviewerPosition = (int)$validated['reviewer_position'];
        $newReviewerId = $validated['reviewer_id'];



        // Handle reviewer removal
        if (is_null($newReviewerId)) {
            $field = $reviewerPosition === 1 ? 'reviewer_id' : 'reviewer_2_id';
            $scoreField = $reviewerPosition === 1 ? 'reviewer_1_score' : 'reviewer_2_score';

            // Safety check for removal
            $currentScore = $abstract->$scoreField;
            if (!is_null($currentScore) && !$request->boolean('force_reassign')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot remove reviewer - review has already been submitted',
                        'requires_force' => true
                    ], 400);
                }
                return redirect()->back()
                    ->with('error', 'Cannot remove reviewer - review already submitted. Use force to override.')
                    ->with('show_force_reassign', true);
            }

            $abstract->update([
                $field => null,
                $scoreField => null
            ]);

            // Re-evaluate status
            $this->blindReviewService->updateAbstractStatusFromAssignments($abstract->id);

            $message = "Reviewer {$reviewerPosition} removed successfully.";
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->back()->with('success', $message);
        }

        // Safety check - prevent reassignment if review is already in progress
        $currentReviewerField = $reviewerPosition === 1 ? 'reviewer_id' : 'reviewer_2_id';
        $currentReviewer = $abstract->$currentReviewerField;
        $scoreField = $reviewerPosition === 1 ? 'reviewer_1_score' : 'reviewer_2_score';
        $currentScore = $abstract->$scoreField;

        // Check if we're trying to change an existing reviewer
        if (!is_null($currentReviewer) && $currentReviewer != $newReviewerId) {
            if (!is_null($currentScore) && !$request->boolean('force_reassign')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot reassign reviewer - review has already been submitted',
                        'requires_force' => true
                    ], 400);
                }
                return redirect()->back()
                    ->with('error', 'Cannot reassign reviewer - review already submitted. Use force to override.')
                    ->with('show_force_reassign', true);
            }

            if (!$request->boolean('force_reassign')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Reviewer already assigned. Use force reassign to override.',
                        'requires_force' => true
                    ], 400);
                }
                return redirect()->back()
                    ->with('error', 'Reviewer already assigned. Use force reassign to override.')
                    ->with('show_force_reassign', true);
            }
        }

        // Use BlindReviewService for assignment
        $result = $this->blindReviewService->assignReviewerWithConflictCheck(
            $abstract->id,
            $newReviewerId,
            $reviewerPosition
        );

        if (!$result['success']) {
            // Check if conflicts can be overridden
            if (isset($result['can_override']) && $result['can_override'] && ($validated['override_conflicts'] ?? false)) {
                // Override conflicts and assign
                $field = $reviewerPosition === 1 ? 'reviewer_id' : 'reviewer_2_id';
                $abstract->update([
                    $field => $newReviewerId,
                    'assigned_at' => now()
                ]);

                // Ensure status is correct and draft review exists
                $this->blindReviewService->updateAbstractStatusFromAssignments($abstract->id);
                $reviewer = User::find($newReviewerId);
                if ($reviewer) {
                    $this->assignmentService->ensureDraftReview($abstract, $reviewer, $reviewerPosition);
                }

                $result = [
                    'success' => true,
                    'message' => 'Reviewer assigned with conflicts overridden'
                ];
            } else {
                if ($request->expectsJson()) {
                    return response()->json($result, 400);
                }
                return redirect()->back()->with('error', $result['message']);
            }
        }

        // Send email notification
        $reviewer = User::find($newReviewerId);
        $matchType = $this->getMatchTypeForReviewer($abstract, $reviewer);
        $this->emailService->sendReviewerAssignment($abstract, $reviewer, 'reviewer', [
            'assignment_type' => 'assigned',
            'match_type' => $matchType
        ]);

        // In-app notification
        $this->notificationService->createReviewAssignmentNotification(
            $reviewer,
            $abstract->title,
            $abstract->id
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }
        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Assign both reviewers to an abstract
     */
    public function assignBothReviewers(Request $request, AbstractSubmission $abstract)
    {
        $validated = $request->validate([
            'reviewer_1_id' => 'required|exists:users,id',
            'reviewer_2_id' => 'required|exists:users,id|different:reviewer_1_id'
        ]);

        // Check if abstract already has reviewers assigned
        if ($abstract->reviewer_id || $abstract->reviewer_2_id) {
            return redirect()->back()->with('error', 'This abstract already has reviewers assigned. Use the change reviewer option instead.');
        }

        // Validate that both reviewers are different
        if ($validated['reviewer_1_id'] === $validated['reviewer_2_id']) {
            return redirect()->back()->with('error', 'Reviewer A and Reviewer B must be different people.');
        }

        // 🚫 CRITICAL: Prevent author from reviewing their own abstract
        if ($abstract->user_id == $validated['reviewer_1_id']) {
            return redirect()->back()->with('error', '⚠️ Cannot assign Reviewer A - This person is the author of this abstract!');
        }

        if ($abstract->user_id == $validated['reviewer_2_id']) {
            return redirect()->back()->with('error', '⚠️ Cannot assign Reviewer B - This person is the author of this abstract!');
        }

        // Check for conflicts
        $conflicts1 = $abstract->checkAllConflicts($validated['reviewer_1_id']);
        $conflicts2 = $abstract->checkAllConflicts($validated['reviewer_2_id']);

        $allConflicts = array_merge($conflicts1, $conflicts2);

        if (!empty($allConflicts) && !($request->override_conflicts ?? false)) {
            $conflictMessages = array_column($allConflicts, 'reason');
            return redirect()->back()->with('error', 'Conflicts detected: ' . implode(', ', $conflictMessages));
        }

        // Assign reviewers
        $abstract->update([
            'reviewer_id' => $validated['reviewer_1_id'],
            'reviewer_2_id' => $validated['reviewer_2_id'],
            'assigned_at' => now(),
            'status' => 'under_review'
        ]);

        // Send email notifications
        $reviewer1 = User::find($validated['reviewer_1_id']);
        $reviewer2 = User::find($validated['reviewer_2_id']);

        if ($reviewer1) {
            $this->assignmentService->ensureDraftReview($abstract, $reviewer1, 1);
        }
        if ($reviewer2) {
            $this->assignmentService->ensureDraftReview($abstract, $reviewer2, 2);
        }

        $matchType1 = $this->getMatchTypeForReviewer($abstract, $reviewer1);
        $this->emailService->sendReviewerAssignment($abstract, $reviewer1, 'reviewer', [
            'assignment_type' => 'assigned',
            'match_type' => $matchType1
        ]);
        
        // Add small delay to avoid SMTP rate limits (especially for Mailtrap free plan)
        sleep(2);

        $matchType2 = $this->getMatchTypeForReviewer($abstract, $reviewer2);
        $this->emailService->sendReviewerAssignment($abstract, $reviewer2, 'reviewer', [
            'assignment_type' => 'assigned',
            'match_type' => $matchType2
        ]);

        // In-app notifications
        $this->notificationService->createReviewAssignmentNotification(
            $reviewer1,
            $abstract->title,
            $abstract->id
        );
        $this->notificationService->createReviewAssignmentNotification(
            $reviewer2,
            $abstract->title,
            $abstract->id
        );

        return redirect()->back()->with('success', 'Both reviewers assigned successfully! 🎯');
    }

    /**
     * Store reviewer assignment from form
     */
    public function storeReviewerAssignment(Request $request, AbstractSubmission $abstract)
    {
        $request->validate([
            'reviewer_1_id' => 'nullable|exists:users,id',
            'reviewer_2_id' => 'nullable|exists:users,id|different:reviewer_1_id'
        ]);

        $updates = [];

        // Prevent changing reviewer 1 if a submitted review exists or score recorded
        if ($request->filled('reviewer_1_id')) {
            $newReviewer1 = (int) $request->reviewer_1_id;
            $currentReviewer1 = $abstract->reviewer_id;
            // Allow reassignment if different reviewer, OR if re-assigning same (no-op)
            if ($currentReviewer1 && $currentReviewer1 !== $newReviewer1) {
                // Only block if THIS SPECIFIC reviewer has submitted
                $hasSubmittedByCurrent = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                    ->where('reviewer_id', $currentReviewer1)
                    ->where('status', 'submitted')
                    ->exists();
                if ($hasSubmittedByCurrent || !is_null($abstract->reviewer_1_score)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot change Reviewer A after they have submitted a review.'
                        ], 400);
                    }
                    return redirect()->back()->with('error', 'Cannot change Reviewer A after they have submitted a review.');
                }
            }
            $updates['reviewer_id'] = $newReviewer1;
        }

        // Prevent changing reviewer 2 if a submitted review exists or score recorded
        if ($request->filled('reviewer_2_id')) {
            $newReviewer2 = (int) $request->reviewer_2_id;
            $currentReviewer2 = $abstract->reviewer_2_id;
            // Allow reassignment if different reviewer
            if ($currentReviewer2 && $currentReviewer2 !== $newReviewer2) {
                // Only block if THIS SPECIFIC reviewer has submitted
                $hasSubmittedByCurrent = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                    ->where('reviewer_id', $currentReviewer2)
                    ->where('status', 'submitted')
                    ->exists();
                if ($hasSubmittedByCurrent || !is_null($abstract->reviewer_2_score)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot change Reviewer B after they have submitted a review.'
                        ], 400);
                    }
                    return redirect()->back()->with('error', 'Cannot change Reviewer B after they have submitted a review.');
                }
            }
            $updates['reviewer_2_id'] = $newReviewer2;
        }

        if (empty($updates)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one reviewer.'
                ], 400);
            }
            return redirect()->back()->with('error', 'Please select at least one reviewer.');
        }

        // Capture old values BEFORE update
        $oldReviewer1 = $abstract->reviewer_id;
        $oldReviewer2 = $abstract->reviewer_2_id;

        // Determine final reviewer presence after this update
        $newReviewer1 = $updates['reviewer_id'] ?? $abstract->reviewer_id;
        $newReviewer2 = $updates['reviewer_2_id'] ?? $abstract->reviewer_2_id;
        $updates['assigned_at'] = now();
        // If both assigned, move to under_review, else mark reviewer_assigned
        $updates['status'] = ($newReviewer1 && $newReviewer2) ? 'under_review' : 'reviewer_assigned';
        $abstract->update($updates);

        // Send notifications for new assignments AFTER returning response (async-style)
        // For AJAX requests, we'll queue the notifications to avoid blocking
        $reviewersToNotify = [];

        if (isset($updates['reviewer_id']) && $updates['reviewer_id'] != $oldReviewer1) {
            $reviewersToNotify[] = ['id' => $updates['reviewer_id'], 'type' => 'reviewer'];
        }

        if (isset($updates['reviewer_2_id']) && $updates['reviewer_2_id'] != $oldReviewer2) {
            $reviewersToNotify[] = ['id' => $updates['reviewer_2_id'], 'type' => 'reviewer'];
        }

        // Ensure draft review records exist for any newly assigned reviewers
        if (isset($updates['reviewer_id']) && $updates['reviewer_id'] != $oldReviewer1) {
            $reviewer = User::find($updates['reviewer_id']);
            if ($reviewer) {
                $this->assignmentService->ensureDraftReview($abstract, $reviewer, 1);
            }
        }
        if (isset($updates['reviewer_2_id']) && $updates['reviewer_2_id'] != $oldReviewer2) {
            $reviewer = User::find($updates['reviewer_2_id']);
            if ($reviewer) {
                $this->assignmentService->ensureDraftReview($abstract, $reviewer, 2);
            }
        }

        // Send notifications (Email & In-app)
        $notifiedCount = 0;
        foreach ($reviewersToNotify as $reviewerData) {
            $reviewer = User::find($reviewerData['id']);
            if ($reviewer) {
                // If this is the second reviewer in this request, add a small delay for SMTP rate limits
                if ($notifiedCount > 0) {
                    sleep(2);
                }

                // Send email (Sent synchronously to match other system emails)
                $matchType = $this->getMatchTypeForReviewer($abstract, $reviewer);
                $this->emailService->sendReviewerAssignment($abstract, $reviewer, $reviewerData['type'], [
                    'assignment_type' => 'assigned',
                    'match_type' => $matchType
                ]);
                
                // Create in-app notification
                $this->notificationService->createReviewAssignmentNotification($reviewer, $abstract->title, $abstract->id);
                
                $notifiedCount++;
            }
        }

        if ($request->expectsJson()) {
            // Refresh abstract with relationships
            $abstract->refresh();
            $abstract->load(['reviewer1', 'reviewer2']);

            // Get review completion status (single query)
            $reviews = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                ->where('status', 'submitted')
                ->pluck('reviewer_id');

            $reviewer1Completed = $abstract->reviewer_id && $reviews->contains($abstract->reviewer_id);
            $reviewer2Completed = $abstract->reviewer_2_id && $reviews->contains($abstract->reviewer_2_id);

            // Get updated stats using optimized queries
            $totalCount = AbstractSubmission::whereNotIn('status', ['draft'])->count();

            $unassignedCount = AbstractSubmission::whereNotIn('status', ['draft'])
                ->where(function($q) {
                    $q->whereNull('reviewer_id')->orWhereNull('reviewer_2_id');
                })->count();

            $assignedCount = $totalCount - $unassignedCount;
            $percentage = $totalCount > 0 ? round(($assignedCount / $totalCount) * 100) : 0;

            $stats = [
                'total' => $totalCount,
                'unassigned' => $unassignedCount,
                'percentage' => $percentage,
            ];

            // Get updated reviewer workloads with optimized query
            $reviewerWorkloads = User::whereHas('roles', function($query) {
                    $query->where('name', 'reviewer');
                })
                ->withCount([
                    'reviewedAbstracts1' => function($q) {
                        $q->whereNotIn('status', ['draft']);
                    },
                    'reviewedAbstracts2' => function($q) {
                        $q->whereNotIn('status', ['draft']);
                    }
                ])
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'workload' => ($user->reviewed_abstracts1_count ?? 0) + ($user->reviewed_abstracts2_count ?? 0)
                    ];
                })
                ->sortByDesc('workload')
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Reviewers assigned successfully! 🎯',
                'abstract' => [
                    'id' => $abstract->id,
                    'title' => $abstract->title,
                    'reviewer_id' => $abstract->reviewer_id,
                    'reviewer_2_id' => $abstract->reviewer_2_id,
                    'reviewer1' => $abstract->reviewer1 ? [
                        'id' => $abstract->reviewer1->id,
                        'first_name' => $abstract->reviewer1->first_name,
                        'last_name' => $abstract->reviewer1->last_name,
                        'name' => $abstract->reviewer1->name
                    ] : null,
                    'reviewer2' => $abstract->reviewer2 ? [
                        'id' => $abstract->reviewer2->id,
                        'first_name' => $abstract->reviewer2->first_name,
                        'last_name' => $abstract->reviewer2->last_name,
                        'name' => $abstract->reviewer2->name
                    ] : null,
                    'reviewer1_completed' => $reviewer1Completed,
                    'reviewer2_completed' => $reviewer2Completed,
                    'is_fully_assigned' => $abstract->reviewer_id && $abstract->reviewer_2_id
                ],
                'stats' => $stats,
                'reviewerWorkloads' => $reviewerWorkloads
            ]);
        }

        return redirect()->back()->with('success', 'Reviewers assigned successfully! 🎯');
    }

    /**
     * Get reviewer assignment statistics
     */
    public function getAssignmentStats()
    {
        $allSubmittedAbstracts = AbstractSubmission::whereNotIn('status', ['draft'])->get();

        $stats = [
            'total_for_review' => $allSubmittedAbstracts->count(),
            'unassigned' => $allSubmittedAbstracts->filter(fn($a) =>
                !$a->reviewer_id || !$a->reviewer_2_id
            )->count(),
            'assigned' => $allSubmittedAbstracts->filter(fn($a) =>
                $a->reviewer_id && $a->reviewer_2_id &&
                !$a->reviewer_1_score && !$a->reviewer_2_score
            )->count(),
            'partially_reviewed' => $allSubmittedAbstracts->filter(fn($a) =>
                $a->reviewer_id && $a->reviewer_2_id &&
                (($a->reviewer_1_score && !$a->reviewer_2_score) || (!$a->reviewer_1_score && $a->reviewer_2_score))
            )->count(),
            'fully_reviewed' => $allSubmittedAbstracts->filter(fn($a) =>
                $a->reviewer_id && $a->reviewer_2_id && $a->reviewer_1_score && $a->reviewer_2_score
            )->count(),
        ];

        $stats['percentage'] = $stats['total_for_review'] > 0
            ? round((($stats['assigned'] + $stats['partially_reviewed'] + $stats['fully_reviewed']) / $stats['total_for_review']) * 100)
            : 0;

        return response()->json($stats);
    }

    /**
     * Reassign reviewers for an abstract
     */
    public function reassignReviewers(Request $request, AbstractSubmission $abstract)
    {
        $request->validate([
            'reviewer_1_id' => 'required|exists:users,id',
            'reviewer_2_id' => 'required|exists:users,id|different:reviewer_1_id',
        ]);

        try {
            // Validate individual reviewers if provided
            if ($request->has('reviewer_1_id')) {
                $reviewer1 = User::findOrFail($request->reviewer_1_id);
                if (!$reviewer1->hasRole('reviewer')) {
                    return redirect()->back()->with('error', 'Reviewer A must have reviewer role.');
                }

                // Check if current reviewer 1 has submitted
                $hasSubmitted1 = $abstract->reviews()
                    ->where('reviewer_id', $abstract->reviewer_id)
                    ->where('status', 'submitted')
                    ->exists();

                if ($hasSubmitted1 || !is_null($abstract->reviewer_1_score)) {
                    return redirect()->back()->with('error', 'Cannot reassign Reviewer A because they have already submitted a review.');
                }

                // Store old ID before update
                $oldReviewer1Id = $abstract->reviewer_id;

                // Update reviewer 1
                $abstract->update([
                    'reviewer_id' => $reviewer1->id,
                    'reviewer_1_score' => null,
                    'reviewer_1_comments' => null,
                ]);

                $this->assignmentService->ensureDraftReview($abstract, $reviewer1, 1);

                // Notify new reviewer 1
                $matchType1 = $this->getMatchTypeForReviewer($abstract, $reviewer1);
                $this->emailService->sendReviewerAssignment($abstract, $reviewer1, 'reviewer', [
                    'assignment_type' => 'reassigned',
                    'match_type' => $matchType1
                ]);
                $this->notificationService->createReviewAssignmentNotification($reviewer1, $abstract->title, $abstract->id);

                // Clear drafts for old reviewer 1
                if ($oldReviewer1Id) {
                    $abstract->reviews()
                        ->where('reviewer_id', $oldReviewer1Id)
                        ->where('status', '!=', 'submitted')
                        ->delete();
                    $oldReviewer = User::find($oldReviewer1Id);
                    if ($oldReviewer) {
                        $this->emailService->sendReviewerRevocation($abstract, $oldReviewer, 'Reviewer reassigned by admin');
                    }
                }
            }

            if ($request->has('reviewer_2_id')) {
                $reviewer2 = User::findOrFail($request->reviewer_2_id);
                if (!$reviewer2->hasRole('reviewer')) {
                    return redirect()->back()->with('error', 'Reviewer B must have reviewer role.');
                }

                // Check if current reviewer 2 has submitted
                $hasSubmitted2 = $abstract->reviews()
                    ->where('reviewer_id', $abstract->reviewer_2_id)
                    ->where('status', 'submitted')
                    ->exists();

                if ($hasSubmitted2 || !is_null($abstract->reviewer_2_score)) {
                    return redirect()->back()->with('error', 'Cannot reassign Reviewer B because they have already submitted a review.');
                }

                // Store old ID before update
                $oldReviewer2Id = $abstract->reviewer_2_id;

                // Update reviewer 2
                $abstract->update([
                    'reviewer_2_id' => $reviewer2->id,
                    'reviewer_2_score' => null,
                    'reviewer_2_comments' => null,
                ]);

                $this->assignmentService->ensureDraftReview($abstract, $reviewer2, 2);

                // Notify new reviewer 2
                $matchType2 = $this->getMatchTypeForReviewer($abstract, $reviewer2);
                $this->emailService->sendReviewerAssignment($abstract, $reviewer2, 'reviewer', [
                    'assignment_type' => 'reassigned',
                    'match_type' => $matchType2
                ]);
                $this->notificationService->createReviewAssignmentNotification($reviewer2, $abstract->title, $abstract->id);

                // Clear drafts for old reviewer 2
                if ($oldReviewer2Id) {
                    $abstract->reviews()
                        ->where('reviewer_id', $oldReviewer2Id)
                        ->where('status', '!=', 'submitted')
                        ->delete();
                    $oldReviewer = User::find($oldReviewer2Id);
                    if ($oldReviewer) {
                        $this->emailService->sendReviewerRevocation($abstract, $oldReviewer, 'Reviewer reassigned by admin');
                    }
                }
            }

            return redirect()->back()->with('success', 'Reviewers updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error reassigning reviewers: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reassign reviewers. Please try again.');
        }
    }

    /**
     * Auto-assign all unassigned abstracts to reviewers
     */
    public function autoAssignAll(Request $request)
    {
        try {
            $assignableStatuses = ['submitted', 'reviewer_assigned', 'under_review', 'revision_submitted', 'revision_under_review'];

            // Get all assignable abstracts that are missing either reviewer
            $unassignedAbstracts = AbstractSubmission::whereIn('status', $assignableStatuses)
                ->where(function($query) {
                    $query->whereNull('reviewer_id')
                          ->orWhereNull('reviewer_2_id');
                })
                ->get();

            if ($unassignedAbstracts->isEmpty()) {
                return redirect()->back()->with('info', 'All abstracts already have reviewers assigned.');
            }

            // Get available reviewers
            $reviewers = User::whereHas('roles', function($query) {
                $query->where('name', 'reviewer');
            })
            ->where('reviewer_preferences_set', true)
            ->get();

            if ($reviewers->isEmpty()) {
                return redirect()->back()->with('error', 'No reviewers with saved preferences are available for auto-assignment.');
            }

            $assignedCount = 0;
            $failedCount = 0;

            foreach ($unassignedAbstracts as $abstract) {
                // Assign Reviewer 1 if missing
                if (!$abstract->reviewer_id) {
                    $result = $this->assignmentService->autoAssign($abstract, 1);
                    if ($result) $assignedCount++;
                    else $failedCount++;
                }

                // Assign Reviewer 2 if missing
                if (!$abstract->reviewer_2_id) {
                    $result = $this->assignmentService->autoAssign($abstract, 2);
                    if ($result) $assignedCount++;
                    else $failedCount++;
                }
            }

            return redirect()->back()->with('success', "Auto-assignment completed. {$assignedCount} reviewers assigned. {$failedCount} assignments could not be completed automatically.");

        } catch (\Exception $e) {
            Log::error('Auto-assignment error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Auto-assignment failed. ' . $e->getMessage());
        }
    }

    /**
     * Determine assignment match type for a reviewer.
     */
    private function getMatchTypeForReviewer(AbstractSubmission $abstract, User $reviewer): string
    {
        if (empty($abstract->subtheme)) {
            return 'fallback';
        }

        $interests = $reviewer->interests()->pluck('subtheme_name')->toArray();
        if (in_array($abstract->subtheme, $interests, true)) {
            return 'exact';
        }

        $relatedMap = config('conference.related_subthemes', []);
        $related = $relatedMap[$abstract->subtheme] ?? [];
        if (!empty($related) && count(array_intersect($related, $interests)) > 0) {
            return 'related';
        }

        return 'fallback';
    }
}
