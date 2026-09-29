<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Speaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SpeakerController extends Controller
{
    /**
     * Display a listing of speakers.
     */
    public function index(Request $request)
    {
        $query = Speaker::orderBy('display_order')
            ->orderBy('name')
            ->withCount('sessions');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('affiliation', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('bio', 'like', "%{$search}%");
            });
        }

        // Apply type filter
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Apply active status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $speakers = $query->get();

        return view('admin.speakers.index', compact('speakers'));
    }

    /**
     * Show the form for creating a new speaker.
     */
    public function create()
    {
        return view('admin.speakers.create');
    }

    /**
     * Store a newly created speaker.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:50', // Prof., Dr., Hon., etc.
            'email' => 'nullable|email|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'affiliation' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'type' => 'required|in:keynote,plenary,invited,panelist,vip,guest_of_honor,distinguished,special,chief_guest',
            'twitter' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('speakers', 'public');
            $validated['photo_path'] = $path;
        }

        // Build social links array
        $socialLinks = [];
        if ($request->twitter) $socialLinks['twitter'] = $request->twitter;
        if ($request->linkedin) $socialLinks['linkedin'] = $request->linkedin;
        if ($request->website) $socialLinks['website'] = $request->website;
        
        $validated['social_links'] = !empty($socialLinks) ? $socialLinks : null;
        $validated['is_active'] = (bool) $request->input('is_active', false);

        // Remove individual social fields
        unset($validated['twitter'], $validated['linkedin'], $validated['website'], $validated['photo']);

        Speaker::create($validated);

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker created successfully!');
    }

    /**
     * Show the form for editing a speaker.
     */
    public function edit(Speaker $speaker)
    {
        return view('admin.speakers.edit', compact('speaker'));
    }

    /**
     * Update the specified speaker.
     */
    public function update(Request $request, Speaker $speaker)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:50', // Prof., Dr., Hon., etc.
            'email' => 'nullable|email|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'affiliation' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'type' => 'required|in:keynote,plenary,invited,panelist,vip,guest_of_honor,distinguished,special,chief_guest',
            'twitter' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

    
        // Handle photo removal
        if ($request->has('remove_photo') && $request->remove_photo) {
            if ($speaker->photo_path) {
                Storage::disk('public')->delete($speaker->photo_path);
                $validated['photo_path'] = null;
            }
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($speaker->photo_path) {
                Storage::disk('public')->delete($speaker->photo_path);
            }
            $path = $request->file('photo')->store('speakers', 'public');
            $validated['photo_path'] = $path;
        }

        // Build social links array
        $socialLinks = [];
        if ($request->twitter) $socialLinks['twitter'] = $request->twitter;
        if ($request->linkedin) $socialLinks['linkedin'] = $request->linkedin;
        if ($request->website) $socialLinks['website'] = $request->website;
        
        $validated['social_links'] = !empty($socialLinks) ? $socialLinks : null;
        $validated['is_active'] = (bool) $request->input('is_active', false);

        // Remove individual social fields
        unset($validated['twitter'], $validated['linkedin'], $validated['website'], $validated['photo']);

        $speaker->update($validated);

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker updated successfully!');
    }

    /**
     * Remove the specified speaker.
     */
    public function destroy(Speaker $speaker)
    {
        // Delete photo if exists
        if ($speaker->photo_path) {
            Storage::disk('public')->delete($speaker->photo_path);
        }

        $speaker->delete();

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker deleted successfully!');
    }

    /**
     * Update speaker order via AJAX.
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'speakers' => 'required|array',
            'speakers.*.id' => 'required|exists:speakers,id',
            'speakers.*.display_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['speakers'] as $speakerData) {
            Speaker::where('id', $speakerData['id'])
                ->update(['display_order' => $speakerData['display_order']]);
        }

        return response()->json(['success' => true]);
    }
}
