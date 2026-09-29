<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailNotificationService;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            
            // Send welcome notification after verification
            try {
                $emailService = app(EmailNotificationService::class);
                $emailService->sendWelcomeNotification($request->user());

                $notificationService = app(NotificationService::class);
                $notificationService->createWelcomeNotification($request->user());
            } catch (\Exception $e) {
                Log::warning("Failed to send welcome notifications: " . $e->getMessage());
            }
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
