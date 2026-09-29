<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CleanupExpiredNotifications extends Command
{
    protected $signature = 'notifications:cleanup-expired';

    protected $description = 'Delete expired in-app notifications';

    public function handle(NotificationService $notificationService): int
    {
        $count = $notificationService->cleanupExpiredNotifications();

        $this->info("Deleted {$count} expired notification(s).");

        return self::SUCCESS;
    }
}
