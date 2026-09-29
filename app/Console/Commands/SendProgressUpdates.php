<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\EmailNotificationService;

class SendProgressUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:send-progress-updates {--reviewer-id= : Send to specific reviewer ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send personalized progress updates to reviewers';

    protected $emailService;

    /**
     * Create a new command instance.
     */
    public function __construct(EmailNotificationService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reviewerId = $this->option('reviewer-id');

        if ($reviewerId) {
            // Send to specific reviewer
            $reviewer = User::find($reviewerId);
            if (!$reviewer) {
                $this->error("Reviewer with ID {$reviewerId} not found.");
                return 1;
            }

            if (!$reviewer->hasRole('reviewer')) {
                $this->error("User {$reviewer->name} is not a reviewer.");
                return 1;
            }

            $this->sendProgressUpdate($reviewer);
            $reviewerName = trim(($reviewer->first_name ?? '') . ' ' . ($reviewer->last_name ?? ''));
            $this->info("Progress update sent to {$reviewerName} ({$reviewer->email})");
        } else {
            // Send to all reviewers
            $reviewers = User::whereHas('roles', function($query) {
                $query->where('name', 'reviewer');
            })->get();

            if ($reviewers->isEmpty()) {
                $this->info('No reviewers found.');
                return 0;
            }

            $this->info("Sending progress updates to {$reviewers->count()} reviewers...");

            $bar = $this->output->createProgressBar($reviewers->count());
            $bar->start();

            $successCount = 0;
            $failCount = 0;

            foreach ($reviewers as $reviewer) {
                try {
                    $this->sendProgressUpdate($reviewer);
                    $successCount++;
                } catch (\Exception $e) {
                    $failCount++;
                    $this->error("Failed to send progress update to {$reviewer->name}: " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $this->info("Progress updates sent successfully: {$successCount}");
            if ($failCount > 0) {
                $this->warn("Failed to send: {$failCount}");
            }
        }

        return 0;
    }

    /**
     * Send progress update to a specific reviewer
     */
    private function sendProgressUpdate(User $reviewer)
    {
        $this->emailService->sendReviewerProgressUpdate($reviewer);
    }
} 