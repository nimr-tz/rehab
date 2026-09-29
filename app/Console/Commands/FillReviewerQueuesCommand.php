<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ReviewAssignmentService;
use Illuminate\Console\Command;

class FillReviewerQueuesCommand extends Command
{
    protected $signature = 'conference:fill-reviewer-queues';

    protected $description = 'Fill reviewer queues for reviewers with preferences set and available capacity';

    public function handle(ReviewAssignmentService $assignmentService): int
    {
        $reviewers = User::where('reviewer_preferences_set', true)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'reviewer');
            })
            ->with('interests')
            ->get();

        $reviewersFilled = 0;
        $assignmentsCreated = 0;

        foreach ($reviewers as $reviewer) {
            $assigned = $assignmentService->fillReviewerQueue($reviewer);

            if ($assigned > 0) {
                $reviewersFilled++;
                $assignmentsCreated += $assigned;
            }
        }

        $this->info("Reviewer queues checked: {$reviewers->count()}");
        $this->info("Reviewers filled: {$reviewersFilled}");
        $this->info("Assignments created: {$assignmentsCreated}");

        return self::SUCCESS;
    }
}
