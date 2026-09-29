<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use App\Models\User;
use App\Mail\AbstractDecisionNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class WorkflowAutomationService
{
    /**
     * Process automatic decisions when both reviewers have submitted reviews
     */
    public function processAutomaticDecisions(): array
    {
        $results = [
            'processed' => 0,
            'accepted' => 0,
            'rejected' => 0,
            'minor_revisions' => 0,
            'major_revisions' => 0,
            'conflicts' => 0,
            'errors' => []
        ];

        // Find abstracts with both reviewers assigned and both reviews submitted
        $abstracts = AbstractSubmission::whereNotNull('reviewer_id')
            ->whereNotNull('reviewer_2_id')
            ->where('status', 'under_review')
            ->with(['reviews' => function($query) {
                $query->where('status', 'submitted');
            }])
            ->get();

        foreach ($abstracts as $abstract) {
            try {
                $result = $this->processAbstractDecision($abstract);
                $results['processed']++;
                $results[$result['action']]++;
            } catch (\Exception $e) {
                $results['errors'][] = "Abstract {$abstract->id}: " . $e->getMessage();
                Log::error("Workflow automation error for abstract {$abstract->id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Process decision for a single abstract
     */
    public function processAbstractDecision(AbstractSubmission $abstract): array
    {
        $submittedReviews = $abstract->reviews->where('status', 'submitted');
        
        if ($submittedReviews->count() < 2) {
            throw new \Exception("Not enough submitted reviews");
        }

        $reviewer1Review = $submittedReviews->where('reviewer_id', $abstract->reviewer_id)->first();
        $reviewer2Review = $submittedReviews->where('reviewer_id', $abstract->reviewer_2_id)->first();

        if (!$reviewer1Review || !$reviewer2Review) {
            throw new \Exception("Missing reviewer submissions");
        }

        $recommendations = [$reviewer1Review->recommendation, $reviewer2Review->recommendation];
        $consensus = $this->determineConsensus($recommendations);

        if ($consensus['type'] === 'consensus') {
            return $this->applyConsensusDecision($abstract, $consensus['decision'], $submittedReviews);
        } else {
            return $this->flagForAdminDecision($abstract, $submittedReviews, $consensus['conflict_type']);
        }
    }

    /**
     * Determine if there's consensus or conflict between reviewers
     */
    private function determineConsensus(array $recommendations): array
    {
        $acceptTypes = ['accept_oral', 'accept_poster'];
        $rejectTypes = ['reject'];
        $minorTypes = ['minor_revisions'];
        $majorTypes = ['major_revisions'];

        // Check for exact match
        if ($recommendations[0] === $recommendations[1]) {
            return [
                'type' => 'consensus',
                'decision' => $recommendations[0]
            ];
        }

        // Check for consensus within categories
        $r1IsAccept = in_array($recommendations[0], $acceptTypes);
        $r2IsAccept = in_array($recommendations[1], $acceptTypes);
        $r1IsReject = in_array($recommendations[0], $rejectTypes);
        $r2IsReject = in_array($recommendations[1], $rejectTypes);
        $r1IsMinor = in_array($recommendations[0], $minorTypes);
        $r2IsMinor = in_array($recommendations[1], $minorTypes);
        $r1IsMajor = in_array($recommendations[0], $majorTypes);
        $r2IsMajor = in_array($recommendations[1], $majorTypes);

        // Both accept (different types)
        if ($r1IsAccept && $r2IsAccept) {
            return [
                'type' => 'consensus',
                'decision' => 'accept_oral' // Default to oral presentation
            ];
        }

        // Both reject
        if ($r1IsReject && $r2IsReject) {
            return [
                'type' => 'consensus',
                'decision' => 'reject'
            ];
        }

        // Both minor revisions
        if ($r1IsMinor && $r2IsMinor) {
            return [
                'type' => 'consensus',
                'decision' => 'minor_revisions'
            ];
        }

        // Both major revisions
        if ($r1IsMajor && $r2IsMajor) {
            return [
                'type' => 'consensus',
                'decision' => 'major_revisions'
            ];
        }

        // Determine conflict type
        $conflictType = $this->determineConflictType($recommendations);
        
        return [
            'type' => 'conflict',
            'conflict_type' => $conflictType
        ];
    }

    /**
     * Determine the type of conflict between reviewers
     */
    private function determineConflictType(array $recommendations): string
    {
        $acceptTypes = ['accept_oral', 'accept_poster'];
        $rejectTypes = ['reject'];
        $minorTypes = ['minor_revisions'];
        $majorTypes = ['major_revisions'];

        $r1 = $recommendations[0];
        $r2 = $recommendations[1];

        // Accept vs Reject
        if ((in_array($r1, $acceptTypes) && in_array($r2, $rejectTypes)) ||
            (in_array($r2, $acceptTypes) && in_array($r1, $rejectTypes))) {
            return 'accept_vs_reject';
        }

        // Minor vs Major Revision
        if ((in_array($r1, $minorTypes) && in_array($r2, $majorTypes)) ||
            (in_array($r2, $minorTypes) && in_array($r1, $majorTypes))) {
            return 'minor_vs_major_revision';
        }

        // Accept vs Minor Revision
        if ((in_array($r1, $acceptTypes) && in_array($r2, $minorTypes)) ||
            (in_array($r2, $acceptTypes) && in_array($r1, $minorTypes))) {
            return 'accept_vs_minor_revision';
        }

        // Accept vs Major Revision
        if ((in_array($r1, $acceptTypes) && in_array($r2, $majorTypes)) ||
            (in_array($r2, $acceptTypes) && in_array($r1, $majorTypes))) {
            return 'accept_vs_major_revision';
        }

        // Reject vs Minor Revision
        if ((in_array($r1, $rejectTypes) && in_array($r2, $minorTypes)) ||
            (in_array($r2, $rejectTypes) && in_array($r1, $minorTypes))) {
            return 'reject_vs_minor_revision';
        }

        // Reject vs Major Revision
        if ((in_array($r1, $rejectTypes) && in_array($r2, $majorTypes)) ||
            (in_array($r2, $rejectTypes) && in_array($r1, $majorTypes))) {
            return 'reject_vs_major_revision';
        }

        return 'unknown_conflict';
    }

    /**
     * Apply consensus decision automatically
     */
    private function applyConsensusDecision(AbstractSubmission $abstract, string $decision, $submittedReviews): array
    {
        $newStatus = $this->mapDecisionToStatus($decision);
        $reason = $this->generateDecisionReason($decision, $submittedReviews);

        // Update abstract status
        $abstract->update([
            'status' => $newStatus,
            'automated_decision' => true,
            'automated_decision_at' => now(),
            'automated_decision_reason' => $reason
        ]);

        // Send notification to author
        $this->sendDecisionNotification($abstract, $decision, $submittedReviews);

        return [
            'action' => $this->getActionFromDecision($decision),
            'status' => $newStatus,
            'reason' => $reason
        ];
    }

    /**
     * Flag abstract for admin decision due to conflict
     */
    private function flagForAdminDecision(AbstractSubmission $abstract, $submittedReviews, string $conflictType): array
    {
        $reason = $this->generateConflictReason($conflictType, $submittedReviews);

        // Update abstract status
        $abstract->update([
            'status' => 'conflict_queue',
            'automated_decision' => false,
            'automated_decision_reason' => $reason
        ]);

        // TODO: Send notification to admin about conflict
        $this->sendConflictNotification($abstract, $conflictType, $submittedReviews);

        return [
            'action' => 'conflicts',
            'status' => 'conflict_queue',
            'reason' => $reason
        ];
    }

    /**
     * Map decision to status
     */
    private function mapDecisionToStatus(string $decision): string
    {
        return match($decision) {
            'accept_oral', 'accept_poster' => 'accepted',
            'reject' => 'rejected',
            'minor_revisions' => 'minor_revision_required',
            'major_revisions' => 'major_revision_required',
            default => 'conflict_queue'
        };
    }

    /**
     * Get action name from decision
     */
    private function getActionFromDecision(string $decision): string
    {
        return match($decision) {
            'accept_oral', 'accept_poster' => 'accepted',
            'reject' => 'rejected',
            'minor_revisions' => 'minor_revisions',
            'major_revisions' => 'major_revisions',
            default => 'conflicts'
        };
    }

    /**
     * Generate decision reason
     */
    private function generateDecisionReason(string $decision, $submittedReviews): string
    {
        $avgScore = $submittedReviews->avg('score');
        $scoreText = $avgScore >= 85 ? 'high-quality' : ($avgScore >= 70 ? 'good' : 'acceptable');
        
        return "Automated decision based on reviewer consensus. Both reviewers recommended '{$decision}' with an average score of " . number_format($avgScore, 1) . " ({$scoreText} quality).";
    }

    /**
     * Generate conflict reason
     */
    private function generateConflictReason(string $conflictType, $submittedReviews): string
    {
        $recommendations = $submittedReviews->pluck('recommendation')->toArray();
        $avgScore = $submittedReviews->avg('score');
        $scoreVariance = $submittedReviews->max('score') - $submittedReviews->min('score');
        
        return "Reviewer conflict detected: {$conflictType}. Recommendations: " . implode(' vs ', $recommendations) . 
               ". Average score: " . number_format($avgScore, 1) . ", Variance: " . number_format($scoreVariance, 1) . " points.";
    }

    /**
     * Send decision notification to author
     */
    private function sendDecisionNotification(AbstractSubmission $abstract, string $decision, $submittedReviews): void
    {
        try {
            $template = match($decision) {
                'accept_oral', 'accept_poster' => 'emails.abstract-accepted',
                'reject' => 'emails.abstract-rejected',
                'minor_revisions', 'major_revisions' => 'emails.revision-required'
            };

            Mail::to($abstract->user->email)->send(new AbstractDecisionNotification(
                $abstract, $decision, $submittedReviews, $template
            ));
        } catch (\Exception $e) {
            Log::error("Failed to send decision notification for abstract {$abstract->id}: " . $e->getMessage());
        }
    }

    /**
     * Send conflict notification to admin
     */
    private function sendConflictNotification(AbstractSubmission $abstract, string $conflictType, $submittedReviews): void
    {
        try {
            // TODO: Implement admin notification for conflicts
            Log::info("Conflict detected for abstract {$abstract->id}: {$conflictType}");
        } catch (\Exception $e) {
            Log::error("Failed to send conflict notification for abstract {$abstract->id}: " . $e->getMessage());
        }
    }
}

