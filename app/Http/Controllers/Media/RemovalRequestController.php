<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\PhotoRemovalRequest;
use App\Services\GalleryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Requests from people who want a photo of themselves taken down. Admins decide. */
class RemovalRequestController extends Controller
{
    public function index(): View
    {
        return view('media.removal-requests', [
            'pending' => PhotoRemovalRequest::pending()->with('photo.album', 'photo.photographer')->oldest()->get(),
            'resolved' => PhotoRemovalRequest::whereNotNull('resolved_at')->with('resolver')->latest('resolved_at')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, PhotoRemovalRequest $removal, GalleryService $gallery): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:remove,keep']]);

        if ($removal->resolved_at) {
            return back()->with('status', 'This request was already settled.');
        }

        $gallery->resolveRemoval($removal, $request->user(), $data['decision'] === 'remove');

        return back()->with('status', $data['decision'] === 'remove'
            ? 'The photo has been taken down. Let '.$removal->name.' know at '.$removal->email.'.'
            : 'The photo stays in the gallery. Let '.$removal->name.' know at '.$removal->email.'.');
    }
}
