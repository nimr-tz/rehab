<?php

namespace App\Http\Controllers\Scientific;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Services\PresenterService;
use App\Support\Csv;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Accepted abstracts and whether their presenters have registered and paid. */
class PresenterController extends Controller
{
    public function __construct(private Summit $summit, private PresenterService $presenters) {}

    public function index(Request $request): View
    {
        $rows = $this->rows();
        $state = array_key_exists($request->query('state'), PresenterService::STATES) ? $request->query('state') : null;

        return view('scientific.presenters', [
            'rows' => $state ? $rows->where('state', $state)->values() : $rows,
            'counts' => $rows->countBy('state'),
            'total' => $rows->count(),
            'state' => $state,
        ]);
    }

    public function remind(AbstractSubmission $abstract): RedirectResponse
    {
        $row = $this->rows()->first(fn ($row) => $row['abstract']->is($abstract));
        abort_unless($row, 404);

        return $this->presenters->remind($row)
            ? back()->with('status', $row['name'].' has been reminded.')
            : back()->withErrors(['presenters' => 'There is no email address for this presenter, or they are already confirmed.']);
    }

    /** Remind every presenter who is not yet confirmed. */
    public function remindAll(): RedirectResponse
    {
        $sent = $this->rows()->filter(fn ($row) => $row['state'] !== PresenterService::CONFIRMED)
            ->filter(fn ($row) => $this->presenters->remind($row))->count();

        return back()->with('status', $sent.' '.str('presenter')->plural($sent).' reminded.');
    }

    public function export(): StreamedResponse
    {
        return Csv::download('presenters-'.now()->format('Y-m-d').'.csv',
            ['Code', 'Title', 'Topic', 'Presenter', 'Email', 'Registration', 'Status', 'Session', 'Last reminded'],
            $this->rows()->map(fn ($row) => [
                $row['abstract']->code, $row['abstract']->title, $row['abstract']->topic->name,
                $row['name'], $row['email'], $row['registration']?->reference,
                PresenterService::STATES[$row['state']][0],
                $row['abstract']->sessions->first()?->title,
                $row['abstract']->presenter_reminded_at?->toDateTimeString(),
            ]));
    }

    private function rows()
    {
        $edition = $this->summit->edition();

        return $edition ? $this->presenters->rows($edition) : collect();
    }
}
