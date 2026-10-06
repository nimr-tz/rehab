<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Photo;
use App\Services\GalleryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Publishing, featuring and deleting selected photos of an album. */
class PhotoController extends Controller
{
    public function bulk(Request $request, Album $album, GalleryService $gallery): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:publish,unpublish,feature,unfeature,delete'],
            'photos' => ['required', 'array'],
            'photos.*' => ['integer'],
        ], [
            'photos.required' => 'Select at least one photo first.',
        ]);

        $selected = $album->photos()->whereIn('id', $data['photos'])->get();
        [$photos, $others] = $selected->partition(fn (Photo $photo) => $photo->isManageableBy($request->user()));

        foreach ($photos as $photo) {
            match ($data['action']) {
                'publish' => $photo->update(['published_at' => $photo->published_at ?? now()]),
                'unpublish' => $photo->update(['published_at' => null, 'is_featured' => false]),
                // A highlight has to be public, so featuring also publishes.
                'feature' => $photo->update(['is_featured' => true, 'published_at' => $photo->published_at ?? now()]),
                'unfeature' => $photo->update(['is_featured' => false]),
                'delete' => $gallery->delete($photo),
            };
        }

        $done = [
            'publish' => 'published', 'unpublish' => 'unpublished', 'feature' => 'added to the highlights',
            'unfeature' => 'removed from the highlights', 'delete' => 'deleted',
        ][$data['action']];

        $message = $photos->count().' '.Str::plural('photo', $photos->count()).' '.$done.'.';
        if ($others->isNotEmpty()) {
            $message .= ' '.$others->count().' '.Str::plural('photo', $others->count()).' by other photographers were left as they were.';
        }

        return back()->with('status', $message);
    }
}
