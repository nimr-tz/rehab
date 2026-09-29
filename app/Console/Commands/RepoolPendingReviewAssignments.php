<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReviewAssignmentService;

class RepoolPendingReviewAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conference:repool-pending-review-assignments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Return unfinished reviewer assignments to the pool and reassign them using reviewer-selected preferences';

    /**
     * Execute the console command.
     */
    public function handle(ReviewAssignmentService $assignmentService)
    {
        $this->info('Re-pooling unfinished reviewer assignments...');

        $result = $assignmentService->repoolUnfinishedAssignmentsAndReassign();

        $this->info("Released unfinished reviewer seats: {$result['released']}");
        $this->info("Reassigned using reviewer preferences: {$result['reassigned']}");
        $this->info("Still unmatched after re-pooling: {$result['unmatched']}");

        return self::SUCCESS;
    }
}
