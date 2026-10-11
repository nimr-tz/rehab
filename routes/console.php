<?php

use App\Support\BackgroundWork;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('portal:background {--force : Run every task now, whenever it last ran}', function (BackgroundWork $work) {
    foreach ($work->run((bool) $this->option('force')) as $task => $result) {
        $this->line($task.': '.$result);
    }
})->purpose('Send due reminders and settle pending M-Pesa payments (also runs after web requests)');
