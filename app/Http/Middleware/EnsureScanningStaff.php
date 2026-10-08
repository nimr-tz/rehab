<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The staff app is for registration officers and admins. Checked on every call, so a removed role takes effect at once. */
class EnsureScanningStaff
{
    public const ROLES = [Role::RegistrationOfficer->value, Role::Admin->value];

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->hasAnyRole(self::ROLES), 403, 'This account cannot use the staff app.');

        return $next($request);
    }
}
