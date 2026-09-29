<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorEmailQueues extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:monitor-emails {--refresh=5}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor email queue status with real-time updates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $refresh = $this->option('refresh');
        
        $this->info("📊 Email Queue Monitor (refreshing every {$refresh} seconds)");
        $this->info('Press Ctrl+C to stop monitoring');
        $this->newLine();

        while (true) {
            $this->clearScreen();
            $this->displayQueueStatus();
            sleep($refresh);
        }

        return 0;
    }

    private function clearScreen()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            system('cls');
        } else {
            system('clear');
        }
    }

    private function displayQueueStatus()
    {
        $this->info("📊 Email Queue Status - " . now()->format('Y-m-d H:i:s'));
        $this->line(str_repeat('=', 70));

        // Get queue statistics
        $stats = $this->getQueueStats();
        
        // Display queue counts
        $this->newLine();
        $this->info('📧 Queue Counts:');
        $this->table(
            ['Queue Name', 'Pending Jobs', 'Priority', 'Description'],
            [
                ['emails-critical', $stats['emails-critical'] ?? 0, '🔴 Critical', 'Status notifications'],
                ['emails-high-priority', $stats['emails-high-priority'] ?? 0, '🟠 High', 'Assignments & codes'],
                ['emails', $stats['emails'] ?? 0, '🟡 Normal', 'Submission confirmations'],
                ['emails-low-priority', $stats['emails-low-priority'] ?? 0, '🟢 Low', 'Review reminders'],
            ]
        );

        // Display recent failed jobs
        $failedJobs = $this->getRecentFailedJobs();
        if ($failedJobs->count() > 0) {
            $this->newLine();
            $this->warn('⚠️  Recent Failed Jobs:');
            foreach ($failedJobs->take(5) as $job) {
                $this->line("  • {$job->payload} - Failed: {$job->failed_at}");
            }
        }

        // Display processing statistics
        $this->newLine();
        $this->info('📈 Processing Stats (Last 24 hours):');
        $processed = DB::table('jobs')->where('created_at', '>=', now()->subDay())->count();
        $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
        $successRate = $processed > 0 ? round((($processed - $failed) / $processed) * 100, 1) : 0;
        
        $this->line("  Processed: {$processed}");
        $this->line("  Failed: {$failed}");
        $this->line("  Success Rate: {$successRate}%");

        $this->newLine();
        $this->line('Commands: php artisan queue:restart | php artisan queue:failed | Ctrl+C to exit');
    }

    private function getQueueStats()
    {
        return DB::table('jobs')
            ->select('queue', DB::raw('count(*) as count'))
            ->groupBy('queue')
            ->pluck('count', 'queue')
            ->toArray();
    }

    private function getRecentFailedJobs()
    {
        return DB::table('failed_jobs')
            ->select('payload', 'failed_at')
            ->orderBy('failed_at', 'desc')
            ->limit(5)
            ->get();
    }
}
