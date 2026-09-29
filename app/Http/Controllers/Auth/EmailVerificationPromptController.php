<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();
        
        // Debug: Log the user's verification status
        \Log::info('Email verification check', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_verified_email' => $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at,
        ]);
        
        return $user->hasVerifiedEmail()
                    ? redirect()->intended(route('dashboard', absolute: false))
                    : view('auth.verify-email');
    }
}
