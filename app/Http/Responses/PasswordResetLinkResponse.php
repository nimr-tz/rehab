<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

/**
 * Same answer whether or not the email has an account, so the reset form
 * cannot be used to find out who is registered. Throttling still says so.
 */
class PasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public const MESSAGE = 'If an account exists for that email, we have sent a link to reset the password. It is valid for 60 minutes.';

    public function __construct(private string $status) {}

    public function toResponse($request)
    {
        if ($this->status === Password::RESET_THROTTLED) {
            if ($request->wantsJson()) {
                throw ValidationException::withMessages(['email' => [trans($this->status)]]);
            }

            return back()->withInput($request->only('email'))->withErrors(['email' => trans($this->status)]);
        }

        return $request->wantsJson()
            ? response()->json(['message' => self::MESSAGE])
            : back()->with('status', self::MESSAGE);
    }
}
