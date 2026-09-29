<?php

namespace App\Jobs;

use App\Models\AbstractSubmission;
use App\Services\ConferenceCodeSyncService;
use App\Services\SessionTopicDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectSessionTopics implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;
    public int $uniqueFor = 600;

    /**
     * @param array<int>|null $abstractIds  null = all accepted abstracts
     */
    public function __construct(private readonly ?array $abstractIds = null) {}

    public function handle(
        SessionTopicDetectionService $topicDetectionService,
        ConferenceCodeSyncService $conferenceCodeSyncService
    ): void {
        $query = AbstractSubmission::where('status', 'accepted');

        if ($this->abstractIds !== null) {
            $query->whereIn('id', $this->abstractIds);
        }

        foreach ($query->get() as $abstract) {
            $topicDetectionService->syncSuggestedTopic($abstract);
        }

        $conferenceCodeSyncService->resyncAcceptedCodes();
    }
}
