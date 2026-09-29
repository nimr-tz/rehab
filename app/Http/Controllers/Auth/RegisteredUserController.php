<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Check if email is available.
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = strtolower($request->email);
        $exists = User::where('email', $email)->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'This email is already registered.' : 'Email is available.'
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Ensure email is lowercase
        $request->merge(['email' => strtolower($request->email)]);

        \Log::info('Registration attempt', [
            'email' => $request->email,
            'has_file' => $request->hasFile('student_document'),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'affiliation' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'registration_category' => ['required', 'string', 'in:professional_local,professional_international,student_local,student_international,test_item'],
            'student_status' => ['required', 'in:yes,no'],
            'student_document' => ['required_if:student_status,yes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'intent_attendee' => ['nullable', 'boolean'],
            'intent_presenter' => ['nullable', 'boolean'],
            'intent_group_leader' => ['nullable', 'boolean'],
        ]);

        // Handle file upload
        $student_document_path = null;
        if ($request->hasFile('student_document')) {
            $student_document_path = $request->file('student_document')->store('student_documents', 'public');
        }

        $user = User::create([
            'title' => $validated['title'],
            'first_name' => mb_convert_case(mb_strtolower($validated['first_name']), MB_CASE_TITLE, 'UTF-8'),
            'last_name' => mb_convert_case(mb_strtolower($validated['last_name']), MB_CASE_TITLE, 'UTF-8'),
            'affiliation' => $validated['affiliation'] ?? null,
            'country' => $validated['country'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'registration_category' => $validated['registration_category'],
            'student_status' => $validated['student_status'],
            'student_document' => $student_document_path,
            'student_verification_status' => $validated['student_status'] === 'yes' ? 'pending' : null,
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'intent_attendee' => $request->boolean('intent_attendee'),
            'intent_presenter' => $request->boolean('intent_presenter'),
            'intent_group_leader' => $request->boolean('intent_group_leader'),
        ]);

        \Log::info('User created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
        ]);

        // ✅ NEW: Assign role using the new multi-role system
        try {
            $roleAssigned = $user->assignRole('author', true, null); // Set author as primary role
            if (!$roleAssigned) {
                \Log::warning('Failed to assign author role to user', ['user_id' => $user->id]);
            }
        } catch (\Exception $e) {
            \Log::error('Error assigning role to user', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        \Log::info('User logged in, redirecting to verification notice');

        // Force redirect to the URL to ensure it works
        return redirect('verify-email');
    }
}
