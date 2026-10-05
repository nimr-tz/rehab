<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'titles' => CreateNewUser::TITLES,
            'countries' => Countries::options(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', Rule::in(CreateNewUser::TITLES)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'country' => ['required', Rule::in(Countries::codes())],
            'institution' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
        ]);

        $request->user()->update($data);

        return back()->with('status', 'Your profile has been saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill(['password' => Hash::make($request->input('password'))])->save();

        return back()->with('status', 'Your password has been changed.');
    }
}
