<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AutoRoutingService;

class ProcessAutoRouting extends Command
{
    protected $signature = 'decisions:auto-route 
                            {--dry-run : Show what would be processed without making changes}
                            {--min-confidence=75 : Minimum confidence threshold for auto-routing}';

    protected $description = 'Automatically route abstracts with clear consensus to appropriate decisions';

    public function handle()
    {
        $this->info('🔄 Starting auto-routing process...');
        
        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }
        
        try {
            if ($this->option('dry-run')) {
                $results = $this->simulateAutoRouting();
            } else {
                $results = AutoRoutingService::processAutoRouting();
            }
            
            $this->displayResults($results);
            
            if (!empty($results['processed'])) {
                $this->info("✅ Auto-routing completed successfully");
            } else {
                $this->comment("ℹ️  No abstracts were eligible for auto-routing");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Auto-routing failed: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function simulateAutoRouting(): array
    {
        $eligibleAbstracts = AutoRoutingService::getEligibleAbstracts();
        $processed = [];
        $failed = [];
        
        foreach ($eligibleAbstracts as $abstract) {
            try {
                $decision = AutoRoutingService::evaluateAutoDecision($abstract);
                
                if ($decision['auto_applicable']) {
                    $processed[] = [
                        'id' => $abstract->id,
                        'title' => $abstract->title,
                        'decision' => $decision['decision_type'],
                        'confidence' => $decision['confidence'],
                        'rationale' => $decision['rationale']
                    ];
                }
            } catch (\Exception $e) {
                $failed[] = [
                    'id' => $abstract->id,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'total_eligible' => $eligibleAbstracts->count()
        ];
    }
    
    private function displayResults(array $results)
    {
        $this->newLine();
        $this->info("📊 Auto-routing Results:");
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Eligible Abstracts', $results['total_eligible']],
                ['Successfully Processed', count($results['processed'])],
                ['Failed', count($results['failed'])]
            ]
        );
        
        if (!empty($results['processed'])) {
            $this->newLine();
            $this->info("✅ Processed Abstracts:");
            
            $tableData = [];
            foreach ($results['processed'] as $item) {
                $tableData[] = [
                    $item['id'],
                    \Str::limit($item['title'], 40),
                    strtoupper($item['decision']),
                    $item['confidence'] . '%'
                ];
            }
            
            $this->table(['ID', 'Title', 'Decision', 'Confidence'], $tableData);
        }
        
        if (!empty($results['failed'])) {
            $this->newLine();
            $this->error("❌ Failed Abstracts:");
            
            foreach ($results['failed'] as $item) {
                $this->line("  • Abstract #{$item['id']}: {$item['error']}");
            }
        }
    }
}