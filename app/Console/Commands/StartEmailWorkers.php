<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class StartEmailWorkers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:start-email-workers {--daemon : Run workers in daemon mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start all email queue workers with different priorities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting email queue workers...');
        
        $daemon = $this->option('daemon');
        $daemonFlag = $daemon ? '--daemon' : '';
        
        // Start workers for different priority queues
        $workers = [
            'emails-critical' => 'Critical emails (submission confirmations, status updates)',
            'emails-high-priority' => 'High priority emails (reviewer assignments, code assignments)',
            'emails' => 'Standard emails (updates, notifications)',
            'emails-low-priority' => 'Low priority emails (draft saves, reminders)'
        ];
        
        foreach ($workers as $queue => $description) {
            $this->info("Starting worker for {$queue}: {$description}");
            
            $command = "php artisan queue:work --queue={$queue} --tries=3 --timeout=120 {$daemonFlag}";
            
            if ($daemon) {
                // Run in background for daemon mode
                Process::run($command);
                $this->line("✓ Worker started for {$queue}");
            } else {
                // Run in foreground for testing
                $this->line("Running: {$command}");
                Process::run($command);
            }
        }
        
        if (!$daemon) {
            $this->info('All email workers started. Press Ctrl+C to stop.');
        } else {
            $this->info('All email workers started in daemon mode.');
        }
    }
} 