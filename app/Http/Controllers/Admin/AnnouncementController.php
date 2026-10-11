<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Audience;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/** Admins email a group of people: write, test, send, and follow the sending. */
class AnnouncementController extends Controller
{
    public function __construct(private Summit $summit, private AnnouncementService $announcements) {}

    public function index(): View
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::where('edition_id', $this->summit->edition()?->id)
                ->withCount(['recipients', 'recipients as sent_count' => fn ($q) => $q->whereNotNull('sent_at'), 'recipients as failed_count' => fn ($q) => $q->whereNotNull('failed_at')])
                ->with('author')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        abort_unless($edition = $this->summit->edition(), 404);

        return $this->form(new Announcement(['audience' => Audience::Registered]), $edition);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($edition = $this->summit->edition(), 404);

        $announcement = $edition->announcements()->create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('admin.announcements.show', $announcement)->with('status', 'Draft saved. Send yourself a test, then send it.');
    }

    public function show(Announcement $announcement): View
    {
        $this->ensureCurrent($announcement);
        $announcement->loadCount(['recipients', 'recipients as sent_count' => fn ($q) => $q->whereNotNull('sent_at'), 'recipients as failed_count' => fn ($q) => $q->whereNotNull('failed_at')]);

        return view('admin.announcements.show', [
            'announcement' => $announcement,
            'reach' => $announcement->isDraft() ? $this->announcements->recipients($announcement->edition, $announcement->audience)->count() : null,
            'failures' => $announcement->recipients()->whereNotNull('failed_at')->limit(20)->get(),
        ]);
    }

    public function edit(Announcement $announcement): View
    {
        $this->ensureCurrent($announcement);
        abort_unless($announcement->isDraft(), 403, 'A sent announcement cannot be changed.');

        return $this->form($announcement, $announcement->edition);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->ensureCurrent($announcement);
        abort_unless($announcement->isDraft(), 403, 'A sent announcement cannot be changed.');

        $announcement->update($this->validated($request));

        return redirect()->route('admin.announcements.show', $announcement)->with('status', 'Draft saved.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->ensureCurrent($announcement);
        abort_unless($announcement->isDraft(), 403, 'A sent announcement cannot be deleted.');

        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('status', 'Draft deleted.');
    }

    public function test(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->ensureCurrent($announcement);
        $this->announcements->test($announcement, $request->user());

        return back()->with('status', 'A test copy was sent to '.$request->user()->email.'.');
    }

    public function send(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->ensureCurrent($announcement);
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Confirm that you want to send it.']);

        try {
            $count = $this->announcements->send($announcement);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['confirm' => $e->getMessage()]);
        }

        // The first batch goes out straight after this page; the rest follow in the background.
        dispatch(fn () => app(AnnouncementService::class)->sendBatch())->afterResponse();

        return back()->with('status', 'Sending to '.$count.' '.str('person')->plural($count).'. The emails go out in batches over the next few minutes.');
    }

    public function retry(Announcement $announcement): RedirectResponse
    {
        $this->ensureCurrent($announcement);
        $count = $this->announcements->retryFailed($announcement);

        return back()->with('status', $count.' failed '.str('email')->plural($count).' will be tried again.');
    }

    private function form(Announcement $announcement, $edition): View
    {
        return view('admin.announcements.form', [
            'announcement' => $announcement,
            'audiences' => collect(Audience::cases())->map(fn (Audience $a) => [
                'audience' => $a,
                'count' => $this->announcements->recipients($edition, $a)->count(),
            ]),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:6000'],
            'audience' => ['required', Rule::enum(Audience::class)],
        ]);
    }

    private function ensureCurrent(Announcement $announcement): void
    {
        abort_unless($announcement->edition_id === $this->summit->edition()?->id, 404);
    }
}
