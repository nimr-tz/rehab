<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesRapporteurReportForm;
use App\Models\RapporteurReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RapporteurReportController extends Controller
{
    use HandlesRapporteurReportForm;

    public function index()
    {
        $reports = RapporteurReport::where('user_id', Auth::id())
            ->with('session')
            ->latest()
            ->get();

        return view('rapporteur-reports.index', compact('reports'));
    }

    public function create()
    {
        [$sessions, $users] = $this->formData();
        return view('rapporteur-reports.create', compact('sessions', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateReport($request);
        $validated['user_id'] = Auth::id();

        $isSubmit = $request->input('action') === 'submit';
        $validated['status'] = $isSubmit ? 'submitted' : 'draft';
        if ($isSubmit) {
            $validated['submitted_at'] = now();
        }

        $report = RapporteurReport::create($validated);

        $message = $isSubmit
            ? 'Report submitted successfully.'
            : 'Draft saved successfully.';

        return redirect()->route('rapporteur-reports.show', $report)->with('success', $message);
    }

    public function show(RapporteurReport $rapporteurReport)
    {
        $this->authorizeReport($rapporteurReport);
        $rapporteurReport->load(['session', 'rapporteur2']);
        return view('rapporteur-reports.show', ['report' => $rapporteurReport]);
    }

    /**
     * Download the author's own report. ?format=pdf (default), word, or html.
     */
    public function download(Request $request, RapporteurReport $rapporteurReport)
    {
        $this->authorizeReport($rapporteurReport);
        $rapporteurReport->load(['session', 'user', 'rapporteur2']);

        $view = view('chief-rapporteur.single', ['report' => $rapporteurReport]);
        $slug = \Illuminate\Support\Str::slug($rapporteurReport->session->name ?? 'session-report') ?: 'session-report';
        $basename = config('conference.file_prefix') . '-' . $slug;
        $format = $request->query('format', 'pdf');

        if ($format === 'html') {
            return $view;
        }

        if ($format === 'word') {
            return response($view->render(), 200, [
                'Content-Type'        => 'application/msword',
                'Content-Disposition' => 'attachment; filename="' . $basename . '.doc"',
            ]);
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($view->render())
            ->setPaper('a4')
            ->download($basename . '.pdf');
    }

    public function edit(RapporteurReport $rapporteurReport)
    {
        $this->authorizeReport($rapporteurReport);

        if (! $rapporteurReport->isEditableByAuthor()) {
            return redirect()->route('rapporteur-reports.show', $rapporteurReport)
                ->with('error', 'This report is locked for review and cannot be edited.');
        }

        [$sessions, $users] = $this->formData();

        return view('rapporteur-reports.create', [
            'sessions' => $sessions,
            'users'    => $users,
            'report'   => $rapporteurReport,
        ]);
    }

    public function update(Request $request, RapporteurReport $rapporteurReport)
    {
        $this->authorizeReport($rapporteurReport);

        if (! $rapporteurReport->isEditableByAuthor()) {
            return redirect()->route('rapporteur-reports.show', $rapporteurReport)
                ->with('error', 'This report is locked for review and cannot be edited.');
        }

        $wasReturned = $rapporteurReport->needsRevision();
        $validated = $this->validateReport($request);

        $isSubmit = $request->input('action') === 'submit';
        if ($isSubmit) {
            // A revised report returns to the chief's review queue rather than going straight to "submitted".
            $validated['status'] = RapporteurReport::STATUS_SUBMITTED;
            $validated['submitted_at'] = now();
            if ($wasReturned) {
                $rapporteurReport->recordHistory('resubmitted', 'Author resubmitted after revision.');
                $validated['review_history'] = $rapporteurReport->review_history;
            }
        } else {
            $validated['status'] = RapporteurReport::STATUS_DRAFT;
        }

        $rapporteurReport->update($validated);

        $message = $isSubmit
            ? 'Report submitted successfully.'
            : 'Draft saved successfully.';

        return redirect()->route('rapporteur-reports.show', $rapporteurReport)->with('success', $message);
    }

    private function formData(): array
    {
        return $this->rapporteurReportFormData();
    }

    private function authorizeReport(RapporteurReport $report): void
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function validateReport(Request $request): array
    {
        return $request->validate($this->rapporteurReportRules());
    }
}
