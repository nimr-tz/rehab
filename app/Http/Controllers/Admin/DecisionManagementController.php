<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\User;
use App\Services\DecisionTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\EmailNotificationService;
use App\Services\NotificationService;

/**
 * Unified Decision Management Controller
 *
 * Consolidates all admin decision-making functionality:
 * - Conflict resolution (previously decision queue)
 * - Revision requests
 * - Status changes
 * - Bulk operations
 */
class DecisionManagementController extends Controller
{
    protected $emailService;
    protected $notificationService;

    public function __construct(EmailNotificationService $emailService, NotificationService $notificationService)
    {
        $this->emailService = $emailService;
        $this->notificationService = $notificationService;
    }
    /**
     * Unified decision dashboard
     * Shows all abstracts that need admin decisions
     * ✅ OPTIMIZED: Enhanced eager loading and query performance
     */
    public function index(Request $request)
    {
        return redirect()->route('admin.abstracts.index', ['status' => 'ready_for_decision']);
    }

    /**
     * Show detailed view for making decisions
     */
    public function show(AbstractSubmission $abstract)
    {
        // Load necessary relationships with detailed reviewer feedback
        $abstract->load([
            'user',
            'reviewer1:id,first_name,last_name,email',
            'reviewer2:id,first_name,last_name,email',
            'reviews' => function($query) {
                $query->where('status', 'submitted')
                      ->with('reviewer:id,first_name,last_name,email')
                      ->orderBy('created_at', 'asc');
            }
        ]);

        // Validate that this abstract is ready for a decision
        $latestReviews = $abstract->reviews->where('status', 'submitted')
            ->sortByDesc('review_round')
            ->unique('reviewer_id');
        $completedReviews = $latestReviews->count();
        $hasReviewers = $abstract->reviewer_id && $abstract->reviewer_2_id;
        $isDecided = in_array($abstract->status, ['accepted', 'rejected']);
        $isReadyForDecision = in_array($abstract->status, ['ready_for_decision']) ||
                              ($completedReviews >= 2 && in_array($abstract->status, ['under_review', 'revision_under_review', 'revision_submitted']));

        // Allow admin to always access the abstract for management, even if decided
        // Only redirect IF there are specifically no reviewers AND it's not decided AND we want to force assignment
        // But for better UX, we should allow viewing the abstract even without reviewers
        if (!$isReadyForDecision && !$isDecided) {
            if (!$hasReviewers) {
                // Instead of a hard redirect that blocks viewing, we'll just set a flag 
                // to show a warning on the page itself if needed, or allow the view.
                // For now, let's REMOVE the hard redirect so the eye icon works.
            }
        }

        // Get decision analysis
        $decisionAnalysis = $this->getDecisionAnalysis($abstract);

        // Get revision history (simplified for now)
        $revisionHistory = [];
        if (str_contains($abstract->status, 'revision') && $abstract->revision_requested_at) {
            $revisionHistory[] = [
                'action' => 'revision_requested',
                'created_at' => $abstract->revision_requested_at ? $abstract->revision_requested_at->format('M d, Y H:i') : 'Unknown',
                'notes' => $abstract->revision_feedback
            ];
        }
        if ($abstract->revision_submitted_at) {
            $revisionHistory[] = [
                'action' => 'revision_submitted',
                'created_at' => $abstract->revision_submitted_at ? $abstract->revision_submitted_at->format('M d, Y H:i') : 'Unknown',
                'notes' => 'Author resubmitted revised version'
            ];
        }

        // Get similar abstracts for context
        $similarAbstracts = $this->getSimilarAbstracts($abstract);

        return view('admin.decisions.show', compact(
            'abstract',
            'decisionAnalysis',
            'revisionHistory',
            'similarAbstracts'
        ));
    }

    /**
     * Process admin decision - unified endpoint for all decision types
     */
    public function processDecision(Request $request, AbstractSubmission $abstract, \App\Services\EmailService $emailService)
    {
        $validated = $request->validate([
            'decision_type' => 'required|in:accept,reject,accept_with_revisions',
            'admin_notes' => 'nullable|string|max:2000',
            'revision_feedback' => 'nullable|string|max:2000',
            'revision_deadline_days' => 'nullable|integer|min:1|max:90',
            'notify_author' => 'nullable|boolean'
        ]);

        // Prevent 3rd revision - if already at revision_round >= 2, only accept or reject allowed
        $currentRevisionRound = $abstract->revision_round ?? 0;
        if ($currentRevisionRound >= 2 && $validated['decision_type'] === 'accept_with_revisions') {
            return redirect()->back()
                ->with('error', 'A third revision is not allowed. After the second revision, you must either accept or reject the abstract.')
                ->withInput();
        }

        // Handle checkbox values (unchecked checkboxes don't send values)
        // Default to true - always notify author unless explicitly unchecked
        $validated['notify_author'] = !$request->has('notify_author') || ($request->has('notify_author') && $request->notify_author == '1');

        try {
            $oldStatus = $abstract->status;

            // Map decision to status
            $newStatus = match($validated['decision_type']) {
                'accept' => 'accepted',
                'reject' => 'rejected',
                'accept_with_revisions' => 'revision_required'
            };

            // Update the abstract
            $updateData = [
                'status' => $newStatus,
                'revision_admin_notes' => $validated['admin_notes'], // Internal notes
                'status_changed_at' => now(),
                'status_changed_by' => Auth::id()
            ];

            // Add revision-specific data
            if ($validated['decision_type'] === 'accept_with_revisions') {
                $deadlineDays = (int) ($validated['revision_deadline_days'] ?? 14);
                // The feedback we send to author goes into admin_comment
                $updateData['admin_comment'] = $validated['revision_feedback'] ?? $validated['admin_notes'];
                $updateData['revision_deadline'] = now()->addDays($deadlineDays);
                $updateData['revision_requested_at'] = now();
                // NOTE: revision_round is NOT incremented here.
                // It is incremented when the AUTHOR submits the revision (in UserController).
            }

            $abstract->update($updateData);

            Log::info("Admin decision processed", [
                'abstract_id' => $abstract->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'decision' => $validated['decision_type'],
                'admin_id' => Auth::id()
            ]);

            // Save record to revision history if applicable
            // (Could be expanded later to a full revisions table)

            // Send email notification to author
                $message = $validated['admin_notes'] ?? null;

            // In-app notification
            $this->notificationService->createStatusChangeNotification(
                $abstract->user,
                $abstract->title,
                $oldStatus,
                $newStatus,
                $abstract->id
            );

            // Always send to author when a decision is made (default is true)
            if ($validated['notify_author']) {
                try {
                $emailSent = false;

                if ($validated['decision_type'] === 'accept_with_revisions') {
                    $feedback = $validated['revision_feedback'] ?? $message;
                    $emailSent = $emailService->sendRevisionRequestedNotification($abstract, $feedback);
                } elseif ($validated['decision_type'] === 'accept') {
                    $emailSent = $emailService->sendAbstractAcceptedNotification($abstract, $message);
                } elseif ($validated['decision_type'] === 'reject') {
                    $emailSent = $emailService->sendAbstractRejectedNotification($abstract, $message);
                }

                if ($emailSent) {
                        Log::info("Author decision notification email sent successfully for abstract #{$abstract->id}");
                } else {
                        Log::warning("Failed to send author decision notification email for abstract #{$abstract->id} - check email logs for details");
                }
                } catch (\Exception $e) {
                    Log::error("Exception while sending author notification email for abstract #{$abstract->id}: " . $e->getMessage());
                }
            } else {
                Log::info("Author notification skipped for abstract #{$abstract->id} (notify_author was unchecked)");
            }

            $message = match($validated['decision_type']) {
                'accept' => 'Abstract accepted successfully',
                'reject' => 'Abstract rejected',
                'accept_with_revisions' => 'Revision requested (Accepted with Revisions)'
            };

            return redirect()->route('admin.decisions.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error("Decision processing failed", [
                'abstract_id' => $abstract->id,
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while processing the decision. Please try again.')
                ->withInput();
        }
    }

    /**
     * Enhanced revision request with better feedback system
     */
    public function requestRevision(Request $request, AbstractSubmission $abstract)
    {
        $validated = $request->validate([
            'revision_type' => 'required|in:minor,major',
            'feedback' => 'required|string|min:20|max:2000',
            'deadline_days' => 'integer|min:7|max:90'
        ]);

        try {
            $oldStatus = $abstract->status;
            $deadlineDays = (int) ($validated['deadline_days'] ?? 14);
            $newStatus = 'revision_required';

            $abstract->update([
                'status' => $newStatus,
                'admin_comment' => $validated['feedback'], // Feedback to author
                'revision_feedback' => '', // Clear author's previous response to avoid confusion
                'revision_deadline' => now()->addDays($deadlineDays),
                'revision_requested_at' => now(),
                'status_changed_at' => now(),
                'status_changed_by' => Auth::id()
            ]);

            // Send email notification
            $emailSent = $this->emailService->sendRevisionRequestedNotification($abstract, $validated['feedback']);

            // In-app notification
            $this->notificationService->createStatusChangeNotification(
                $abstract->user,
                $abstract->title,
                $oldStatus,
                $newStatus,
                $abstract->id
            );

            if ($emailSent) {
                Log::info("Revision request email sent for abstract #{$abstract->id}");
            } else {
                Log::warning("Failed to send revision request email for abstract #{$abstract->id}");
            }

            return response()->json([
                'success' => true,
                'message' => ucfirst($validated['revision_type']) . ' revision requested successfully',
                'redirect' => route('admin.decisions.index')
            ]);

        } catch (\Exception $e) {
            Log::error("Revision request failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to request revision. Please try again.'
            ], 500);
        }
    }

    /**
     * Get decision analysis data (AJAX endpoint)
     */
    public function getDecisionAnalysis(AbstractSubmission $abstract)
    {
        $reviews = $abstract->reviews->where('status', 'submitted')
            ->sortByDesc('review_round')
            ->unique('reviewer_id');

        $analysis = [
            'decision_type' => $this->getDecisionType($abstract),
            'urgency_level' => $this->getUrgencyLevel($abstract),
            'review_summary' => $this->generateReviewSummary($reviews),
            'conflict_analysis' => null,
            'recommendations' => [],
            'consensus_level' => $this->calculateConsensusLevel($reviews)
        ];

        // Detailed review conflict analysis
        if ($reviews->count() >= 2) {
            $analysis['conflict_analysis'] = $this->analyzeReviewConflict($reviews);
        }

        // Enhanced recommendations based on reviewer feedback
        $analysis['recommendations'] = $this->generateDecisionRecommendations($abstract, $analysis);

        // Generate decision template suggestions
        $suggestedTemplate = DecisionTemplateService::selectTemplate($abstract, $analysis);
        if ($suggestedTemplate) {
            $analysis['suggested_template'] = [
                'template' => $suggestedTemplate,
                'populated_content' => DecisionTemplateService::populateTemplate($suggestedTemplate, $abstract, $analysis),
                'auto_applicable' => $suggestedTemplate['auto_applicable'] ?? false,
                'confidence' => $this->calculateTemplateConfidence($abstract, $analysis, $suggestedTemplate)
            ];
        }

        if (request()->expectsJson()) {
            return response()->json($analysis);
        }

        return $analysis;
    }

    private function calculateTemplateConfidence($abstract, $analysis, $template): int
    {
        $baseConfidence = $template['confidence_threshold'] ?? 50;
        $reviews = $abstract->reviews->where('status', 'submitted');

        // Adjust based on review count
        if ($reviews->count() >= 3) {
            $baseConfidence += 10;
        } elseif ($reviews->count() < 2) {
            $baseConfidence -= 20;
        }

        // Adjust based on conflict score
        $conflictScore = $analysis['conflict_analysis']['conflict_score'] ?? 0;
        if ($conflictScore < 20) {
            $baseConfidence += 15; // Low conflict = high confidence
        } elseif ($conflictScore > 60) {
            $baseConfidence -= 15; // High conflict = low confidence
        }

        // Adjust based on reviewer expertise
        if (isset($analysis['review_summary']['expertise_analysis'])) {
            $avgExperience = $analysis['review_summary']['expertise_analysis']['total_experience_years'] / max(1, $reviews->count());
            if ($avgExperience >= 5) {
                $baseConfidence += 10;
            }
        }

        return max(0, min(100, $baseConfidence));
    }

    private function generateReviewSummary($reviews)
    {
        if ($reviews->count() === 0) {
            return null;
        }

        // Calculate expertise weights for reviews
        $reviewsWithWeights = $this->calculateExpertiseWeights($reviews);

        // Calculate weighted scores
        $weightedScores = [];
        $totalWeight = 0;

        foreach ($reviewsWithWeights as $review) {
            if ($review->score) {
                $weight = $review->calculated_weight ?? 1.0;
                $weightedScores[] = $review->score * $weight;
                $totalWeight += $weight;
            }
        }

        $weightedAverage = $totalWeight > 0 ? array_sum($weightedScores) / $totalWeight : 0;

        $summary = [
            'total_reviews' => $reviews->count(),
            'average_score' => round($reviews->avg('score'), 1),
            'weighted_average_score' => round($weightedAverage, 1),
            'score_range' => [
                'min' => $reviews->min('score'),
                'max' => $reviews->max('score')
            ],
            'recommendations' => $reviews->pluck('recommendation')->toArray(),
            'common_themes' => $this->extractCommonThemes($reviews),
            'consensus' => $this->determineRecommendationConsensus($reviews),
            'expertise_analysis' => $this->analyzeReviewerExpertise($reviewsWithWeights),
            'review_weights' => $reviewsWithWeights->pluck('calculated_weight', 'reviewer.name')->toArray()
        ];

        return $summary;
    }

    private function calculateExpertiseWeights($reviews)
    {
        return $reviews->map(function ($review) {
            // Base weight
            $weight = 1.0;

            // Experience factor (0.8 - 1.3)
            if ($review->reviewer && $review->reviewer->review_experience_years) {
                $experienceYears = $review->reviewer->review_experience_years;
                if ($experienceYears >= 10) {
                    $weight *= 1.3;
                } elseif ($experienceYears >= 5) {
                    $weight *= 1.2;
                } elseif ($experienceYears >= 2) {
                    $weight *= 1.1;
                } elseif ($experienceYears < 1) {
                    $weight *= 0.8;
                }
            }

            // Consistency factor (0.7 - 1.2)
            if ($review->reviewer && $review->reviewer->reviewer_consistency_score) {
                $consistencyScore = $review->reviewer->reviewer_consistency_score;
                if ($consistencyScore >= 85) {
                    $weight *= 1.2;
                } elseif ($consistencyScore >= 70) {
                    $weight *= 1.1;
                } elseif ($consistencyScore < 50) {
                    $weight *= 0.7;
                } elseif ($consistencyScore < 60) {
                    $weight *= 0.8;
                }
            }

            // Review volume factor (0.9 - 1.1)
            if ($review->reviewer && $review->reviewer->total_reviews_completed) {
                $totalReviews = $review->reviewer->total_reviews_completed;
                if ($totalReviews >= 50) {
                    $weight *= 1.1;
                } elseif ($totalReviews >= 20) {
                    $weight *= 1.05;
                } elseif ($totalReviews < 5) {
                    $weight *= 0.9;
                }
            }

            // Expertise match factor (stored in database or calculated)
            if ($review->expertise_match_score) {
                $expertiseMatch = $review->expertise_match_score / 100; // Convert to 0-1
                $weight *= (0.8 + ($expertiseMatch * 0.4)); // 0.8 - 1.2 multiplier
            }

            // Cap the weight between 0.5 and 2.0
            $weight = max(0.5, min(2.0, $weight));

            $review->calculated_weight = round($weight, 2);
            return $review;
        });
    }

    private function analyzeReviewerExpertise($reviews)
    {
        $analysis = [
            'total_experience_years' => 0,
            'average_consistency' => 0,
            'expertise_distribution' => [],
            'weight_justification' => []
        ];

        $totalExperience = 0;
        $totalConsistency = 0;
        $reviewerCount = 0;

        foreach ($reviews as $review) {
            if ($review->reviewer) {
                $reviewer = $review->reviewer;
                $weight = $review->calculated_weight ?? 1.0;

                $totalExperience += $reviewer->review_experience_years ?? 0;
                $totalConsistency += $reviewer->reviewer_consistency_score ?? 0;
                $reviewerCount++;

                // Build weight justification
                $justification = [];
                if ($weight > 1.1) {
                    $justification[] = 'High expertise weight';
                } elseif ($weight < 0.9) {
                    $justification[] = 'Lower expertise weight';
                }

                if ($reviewer->review_experience_years >= 5) {
                    $justification[] = "Experienced ({$reviewer->review_experience_years} years)";
                }

                if ($reviewer->reviewer_consistency_score >= 80) {
                    $justification[] = "High consistency ({$reviewer->reviewer_consistency_score}%)";
                }

                $analysis['weight_justification'][$reviewer->name ?? 'Anonymous'] = [
                    'weight' => $weight,
                    'reasons' => $justification
                ];
            }
        }

        if ($reviewerCount > 0) {
            $analysis['total_experience_years'] = $totalExperience;
            $analysis['average_consistency'] = round($totalConsistency / $reviewerCount, 1);
        }

        return $analysis;
    }

    private function calculateConsensusLevel($reviews)
    {
        if ($reviews->count() < 2) {
            return 'insufficient_data';
        }

        $recommendations = $reviews->pluck('recommendation')->toArray();
        $scores = $reviews->pluck('score')->toArray();

        // Check recommendation consensus
        $uniqueRecommendations = array_unique($recommendations);
        $scoreDifference = max($scores) - min($scores);

        if (count($uniqueRecommendations) === 1) {
            return $scoreDifference <= 10 ? 'strong_consensus' : 'weak_consensus';
        } elseif (count($uniqueRecommendations) === 2 && $scoreDifference <= 20) {
            return 'minor_disagreement';
        } else {
            return 'major_conflict';
        }
    }

    private function extractCommonThemes($reviews)
    {
        $themes = [];

        foreach ($reviews as $review) {
            if ($review->comments) {
                $text = strtolower($review->comments);

                // Simple keyword extraction (can be enhanced with NLP)
                if (str_contains($text, 'methodology') || str_contains($text, 'method')) {
                    $themes[] = 'methodology';
                }
                if (str_contains($text, 'result') || str_contains($text, 'finding')) {
                    $themes[] = 'results';
                }
                if (str_contains($text, 'writing') || str_contains($text, 'clarity')) {
                    $themes[] = 'presentation';
                }
                if (str_contains($text, 'significance') || str_contains($text, 'important')) {
                    $themes[] = 'significance';
                }
                if (str_contains($text, 'good') || str_contains($text, 'excellent')) {
                    $themes[] = 'positive_feedback';
                }
                if (str_contains($text, 'weak') || str_contains($text, 'poor')) {
                    $themes[] = 'concerns';
                }
            }
        }

        return array_unique($themes);
    }

    /**
     * Get smart conflict flags with caching for performance
     * ✅ OPTIMIZED: Added caching mechanism for expensive conflict analysis
     */
    private function getSmartConflictFlags(AbstractSubmission $abstract): array
    {
        // ✅ PERFORMANCE: Cache conflict analysis results
        $cacheKey = "conflict_flags_{$abstract->id}_{$abstract->updated_at->timestamp}";

        return cache()->remember($cacheKey, now()->addHours(2), function() use ($abstract) {
            $flags = [];
            // ONLY analyze the latest submitted review for each assigned reviewer
            $reviews = $abstract->reviews->where('status', 'submitted')
                ->sortByDesc('review_round')
                ->unique('reviewer_id');

            if ($reviews->count() < 2) {
                return $flags;
            }

        $scores = $reviews->pluck('score')->filter()->toArray();
        $recommendations = $reviews->pluck('recommendation')->map(fn($r) => strtolower($r))->toArray();

        // High-stakes conflict (accept vs reject)
        $positiveOptions = ['accept', 'accept_oral', 'accept_poster'];
        $hasAccept = !empty(array_intersect($recommendations, $positiveOptions));
        $hasReject = in_array('reject', $recommendations);

        if ($hasAccept && $hasReject) {
            $flags[] = [
                'type' => 'high_stakes_conflict',
                'severity' => 'critical',
                'message' => 'Accept vs Reject conflict - requires immediate attention',
                'icon' => 'exclamation-triangle',
                'color' => 'red'
            ];
        }

        // Extreme score variance
        if (!empty($scores) && (max($scores) - min($scores)) > 40) {
            $flags[] = [
                'type' => 'extreme_score_variance',
                'severity' => 'high',
                'message' => 'Extreme score difference: ' . min($scores) . ' to ' . max($scores),
                'icon' => 'chart-line',
                'color' => 'orange'
            ];
        }

        // Expert disagreement (if reviewer expertise data available)
        $expertReviewers = $reviews->filter(function($review) {
            return $review->reviewer &&
                   ($review->reviewer->review_experience_years >= 5 ||
                    $review->reviewer->reviewer_consistency_score >= 80);
        });

        if ($expertReviewers->count() >= 2) {
            $expertRecommendations = $expertReviewers->pluck('recommendation')->unique();
            if ($expertRecommendations->count() > 1) {
                $flags[] = [
                    'type' => 'expert_disagreement',
                    'severity' => 'high',
                    'message' => 'Experienced reviewers disagree - careful analysis needed',
                    'icon' => 'user-graduate',
                    'color' => 'purple'
                ];
            }
        }

        // Time-sensitive decisions
        $daysSinceSubmission = (int)now()->diffInDays($abstract->created_at);
        if ($daysSinceSubmission > 14) {
            $flags[] = [
                'type' => 'overdue_decision',
                'severity' => 'medium',
                'message' => "Decision overdue by {$daysSinceSubmission} days",
                'icon' => 'clock',
                'color' => 'yellow'
            ];
        }

        // Reviewer confidence issues
        $lowConfidenceReviews = $reviews->filter(function($review) {
            // Assuming comments contain confidence indicators
            $comments = strtolower($review->comments ?? '');
            return str_contains($comments, 'unsure') ||
                   str_contains($comments, 'uncertain') ||
                   str_contains($comments, 'not confident');
        });

        if ($lowConfidenceReviews->count() > 0) {
            $flags[] = [
                'type' => 'reviewer_uncertainty',
                'severity' => 'medium',
                'message' => 'Reviewers express uncertainty - consider additional review',
                'icon' => 'question-circle',
                'color' => 'blue'
            ];
        }

        // Quality concerns with mixed recommendations
        if (!empty($scores)) {
            $avgScore = array_sum($scores) / count($scores);
            $uniqueRecommendations = array_unique($recommendations);

            if ($avgScore >= 70 && in_array('reject', $recommendations)) {
                $flags[] = [
                    'type' => 'quality_recommendation_mismatch',
                    'severity' => 'medium',
                    'message' => 'High scores conflict with rejection recommendation',
                    'icon' => 'balance-scale',
                    'color' => 'indigo'
                ];
            } elseif ($avgScore < 55 && ($hasAccept)) {
                $flags[] = [
                    'type' => 'quality_recommendation_mismatch',
                    'severity' => 'medium',
                    'message' => 'Low scores conflict with acceptance recommendation',
                    'icon' => 'balance-scale',
                    'color' => 'indigo'
                ];
            }
        }

        return $flags;
        });
    }

    private function calculatePriorityScore(AbstractSubmission $abstract): int
    {
        $score = 50; // Base score

        // Urgency factors
        $urgencyLevel = $this->getUrgencyLevel($abstract);
        switch ($urgencyLevel) {
            case 'high': $score += 30; break;
            case 'medium': $score += 15; break;
            case 'low': $score += 0; break;
        }

        // Conflict severity
        $flags = $this->getSmartConflictFlags($abstract);
        foreach ($flags as $flag) {
            switch ($flag['severity']) {
                case 'critical': $score += 25; break;
                case 'high': $score += 15; break;
                case 'medium': $score += 10; break;
                case 'low': $score += 5; break;
            }
        }

        // Review completeness
        $reviews = $abstract->reviews->where('status', 'submitted');
        if ($reviews->count() >= 3) {
            $score += 10; // More reviews = higher priority to clear
        } elseif ($reviews->count() < 2) {
            $score -= 20; // Incomplete reviews = lower priority
        }

        // Time factors
        $daysSinceSubmission = (int)now()->diffInDays($abstract->created_at);
        if ($daysSinceSubmission > 21) {
            $score += 20; // Very old submissions
        } elseif ($daysSinceSubmission > 14) {
            $score += 10; // Old submissions
        }

        return max(0, min(100, $score));
    }

    private function determineRecommendationConsensus($reviews)
    {
        $recommendations = $reviews->pluck('recommendation')->toArray();
        $counts = array_count_values($recommendations);
        $mostCommon = array_key_first(array_slice($counts, 0, 1, true));

        return [
            'dominant_recommendation' => $mostCommon,
            'agreement_percentage' => round(($counts[$mostCommon] / count($recommendations)) * 100),
            'distribution' => $counts
        ];
    }

    /**
     * Bulk process multiple decisions
     */
    public function bulkProcess(Request $request)
    {
        $validated = $request->validate([
            'abstract_ids' => 'required|string', // JSON string
            'bulk_action' => 'required|in:accept,reject',
            'admin_notes' => 'nullable|string|max:1000'
        ]);

        try {
            $abstractIds = json_decode($validated['abstract_ids'], true);

            if (!is_array($abstractIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid abstract IDs'
                ], 422);
            }

            $processed = 0;
            $failed = 0;

            foreach ($abstractIds as $abstractId) {
                try {
                    $abstract = AbstractSubmission::findOrFail($abstractId);

                    $newStatus = match($validated['bulk_action']) {
                        'accept' => 'accepted',
                        'reject' => 'rejected'
                    };

                    $abstract->update([
                        'status' => $newStatus,
                        'admin_comment' => $validated['admin_notes'],
                        'status_changed_at' => now(),
                        'status_changed_by' => Auth::id()
                    ]);

                    $processed++;

                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Bulk processing failed for abstract {$abstractId}: " . $e->getMessage());
                }
            }

            $message = "Processed: {$processed} abstracts";
            if ($failed > 0) {
                $message .= ", Failed: {$failed}";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'processed' => $processed,
                'failed' => $failed
            ]);

        } catch (\Exception $e) {
            Log::error("Bulk processing failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bulk processing failed. Please try again.'
            ], 500);
        }
    }

    // Private helper methods

    private function getDecisionType(AbstractSubmission $abstract): string
    {
        // Get the latest submitted review for each assigned reviewer
        $reviews = $abstract->reviews->where('status', 'submitted')
            ->sortByDesc('review_round')
            ->unique('reviewer_id');

        // Define statuses where a conflict might need resolving NOW
        $actionableStatuses = [
            'ready_for_decision',
            'under_review',
            'minor_revision_submitted',
            'major_revision_submitted',
            'revision_submitted',
            'revision_under_review'
        ];

        // Check for conflicts (different recommendations)
        if (in_array($abstract->status, $actionableStatuses) && $reviews->count() >= 2) {
            $recommendations = $reviews->pluck('recommendation')->map(fn($r) => strtolower($r))->toArray();

            // Normalize recommendations for conflict check
            $normalized = array_map(function($r) {
                if (in_array($r, ['accept', 'accept_oral', 'accept_poster'])) return 'accept';
                return $r;
            }, $recommendations);

            if (count(array_unique($normalized)) > 1) {
                return 'conflict_resolution';
            }
        }

        // Check if status indicates revision-related decision
        if (in_array($abstract->status, [
            'minor_revision_submitted',
            'major_revision_submitted',
            'revision_submitted',
            'revision_under_review',
            'ready_for_decision'
        ])) {
            // Revisions are revision reviews
            return 'revision_review';
        }

        // Check for under_review status with completed reviews
        if ($abstract->status === 'under_review' && $reviews->count() >= 2) {
            return 'standard_review';
        }

        // Check for under_review status with completed reviews
        if ($abstract->status === 'under_review' && $reviews->count() >= 2) {
            return 'conflict_resolution';
        }

        return 'standard_review';
    }

    private function getUrgencyLevel(AbstractSubmission $abstract): string
    {
        // Use updated_at if available, otherwise fall back to created_at
        $date = $abstract->updated_at ?? $abstract->created_at;

        if (!$date) {
            return 'low'; // Default if no date available
        }

        // Ensure we have a Carbon instance
        $date = \Carbon\Carbon::parse($date);
        $daysOld = (int)now()->diffInDays($date);

        if ($daysOld > 14) return 'high';
        if ($daysOld > 7) return 'medium';
        return 'low';
    }

    private function analyzeReviewConflict($reviews): array
    {
        $scores = $reviews->pluck('score')->filter()->toArray();
        $recommendations = $reviews->pluck('recommendation')->filter()->toArray();

        if (count($scores) < 2) {
            return [
                'score_difference' => 0,
                'recommendation_conflict' => false,
                'severity' => 'none',
                'conflict_score' => 0,
                'detailed_analysis' => 'Insufficient reviews for conflict analysis'
            ];
        }

        // Calculate score-based conflict
        $scoreDiff = max($scores) - min($scores);
        $avgScore = array_sum($scores) / count($scores);

        // Calculate recommendation conflict
        $uniqueRecommendations = array_unique($recommendations);
        $recommendationConflict = count($uniqueRecommendations) > 1;

        // Enhanced conflict severity calculation
        $conflictScore = $this->calculateConflictSeverityScore($scores, $recommendations);

        // Determine severity level
        $severity = $this->determineConflictSeverity($conflictScore);

        // Generate detailed analysis
        $detailedAnalysis = $this->generateConflictAnalysis($scores, $recommendations, $conflictScore);

        return [
            'score_difference' => $scoreDiff,
            'average_score' => round($avgScore, 1),
            'recommendation_conflict' => $recommendationConflict,
            'severity' => $severity,
            'conflict_score' => $conflictScore,
            'detailed_analysis' => $detailedAnalysis,
            'requires_urgent_attention' => $conflictScore >= 80,
            'auto_resolution_possible' => $conflictScore < 30 && !$recommendationConflict
        ];
    }

    /**
     * Calculate conflict severity score with caching
     * ✅ OPTIMIZED: Added caching for expensive conflict severity calculations
     */
    private function calculateConflictSeverityScore($scores, $recommendations): int
    {
        if (empty($scores) || empty($recommendations)) {
            return 0;
        }

        // ✅ PERFORMANCE: Cache severity calculations
        $cacheKey = 'conflict_severity_' . md5(serialize([$scores, $recommendations]));

        return Cache::remember($cacheKey, now()->addHours(6), function() use ($scores, $recommendations) {
            $severityScore = 0;

        // Score variance component (0-40 points)
        $scoreDiff = max($scores) - min($scores);
        $scoreVarianceScore = min(($scoreDiff / 80) * 40, 40); // Normalize to 0-40

        // Recommendation conflict component (0-30 points)
        $uniqueRecommendations = array_unique($recommendations);
        $recommendationScore = 0;

        if (count($uniqueRecommendations) > 1) {
            // Check for major conflicts (accept vs reject)
            $hasAccept = array_intersect($recommendations, ['accept_oral', 'accept_poster']);
            $hasReject = in_array('reject', $recommendations);

            if ($hasAccept && $hasReject) {
                $recommendationScore = 30; // Maximum conflict
            } elseif (in_array('major_revisions', $recommendations) && $hasAccept) {
                $recommendationScore = 25; // High conflict
            } elseif (in_array('minor_revisions', $recommendations) && $hasAccept) {
                $recommendationScore = 15; // Moderate conflict
            } else {
                $recommendationScore = 20; // Other conflicts
            }
        }

        // Extreme score component (0-20 points)
        $extremeScoreBonus = 0;
        $maxScore = max($scores);
        $minScore = min($scores);

        if ($maxScore >= 85 && $minScore <= 40) {
            $extremeScoreBonus = 20; // Extreme disagreement
        } elseif ($maxScore >= 80 && $minScore <= 50) {
            $extremeScoreBonus = 15;
        } elseif ($maxScore >= 75 && $minScore <= 60) {
            $extremeScoreBonus = 10;
        }

        // Pattern analysis component (0-10 points)
        $patternBonus = 0;
        if (count($scores) >= 3) {
            $median = $scores[floor(count($scores) / 2)];
            $outliers = array_filter($scores, fn($score) => abs($score - $median) > 25);
            $patternBonus = count($outliers) * 3;
        }

        $severityScore = $scoreVarianceScore + $recommendationScore + $extremeScoreBonus + min($patternBonus, 10);

        return min(round($severityScore), 100);
        });
    }

    private function determineConflictSeverity($conflictScore): string
    {
        if ($conflictScore >= 80) return 'critical';
        if ($conflictScore >= 60) return 'high';
        if ($conflictScore >= 40) return 'moderate';
        if ($conflictScore >= 20) return 'low';
        return 'minimal';
    }

    private function generateConflictAnalysis($scores, $recommendations, $conflictScore): string
    {
        $analysis = [];

        $scoreDiff = max($scores) - min($scores);
        if ($scoreDiff > 40) {
            $analysis[] = "Significant score disagreement ({$scoreDiff} point difference)";
        }

        $uniqueRecommendations = array_unique($recommendations);
        if (count($uniqueRecommendations) > 1) {
            $analysis[] = "Conflicting recommendations: " . implode(' vs ', $uniqueRecommendations);
        }

        if ($conflictScore >= 80) {
            $analysis[] = "CRITICAL: Requires immediate admin attention";
        } elseif ($conflictScore >= 60) {
            $analysis[] = "HIGH: Significant reviewer disagreement detected";
        }

        $avgScore = array_sum($scores) / count($scores);
        if ($avgScore >= 70 && in_array('reject', $recommendations)) {
            $analysis[] = "High average score conflicts with rejection recommendation";
        } elseif ($avgScore < 50 && (in_array('accept_oral', $recommendations) || in_array('accept_poster', $recommendations))) {
            $analysis[] = "Low average score conflicts with acceptance recommendation";
        }

        return empty($analysis) ? "Minor reviewer disagreement within acceptable range" : implode('. ', $analysis);
    }

    private function generateDecisionRecommendations(AbstractSubmission $abstract, array $analysis): array
    {
        $recommendations = [
            'primary' => 'Review the detailed feedback and make an informed decision',
            'alternatives' => [],
            'considerations' => []
        ];

        if ($analysis['decision_type'] === 'conflict_resolution') {
            $consensusLevel = $analysis['consensus_level'] ?? 'unknown';

            switch ($consensusLevel) {
                case 'major_conflict':
                    $recommendations['primary'] = 'Significant disagreement - carefully weigh each reviewer\'s detailed comments';
                    $recommendations['alternatives'] = [
                        'Consider the specific expertise of each reviewer',
                        'Focus on the quality of arguments rather than just scores',
                        'Request revision to address both reviewers\' concerns'
                    ];
                    break;

                case 'minor_disagreement':
                    $recommendations['primary'] = 'Minor disagreement - look for common ground in reviewer feedback';
                    $recommendations['alternatives'] = [
                        'Accept if overall consensus is positive',
                        'Minor revision to address specific concerns'
                    ];
                    break;

                default:
                    $recommendations['primary'] = 'Review detailed comments to understand the basis for different recommendations';
            }

            // Add specific considerations based on review summary
            if (isset($analysis['review_summary'])) {
                $summary = $analysis['review_summary'];

                if ($summary['average_score'] >= 70) {
                    $recommendations['considerations'][] = 'High average score (' . $summary['average_score'] . '/100) suggests quality work';
                } elseif ($summary['average_score'] < 50) {
                    $recommendations['considerations'][] = 'Low average score (' . $summary['average_score'] . '/100) indicates significant concerns';
                }

                $scoreDiff = $summary['score_range']['max'] - $summary['score_range']['min'];
                if ($scoreDiff > 30) {
                    $recommendations['considerations'][] = 'Large score difference (' . $scoreDiff . ' points) indicates significant reviewer disagreement';
                }

                if (!empty($summary['common_themes'])) {
                    $recommendations['considerations'][] = 'Common review themes: ' . implode(', ', $summary['common_themes']);
                }
            }

        } elseif ($analysis['decision_type'] === 'revision_review') {
            // Remove AI recommendations for revision reviews - let reviewers decide
            $recommendations = [
                'primary' => '',
                'alternatives' => [],
                'considerations' => []
            ];
        }

        return $recommendations;
    }

    /**
     * Get similar abstracts for decision context with optimized caching
     * ✅ OPTIMIZED: Added caching and improved query performance
     */
    private function getSimilarAbstracts(AbstractSubmission $abstract, int $limit = 3)
    {
        $cacheKey = "similar_abstracts_{$abstract->id}_{$abstract->subtheme}";

        return Cache::remember($cacheKey, now()->addHours(12), function() use ($abstract, $limit) {
            return AbstractSubmission::select(['id', 'title', 'status', 'average_score', 'created_at'])
                ->where('id', '!=', $abstract->id)
                ->where('subtheme', $abstract->subtheme)
                ->whereIn('status', ['accepted', 'rejected'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get decision statistics with caching for performance
     * ✅ OPTIMIZED: Added caching and query optimization for dashboard stats
     */
    private function getDecisionStats(): array
    {
        $stats = [];

        // 1. My Resolutions (Completed decisions by admin - accepted, rejected, OR sent for revision)
        $stats['my_resolutions'] = DB::table('abstract_submissions')
            ->whereIn('status', [
                'accepted',
                'rejected',
                'minor_revision_required',
                'major_revision_required'
            ])
            ->count();

        // 2. Revisions Submitted (Items currently awaiting reviewer feedback in Round 1+)
        $stats['revisions'] = DB::table('abstract_submissions')
            ->whereIn('status', ['under_review', 'revision_submitted', 'minor_revision_submitted', 'major_revision_submitted', 'revision_under_review'])
            // It's a "revision submitted" card only if it's NOT yet ready for admin decision (needs more reviews)
            ->whereRaw('(SELECT COUNT(DISTINCT reviewer_id) FROM abstract_reviews WHERE abstract_reviews.abstract_submission_id = abstract_submissions.id AND abstract_reviews.status = \'submitted\' AND (abstract_reviews.review_round = COALESCE(abstract_submissions.revision_round, 0) OR abstract_reviews.recommendation IN (\'accept\', \'accept_oral\', \'accept_poster\'))) < 2')
            ->count();

        // 3. Needs Resolution (Actionable Conflicts or decisions)
        $stats['needs_resolution'] = DB::table('abstract_submissions')
            ->where(function($query) {
                // Explicitly ready statuses
                $query->where('status', 'ready_for_decision')
                ->orWhere(function($q) {
                    // Under review but effectively ready for the CURRENT round
                    $q->whereIn('status', ['under_review', 'revision_under_review', 'revision_submitted', 'minor_revision_submitted', 'major_revision_submitted'])
                        ->whereRaw('(SELECT COUNT(DISTINCT reviewer_id) FROM abstract_reviews WHERE abstract_reviews.abstract_submission_id = abstract_submissions.id AND abstract_reviews.status = \'submitted\' AND (abstract_reviews.review_round = COALESCE(abstract_submissions.revision_round, 0) OR abstract_reviews.recommendation IN (\'accept\', \'accept_oral\', \'accept_poster\'))) >= 2');
                });
            })
            ->whereNotIn('status', [
                'minor_revision_required',
                'major_revision_required',
                'revision_requested',
                'accepted',
                'rejected',
                'draft'
            ])
            ->count();

        $stats['total_ready'] = $stats['needs_resolution'];

        return $stats;
    }
}
