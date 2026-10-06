<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Album;
use App\Models\Edition;
use App\Models\Photo;
use App\Models\PhotoRemovalRequest;
use App\Models\User;
use App\Notifications\PhotoRemovalRequested;
use App\Services\GalleryService;
use App\Support\Summit;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The public photo gallery. Only published photos are shown; photographers and
 * admins can also open unpublished ones, to check them before publishing.
 */
class GalleryController extends Controller
{
    public function __construct(private GalleryService $gallery) {}

    public function index(Request $request, Summit $summit): View
    {
        $editions = Edition::whereHas('albums', fn ($q) => $q->visible())->orderByDesc('year')->get();
        $current = $summit->edition();
        $edition = $editions->firstWhere('year', (int) $request->query('year'))
            ?? ($current && $editions->contains($current) ? $current : null)
            ?? $editions->first()
            ?? $current;

        $albums = $edition
            ? $edition->albums()->visible()->inProgrammeOrder()
                ->with(['cover', 'session.topic'])
                ->withCount(['photos as published_count' => fn ($q) => $q->published()])
                ->get()
            : collect();

        $featured = $edition
            ? Photo::published()->where('is_featured', true)
                ->whereHas('album', fn ($q) => $q->where('edition_id', $edition->id))
                ->with(['album.edition', 'photographer'])->inShootingOrder()->limit(10)->get()
            : collect();

        return view('gallery.index', [
            'edition' => $edition,
            'editions' => $editions,
            'days' => $albums->groupBy(fn (Album $album) => $album->day?->toDateString() ?? ''),
            'dayLabels' => $this->dayLabels($edition, $albums),
            'featured' => $featured,
            'photoCount' => $albums->sum('published_count'),
        ]);
    }

    public function album(Album $album): View
    {
        $photos = $album->publishedPhotos()->with(['photographer', 'album.edition'])->paginate(config('gallery.per_page'));
        abort_if($photos->total() === 0, 404);

        $album->load(['edition', 'session.topic']);

        return view('gallery.album', [
            'album' => $album,
            'photos' => $photos,
            'credits' => $album->credits(),
            'dayLabel' => $this->dayLabels($album->edition, collect([$album]))[$album->day?->toDateString() ?? ''] ?? null,
            'more' => $album->edition->albums()->visible()->whereKeyNot($album->id)->inProgrammeOrder()
                ->with('cover')->withCount(['photos as published_count' => fn ($q) => $q->published()])->limit(4)->get(),
        ]);
    }

    public function photo(Request $request, Photo $photo, string $size): StreamedResponse
    {
        $this->authorizeView($request, $photo);

        return $this->gallery->disk()->response(
            $size === 'display' ? $photo->display_path : $photo->thumb_path,
            headers: ['Cache-Control' => $photo->isPublished() ? 'public, max-age=86400' : 'private, no-store'],
        );
    }

    public function download(Request $request, Photo $photo): StreamedResponse
    {
        $this->authorizeView($request, $photo);

        return $this->gallery->disk()->download($photo->original_path, $photo->downloadName());
    }

    public function requestRemoval(Request $request, Photo $photo): RedirectResponse
    {
        abort_unless($photo->isPublished(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $removal = PhotoRemovalRequest::create($data + ['photo_id' => $photo->id, 'album_title' => $photo->album->title]);
        Notification::send(User::role(Role::Admin->value)->get(), new PhotoRemovalRequested($removal));

        return back()->with('status', 'Thank you. The organisers have your request and will review it. If they agree, the photo is taken down.');
    }

    private function authorizeView(Request $request, Photo $photo): void
    {
        abort_unless(
            $photo->isPublished() || $request->user()?->hasAnyRole([Role::Photographer->value, Role::Admin->value]),
            404,
        );
    }

    /**
     * "Day 1 · Wed 15 Sep" for days within the summit, "Mon 13 Sep" for others.
     *
     * @return array<string, string>
     */
    private function dayLabels(?Edition $edition, Collection $albums): array
    {
        return $albums->pluck('day')->filter()->unique()->mapWithKeys(function (CarbonInterface $day) use ($edition) {
            $start = $edition?->start_date;
            $end = $edition?->end_date ?? $start;
            $label = $day->format('D j M');

            if ($start && $day->betweenIncluded($start, $end)) {
                $label = 'Day '.((int) $start->diffInDays($day) + 1).' · '.$label;
            }

            return [$day->toDateString() => $label];
        })->all();
    }
}
