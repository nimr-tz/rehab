<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureScanningStaff;
use App\Models\User;
use App\Support\Summit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Staff sign in to the app with their portal email and password and get a token. */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::where('email', Str::lower(trim($data['email'])))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect.']);
        }
        if (! $user->hasAnyRole(EnsureScanningStaff::ROLES)) {
            throw ValidationException::withMessages(['email' => 'This account cannot use the staff app. Ask an admin for the registration officer role.']);
        }

        return response()->json([
            'token' => $user->createToken($data['device_name'], ['scan'])->plainTextToken,
            'user' => $this->user($user),
        ]);
    }

    public function me(Request $request, Summit $summit): JsonResponse
    {
        return response()->json([
            'user' => $this->user($request->user()),
            'summit' => [
                'title' => $summit->title(),
                'dates' => $summit->dateRange(),
                'venue' => $summit->venueLine(),
            ],
        ]);
    }

    /** Sign out this device only. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    private function user(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }
}
