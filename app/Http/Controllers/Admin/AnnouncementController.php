<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of the resource.
     */
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $announcements = \App\Models\Announcement::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.announcements.index', compact('announcements'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.announcements.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:50',
            'is_urgent' => 'boolean',
        ]);

        $announcement = \App\Models\Announcement::create($validated);

        $users = User::all();
        foreach ($users as $user) {
            $this->notificationService->createAnnouncementNotification(
                $user,
                $announcement->id,
                $announcement->title,
                $announcement->title . ': ' . str($announcement->content)->stripTags()->limit(140),
                $announcement->is_urgent,
                route('admin.announcements.index')
            );
        }

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement created and pushed to mobile users with notifications enabled.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Not used currently
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);
        return view('admin.announcements.edit', compact('announcement'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:50',
            'is_urgent' => 'boolean',
        ]);

        $announcement = \App\Models\Announcement::findOrFail($id);
        
        // Handle checkbox boolean which might be missing from request if unchecked
        $validated['is_urgent'] = $request->has('is_urgent');

        $announcement->update($validated);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }
}
