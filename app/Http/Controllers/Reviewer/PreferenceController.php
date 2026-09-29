<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReviewerSubtheme;
use Illuminate\Support\Facades\Auth;

class PreferenceController extends Controller
{
    /**
     * Show the preference selection form
     */
    public function index()
    {
        return redirect()->route('reviewer.dashboard');
    }

    /**
     * Save the reviewer preferences
     */
    public function store(Request $request)
    {
        return redirect()->route('reviewer.dashboard');
        $request->validate([
            'subthemes' => 'required|array|min:1',
            'reviewer_max_load' => 'required|integer|min:1|max:50',
        ]);

        $user = Auth::user();

        // Update basic load
        $user->update([
            'reviewer_max_load' => $request->reviewer_max_load,
            'reviewer_preferences_set' => true
        ]);

        // Sync subthemes
        $user->interests()->delete();
        foreach ($request->subthemes as $subtheme) {
            $user->interests()->create([
                'subtheme_name' => $subtheme
            ]);
        }

        return redirect()->route('reviewer.dashboard')->with('success', 'Your reviewing preferences have been updated successfully.');
    }
}
