<?php

namespace App\Services;

class ConferenceCodeSyncService
{
    public function __construct(
        private readonly ConferenceCodeAssignmentService $assignmentService
    ) {}

    /**
     * Delegates to the unified ConferenceCodeAssignmentService.
     * Kept for backwards compatibility with existing admin controller calls.
     */
    public function resyncAcceptedCodes(): int
    {
        return $this->assignmentService->resyncAll();
    }
}
