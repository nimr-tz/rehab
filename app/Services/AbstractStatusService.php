<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Abstract Status Management Service
 *
 * Provides centralized, consistent handling of abstract status changes
 * and validation of status transitions across the entire system.
 */
class AbstractStatusService
{
    private array $statusConfig;
    private array $statusGroups;
    private array $decisionMappings;
    private EmailNotificationService $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->statusConfig = Config::get('abstract_status.statuses');
        $this->statusGroups = Config::get('abstract_status.status_groups');
        $this->decisionMappings = Config::get('abstract_status.decision_mappings');
        $this->emailService = $emailService;
    }

    /**
     * Change abstract status with validation and logging
     */
    public function changeStatus(
        AbstractSubmission $abstract,
        string $newStatus,
        ?string $reason = null,
        ?array $metadata = null,
        array $options = []
    ): array {
        try {
            // Validate status transition
            if (!$this->canTransitionTo($abstract, $newStatus)) {
                return [
                    'success' => false,
                    'message' => "Invalid status transition from '{$abstract->status}' to '{$newStatus}'"
                ];
            }

            $oldStatus = $abstract->status;

            // Update the abstract
            $updateData = [
                'status' => $newStatus,
                'status_changed_at' => now(),
                'status_changed_by' => Auth::id()
            ];

            // Add metadata if provided
            if ($metadata) {
                foreach ($metadata as $key => $value) {
                    $updateData[$key] = $value;
                }
            }

            $abstract->update($updateData);

            // Log the status change
            $this->logStatusChange($abstract, $oldStatus, $newStatus, $reason);

            // Trigger post-transition actions
            $this->handlePostTransitionActions($abstract, $oldStatus, $newStatus, $options);

            return [
                'success' => true,
                'message' => "Status changed from '{$oldStatus}' to '{$newStatus}'",
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ];

        } catch (\Exception $e) {
            Log::error("Status change failed for abstract {$abstract->id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Status change failed due to an error'
            ];
        }
    }

    /**
     * Check if a status transition is valid
     */
    public function canTransitionTo(AbstractSubmission $abstract, string $newStatus): bool
    {
        $currentStatus = $abstract->status;

        // Check for legacy status and map to current equivalent
        $legacyMappings = Config::get('abstract_status.legacy_mappings', []);
        if (isset($legacyMappings[$currentStatus])) {
            $currentStatus = $legacyMappings[$currentStatus];
        }

        // Check if current status exists in config
        if (!isset($this->statusConfig[$currentStatus])) {
            Log::warning("Unknown current status: {$currentStatus} for abstract {$abstract->id}");
            // If it's unknown but we want to move it to a valid final state, allow it for admins
            return Auth::check() && Auth::user()->hasRole('admin');
        }

        // Check if new status exists in config
        if (!isset($this->statusConfig[$newStatus])) {
            Log::warning("Unknown target status: {$newStatus}");
            return false;
        }

        // Check if transition is allowed
        $allowedTransitions = $this->statusConfig[$currentStatus]['transitions'] ?? [];
        return in_array($newStatus, $allowedTransitions);
    }

    /**
     * Get all valid next statuses for an abstract
     */
    public function getValidTransitions(AbstractSubmission $abstract): array
    {
        $currentStatus = $abstract->status;

        if (!isset($this->statusConfig[$currentStatus])) {
            return [];
        }

        $transitions = $this->statusConfig[$currentStatus]['transitions'] ?? [];
        $validTransitions = [];

        foreach ($transitions as $status) {
            if (isset($this->statusConfig[$status])) {
                $validTransitions[$status] = $this->statusConfig[$status];
            }
        }

        return $validTransitions;
    }

    /**
     * Get status information with styling
     */
    public function getStatusInfo(string $status): ?array
    {
        return $this->statusConfig[$status] ?? null;
    }

    /**
     * Get abstracts by status group
     */
    public function getAbstractsByGroup(string $group): array
    {
        if (!isset($this->statusGroups[$group])) {
            return [];
        }

        return $this->statusGroups[$group];
    }

    /**
     * Check if abstract is in a specific status group
     */
    public function isInStatusGroup(AbstractSubmission $abstract, string $group): bool
    {
        $groupStatuses = $this->statusGroups[$group] ?? [];
        return in_array($abstract->status, $groupStatuses);
    }

    /**
     * Process admin decision and return appropriate status
     */
    public function processAdminDecision(string $decisionType): string
    {
        return $this->decisionMappings[$decisionType] ?? $decisionType;
    }

    /**
     * Get status color for UI display
     */
    public function getStatusColor(string $status): string
    {
        return $this->statusConfig[$status]['color'] ?? 'gray';
    }

    /**
     * Get status icon for UI display
     */
    public function getStatusIcon(string $status): string
    {
        return $this->statusConfig[$status]['icon'] ?? '📄';
    }

    /**
     * Check if status change requires admin approval
     */
    public function requiresAdminApproval(string $status): bool
    {
        $adminOnly = Config::get('abstract_status.validation.admin_only', []);
        return in_array($status, $adminOnly);
    }

    /**
     * Check if author can change to this status
     */
    public function authorCanChange(string $status): bool
    {
        $authorChangeable = Config::get('abstract_status.validation.author_changeable', []);
        return in_array($status, $authorChangeable);
    }

    /**
     * Check if abstract is in a locked state
     */
    public function isLocked(AbstractSubmission $abstract): bool
    {
        $lockedStatuses = Config::get('abstract_status.validation.locked', []);
        return in_array($abstract->status, $lockedStatuses);
    }

    /**
     * Check if abstract is active (not completed/withdrawn)
     */
    public function isActive(AbstractSubmission $abstract): bool
    {
        $activeStatuses = Config::get('abstract_status.validation.active', []);
        return in_array($abstract->status, $activeStatuses);
    }

    /**
     * Get abstracts that need admin attention
     */
    public function getAbstractsNeedingAdminAction(): array
    {
        return [
            'conflicts' => AbstractSubmission::where('status', 'ready_for_decision')->count(),
            'revisions' => AbstractSubmission::where('status', 'revision_submitted')->count(),
            'overdue' => $this->getOverdueCount()
        ];
    }

    /**
     * Bulk status update with validation
     */
    public function bulkUpdateStatus(
        array $abstractIds,
        string $newStatus,
        ?string $reason = null
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($abstractIds as $abstractId) {
            try {
                $abstract = AbstractSubmission::findOrFail($abstractId);
                $result = $this->changeStatus($abstract, $newStatus, $reason);

                if ($result['success']) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Abstract {$abstractId}: " . $result['message'];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Abstract {$abstractId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get status statistics for dashboard
     */
    public function getStatusStatistics(): array
    {
        $stats = [];

        foreach ($this->statusGroups as $group => $statuses) {
            $stats[$group] = AbstractSubmission::whereIn('status', $statuses)->count();
        }

        return $stats;
    }

    /**
     * Convert legacy status to new status
     */
    public function convertLegacyStatus(string $legacyStatus): string
    {
        $legacyMappings = Config::get('abstract_status.legacy_mappings', []);
        return $legacyMappings[$legacyStatus] ?? $legacyStatus;
    }

    // Private helper methods

    private function logStatusChange(
        AbstractSubmission $abstract,
        string $oldStatus,
        string $newStatus,
        ?string $reason
    ): void {
        Log::info("Abstract status changed", [
            'abstract_id' => $abstract->id,
            'title' => substr($abstract->title, 0, 50) . '...',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString()
        ]);
    }

    private function handlePostTransitionActions(
        AbstractSubmission $abstract,
        string $oldStatus,
        string $newStatus,
        array $options = []
    ): void {
        $suppressNotifications = (bool) ($options['suppress_notifications'] ?? false);

        // Trigger notifications based on status change
        if (!$suppressNotifications) {
            match($newStatus) {
                'reviewer_assigned' => $this->notifyReviewersAssigned($abstract),
                'under_review' => $this->notifyReviewStarted($abstract),
                'ready_for_decision' => $this->notifyAdminDecisionNeeded($abstract),
                'revision_required' => $this->notifyRevisionRequired($abstract),
                'accepted' => null,
                'rejected' => null,
                default => null
            };
        }

        // Update related records
        $this->updateRelatedRecords($abstract, $newStatus);
    }

    private function getOverdueCount(): int
    {
        $reviewDeadline = Config::get('abstract_status.deadlines.review', 14);
        $revisionDeadline = Config::get('abstract_status.deadlines.revision', 14);

        return AbstractSubmission::where(function($query) use ($reviewDeadline, $revisionDeadline) {
            $query->where('status', 'under_review')
                  ->where('created_at', '<', now()->subDays($reviewDeadline))
                  ->orWhere('status', 'revision_required')
                  ->where('revision_deadline', '<', now());
        })->count();
    }

    private function notifyReviewersAssigned(AbstractSubmission $abstract): void
    {
        if ($abstract->reviewer_id) {
            $reviewer = User::find($abstract->reviewer_id);
            if ($reviewer) {
                $this->emailService->sendReviewerAssignment($abstract, $reviewer, 'reviewer1');
            }
        }
        if ($abstract->reviewer_2_id) {
            $reviewer2 = User::find($abstract->reviewer_2_id);
            if ($reviewer2) {
                $this->emailService->sendReviewerAssignment($abstract, $reviewer2, 'reviewer2');
            }
        }
    }

    private function notifyReviewStarted(AbstractSubmission $abstract): void
    {
        // Implementation for review start notifications if needed
    }

    private function notifyAdminDecisionNeeded(AbstractSubmission $abstract): void
    {
        // Implementation for admin notifications if needed
    }

    private function notifyRevisionRequired(AbstractSubmission $abstract): void
    {
        $this->emailService->sendRevisionRequestedNotification($abstract, $abstract->admin_comment);
    }

    private function notifyAccepted(AbstractSubmission $abstract): void
    {
        $this->emailService->sendAbstractAcceptedNotification($abstract, $abstract->admin_comment);

        if ($abstract->hasAuthorVisibleFeedback()) {
            $this->emailService->sendAcceptedClarificationNotification($abstract);
        }
    }

    private function notifyRejected(AbstractSubmission $abstract): void
    {
        $this->emailService->sendAbstractRejectedNotification($abstract, $abstract->admin_comment);
    }

    private function updateRelatedRecords(AbstractSubmission $abstract, string $newStatus): void
    {
        // Update review assignments, deadlines, etc. based on new status
        match($newStatus) {
            'accepted' => app(ConferenceCodeAssignmentService::class)->onAbstractAccepted($abstract),
            'under_review' => $this->setReviewDeadlines($abstract),
            'revision_required' => $this->setRevisionDeadlines($abstract),
            default => null
        };
    }

    private function setReviewDeadlines(AbstractSubmission $abstract): void
    {
        $deadlineDays = Config::get('abstract_status.deadlines.review', 14);
        // Set review deadlines for assigned reviewers
    }

    private function setRevisionDeadlines(AbstractSubmission $abstract): void
    {
        $deadlineDays = Config::get('abstract_status.deadlines.revision', 14);

        $abstract->update([
            'revision_deadline' => now()->addDays($deadlineDays)
        ]);
    }

}
