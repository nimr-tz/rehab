<?php

namespace App\Http\Controllers\Scientific;

use App\Enums\ApplicationStatus;
use App\Enums\SessionRole;
use App\Http\Controllers\Controller;
use App\Models\ProgrammeSession;
use App\Models\SessionRoleApplication;
use App\Services\SessionRoleService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/** The committee decides who chairs and reports each session. */
class SessionRoleController extends Controller
{
    public function __construct(private Summit $summit) {}

    public function index(Request $request): View
    {
        $edition = $this->summit->edition();
        $status = ApplicationStatus::tryFrom((string) $request->query('status')) ?? ApplicationStatus::Pending;
        $role = SessionRole::tryFrom((string) $request->query('role'));
        $base = fn () => SessionRoleApplication::where('edition_id', $edition?->id);

        $sessions = $edition?->sessions()->where('kind', '!=', 'break')->with('topic')->get() ?? collect();

        return view('scientific.session-roles.index', [
            'status' => $status,
            'role' => $role,
            'applications' => $base()->where('status', $status)->when($role, fn ($q) => $q->where('role', $role))
                ->with('user', 'session', 'decider')->oldest()->paginate(20)->withQueryString(),
            'counts' => $base()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'sessions' => $sessions,
            'topics' => $edition?->topics->keyBy('id') ?? collect(),
            'gaps' => [
                'chair' => $sessions->whereNull('chair')->count(),
                'rapporteur' => $sessions->whereNull('rapporteur')->count(),
            ],
            'deadline' => $edition?->session_role_deadline,
        ]);
    }

    public function accept(Request $request, SessionRoleApplication $application, SessionRoleService $roles): RedirectResponse
    {
        $this->ensureCurrent($application);

        $data = $request->validate([
            'session_id' => ['required', Rule::exists('programme_sessions', 'id')->where('edition_id', $application->edition_id)],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['session_id.required' => 'Choose the session.']);

        try {
            $roles->accept($application, ProgrammeSession::findOrFail($data['session_id']), $request->user(), $data['note'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['application_'.$application->id => $e->getMessage()]);
        }

        return back()->with('status', $application->user->name.' is now '.strtolower($application->role->label()).' of '.$application->fresh()->session->title.', and has been told.');
    }

    public function decline(Request $request, SessionRoleApplication $application, SessionRoleService $roles): RedirectResponse
    {
        $this->ensureCurrent($application);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:255']]);

        try {
            $roles->decline($application, $request->user(), $data['note'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['application_'.$application->id => $e->getMessage()]);
        }

        return back()->with('status', $application->user->name.' has been told.');
    }

    public function release(SessionRoleApplication $application, SessionRoleService $roles): RedirectResponse
    {
        $this->ensureCurrent($application);

        try {
            $roles->release($application);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['application_'.$application->id => $e->getMessage()]);
        }

        return back()->with('status', $application->user->name.' was taken off the session. The application is back under consideration.');
    }

    private function ensureCurrent(SessionRoleApplication $application): void
    {
        abort_unless($application->edition_id === $this->summit->edition()?->id, 404);
    }
}
