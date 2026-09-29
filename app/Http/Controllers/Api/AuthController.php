<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private function mobileUserPayload(User $user): array
    {
        $isPaid = $user->isPaid();

        return [
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'registration_category' => $user->registration_category,
            'payment_status' => $user->payment_status,
            'is_paid' => $isPaid,
            'role' => $user->role,
            'roles' => $user->roles->pluck('name')->toArray(),
            'profile_image_url' => $user->profile_image
                ? asset('storage/' . $user->profile_image)
                : 'https://ui-avatars.com/api/?name=' . urlencode($user->full_name) . '&color=05499c&background=E8F1FA&size=200',
            'title' => $user->title,
            'affiliation' => $user->affiliation,
            'bio' => $user->bio,
            'qr_code_token' => $isPaid ? $user->getOrCreateQrToken() : null,
        ];
    }

    /**
     * Authenticate user and return token.
     */
    public function login(Request $request)
    {
        Log::info('Login attempt', [
            'email' => $request->email,
            'device_name' => $request->device_name,
            'has_password' => $request->has('password')
        ]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);
        
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            Log::warning('Login failed: User not found', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! Hash::check($request->password, $user->password)) {
            Log::warning('Login failed: Password mismatch', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        Log::info('Login successful', ['email' => $request->email]);

        return response()->json([
            'token' => $user->createToken($request->device_name)->plainTextToken,
            'user' => $this->mobileUserPayload($user),
        ]);
    }

    /**
     * Log out current device.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get user profile status.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json($this->mobileUserPayload($user));
    }
}
