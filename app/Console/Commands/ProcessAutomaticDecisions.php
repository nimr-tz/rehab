<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WorkflowAutomationService;

class ProcessAutomaticDecisions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'abstracts:process-decisions {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automatic decisions for abstracts with completed reviews';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Processing automatic decisions for abstracts...');
        
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $service = new WorkflowAutomationService();
        $results = $service->processAutomaticDecisions();

        // Display results
        $this->newLine();
        $this->info('📊 Processing Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $results['processed']],
                ['Automatically Accepted', $results['accepted']],
                ['Automatically Rejected', $results['rejected']],
                ['Minor Revisions Required', $results['minor_revisions']],
                ['Major Revisions Required', $results['major_revisions']],
                ['Conflicts (Admin Review)', $results['conflicts']],
                ['Errors', count($results['errors'])],
            ]
        );

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('❌ Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->line("  • {$error}");
            }
        }

        if ($results['processed'] > 0) {
            $this->newLine();
            $this->info('✅ Automatic decision processing completed successfully!');
            
            if ($results['conflicts'] > 0) {
                $this->warn("⚠️  {$results['conflicts']} abstracts require admin review due to reviewer conflicts.");
            }
        } else {
            $this->info('ℹ️  No abstracts ready for automatic processing.');
        }

        return Command::SUCCESS;
    }
}