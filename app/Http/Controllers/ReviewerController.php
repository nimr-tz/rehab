<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\User;
use App\Services\BlindReviewService;
use App\Services\ReviewWorkflowService;
use App\Services\EmailNotificationService;
use App\Services\NotificationService;
use App\Services\ReviewAssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\ReviewerPerformanceTrackingService;
class ReviewerController extends Controller
{
    private const FINAL_ABSTRACT_STATUSES = ['accepted', 'rejected'];

    protected $emailService;
    protected $notificationService;
    protected $assignmentService;

    public function __construct(
        EmailNotificationService $emailService,
        NotificationService $notificationService,
        ReviewAssignmentService $assignmentService
    )
    {
        $this->emailService = $emailService;
        $this->notificationService = $notificationService;
        $this->assignmentService = $assignmentService;
    }
    public function dashboard()
    {
        $reviewer = Auth::user();

        $performanceService = app(ReviewerPerformanceTrackingService::class);
        $performanceData = $performanceService->getReviewerPerformance($reviewer->id);
        $metrics = $performanceData['metrics'] ?? [];

        $totalReviewed = $metrics['basic_stats']['total_completed'] ?? 0;
        $qualityScore  = $metrics['quality_metrics']['quality_score'] ?? 0;
        $consistency   = $metrics['consistency_metrics']['average_consistency_score'] ?? 0;
        $onTimeRate    = $metrics['timeliness_metrics']['timeliness_score'] ?? 0;
        $avgScore      = $metrics['basic_stats']['average_score'] ?? 0;
        $avgCommentLen = $metrics['basic_stats']['average_comment_length'] ?? 0;

        $improvements = $this->buildImprovementFeedback($metrics, $totalReviewed);

        return view('reviewer.congratulations', compact(
            'reviewer',
            'totalReviewed',
            'qualityScore',
            'consistency',
            'onTimeRate',
            'avgScore',
            'avgCommentLen',
            'improvements'
        ));
    }

    private function buildImprovementFeedback(array $metrics, int $totalReviewed): array
    {
        if ($totalReviewed === 0) {
            return [];
        }

        $items = [];

        $shortComments   = $metrics['quality_metrics']['short_comments'] ?? 0;
        $missingCriteria = $metrics['quality_metrics']['missing_criteria'] ?? 0;
        $lateReviews     = $metrics['timeliness_metrics']['late_reviews'] ?? 0;
        $earlyReviews    = $metrics['timeliness_metrics']['early_reviews'] ?? 0;
        $avgScoreDiff    = $metrics['consistency_metrics']['average_score_difference'] ?? 0;
        $highDisagreement = $metrics['consistency_metrics']['high_disagreement_reviews'] ?? 0;
        $avgCommentLen   = $metrics['basic_stats']['average_comment_length'] ?? 0;
        $avgScore        = $metrics['basic_stats']['average_score'] ?? 0;

        if ($shortComments > 0) {
            $pct = round(($shortComments / $totalReviewed) * 100);
            $items[] = [
                'title' => 'Written feedback too brief',
                'body'  => "{$shortComments} of your {$totalReviewed} reviews ({$pct}%) contained fewer than 50 characters of written feedback. "
                         . "Authors rely on reviewer comments to understand the strengths and weaknesses of their work. "
                         . "Aim for at least 150 characters per review — explain your reasoning rather than simply stating a conclusion.",
            ];
        }

        if ($avgCommentLen > 0 && $avgCommentLen < 100 && $shortComments === 0) {
            $items[] = [
                'title' => 'Comments could be more detailed',
                'body'  => "Your average comment length was {$avgCommentLen} characters. While none were critically short, "
                         . "more detailed feedback (150+ characters) gives authors clearer guidance for improving their work.",
            ];
        }

        if ($missingCriteria > 0) {
            $pct = round(($missingCriteria / $totalReviewed) * 100);
            $items[] = [
                'title' => 'Incomplete rubric scores',
                'body'  => "{$missingCriteria} of your {$totalReviewed} reviews ({$pct}%) had one or more rubric criteria left blank. "
                         . "Every criterion exists for a reason — incomplete scoring makes it harder for the scientific committee to make fair, consistent decisions. "
                         . "Please ensure all fields are filled before submitting.",
            ];
        }

        if ($lateReviews > 0) {
            $items[] = [
                'title' => 'Some abstracts took over 4 days to review',
                'body'  => "{$lateReviews} of your {$totalReviewed} abstract(s) went more than 4 days between assignment and submission. "
                         . "For context, the scientific committee aims to keep the review cycle within 4 days per abstract to stay on schedule.",
            ];
        }

if ($highDisagreement > 0 && $avgScoreDiff > 15) {
            $items[] = [
                'title' => 'Scoring diverged significantly from the second reviewer',
                'body'  => "On {$highDisagreement} abstract(s), your score differed from the other reviewer by more than 20 points "
                         . "(your overall average difference was " . round($avgScoreDiff, 1) . " points). "
                         . "Large disagreements are not necessarily wrong, but they trigger additional committee review and can slow decisions. "
                         . "Re-reading the scoring rubric before each review can help calibrate your assessments.",
            ];
        }

        return $items;
    }

    /**
     * Centralized logic for reviewer stats
     */
    private function getReviewerStats($reviewer, $allAssignments = null)
    {
        // Get all currently actionable assignments for this reviewer.
        $allAssignments = $allAssignments
            ?? AbstractSubmission::where(function($q) use ($reviewer) {
                $q->where('reviewer_id', $reviewer->id)
                  ->orWhere('reviewer_2_id', $reviewer->id);
            })
            ->with('reviews')
            ->get()
            ->reject(fn ($abstract) => in_array($abstract->status, self::FINAL_ABSTRACT_STATUSES, true))
            ->filter(fn ($abstract) => $this->reviewerHasCurrentSeat($abstract, $reviewer->id))
            ->values();

        // Excluded statuses (waiting for author or final)
        $excludedStatuses = ['revision_required', 'revision_requested', 'accepted', 'rejected'];

        // 1. Completed: Papers where the reviewer's LATEST review is submitted
        $completedCount = $allAssignments->filter(function($abstract) use ($reviewer) {
            $latestReview = $abstract->reviews()
                ->where('reviewer_id', $reviewer->id)
                ->orderBy('review_round', 'desc')
                ->first();

            return $latestReview && $latestReview->status === 'submitted';
        })->count();

        // Helper to know if current round is completed for this reviewer
        $isCurrentRoundCompleted = function ($abstract) use ($reviewer) {
            $currentRound = $abstract->revision_round ?? 0;

            $currentReview = $abstract->reviews()
                ->where('reviewer_id', $reviewer->id)
                ->where('review_round', $currentRound)
                ->first();

            return $currentReview && $currentReview->status === 'submitted';
        };

        // Helper to know if there is an actionable draft for the current round
        $hasCurrentRoundDraft = function ($abstract) use ($reviewer) {
            $currentRound = $abstract->revision_round ?? 0;

            return $abstract->reviews()
                ->where('reviewer_id', $reviewer->id)
                ->where('review_round', $currentRound)
                ->where('status', 'draft')
                ->exists();
        };

        // 2. Re-Reviews: Round > 0 with an actionable current-round draft
        $reReviewsCount = $allAssignments->filter(function($abstract) use ($excludedStatuses, $isCurrentRoundCompleted, $hasCurrentRoundDraft) {
            if (in_array($abstract->status, $excludedStatuses)) {
                return false;
            }

            $currentRound = $abstract->revision_round ?? 0;
            if ($currentRound <= 0) {
                return false;
            }

            return !$isCurrentRoundCompleted($abstract) && $hasCurrentRoundDraft($abstract);
        })->count();

        // 3. Pending New: Initial-round abstracts (round 0) with an actionable draft
        $pendingNewCount = $allAssignments->filter(function($abstract) use ($excludedStatuses, $isCurrentRoundCompleted, $hasCurrentRoundDraft) {
            if (in_array($abstract->status, $excludedStatuses)) {
                return false;
            }

            $currentRound = $abstract->revision_round ?? 0;
            if ($currentRound > 0) {
                return false;
            }

            return !$isCurrentRoundCompleted($abstract) && $hasCurrentRoundDraft($abstract);
        })->count();

        // 4. Overall pending: anything assigned that isn't completed yet
        $pendingTotal = max(0, $allAssignments->count() - $completedCount);
        $historicalCompletedReviews = AbstractReview::where('reviewer_id', $reviewer->id)
            ->where('status', 'submitted')
            ->count();
        $historicalCompletedAbstracts = AbstractReview::where('reviewer_id', $reviewer->id)
            ->where('status', 'submitted')
            ->distinct('abstract_submission_id')
            ->count('abstract_submission_id');

        return [
            'total_assigned' => $allAssignments->count(),
            'completed' => $completedCount,
            'pending_new' => $pendingNewCount,
            'pending' => $pendingTotal, // Overall pending
            'pending_total' => $pendingTotal,
            'revisions' => $reReviewsCount, // Alias
            're_reviews' => $reReviewsCount,
            'historical_completed_reviews' => $historicalCompletedReviews,
            'historical_completed_abstracts' => $historicalCompletedAbstracts,
            'drafts' => AbstractReview::where('reviewer_id', $reviewer->id)
                ->where('status', 'draft')
                ->whereIn('abstract_submission_id', $allAssignments->pluck('id'))
                ->count(),
        ];
    }

    private function reviewerHasCurrentSeat(AbstractSubmission $abstract, int $reviewerId): bool
    {
        if ((int) $abstract->reviewer_id !== $reviewerId && (int) $abstract->reviewer_2_id !== $reviewerId) {
            return false;
        }

        $currentRound = (int) ($abstract->revision_round ?? 0);
        $reviews = $abstract->relationLoaded('reviews') ? $abstract->reviews : $abstract->reviews()->get();

        return $reviews->contains(function ($review) use ($reviewerId, $currentRound) {
            return (int) $review->reviewer_id === $reviewerId
                && (int) ($review->review_round ?? 0) === $currentRound
                && in_array($review->status, ['draft', 'submitted'], true);
        });
    }

    public function abstracts(Request $request)
    {
        return redirect()->route('reviewer.dashboard');
        $reviewer = Auth::user();
        $search = $request->get('search');
        $status = $request->get('status');

        // Base query - only show abstracts currently assigned to this reviewer.
        // Historical review rows should not bring returned-to-pool work back into view.
        $query = AbstractSubmission::where(function($q) use ($reviewer) {
            $q->where('reviewer_id', $reviewer->id)
              ->orWhere('reviewer_2_id', $reviewer->id);
        })->with(['reviewer1', 'reviewer2', 'reviews']);

        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('subtheme', 'like', "%{$search}%");
            });
        }

        // Status filter - Only relevant statuses
        if ($status === 'pending') {
            // PENDING (NEW REVIEWS ONLY): First-time reviews (round 0 or 1) not yet submitted
            $query->where(function($q) use ($reviewer) {
                $q->where(function($subQ) use ($reviewer) {
                    // Reviewer 1 assignment
                    $subQ->where('reviewer_id', $reviewer->id);
                })->orWhere(function($subQ) use ($reviewer) {
                    // Reviewer 2 assignment
                    $subQ->where('reviewer_2_id', $reviewer->id);
                });
            })
            // ONLY first-time reviews (round 0) - EXCLUDE re-reviews
            ->where(function($q) {
                $q->whereNull('revision_round')
                  ->orWhere('revision_round', 0);
            })
            // Exclude waiting for author and final statuses
            ->whereNotIn('status', ['revision_required', 'revision_requested', 'accepted', 'rejected'])
            // Only show if NOT completed for current round AND a review record exists for current round
            ->whereHas('reviews', function($reviewQuery) use ($reviewer) {
                $reviewQuery->where('reviewer_id', $reviewer->id)
                           ->where('review_round', 0)
                           ->where('status', 'draft'); // It must be in draft/pending state to be "pending"
            });
        } elseif ($status === 'revisions') {
            // RE-REVIEWS ONLY: Round 2+ abstracts that haven't been reviewed for current round
            $query->where(function($q) use ($reviewer) {
                $q->where('reviewer_id', $reviewer->id)
                  ->orWhere('reviewer_2_id', $reviewer->id);
            })
            // Must be revision round > 0
            ->where('revision_round', '>', 0)
            // Exclude waiting for author
            ->whereNotIn('status', ['revision_required', 'revision_requested', 'accepted', 'rejected'])
            // Only show if a review record exists for current round AND it is NOT yet submitted
            ->whereHas('reviews', function($reviewQuery) use ($reviewer) {
                $reviewQuery->where('reviewer_id', $reviewer->id)
                           ->whereRaw('review_round = abstract_submissions.revision_round')
                           ->where('status', 'draft');
            });
        } elseif ($status === 'completed') {
            // COMPLETED: Has submitted review for current round
            $query->where(function($q) use ($reviewer) {
                $q->where('reviewer_id', $reviewer->id)
                  ->orWhere('reviewer_2_id', $reviewer->id);
            })->whereHas('reviews', function($reviewQuery) use ($reviewer) {
                $reviewQuery->where('reviewer_id', $reviewer->id)
                           ->where('status', 'submitted')
                           ->whereRaw('review_round = COALESCE(abstract_submissions.revision_round, 0)');
            });
        }

        // Get all results (no pagination) and sort with incomplete reviews at the top
        $assignedAbstracts = $query->get()
            ->reject(fn ($abstract) => in_array($abstract->status, self::FINAL_ABSTRACT_STATUSES, true))
            ->filter(fn ($abstract) => $this->reviewerHasCurrentSeat($abstract, $reviewer->id))
            ->sortBy(function($abstract) use ($reviewer) {
            $currentRound = $abstract->revision_round ?? 0;

            // Check for record in current round
            $currentReview = $abstract->reviews()
                ->where('reviewer_id', $reviewer->id)
                ->where('review_round', $currentRound)
                ->first();

            $isSubmittedForCurrentRound = $currentReview && $currentReview->status === 'submitted';

            // Check if they ever accepted in a PREVIOUS round (meaning they are done)
            $hasAlreadyAccepted = $abstract->reviews()
                ->where('reviewer_id', $reviewer->id)
                ->where('status', 'submitted')
                ->whereIn('recommendation', ['accept', 'accept_oral', 'accept_poster'])
                ->exists();

            // Return 0 for incomplete (will be at top), 1 for completed (will be at bottom)
            // It is NOT completed if they have a draft for current round OR if they are assigned but haven't started.
            // It IS completed if they submitted for this round OR if they already accepted previously.
            return ($isSubmittedForCurrentRound || $hasAlreadyAccepted) ? 1 : 0;
        })->values(); // Reset keys after sorting

        // Use centralized stats logic based on the same visible assignment set.
        $stats = $this->getReviewerStats($reviewer, $assignedAbstracts);

        return view('reviewer.abstracts', compact('assignedAbstracts', 'stats'));
    }

    /**
     * Show comparison view for revisions
     */
    public function comparison(AbstractSubmission $abstract)
    {
        return redirect()->route('reviewer.dashboard');
        $reviewer = Auth::user();

        // Check if this reviewer is assigned to this abstract
        if ($abstract->reviewer_id !== $reviewer->id && $abstract->reviewer_2_id !== $reviewer->id) {
            abort(403, 'You are not assigned to review this abstract.');
        }

        // Get comparison data
        $comparisonService = app(\App\Services\RevisionComparisonService::class);
        $currentRound = $abstract->revision_round ?? 1;

        if ($currentRound <= 1) {
            return redirect()->route('reviewer.review', $abstract)
                ->with('info', 'This is the original submission. No comparison available.');
        }

        $comparison = $comparisonService->generateComparisonData($abstract, 1, $currentRound);

        return view('reviewer.comparison', [
            'abstract' => $abstract,
            'comparison' => $comparison,
            'currentRound' => $currentRound,
        ]);
    }

    public function review(AbstractSubmission $abstract)
    {
        return redirect()->route('reviewer.dashboard');
        $reviewer = Auth::user();

        // Check if this reviewer is assigned to this abstract
        if ($abstract->reviewer_id !== $reviewer->id && $abstract->reviewer_2_id !== $reviewer->id) {
            abort(403, 'You are not assigned to review this abstract.');
        }

        // Check if abstract is waiting for author revisions
        if (in_array($abstract->status, ['revision_required', 'revision_requested'])) {
            return redirect()->route('reviewer.abstracts')
                ->with('info', 'This abstract is currently returned to the author for revisions. You will be notified when the revision is submitted.');
        }

        // Load the reviews relationship
        $abstract->load(['reviews' => function($query) use ($reviewer) {
            $query->where('reviewer_id', $reviewer->id)->orderBy('review_round');
        }]);

        // Get review data using BlindReviewService
        $blindReviewService = new BlindReviewService();
        $reviewData = $blindReviewService->getAnonymizedAbstractForReviewer($abstract->id, $reviewer->id);

        if (!$reviewData) {
            abort(403, 'Unable to access review data.');
        }

        // Determine reviewer position and current review
        $reviewerPosition = $abstract->reviewer_id === $reviewer->id ? 1 : 2;

        // Get the current revision round of the abstract
        $currentRevisionRound = $abstract->revision_round ?? 0;

        // 1. Try to get a review for the current abstract round
        $currentReview = $abstract->reviews()
            ->where('reviewer_id', $reviewer->id)
            ->where('review_round', $currentRevisionRound)
            ->first();

        // 2. If no record for the abstract's current round, find the LATEST submitted review from a prev round
        if (!$currentReview) {
            $currentReview = $abstract->reviews()
                ->where('reviewer_id', $reviewer->id)
                ->where('status', 'submitted')
                ->orderBy('review_round', 'desc')
                ->first();
        }

        $currentScore = $currentReview ? $currentReview->score : null;

        // A reviewer is "completed" if their most relevant review record is already submitted
        $isCompleted = $currentReview && $currentReview->status === 'submitted';

        // Check for draft and submitted status for current round specifically
        $hasDraft = $currentReview && $currentReview->status === 'draft';
        $hasSubmitted = $isCompleted;

        // Get previous reviews by this reviewer (any round BEFORE the one we are currently showing)
        $showingRound = $currentReview ? $currentReview->review_round : $currentRevisionRound;
        $previousReviews = $abstract->reviews()
            ->where('reviewer_id', $reviewer->id)
            ->where('status', 'submitted')
            ->where('review_round', '<', $showingRound)
            ->orderBy('review_round', 'desc')
            ->get();

        // Get other reviewer's decision (for re-reviews) - NOT full comments, just recommendation
        $otherReviewerDecision = null;
        if ($currentRevisionRound > 0) {
            $previousRound = $currentRevisionRound - 1;
            $otherReviewerId = $abstract->reviewer_id === $reviewer->id ? $abstract->reviewer_2_id : $abstract->reviewer_id;

            if ($otherReviewerId) {
                $otherReview = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
                    ->where('reviewer_id', $otherReviewerId)
                    ->where('review_round', $previousRound)
                    ->where('status', 'submitted')
                    ->first();

                if ($otherReview) {
                    $otherReviewerDecision = [
                        'recommendation' => $otherReview->recommendation,
                        'score' => $otherReview->score,
                    ];
                }
            }
        }

        // Check for "Already Accepted" state (for conflicts)
        $hasAlreadyAccepted = $abstract->reviews()
            ->where('reviewer_id', $reviewer->id)
            ->where('status', 'submitted')
            ->whereIn('recommendation', ['accept', 'accept_oral', 'accept_poster'])
            ->exists();

        return view('reviewer.review', [
            'abstract' => $abstract,
            'reviewData' => $reviewData,
            'isBlindReview' => $abstract->is_blind_review,
            'reviewerPosition' => $reviewerPosition,
            'currentScore' => $currentScore,
            'isCompleted' => $isCompleted || $hasAlreadyAccepted,
            'hasAlreadyAccepted' => $hasAlreadyAccepted,
            'hasDraft' => $hasDraft,
            'hasSubmitted' => $hasSubmitted,
            'currentReview' => $currentReview,
            'previousReviews' => $previousReviews,
            'currentRevisionRound' => $currentRevisionRound,
            'otherReviewerDecision' => $otherReviewerDecision, // New data for review context
            'availableSubthemes' => array_keys(config('conference.subtheme_prefixes', [])),
        ]);
    }

    public function declineAssignment(Request $request, AbstractSubmission $abstract)
    {
        return redirect()->route('reviewer.dashboard');
        $reviewer = Auth::user();

        if ($abstract->reviewer_id !== $reviewer->id && $abstract->reviewer_2_id !== $reviewer->id) {
            abort(403, 'You are not assigned to review this abstract.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $result = $this->assignmentService->declineForExpertiseMismatch($abstract, $reviewer, $validated['reason']);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($result['replacement']) {
            return redirect()->route('reviewer.abstracts')
                ->with('success', 'The abstract was removed from your queue and reassigned to another reviewer.');
        }

        return redirect()->route('reviewer.abstracts')
            ->with('success', 'The abstract was removed from your queue and returned for reassignment.');
    }

    public function submitReview(Request $request, AbstractSubmission $abstract)
    {
        return redirect()->route('reviewer.dashboard');
        $reviewer = Auth::user();

        // Security check - ensure reviewer is assigned
        if ($abstract->reviewer_id !== $reviewer->id && $abstract->reviewer_2_id !== $reviewer->id) {
            abort(403, 'You are not assigned to review this abstract.');
        }

        // Validate the review submission
        $validated = $request->validate([
            'title_score' => 'required|numeric|min:0|max:4',
            'word_count_score' => 'required|numeric|min:0|max:3',
            'writing_quality_score' => 'required|numeric|min:0|max:4',
            'structure_score' => 'required|numeric|min:0|max:4',
            'background_score' => 'required|numeric|min:0|max:5',
            'rationale_score' => 'required|numeric|min:0|max:5',
            'objective_score' => 'required|numeric|min:0|max:10',
            'methodology_design_score' => 'required|numeric|min:0|max:7.5',
            'methodology_analysis_score' => 'required|numeric|min:0|max:7.5',
            'results_logic_score' => 'required|numeric|min:0|max:10',
            'results_findings_score' => 'required|numeric|min:0|max:10',
            'results_data_score' => 'required|numeric|min:0|max:10',
            'conclusion_interpretation_score' => 'required|numeric|min:0|max:7.5',
            'conclusion_impact_score' => 'required|numeric|min:0|max:7.5',
            'relevance_theme_score' => 'required|numeric|min:0|max:5',
            'comments' => 'required_if:recommendation,accept_with_revisions,reject|nullable|string|max:2000',
            'recommendation' => 'required|in:accept,accept_with_revisions,reject',
            'subtheme_relevance' => 'required|in:relevant,suggest_change',
            'suggested_subtheme' => 'nullable|required_if:subtheme_relevance,suggest_change|string|max:255',
        ]);

        // Calculate final score
        $finalScore =
            $validated['title_score'] +
            $validated['word_count_score'] +
            $validated['writing_quality_score'] +
            $validated['structure_score'] +
            $validated['background_score'] +
            $validated['rationale_score'] +
            $validated['objective_score'] +
            $validated['methodology_design_score'] +
            $validated['methodology_analysis_score'] +
            $validated['results_logic_score'] +
            $validated['results_findings_score'] +
            $validated['results_data_score'] +
            $validated['conclusion_interpretation_score'] +
            $validated['conclusion_impact_score'] +
            $validated['relevance_theme_score'];

        // Prevent positive recommendation if TOTAL score is 0 (likely they forgot to evaluate)
        if (in_array($validated['recommendation'], ['accept', 'accept_with_revisions']) && $finalScore <= 0) {
            return redirect()->back()->withInput()->withErrors([
                'title_score' => 'Please provide scores for the criteria before recommending acceptance. A 0% total score is not valid for approval.'
            ]);
        }
        $reviewerPosition = $abstract->reviewer_id === $reviewer->id ? 1 : 2;

        // Get the current revision round (default to 0 for initial)
        $currentRevisionRound = $abstract->revision_round ?? 0;

        // Check if already submitted for this revision round
        $existingReview = \App\Models\AbstractReview::where('abstract_submission_id', $abstract->id)
            ->where('reviewer_id', $reviewer->id)
            ->where('review_round', $currentRevisionRound)
            ->where('status', 'submitted')
            ->first();

        if ($existingReview) {
            return redirect()->back()->with('error', 'You have already submitted your review for this revision round.');
        }

        // Store review as submitted in abstract_reviews table
        $updateData = [
            'reviewer_number' => $reviewerPosition,
            'title_score' => $validated['title_score'],
            'word_count_score' => $validated['word_count_score'],
            'writing_quality_score' => $validated['writing_quality_score'],
            'structure_score' => $validated['structure_score'],
            'background_score' => $validated['background_score'],
            'rationale_score' => $validated['rationale_score'],
            'objective_score' => $validated['objective_score'],
            'methodology_design_score' => $validated['methodology_design_score'],
            'methodology_analysis_score' => $validated['methodology_analysis_score'],
            'results_logic_score' => $validated['results_logic_score'],
            'results_findings_score' => $validated['results_findings_score'],
            'results_data_score' => $validated['results_data_score'],
            'conclusion_interpretation_score' => $validated['conclusion_interpretation_score'],
            'conclusion_impact_score' => $validated['conclusion_impact_score'],
            'relevance_theme_score' => $validated['relevance_theme_score'],
            'score' => $finalScore,
            'comments' => $validated['comments'],
            'recommendation' => $validated['recommendation'],
            'subtheme_relevance' => $validated['subtheme_relevance'],
            'suggested_subtheme' => $validated['subtheme_relevance'] === 'suggest_change'
                ? ($validated['suggested_subtheme'] ?? null)
                : null,
            'status' => 'submitted',
            'submitted_at' => now(),
            'updated_at' => now(),
        ];

        \App\Models\AbstractReview::updateOrCreate([
            'abstract_submission_id' => $abstract->id,
            'reviewer_id' => $reviewer->id,
            'review_round' => $currentRevisionRound,
        ], $updateData);

        // Refresh the abstract to get latest data
        $abstract->refresh();

        // For re-reviews, ensure status is under_review so both reviewers can submit
        // For initial reviews, also ensure status is under_review
        $statusesThatAllowReview = ['under_review', 'revision_submitted', 'revision_under_review'];
        if (!in_array($abstract->status, $statusesThatAllowReview)) {
            $abstract->update(['status' => 'under_review']);
        }

        // Check if both reviews are now complete and update status accordingly
        $this->checkAndUpdateAbstractStatus($abstract);

        // Immediately refill this reviewer's queue if they now have free capacity.
        $this->assignmentService->assignNextPreferredAbstract($reviewer);

        if ($validated['subtheme_relevance'] === 'suggest_change' && !empty($validated['suggested_subtheme'])) {
            $this->emailService->sendSubthemeChangeRecommendationNotification(
                $abstract,
                $reviewer,
                $reviewerPosition,
                $validated['suggested_subtheme']
            );
        }

        // In-app notification for admins
        $this->notificationService->createAdminNotification(
            'review_submission',
            'New Review Submitted',
            "Reviewer {$reviewer->full_name} has submitted a review for: \"{$abstract->title}\"",
            ['abstract_id' => $abstract->id, 'reviewer_id' => $reviewer->id],
            route('admin.abstracts.view', $abstract->id),
            'medium'
        );

        // Different message for re-reviews vs initial reviews
        if ($currentRevisionRound > 1) {
            $message = 'Re-review submitted successfully! Thank you for evaluating the revised abstract.';
        } else {
            $message = 'Review submitted successfully!';
        }

        return redirect()->route('reviewer.abstracts')->with('success', $message);
    }
    public function saveDraft(Request $request, AbstractSubmission $abstract)
    {
        return response()->json(['disabled' => true]);
        $reviewer = Auth::user();

        // Security check - ensure reviewer is assigned
        if ($abstract->reviewer_id !== $reviewer->id && $abstract->reviewer_2_id !== $reviewer->id) {
            abort(403, 'You are not assigned to review this abstract.');
        }

        // Validate the draft data
        $validated = $request->validate([
            'title_score' => 'nullable|numeric|min:0|max:4',
            'word_count_score' => 'nullable|numeric|min:0|max:3',
            'writing_quality_score' => 'nullable|numeric|min:0|max:4',
            'structure_score' => 'nullable|numeric|min:0|max:4',
            'background_score' => 'nullable|numeric|min:0|max:5',
            'rationale_score' => 'nullable|numeric|min:0|max:5',
            'objective_score' => 'nullable|numeric|min:0|max:10',
            'methodology_design_score' => 'nullable|numeric|min:0|max:7.5',
            'methodology_analysis_score' => 'nullable|numeric|min:0|max:7.5',
            'results_logic_score' => 'nullable|numeric|min:0|max:10',
            'results_findings_score' => 'nullable|numeric|min:0|max:10',
            'results_data_score' => 'nullable|numeric|min:0|max:10',
            'conclusion_interpretation_score' => 'nullable|numeric|min:0|max:7.5',
            'conclusion_impact_score' => 'nullable|numeric|min:0|max:7.5',
            'relevance_theme_score' => 'nullable|numeric|min:0|max:5',
            'comments' => 'nullable|string|max:2000',
            'recommendation' => 'nullable|in:accept,accept_with_revisions,reject',
            'subtheme_relevance' => 'nullable|in:relevant,suggest_change',
            'suggested_subtheme' => 'nullable|required_if:subtheme_relevance,suggest_change|string|max:255',
        ]);

        // Calculate score for draft
        $calculatedScore =
            ($validated['title_score'] ?? 0) +
            ($validated['word_count_score'] ?? 0) +
            ($validated['writing_quality_score'] ?? 0) +
            ($validated['structure_score'] ?? 0) +
            ($validated['background_score'] ?? 0) +
            ($validated['rationale_score'] ?? 0) +
            ($validated['objective_score'] ?? 0) +
            ($validated['methodology_design_score'] ?? 0) +
            ($validated['methodology_analysis_score'] ?? 0) +
            ($validated['results_logic_score'] ?? 0) +
            ($validated['results_findings_score'] ?? 0) +
            ($validated['results_data_score'] ?? 0) +
            ($validated['conclusion_interpretation_score'] ?? 0) +
            ($validated['conclusion_impact_score'] ?? 0) +
            ($validated['relevance_theme_score'] ?? 0);

        // Determine which reviewer this is
        $reviewerPosition = $abstract->reviewer_id === $reviewer->id ? 1 : 2;

        // Store draft in abstract_reviews table
        \App\Models\AbstractReview::updateOrCreate([
            'abstract_submission_id' => $abstract->id,
            'reviewer_id' => $reviewer->id,
            'review_round' => $abstract->revision_round ?? 0,
        ], [
            'reviewer_number' => $reviewerPosition,
            'title_score' => $validated['title_score'] ?? 0,
            'word_count_score' => $validated['word_count_score'] ?? 0,
            'writing_quality_score' => $validated['writing_quality_score'] ?? 0,
            'structure_score' => $validated['structure_score'] ?? 0,
            'background_score' => $validated['background_score'] ?? 0,
            'rationale_score' => $validated['rationale_score'] ?? 0,
            'objective_score' => $validated['objective_score'] ?? 0,
            'methodology_design_score' => $validated['methodology_design_score'] ?? 0,
            'methodology_analysis_score' => $validated['methodology_analysis_score'] ?? 0,
            'results_logic_score' => $validated['results_logic_score'] ?? 0,
            'results_findings_score' => $validated['results_findings_score'] ?? 0,
            'results_data_score' => $validated['results_data_score'] ?? 0,
            'conclusion_interpretation_score' => $validated['conclusion_interpretation_score'] ?? 0,
            'conclusion_impact_score' => $validated['conclusion_impact_score'] ?? 0,
            'relevance_theme_score' => $validated['relevance_theme_score'] ?? 0,
            'score' => $calculatedScore,
            'comments' => $validated['comments'] ?? null,
            'recommendation' => $validated['recommendation'] ?? null,
            'subtheme_relevance' => $validated['subtheme_relevance'] ?? null,
            'suggested_subtheme' => ($validated['subtheme_relevance'] ?? null) === 'suggest_change'
                ? ($validated['suggested_subtheme'] ?? null)
                : null,
            'status' => 'draft',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Draft saved successfully! You can continue editing or submit when ready.');
    }

    public function getReviewProgress()
    {
        $reviewer = Auth::user();

        $assignedCount = AbstractSubmission::where('reviewer_id', $reviewer->id)
            ->orWhere('reviewer_2_id', $reviewer->id)
            ->count();

        $completedCount = AbstractSubmission::where(function($query) use ($reviewer) {
            $query->where('reviewer_id', $reviewer->id)->whereNotNull('reviewer_1_score')
                  ->orWhere('reviewer_2_id', $reviewer->id)->whereNotNull('reviewer_2_score');
        })->count();

        return response()->json([
            'assigned' => $assignedCount,
            'completed' => $completedCount,
            'pending' => $assignedCount - $completedCount,
            'completion_rate' => $assignedCount > 0 ? round(($completedCount / $assignedCount) * 100) : 0
        ]);
    }

    public function mySubmissions()
    {
        return redirect()->route('reviewer.dashboard');
        $reviewer = Auth::user();

        // Get reviewer's own abstract submissions - using correct column names
        $mySubmissions = AbstractSubmission::where('user_id', $reviewer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get all submissions for stats calculation (without pagination)
        $allSubmissions = AbstractSubmission::where('user_id', $reviewer->id)->get();

        // Calculate statistics
        $stats = [
            'total_submissions' => $allSubmissions->count(),
            'submitted' => $allSubmissions->where('status', 'submitted')->count(),
            'under_review' => $allSubmissions->whereIn('status', ['under_review', 'revision_under_review'])->count(),
            'accepted' => $allSubmissions->where('status', 'accepted')->count(),
            'drafts' => $allSubmissions->where('status', 'draft')->count(),
        ];

        return view('reviewer.my-submissions', compact('mySubmissions', 'stats'));
    }

    /**
     * Check if both reviews are complete and update abstract status accordingly
     */
    private function checkAndUpdateAbstractStatus(AbstractSubmission $abstract)
    {
        $abstract->syncReviewStatus();
    }
}
