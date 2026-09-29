<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailLog;
use Carbon\Carbon;

class CleanupEmailLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:cleanup 
                          {--days=30 : Number of days to keep email logs}
                          {--keep-failed : Keep failed email logs regardless of age}
                          {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old email logs to maintain database performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $keepFailed = $this->option('keep-failed');
        $dryRun = $this->option('dry-run');
        
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("🧹 Email Logs Cleanup");
        $this->info("Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");
        $this->info("Keep failed logs: " . ($keepFailed ? 'Yes' : 'No'));
        $this->info("Dry run: " . ($dryRun ? 'Yes' : 'No'));
        $this->info('');

        // Build the query
        $query = EmailLog::where('created_at', '<', $cutoffDate);
        
        if ($keepFailed) {
            $query->whereNotIn('status', [EmailLog::STATUS_FAILED, EmailLog::STATUS_BOUNCED]);
        }

        $totalCount = $query->count();
        
        if ($totalCount === 0) {
            $this->info("✅ No email logs found to clean up.");
            return 0;
        }

        // Show breakdown by status
        $this->table(
            ['Status', 'Count'],
            EmailLog::where('created_at', '<', $cutoffDate)
                ->when($keepFailed, function($q) {
                    return $q->whereNotIn('status', [EmailLog::STATUS_FAILED, EmailLog::STATUS_BOUNCED]);
                })
                ->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->get()
                ->map(function($item) {
                    return [$item->status, $item->count];
                })
                ->toArray()
        );

        if ($dryRun) {
            $this->warn("🔍 DRY RUN: Would delete {$totalCount} email log(s)");
            return 0;
        }

        if (!$this->confirm("Are you sure you want to delete {$totalCount} email log(s)?")) {
            $this->info("❌ Operation cancelled.");
            return 0;
        }

        $this->info("🗑️ Deleting email logs...");
        
        // Delete in batches to avoid memory issues
        $batchSize = 1000;
        $deletedCount = 0;
        
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        while (true) {
            $batch = EmailLog::where('created_at', '<', $cutoffDate)
                ->when($keepFailed, function($q) {
                    return $q->whereNotIn('status', [EmailLog::STATUS_FAILED, EmailLog::STATUS_BOUNCED]);
                })
                ->limit($batchSize)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $log) {
                $log->delete();
                $deletedCount++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->info('');
        $this->info("✅ Successfully deleted {$deletedCount} email log(s)");

        // Show remaining counts
        $remainingTotal = EmailLog::count();
        $remainingFailed = EmailLog::whereIn('status', [EmailLog::STATUS_FAILED, EmailLog::STATUS_BOUNCED])->count();
        
        $this->info("📊 Remaining email logs: {$remainingTotal} total, {$remainingFailed} failed");

        return 0;
    }
}
