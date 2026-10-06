<?php

namespace App\Http\Controllers\Media;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Edition;
use App\Services\GalleryService;
use App\Support\Summit;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Albums for photographers. Any photographer can add photos to any album of
 * the current summit; the person who created an album, and admins, can change it.
 */
class AlbumController extends Controller
{
    public function index(Request $request, Summit $summit): View
    {
        $edition = $summit->edition();
        $user = $request->user();

        $albums = $edition
            ? $edition->albums()->inProgrammeOrder()
                ->with(['cover', 'latestPhoto', 'session'])
                ->withCount([
                    'photos',
                    'photos as published_count' => fn ($q) => $q->published(),
                    'photos as mine_count' => fn ($q) => $q->where('user_id', $user->id),
                    'photos as my_unpublished_count' => fn ($q) => $q->where('user_id', $user->id)->whereNull('published_at'),
                ])
                ->get()
            : collect();

        return view('media.albums.index', [
            'edition' => $edition,
            'albums' => $albums,
            'stats' => [
                'published' => $albums->sum('published_count'),
                'mine' => $albums->sum('mine_count'),
                'waiting' => $albums->sum('my_unpublished_count'),
            ],
        ]);
    }

    public function create(Summit $summit): View|RedirectResponse
    {
        $edition = $summit->edition();

        if (! $edition) {
            return redirect()->route('media.albums.index');
        }

        return view('media.albums.form', ['album' => new Album] + $this->formOptions($edition));
    }

    public function store(Request $request, Summit $summit): RedirectResponse
    {
        $edition = $summit->edition();
        abort_unless($edition, 404);

        $album = $edition->albums()->create($this->validated($request, $edition) + [
            'slug' => Album::uniqueSlug($edition, $request->input('title')),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('media.albums.show', $album)->with('status', 'Album created. Add photos below.');
    }

    public function show(Request $request, Album $album): View
    {
        $user = $request->user();
        $filter = in_array($request->query('show'), ['unpublished', 'published', 'mine'], true) ? $request->query('show') : 'all';

        $photos = $album->photos()
            ->with('photographer')
            ->when($filter === 'unpublished', fn ($q) => $q->whereNull('published_at'))
            ->when($filter === 'published', fn ($q) => $q->published())
            ->when($filter === 'mine', fn ($q) => $q->where('user_id', $user->id))
            ->latest('id')
            ->paginate(96)
            ->withQueryString();

        $album->load(['edition', 'session'])->loadCount([
            'photos',
            'photos as published_count' => fn ($q) => $q->published(),
        ]);

        return view('media.albums.show', [
            'album' => $album,
            'photos' => $photos,
            'filter' => $filter,
            'canEdit' => $album->isEditableBy($user),
        ]);
    }

    public function edit(Request $request, Album $album): View
    {
        abort_unless($album->isEditableBy($request->user()), 403);

        return view('media.albums.form', ['album' => $album] + $this->formOptions($album->edition));
    }

    public function update(Request $request, Album $album): RedirectResponse
    {
        abort_unless($album->isEditableBy($request->user()), 403);

        // The slug stays as it was, so links people have shared keep working.
        $album->update($this->validated($request, $album->edition));

        return redirect()->route('media.albums.show', $album)->with('status', 'Album details saved.');
    }

    public function destroy(Request $request, Album $album, GalleryService $gallery): RedirectResponse
    {
        $user = $request->user();
        abort_unless($album->isEditableBy($user), 403);

        if ($album->photos()->exists() && ! $user->hasRole(Role::Admin->value)) {
            return back()->withErrors(['album' => 'Delete the photos first. Only an administrator can delete an album that still has photos.']);
        }

        $gallery->deleteAlbum($album);

        return redirect()->route('media.albums.index')->with('status', 'Album "'.$album->title.'" deleted.');
    }

    private function validated(Request $request, Edition $edition): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'day' => ['nullable', 'date'],
            'programme_session_id' => ['nullable', Rule::exists('programme_sessions', 'id')->where('edition_id', $edition->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'programme_session_id.exists' => 'Choose a session from this summit’s programme.',
        ]);

        // An album for a session belongs to that session's day unless another day is chosen.
        $data['day'] ??= null;
        $data['programme_session_id'] ??= null;

        if (! $data['day'] && $data['programme_session_id']) {
            $data['day'] = $edition->sessions()->find($data['programme_session_id'])->starts_at->toDateString();
        }

        return $data;
    }

    private function formOptions(Edition $edition): array
    {
        $days = [];
        if ($edition->start_date) {
            $period = CarbonPeriod::create($edition->start_date, $edition->end_date ?? $edition->start_date);
            foreach ($period as $i => $day) {
                $days[$day->toDateString()] = 'Day '.($i + 1).' · '.$day->format('l j F');
            }
        }

        $sessions = $edition->sessions()->where('kind', '!=', 'break')->get()
            ->mapWithKeys(fn ($session) => [$session->id => $session->starts_at->format('D j M · H:i').' · '.$session->title])
            ->all();

        return ['edition' => $edition, 'days' => $days, 'sessions' => $sessions];
    }
}
