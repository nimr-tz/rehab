<?php

namespace App\Http\Controllers;

use App\Enums\SessionRole;
use App\Models\ProgrammeSession;
use App\Models\SessionRoleApplication;
use App\Services\SessionRoleService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/** A participant applies to chair or report a session, and does the job once given one. */
class SessionRoleController extends Controller
{
    public function __construct(private Summit $summit) {}

    public function index(Request $request): View
    {
        $edition = $this->summit->edition();
        $applications = $edition
            ? SessionRoleApplication::where('edition_id', $edition->id)->where('user_id', $request->user()->id)
                ->with('session.abstracts.presentationFile', 'session.abstracts.authors')->get()->keyBy(fn ($a) => $a->role->value)
            : collect();

        return view('session-roles.index', [
            'edition' => $edition,
            'open' => $edition?->acceptsSessionRoleApplications() ?? false,
            'roles' => SessionRole::cases(),
            'applications' => $applications,
            'topics' => $edition?->topics ?? collect(),
        ]);
    }

    public function store(Request $request, SessionRoleService $roles): RedirectResponse
    {
        $edition = $this->summit->edition();
        abort_unless($edition, 404);

        $data = $request->validate([
            'role' => ['required', Rule::enum(SessionRole::class)],
            'topics' => ['nullable', 'array'],
            'topics.*' => [Rule::exists('topics', 'id')->where('edition_id', $edition->id)],
            'experience' => ['required', 'string', 'min:30', 'max:2000'],
        ], [
            'experience.required' => 'Tell the committee about your experience.',
            'experience.min' => 'Tell the committee a little more about your experience, in a few sentences.',
        ]);

        try {
            $roles->apply($request->user(), $edition, SessionRole::from($data['role']), $data['topics'] ?? [], $data['experience']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['experience' => $e->getMessage()]);
        }

        return redirect()->route('session-roles.index')->with('status', 'Thank you. The scientific committee will let you know.');
    }

    public function destroy(Request $request, SessionRoleApplication $application, SessionRoleService $roles): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 404);

        try {
            $roles->withdraw($application);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['experience' => $e->getMessage()]);
        }

        return redirect()->route('session-roles.index')->with('status', 'Your application was withdrawn.');
    }

    /** The rapporteur's report of their session. */
    public function report(Request $request, ProgrammeSession $session, SessionRoleService $roles): RedirectResponse
    {
        $data = $request->validate(['report' => ['required', 'string', 'min:50', 'max:6000']], [
            'report.min' => 'The report should be at least a short paragraph.',
        ]);

        try {
            $roles->report($session, $request->user(), $data['report']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['report' => $e->getMessage()]);
        }

        return back()->with('status', 'Thank you. Your report is published with the programme.');
    }
}
