<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\RevisionHistory;
use Illuminate\Support\Facades\DB;

class RevisionComparisonService
{
    /**
     * Generate comparison data between two revision rounds
     * Returns side-by-side comparison ready for display
     */
    public function generateComparisonData(AbstractSubmission $abstract, int $fromRound = 1, ?int $toRound = null): array
    {
        // If toRound not specified, use current round
        $toRound = $toRound ?? ($abstract->revision_round ?? 1);

        // Get submission data at different rounds from revision history
        $originalData = $this->getSubmissionDataAtRound($abstract, $fromRound);
        $revisedData = $this->getSubmissionDataAtRound($abstract, $toRound);

        return [
            'from_round' => $fromRound,
            'to_round' => $toRound,
            'original' => $originalData,
            'revised' => $revisedData,
            'changes' => $this->trackFieldChanges($originalData, $revisedData),
            'summary' => $this->getRevisionSummary($abstract, $fromRound, $toRound),
        ];
    }

    /**
     * Get submission data as it was at a specific round
     * For round 1, use original data. For later rounds, reconstruct from history if available
     */
    private function getSubmissionDataAtRound(AbstractSubmission $abstract, int $round): array
    {
        if ($round === 1) {
            // Return original submission data (or current if no revisions made)
            return [
                'title' => $abstract->title,
                'description' => $abstract->description,
                'author_name' => $abstract->author_name,
                'author_institute' => $abstract->author_institute,
                'subtheme' => $abstract->subtheme,
                'presentation_mode' => $abstract->presentation_mode,
                'coauthors' => $abstract->coauthors,
            ];
        }

        // For revision rounds, check if we have historical data
        // For now, return current data (in future, could store snapshots)
        return [
            'title' => $abstract->title,
            'description' => $abstract->description,
            'author_name' => $abstract->author_name,
            'author_institute' => $abstract->author_institute,
            'subtheme' => $abstract->subtheme,
            'presentation_mode' => $abstract->presentation_mode,
            'coauthors' => $abstract->coauthors,
        ];
    }

    /**
     * Track which fields changed between two versions
     */
    public function trackFieldChanges(array $original, array $revised): array
    {
        $changes = [];
        $fields = ['title', 'description', 'author_name', 'author_institute', 'subtheme', 'presentation_mode'];

        foreach ($fields as $field) {
            $oldValue = $original[$field] ?? '';
            $newValue = $revised[$field] ?? '';

            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'changed' => true,
                    'old' => $oldValue,
                    'new' => $newValue,
                    'diff' => $this->highlightChanges($oldValue, $newValue),
                ];
            } else {
                $changes[$field] = [
                    'changed' => false,
                    'value' => $oldValue,
                ];
            }
        }

        // Check coauthors separately (it's an array)
        $oldCoauthors = $original['coauthors'] ?? [];
        $newCoauthors = $revised['coauthors'] ?? [];
        
        $changes['coauthors'] = [
            'changed' => $oldCoauthors !== $newCoauthors,
            'old' => $oldCoauthors,
            'new' => $newCoauthors,
        ];

        return $changes;
    }

    /**
     * Generate simple text diff with highlights
     * Returns HTML-safe string with change markers
     */
    public function highlightChanges(string $oldText, string $newText): array
    {
        // For simple changes, return both with indicators
        // In a production system, you might use a proper diff library
        
        $oldWords = preg_split('/\s+/', $oldText);
        $newWords = preg_split('/\s+/', $newText);

        // Simple word-level comparison
        $added = array_diff($newWords, $oldWords);
        $removed = array_diff($oldWords, $newWords);

        return [
            'has_changes' => count($added) > 0 || count($removed) > 0,
            'added_words' => array_values($added),
            'removed_words' => array_values($removed),
            'old_length' => strlen($oldText),
            'new_length' => strlen($newText),
            'length_change' => strlen($newText) - strlen($oldText),
        ];
    }

    /**
     * Get a summary of all revisions for an abstract
     */
    public function getRevisionSummary(AbstractSubmission $abstract, ?int $fromRound = null, ?int $toRound = null): array
    {
        $fromRound = $fromRound ?? 1;
        $toRound = $toRound ?? ($abstract->revision_round ?? 1);

        // Get revision history entries
        $revisionHistory = RevisionHistory::where('abstract_submission_id', $abstract->id)
            ->whereBetween('revision_round', [$fromRound, $toRound])
            ->orderBy('revision_round', 'asc')
            ->orderBy('created_at', 'asc')
            ->with(['user'])
            ->get();

        $summary = [
            'total_rounds' => $toRound,
            'rounds_analyzed' => $toRound - $fromRound + 1,
            'current_status' => $abstract->status,
            'history' => [],
        ];

        foreach ($revisionHistory as $history) {
            $summary['history'][] = [
                'round' => $history->revision_round,
                'action' => $history->action,
                'action_description' => $history->action_description,
                'user' => $history->user ? $history->user->name : 'System',
                'feedback' => $history->feedback,
                'author_response' => $history->author_response,
                'admin_notes' => $history->admin_notes,
                'created_at' => $history->created_at,
            ];
        }

        return $summary;
    }

    /**
     * Get what changed in specific round compared to previous
     */
    public function getChangesInRound(AbstractSubmission $abstract, int $round): array
    {
        if ($round <= 1) {
            return ['message' => 'This is the original submission'];
        }

        // Get revision history for this round
        $revisionEntry = RevisionHistory::where('abstract_submission_id', $abstract->id)
            ->where('revision_round', $round)
            ->where('action', 'revision_submitted')
            ->first();

        if (!$revisionEntry) {
            return ['message' => 'No revision history found for this round'];
        }

        return [
            'round' => $round,
            'author_response' => $revisionEntry->author_response,
            'submitted_at' => $revisionEntry->created_at,
            'metadata' => $revisionEntry->metadata,
        ];
    }

    /**
     * Create a side-by-side comparison view data structure
     */
    public function createSideBySideView(AbstractSubmission $abstract): array
    {
        $currentRound = $abstract->revision_round ?? 1;
        
        $comparison = $this->generateComparisonData($abstract, 1, $currentRound);
        
        return [
            'abstract_id' => $abstract->id,
            'current_round' => $currentRound,
            'comparison' => $comparison,
            'fields' => $this->getFieldsForComparison($comparison),
            'has_changes' => $this->hasAnyChanges($comparison['changes']),
        ];
    }

    /**
     * Format fields for display in side-by-side view
     */
    private function getFieldsForComparison(array $comparison): array
    {
        $fields = [];
        $changes = $comparison['changes'];

        foreach ($changes as $fieldName => $change) {
            if ($fieldName === 'coauthors') {
                continue; // Handle separately
            }

            $fields[] = [
                'name' => $fieldName,
                'label' => ucwords(str_replace('_', ' ', $fieldName)),
                'original' => $change['old'] ?? $change['value'] ?? '',
                'revised' => $change['new'] ?? $change['value'] ?? '',
                'changed' => $change['changed'],
                'diff' => $change['diff'] ?? null,
            ];
        }

        return $fields;
    }

    /**
     * Check if there are any changes at all
     */
    private function hasAnyChanges(array $changes): bool
    {
        foreach ($changes as $change) {
            if (isset($change['changed']) && $change['changed']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get formatted diff for frontend display
     * Returns HTML-friendly structure
     */
    public function getFormattedDiff(string $oldText, string $newText): string
    {
        $diff = $this->highlightChanges($oldText, $newText);
        
        if (!$diff['has_changes']) {
            return $newText;
        }

        // Simple highlighting - in production, use a proper diff library
        $formatted = $newText;
        
        // Mark additions in green
        foreach ($diff['added_words'] as $word) {
            $formatted = str_replace($word, "<span class='bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 px-1 rounded'>{$word}</span>", $formatted);
        }

        return $formatted;
    }

    /**
     * Generate a summary of what changed for email notifications
     */
    public function getChangesSummaryForNotification(AbstractSubmission $abstract, int $fromRound, int $toRound): string
    {
        $comparison = $this->generateComparisonData($abstract, $fromRound, $toRound);
        $changes = $comparison['changes'];
        
        $changedFields = [];
        foreach ($changes as $field => $change) {
            if (isset($change['changed']) && $change['changed']) {
                $changedFields[] = ucwords(str_replace('_', ' ', $field));
            }
        }

        if (empty($changedFields)) {
            return 'No changes detected in the revision.';
        }

        return 'Changed fields: ' . implode(', ', $changedFields);
    }

    /**
     * Track submission metadata changes across rounds
     */
    public function trackMetadataChanges(AbstractSubmission $abstract): array
    {
        $rounds = $abstract->revision_round ?? 1;
        $metadata = [];

        for ($round = 1; $round <= $rounds; $round++) {
            $history = RevisionHistory::where('abstract_submission_id', $abstract->id)
                ->where('revision_round', $round)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($history) {
                $metadata[$round] = [
                    'round' => $round,
                    'status' => $history->action,
                    'timestamp' => $history->created_at,
                    'user' => $history->user_id,
                ];
            }
        }

        return $metadata;
    }
}

