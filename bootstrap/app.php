<?php

use App\Services\CriticalErrorAlertService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Prevent HTML pages (which carry a per-session CSRF token) from being
        // served from cache with a stale token — a common source of 419 errors.
        $middleware->appendToGroup('web', \App\Http\Middleware\PreventStaleHtmlCache::class);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'reviewer' => \App\Http\Middleware\ReviewerMiddleware::class,
            'finance_officer' => \App\Http\Middleware\FinanceOfficerMiddleware::class,
            'registration_officer' => \App\Http\Middleware\RegistrationOfficerMiddleware::class,
            'scientific_admin' => \App\Http\Middleware\ScientificAdminMiddleware::class,
            'reviewer_onboarding' => \App\Http\Middleware\EnsureReviewerPreferencesSet::class,
        ]);
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('conference:review-watchdog')->dailyAt('02:00');
        $schedule->command('conference:fill-reviewer-queues')->everyFiveMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $exception): void {
            app(CriticalErrorAlertService::class)->handle($exception, request());
        });

        // Turn the raw "419 Page Expired" wall into a graceful, recoverable flow.
        // A token mismatch almost always means the session quietly expired or the
        // page was reopened from cache — not something the user did wrong.
        //
        // Note: by the time render callbacks run, Laravel has already converted the
        // TokenMismatchException into an HttpException(419), so we match on that and
        // bail out (return null) for any other HTTP error so it renders normally.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $exception, \Illuminate\Http\Request $request) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please refresh the page and try again.',
                ], 419);
            }

            return redirect()->back()
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password']))
                ->with('error', 'Your session timed out for security. Please check your details and submit again.');
        });
    })->create();
