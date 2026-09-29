<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AbstractReview;
use App\Models\AbstractSubmission;

class InitializeReviewAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conference:init-review-automation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the review clocks for all pending assignments to start the 3-day/4-day automation cycles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting review automation initialization...');

        $now = now();

        $eligibleAbstracts = AbstractSubmission::where(function ($q) {
                $q->whereNotNull('reviewer_id')->orWhereNotNull('reviewer_2_id');
            })
            ->whereNotIn('status', ['draft', 'accepted', 'rejected', 'revision_required', 'revision_requested'])
            ->get();

        $draftsReset = 0;
        $draftsCreated = 0;
        $submissionsTouched = 0;

        foreach ($eligibleAbstracts as $abstract) {
            $pendingForAbstract = false;
            $round = $abstract->revision_round ?? 0;

            $reviewerSlots = [
                1 => $abstract->reviewer_id,
                2 => $abstract->reviewer_2_id,
            ];

            foreach ($reviewerSlots as $slot => $reviewerId) {
                if (!$reviewerId) {
                    continue;
                }

                $submittedQuery = AbstractReview::where('abstract_submission_id', $abstract->id)
                    ->where('reviewer_id', $reviewerId)
                    ->where('status', 'submitted');

                if ($round > 0) {
                    $submittedQuery->where('review_round', $round);
                }

                $hasSubmitted = $submittedQuery->exists();
                if ($hasSubmitted) {
                    continue;
                }

                $pendingForAbstract = true;

                $draft = AbstractReview::where('abstract_submission_id', $abstract->id)
                    ->where('reviewer_id', $reviewerId)
                    ->where('review_round', $round)
                    ->where('status', 'draft')
                    ->first();

                if ($draft) {
                    $draft->update([
                        'assigned_at' => $now,
                        'last_reminded_at' => null,
                    ]);
                    $draftsReset++;
                } else {
                    AbstractReview::create([
                        'abstract_submission_id' => $abstract->id,
                        'reviewer_id' => $reviewerId,
                        'reviewer_number' => $slot,
                        'review_round' => $round,
                        'status' => 'draft',
                        'assigned_at' => $now,
                        'last_reminded_at' => null,
                    ]);
                    $draftsCreated++;
                }
            }

            if ($pendingForAbstract) {
                $abstract->update(['assigned_at' => $now]);
                $submissionsTouched++;
            }
        }

        $this->info("Reset {$draftsReset} existing draft reviews.");
        $this->info("Created {$draftsCreated} missing draft reviews.");
        $this->info("Reset assigned_at for {$submissionsTouched} abstracts.");

        $this->info('Review automation initialized successfully. The clock starts now!');
    }
}
