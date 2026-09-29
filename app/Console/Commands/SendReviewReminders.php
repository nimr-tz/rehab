<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AbstractSubmission;
use App\Models\User;
use App\Services\EmailNotificationService;
use Carbon\Carbon;

class SendReviewReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:send-reminders {--days=2 : Days since assignment to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send review reminders to reviewers who have pending assignments';

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
        $daysSinceAssignment = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($daysSinceAssignment);

        $this->info("Sending review reminders for assignments older than {$daysSinceAssignment} days...");

        // Find abstracts assigned more than X days ago that haven't been reviewed
        $pendingReviews = AbstractSubmission::where(function($query) use ($cutoffDate) {
            $query->where(function($q) use ($cutoffDate) {
                $q->whereNotNull('reviewer1_id')
                  ->whereNull('reviewer1_score')
                  ->where('assigned_at', '<=', $cutoffDate);
            })->orWhere(function($q) use ($cutoffDate) {
                $q->whereNotNull('reviewer2_id')
                  ->whereNull('reviewer2_score')
                  ->where('assigned_at', '<=', $cutoffDate);
            });
        })->get();

        $remindersSent = 0;
        $errors = [];

        foreach ($pendingReviews as $abstract) {
            try {
                // Send reminder to primary reviewer if they haven't completed
                if ($abstract->reviewer1_id && $abstract->reviewer1_score === null) {
                    $reviewer1 = User::find($abstract->reviewer1_id);
                    if ($reviewer1) {
                        $this->emailService->sendDelayedReviewReminder($abstract, $reviewer1, 1);
                        $remindersSent++;
                        $this->info("Sent reminder to Reviewer A {$reviewer1->name} for abstract #{$abstract->id}");
                    }
                }

                // Send reminder to Reviewer B if they haven't completed
                if ($abstract->reviewer2_id && $abstract->reviewer2_score === null) {
                    $reviewer2 = User::find($abstract->reviewer2_id);
                    if ($reviewer2) {
                        $this->emailService->sendDelayedReviewReminder($abstract, $reviewer2, 2);
                        $remindersSent++;
                        $this->info("Sent reminder to Reviewer B {$reviewer2->name} for abstract #{$abstract->id}");
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Error sending reminder for abstract #{$abstract->id}: " . $e->getMessage();
                $this->error("Error sending reminder for abstract #{$abstract->id}: " . $e->getMessage());
            }
        }

        $this->info("Review reminders sent: {$remindersSent}");
        
        if (!empty($errors)) {
            $this->warn("Errors encountered: " . count($errors));
            foreach ($errors as $error) {
                $this->error($error);
            }
        }

        return 0;
    }
}
