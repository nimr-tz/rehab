<?php

namespace App\Support;

use App\Services\MpesaPaymentService;
use App\Services\ReminderService;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Work that is due on a clock rather than on a click. Production has no cron
 * and no queue worker, so it runs after web pages are sent (see
 * RunBackgroundWork), or from `php artisan portal:background`.
 */
class BackgroundWork
{
    /**
     * Run each task whose interval has passed. Returns what each one did.
     *
     * @return array<string, int|string>
     */
    public function run(bool $force = false): array
    {
        $lock = Cache::lock('background:running', 600);

        if (! $lock->get()) {
            return [];
        }

        try {
            $done = [];

            foreach ($this->tasks() as $name => $task) {
                $minutes = config("background.tasks.{$name}", 60);

                if (! $force && ! Cache::add("background:{$name}", now()->timestamp, $minutes * 60)) {
                    continue;
                }

                try {
                    $done[$name] = $task();
                } catch (Throwable $e) {
                    report($e);
                    $done[$name] = 'failed: '.$e->getMessage();
                }
            }

            return $done;
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, callable(): int> */
    private function tasks(): array
    {
        return [
            'mpesa' => fn () => app(MpesaPaymentService::class)->reconcileStale(),
            'reminders' => fn () => app(ReminderService::class)->sendDue(),
        ];
    }
}
