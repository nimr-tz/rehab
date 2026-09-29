<?php

namespace App\Jobs;

use App\Services\AbstractBookGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateAbstractBookPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries = 1;

    public function __construct(private readonly ?int $userId = null)
    {
    }

    public function handle(AbstractBookGenerationService $abstractBook): void
    {
        $abstractBook->generate($this->userId);
    }

    public function failed(Throwable $exception): void
    {
        app(AbstractBookGenerationService::class)
            ->markFailed('Abstract book generation failed: ' . $exception->getMessage(), $this->userId);
    }
}
