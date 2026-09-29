<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            // Do NOT include 'title', 'first_name' or 'last_name': the name is
            // printed on certificates, so it is locked once the account exists.
            // Name corrections go through the organizers/admin.
            'phone' => 'nullable|string|max:30',
            'affiliation' => 'nullable|string|max:255',
            'institute' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'student_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'intent_attendee' => 'nullable|boolean',
            'intent_presenter' => 'nullable|boolean',
            'intent_group_leader' => 'nullable|boolean',
            'linkedin_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'professional_board' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            // Do NOT include 'email' in validation, so it can't be edited
        ]);

        // Explicitly handle boolean checkboxes with direct assignment
        $user->intent_attendee = $request->has('intent_attendee') || $request->input('intent_attendee') === '1';
        $user->intent_presenter = $request->has('intent_presenter') || $request->input('intent_presenter') === '1';
        $user->intent_group_leader = $request->has('intent_group_leader') || $request->input('intent_group_leader') === '1';

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $validated['profile_image'] = $path;

            // Delete old image if exists
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
        } elseif ($request->input('remove_profile_image') == '1') {
            // User requested to remove the image
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $validated['profile_image'] = null;
        }

        if ($request->hasFile('student_document')) {
            $path = $request->file('student_document')->store('student_documents', 'public');
            $validated['student_document'] = $path;

            // Re-trigger verification process
            $user->student_verification_status = 'pending';
            $user->student_verified_at = null;
            $user->student_verified_by = null;
            $user->student_verification_notes = null;

            // Delete old document if exists
            if ($user->student_document) {
                Storage::disk('public')->delete($user->student_document);
            }
        }

        $user->fill($validated);
        $user->save();

        return back()->with('status', 'profile-updated');
    }

    /**
     * Update the user's conference journey intents.
     */
    public function updateJourney(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'intent_attendee' => 'nullable|boolean',
            'intent_presenter' => 'nullable|boolean',
            'intent_group_leader' => 'nullable|boolean',
        ]);

        // Capture boolean states manually as checkboxes only send when checked
        $user->intent_attendee = $request->has('intent_attendee');
        $user->intent_presenter = $request->has('intent_presenter');
        $user->intent_group_leader = $request->has('intent_group_leader');

        $user->save();

        return back()->with('status', 'journey-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
