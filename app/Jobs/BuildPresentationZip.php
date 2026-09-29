<?php

namespace App\Jobs;

use App\Services\PresentationDownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class BuildPresentationZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public readonly string $jobId) {}

    public function handle(PresentationDownloadService $service): void
    {
        $service->build($this->jobId);
    }

    public function failed(Throwable $exception): void
    {
        app(PresentationDownloadService::class)
            ->markFailed($this->jobId, $exception->getMessage());
    }
}
