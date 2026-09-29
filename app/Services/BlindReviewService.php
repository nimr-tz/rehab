<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\ReviewConflict;
use App\Models\User;
use App\Services\ReviewAssignmentService;

class BlindReviewService
{
    public function assignReviewerWithConflictCheck($abstractId, $reviewerId, $reviewerPosition = 1)
    {
        $abstract = AbstractSubmission::find($abstractId);
        $reviewer = User::find($reviewerId);
        
        if (!$abstract || !$reviewer) {
            return [
                'success' => false,
                'message' => 'Abstract or reviewer not found'
            ];
        }
        
        // 🚫 VALIDATION: Prevent same reviewer being assigned to both positions
        $otherReviewerField = $reviewerPosition === 1 ? 'reviewer_2_id' : 'reviewer_id';
        $otherReviewerId = $abstract->$otherReviewerField;
        
        if (!is_null($otherReviewerId) && $otherReviewerId == $reviewerId) {
            $otherPosition = $reviewerPosition === 1 ? 'Reviewer B' : 'Reviewer A';
            $currentPosition = $reviewerPosition === 1 ? 'Reviewer A' : 'Reviewer B';
            
            return [
                'success' => false,
                'message' => "Cannot assign {$reviewer->first_name} as {$currentPosition} - they are already assigned as {$otherPosition} for this abstract!"
            ];
        }
        
        // Check for conflicts using the model methods
        $conflicts = $abstract->checkAllConflicts($reviewerId);
        
        if (!empty($conflicts)) {
            // Log conflicts but allow admin to override
            foreach ($conflicts as $conflict) {
                ReviewConflict::updateOrCreate([
                    'abstract_submission_id' => $abstractId,
                    'reviewer_id' => $reviewerId,
                    'conflict_type' => $conflict['type']
                ], [
                    'conflict_reason' => $conflict['reason'],
                    'is_resolved' => false
                ]);
            }
            
            return [
                'success' => false,
                'message' => 'Conflicts detected: ' . implode(', ', array_column($conflicts, 'reason')),
                'conflicts' => $conflicts,
                'can_override' => true
            ];
        }
          // No conflicts, proceed with assignment
        $field = $reviewerPosition === 1 ? 'reviewer_id' : 'reviewer_2_id';
        $abstract->update([
            $field => $reviewerId,
            'assigned_at' => now(),
            'conflict_checked' => true
        ]);

        // Update status based on reviewer assignment state
        $abstract->refresh();
        if ($abstract->hasFinalDecisionStatus()) {
            if ($reviewer) {
                app(ReviewAssignmentService::class)->ensureDraftReview($abstract, $reviewer, $reviewerPosition);
            }

            return [
                'success' => true,
                'message' => 'Reviewer assigned successfully'
            ];
        }

        $hasBothReviewers = !is_null($abstract->reviewer_id) && !is_null($abstract->reviewer_2_id);
        $hasAnyReviewer = !is_null($abstract->reviewer_id) || !is_null($abstract->reviewer_2_id);
        
        if ($hasBothReviewers) {
            $abstract->update(['status' => 'under_review']);
        } elseif ($hasAnyReviewer) {
            $abstract->update(['status' => 'reviewer_assigned']);
        }

        // Ensure a draft review record exists for automation
        if ($reviewer) {
            app(ReviewAssignmentService::class)->ensureDraftReview($abstract, $reviewer, $reviewerPosition);
        }

        return [
            'success' => true,
            'message' => 'Reviewer assigned successfully'
        ];
    }
    
    public function getAnonymizedAbstractForReviewer($abstractId, $reviewerId)
    {
        $abstract = AbstractSubmission::find($abstractId);
        
        if (!$abstract) {
            return null;
        }
        
        // Use the model's method to get appropriate review data
        return $abstract->getReviewDataForReviewer($reviewerId);
    }
    
    public function enableBlindReview($abstractId)
    {
        $abstract = AbstractSubmission::find($abstractId);
        
        if (!$abstract) return false;
        
        return $abstract->enableBlindReview();
    }
    
    public function disableBlindReview($abstractId)
    {
        $abstract = AbstractSubmission::find($abstractId);
        
        if (!$abstract) return false;
        
        $abstract->disableBlindReview();
        return true;
    }
    
    public function getEligibleReviewers($abstractId)
    {
        $abstract = AbstractSubmission::find($abstractId);
        if (!$abstract) return collect();
        
        // Get all users with reviewer role
        $reviewers = User::whereHas('roles', function($query) {
            $query->where('name', 'reviewer');
        })->get();
        
        // Filter out conflicted reviewers
        return $reviewers->filter(function ($reviewer) use ($abstract) {
            $conflicts = $abstract->checkAllConflicts($reviewer->id);
            return empty($conflicts);
        });
    }
    
    public function bulkEnableBlindReview($abstractIds = null)
    {
        $query = AbstractSubmission::query();
        
        if ($abstractIds) {
            $query->whereIn('id', $abstractIds);
        }
        
        $abstracts = $query->where('is_blind_review', false)->get();
        
        $count = 0;
        foreach ($abstracts as $abstract) {
            $abstract->enableBlindReview();
            $count++;
        }
        
        return [
            'success' => true,
            'message' => "Blind review enabled for {$count} abstracts",
            'count' => $count
        ];
    }
    
    public function getConflictSummary($abstractId)
    {
        $abstract = AbstractSubmission::find($abstractId);
        if (!$abstract) return null;
        
        $conflicts = ReviewConflict::where('abstract_submission_id', $abstractId)
            ->with('reviewer')
            ->get();
            
        return [
            'total_conflicts' => $conflicts->count(),
            'unresolved_conflicts' => $conflicts->where('is_resolved', false)->count(),
            'conflict_types' => $conflicts->groupBy('conflict_type')->map->count(),
            'conflicts' => $conflicts
        ];
    }
    
    public function resolveConflict($conflictId, $adminNote = null)
    {
        $conflict = ReviewConflict::find($conflictId);
        
        if (!$conflict) return false;
        
        $conflict->update([
            'is_resolved' => true,
            'conflict_reason' => $conflict->conflict_reason . 
                ($adminNote ? " [Admin Note: {$adminNote}]" : " [Resolved by admin]")
        ]);
        
        return true;
    }
    
    /**
     * Update abstract status based on reviewer assignment state
     * This ensures consistent status updates across all assignment operations
     */
    public function updateAbstractStatusFromAssignments($abstractId)
    {
        $abstract = AbstractSubmission::find($abstractId);
        if (!$abstract) {
            return false;
        }
        
        $abstract->refresh(); // Ensure we have the latest data
        if ($abstract->hasFinalDecisionStatus()) {
            return true;
        }

        $hasBothReviewers = !is_null($abstract->reviewer_id) && !is_null($abstract->reviewer_2_id);
        $hasAnyReviewer = !is_null($abstract->reviewer_id) || !is_null($abstract->reviewer_2_id);
        
        $oldStatus = $abstract->status;
        
        if ($hasBothReviewers) {
            $newStatus = 'under_review';
        } elseif ($hasAnyReviewer) {
            $newStatus = 'reviewer_assigned';
        } else {
            $newStatus = 'submitted';
        }
        
        if ($oldStatus !== $newStatus) {
            $updateData = ['status' => $newStatus];
            
            // Clear assigned_at if no reviewers are assigned
            if ($newStatus === 'submitted' && !$hasAnyReviewer) {
                $updateData['assigned_at'] = null;
            }
            
            $abstract->update($updateData);
            \Illuminate\Support\Facades\Log::info("SIMPLIFIED STATUS UPDATE: Abstract {$abstract->id} - Status changed from '{$oldStatus}' to '{$newStatus}' - Has both reviewers: " . ($hasBothReviewers ? 'Yes' : 'No') . " - Has any reviewer: " . ($hasAnyReviewer ? 'Yes' : 'No'));
        }
        
        return true;
    }
}
