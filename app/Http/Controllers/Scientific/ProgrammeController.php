<?php

namespace App\Http\Controllers\Scientific;

use App\Enums\AbstractStatus;
use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\ProgrammeSession;
use App\Models\Registration;
use App\Models\SessionAttendance;
use App\Services\ProgrammeService;
use App\Support\Csv;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The committee edits the programme: sessions, halls, times and the CPD points each session earns. */
class ProgrammeController extends Controller
{
    public function __construct(private Summit $summit) {}

    public function index(): View
    {
        $edition = $this->summit->edition();
        $sessions = $edition?->sessions()->with('topic')->withCount('attendances', 'abstracts')->get() ?? collect();

        return view('scientific.programme.index', [
            'days' => $sessions->groupBy(fn (ProgrammeSession $s) => $s->starts_at->toDateString()),
            'totalPoints' => $sessions->sum(fn (ProgrammeSession $s) => $s->points()),
            'unpointed' => $sessions->filter(fn (ProgrammeSession $s) => $s->isScannable() && $s->cpd_points === null)->count(),
            'unscheduled' => $edition ? $this->unscheduled($edition->id)->count() : 0,
        ]);
    }

    /** Every scan: who attended which session, when, and what it earned. */
    public function attendanceExport(): StreamedResponse
    {
        $attendances = SessionAttendance::query()
            ->whereHas('session', fn ($q) => $q->where('edition_id', $this->summit->edition()?->id))
            ->with('session', 'registration.user', 'scanner')
            ->get()
            ->sortBy(fn (SessionAttendance $a) => [$a->session->starts_at->timestamp, $a->scanned_at->timestamp]);

        return Csv::download('attendance-'.now()->format('Y-m-d').'.csv',
            ['Session', 'Day', 'Starts', 'Hall', 'Badge number', 'Name', 'Email', 'Profession', 'Institution', 'Scanned at', 'Scanned by', 'CPD points'],
            $attendances->map(fn (SessionAttendance $a) => [
                $a->session->title, $a->session->starts_at->toDateString(), $a->session->starts_at->format('H:i'), $a->session->hall,
                $a->registration->reference, $a->registration->user->name, $a->registration->user->email,
                $a->registration->user->profession, $a->registration->user->institution,
                $a->scanned_at->toDateTimeString(), $a->scanner?->name, $a->session->points(),
            ]));
    }

    /** One row per participant who attended: their sessions and CPD points, for professional boards. */
    public function cpdExport(): StreamedResponse
    {
        $registrations = Registration::where('edition_id', $this->summit->edition()?->id)
            ->whereHas('attendances')
            ->with('user', 'attendances.session')
            ->orderBy('reference')
            ->get();

        return Csv::download('cpd-points-'.now()->format('Y-m-d').'.csv',
            ['Badge number', 'Title', 'Name', 'Email', 'Profession', 'Institution', 'Country', 'Sessions attended', 'CPD points'],
            $registrations->map(fn (Registration $r) => [
                $r->reference, $r->user->title, $r->user->first_name.' '.$r->user->last_name, $r->user->email,
                $r->user->profession, $r->user->institution, $r->user->countryName(),
                $r->attendances->count(), round($r->attendances->sum(fn (SessionAttendance $a) => $a->session->points()), 2),
            ]));
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
        $data = $this->validated($request);

        // A chair or rapporteur typed in by hand is no longer the person the committee accepted.
        foreach (['chair' => 'chair_user_id', 'rapporteur' => 'rapporteur_user_id'] as $name => $user) {
            if (($data[$name] ?? null) !== $session->{$name}) {
                $data[$user] = null;
            }
        }

        $session->update($data);

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

    /** Add an accepted abstract to the session. */
    public function schedule(Request $request, ProgrammeSession $session, ProgrammeService $programme): RedirectResponse
    {
        $this->ensureCurrent($session);
        $data = $request->validate(['abstract_id' => ['required', 'integer']]);

        try {
            $programme->schedule($session, AbstractSubmission::findOrFail($data['abstract_id']));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['abstract_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Presentation added to '.$session->title.'.');
    }

    public function unschedule(ProgrammeSession $session, AbstractSubmission $abstract, ProgrammeService $programme): RedirectResponse
    {
        $this->ensureCurrent($session);
        $programme->unschedule($session, $abstract);

        return back()->with('status', ($abstract->code ?: 'The abstract').' was taken out of '.$session->title.'.');
    }

    public function move(Request $request, ProgrammeSession $session, AbstractSubmission $abstract, ProgrammeService $programme): RedirectResponse
    {
        $this->ensureCurrent($session);
        $programme->move($session, $abstract, $request->input('direction') === 'up' ? -1 : 1);

        return back();
    }

    private function form(ProgrammeSession $session): View
    {
        return view('scientific.programme.form', [
            'session' => $session,
            'kinds' => ProgrammeSession::KINDS,
            'topics' => $this->summit->edition()->topics()->orderBy('name')->pluck('name', 'id')->all(),
            'presentations' => $session->exists ? $session->abstracts()->with('topic', 'authors', 'presentationFile')->get() : collect(),
            // Accepted abstracts not yet in any session, those in this session's topic first.
            'candidates' => $session->exists
                ? $this->unscheduled($session->edition_id)->with('topic', 'authors')->get()
                    ->sortBy(fn (AbstractSubmission $a) => [$a->topic_id === $session->topic_id ? 0 : 1, $a->code])->values()
                : collect(),
        ]);
    }

    private function unscheduled(int $editionId)
    {
        return AbstractSubmission::where('edition_id', $editionId)
            ->where('status', AbstractStatus::Accepted)
            ->whereDoesntHave('sessions');
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
            'rapporteur' => ['nullable', 'string', 'max:255'],
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
