<?php

/*
|--------------------------------------------------------------------------
| Background work
|--------------------------------------------------------------------------
|
| Production has no cron and no queue worker, so work that is due (reminders,
| and settling M-Pesa payments nobody is watching) runs after a web page has
| been sent, at most once every `every_seconds`.
|
| `php artisan portal:background` runs the same work, for a cron job.
|
*/

return [

    'after_requests' => (bool) env('BACKGROUND_AFTER_REQUESTS', true),

    'every_seconds' => (int) env('BACKGROUND_EVERY_SECONDS', 120),

    // Minutes between runs of each task.
    'tasks' => [
        'mpesa' => 5,
        'reminders' => 60,
    ],

    // Days before a deadline that a reminder goes out.
    'remind_days_before' => 3,

    // Days after registering without paying before a reminder goes out.
    'unpaid_after_days' => 7,

    // Days before the summit that anyone still unpaid is reminded.
    'unpaid_before_summit_days' => 14,

];
