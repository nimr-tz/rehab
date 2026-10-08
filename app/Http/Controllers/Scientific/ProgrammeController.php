<?php

namespace App\Http\Controllers\Scientific;

use App\Http\Controllers\Controller;
use App\Models\ProgrammeSession;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The committee edits the programme: sessions, halls, times and the CPD points each session earns. */
class ProgrammeController extends Controller
{
    public function __construct(private Summit $summit) {}

    public function index(): View
    {
        $edition = $this->summit->edition();
        $sessions = $edition?->sessions()->with('topic')->withCount('attendances')->get() ?? collect();

        return view('scientific.programme.index', [
            'days' => $sessions->groupBy(fn (ProgrammeSession $s) => $s->starts_at->toDateString()),
            'totalPoints' => $sessions->sum(fn (ProgrammeSession $s) => $s->points()),
            'unpointed' => $sessions->filter(fn (ProgrammeSession $s) => $s->isScannable() && $s->cpd_points === null)->count(),
        ]);
    }

    public function create(): View
    {
        abort_unless($this->summit->edition(), 404);

        $session = new ProgrammeSession([
            'kind' => 'parallel',
            'starts_at' => $this->summit->edition()->start_date?->copy()->setTime(9, 0),
            'ends_at' => $this->summit->edition()->start_date?->copy()->setTime(10, 30),
        ]);

        return $this->form($session);
    }

    public function store(Request $request): RedirectResponse
    {
        $edition = $this->summit->edition();
        abort_unless($edition, 404);

        $session = $edition->sessions()->create($this->validated($request));

        return redirect()->route('scientific.programme.index')->with('status', $session->title.' was added to the programme.');
    }

    public function edit(ProgrammeSession $session): View
    {
        $this->ensureCurrent($session);

        return $this->form($session);
    }

    public function update(Request $request, ProgrammeSession $session): RedirectResponse
    {
        $this->ensureCurrent($session);
        $session->update($this->validated($request));

        return redirect()->route('scientific.programme.index')->with('status', $session->title.' was updated.');
    }

    public function destroy(ProgrammeSession $session): RedirectResponse
    {
        $this->ensureCurrent($session);

        // Attendance is someone's CPD record: never lose it with a session.
        if ($session->attendances()->exists()) {
            return back()->withErrors(['session' => $session->title.' has recorded attendance, so it cannot be deleted. Edit it instead.']);
        }

        $session->delete();

        return redirect()->route('scientific.programme.index')->with('status', $session->title.' was removed from the programme.');
    }

    private function form(ProgrammeSession $session): View
    {
        return view('scientific.programme.form', [
            'session' => $session,
            'kinds' => ProgrammeSession::KINDS,
            'topics' => $this->summit->edition()->topics()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(array_keys(ProgrammeSession::KINDS))],
            'topic_id' => ['nullable', Rule::exists('topics', 'id')->where('edition_id', $this->summit->edition()->id)],
            'hall' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'chair' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'cpd_points' => ['nullable', 'numeric', 'min:0', 'max:99'],
        ], [
            'ends_at.after' => 'The session must end after it starts.',
        ]);

        // Breaks earn nothing.
        if ($data['kind'] === 'break') {
            $data['cpd_points'] = null;
        }

        return $data;
    }

    private function ensureCurrent(ProgrammeSession $session): void
    {
        abort_unless($session->edition_id === $this->summit->edition()?->id, 404);
    }
}
