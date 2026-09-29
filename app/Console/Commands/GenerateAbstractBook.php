<?php

namespace App\Console\Commands;

use App\Services\AbstractBookGenerationService;
use Illuminate\Console\Command;

class GenerateAbstractBook extends Command
{
    protected $signature = 'abstract-book:generate {userId? : The admin user ID who triggered generation}';
    protected $description = 'Generate the abstract book PDF in the background';

    public function handle(AbstractBookGenerationService $service): int
    {
        $userId = $this->argument('userId') ? (int) $this->argument('userId') : null;

        $this->info('Starting abstract book generation...');
        $result = $service->generate($userId);

        if (($result['status'] ?? '') === 'ready') {
            $this->info('Abstract book generated successfully.');
            return Command::SUCCESS;
        }

        $this->error('Generation failed: ' . ($result['message'] ?? 'Unknown error'));
        return Command::FAILURE;
    }
}
