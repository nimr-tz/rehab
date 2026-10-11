<?php

namespace App\Providers;

use App\Models\AbstractSubmission;
use App\Models\ReviewAssignment;
use App\Support\Summit;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\DevCommands;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Process\ExecutableFinder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Summit::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', fn ($view) => $view->with('summit', $this->app->make(Summit::class)));

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        Route::model('abstract', AbstractSubmission::class);
        Route::model('assignment', ReviewAssignment::class);

        Paginator::defaultView('pagination.portal');

        // Setting a password from an emailed link proves the address, for
        // accounts the desk or a group leader created on someone's behalf.
        Event::listen(PasswordReset::class, function (PasswordReset $event) {
            if (! $event->user->hasVerifiedEmail()) {
                $event->user->markEmailAsVerified();
            }
        });

        $this->registerDevCommands();
    }

    /**
     * `artisan dev` starts its processes with a bare "php", which on a machine
     * with several PHP versions may not be the one this app needs. Use the
     * binary that is running now instead, and add Mailpit when it is installed.
     */
    private function registerDevCommands(): void
    {
        if (! $this->app->runningInConsole() || ! $this->app->isLocal()) {
            return;
        }

        $php = '"'.PHP_BINARY.'"';

        DevCommands::register("{$php} artisan serve", 'server');
        DevCommands::register("{$php} artisan queue:listen --tries=1 --timeout=0", 'queue');

        if ($mailpit = (new ExecutableFinder)->find('mailpit')) {
            DevCommands::register('"'.$mailpit.'"', 'mail');
        }
    }
}
