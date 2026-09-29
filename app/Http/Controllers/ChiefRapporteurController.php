<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesRapporteurReportForm;
use App\Models\ConferenceSession;
use App\Models\RapporteurReport;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChiefRapporteurController extends Controller
{
    use HandlesRapporteurReportForm;

    public function __construct(private EmailNotificationService $emailService)
    {
    }

    /**
     * Dashboard: every report across all rapporteurs, with filters and coverage tracking.
     */
    public function index(Request $request)
    {
        // Drafts are the rapporteur's private work-in-progress — the chief only ever
        // deals with submitted work, so they are excluded from the whole dashboard.
        $query = RapporteurReport::with(['session', 'user', 'rapporteur2', 'reviewer'])
            ->where('status', '!=', RapporteurReport::STATUS_DRAFT);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($subtheme = $request->query('subtheme')) {
            $query->where('subtheme', $subtheme);
        }
        if ($sessionId = $request->query('session')) {
            $query->where('conference_session_id', $sessionId);
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('session', function ($s) use ($search) {
                    $s->where('name', 'like', "%{$search}%");
                });
            });
        }

        $reports = $query->latest('updated_at')->get();

        if ($day = $request->query('day')) {
            $reports = $reports->filter(fn ($r) => in_array($day, $r->session->schedule_days ?? [], true))->values();
        }

        // Status counts across all submitted reports (independent of the active filter).
        $counts = RapporteurReport::where('status', '!=', RapporteurReport::STATUS_DRAFT)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusOrder = [
            RapporteurReport::STATUS_SUBMITTED,
            RapporteurReport::STATUS_UNDER_REVIEW,
            RapporteurReport::STATUS_NEEDS_REVISION,
            RapporteurReport::STATUS_APPROVED,
        ];

        // Coverage: which active scientific sessions still have no report at all.
        $reportableSessions = ConferenceSession::reportable()
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(schedule_days, '$[0]')) ASC")
            ->orderBy('start_time')
            ->get();

        $reportedSessionIds = RapporteurReport::where('status', '!=', RapporteurReport::STATUS_DRAFT)
            ->pluck('conference_session_id')->unique();
        $sessionsWithoutReport = $reportableSessions->whereNotIn('id', $reportedSessionIds)->values();

        // Rapporteur leaderboard: distinct submitted titles per person. The same title
        // submitted more than once by the same person counts once (people re-submit
        // duplicates). Credits both lead and co-rapporteur.
        $titlesByPerson = [];
        $peopleById = [];
        RapporteurReport::with(['session', 'user', 'rapporteur2'])
            ->where('status', '!=', RapporteurReport::STATUS_DRAFT)
            ->get()
            ->each(function ($report) use (&$titlesByPerson, &$peopleById) {
                $title = \Illuminate\Support\Str::of($report->session->name ?? 'unknown')->lower()->squish()->value();
                foreach (array_filter([$report->user, $report->rapporteur2]) as $person) {
                    $peopleById[$person->id] = $person;
                    $titlesByPerson[$person->id][$title] = true;
                }
            });
        $rapporteurStats = collect($titlesByPerson)
            ->map(fn ($titles, $id) => ['user' => $peopleById[$id], 'total' => count($titles)])
            ->sortBy(fn ($s) => $this->personName($s['user']))
            ->sortByDesc('total')
            ->values();

        // Duplicate detection: among submitted reports (drafts excluded), flag any
        // whose session title matches another — i.e. the same report filed more than
        // once. Grouped by normalised title, most-duplicated first.
        $duplicateGroups = RapporteurReport::with(['session', 'user'])
            ->where('status', '!=', RapporteurReport::STATUS_DRAFT)
            ->get()
            ->groupBy(fn ($r) => \Illuminate\Support\Str::of($r->session->name ?? 'unknown')->lower()->squish()->value())
            ->filter(fn ($g) => $g->count() > 1)
            ->sortByDesc(fn ($g) => $g->count())
            ->values();

        // Filter option lists.
        $subthemes = RapporteurReport::whereNotNull('subtheme')
            ->distinct()->orderBy('subtheme')->pluck('subtheme');
        $days = $reportableSessions->flatMap(fn ($s) => $s->schedule_days ?? [])->unique()->sort()->values();

        return view('chief-rapporteur.index', [
            'reports'               => $reports,
            'counts'                => $counts,
            'statusOrder'           => $statusOrder,
            'totalReportable'       => $reportableSessions->count(),
            'sessionsWithoutReport' => $sessionsWithoutReport,
            'subthemes'             => $subthemes,
            'days'                  => $days,
            'sessions'              => $reportableSessions,
            'rapporteurStats'       => $rapporteurStats,
            'duplicateGroups'       => $duplicateGroups,
            'filters'               => $request->only(['status', 'subtheme', 'session', 'day', 'q']),
        ]);
    }

    public function show(RapporteurReport $rapporteurReport)
    {
        $rapporteurReport->load(['session', 'user', 'rapporteur2', 'reviewer']);

        return view('chief-rapporteur.show', ['report' => $rapporteurReport]);
    }

    /**
     * Edit-in-place: the chief opens the rapporteur's form and adjusts wording directly.
     */
    public function edit(RapporteurReport $rapporteurReport)
    {
        [$sessions, $users] = $this->rapporteurReportFormData();

        return view('rapporteur-reports.create', [
            'sessions' => $sessions,
            'users'    => $users,
            'report'   => $rapporteurReport,
            'isChief'  => true,
        ]);
    }

    public function update(Request $request, RapporteurReport $rapporteurReport)
    {
        $validated = $request->validate($this->rapporteurReportRules());

        $changed = $this->changedFields($rapporteurReport, $validated);

        // Editing an as-yet-unreviewed report moves it into the review queue.
        if (in_array($rapporteurReport->status, [RapporteurReport::STATUS_SUBMITTED], true)) {
            $validated['status'] = RapporteurReport::STATUS_UNDER_REVIEW;
        }

        $rapporteurReport->fill($validated);
        $rapporteurReport->recordHistory(
            'edited',
            $changed ? 'Chief edited: ' . implode(', ', $changed) : 'Chief opened and saved without field changes.',
            ['fields' => $changed]
        );
        $rapporteurReport->save();

        return redirect()->route('chief-rapporteur.show', $rapporteurReport)
            ->with('success', 'Your changes were saved to the report.');
    }

    public function approve(Request $request, RapporteurReport $rapporteurReport)
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        $rapporteurReport->status = RapporteurReport::STATUS_APPROVED;
        $rapporteurReport->reviewed_by = Auth::id();
        $rapporteurReport->reviewed_at = now();
        $rapporteurReport->approved_at = now();
        $rapporteurReport->recordHistory('approved', $data['note'] ?? null);
        $rapporteurReport->save();

        $this->safelyNotify(fn () => $this->emailService->sendRapporteurReportApproved($rapporteurReport, $data['note'] ?? null));

        return redirect()->route('chief-rapporteur.show', $rapporteurReport)
            ->with('success', 'Report approved.');
    }

    public function returnForRevision(Request $request, RapporteurReport $rapporteurReport)
    {
        $data = $request->validate([
            'feedback' => 'required|string|max:5000',
        ]);

        $rapporteurReport->status = RapporteurReport::STATUS_NEEDS_REVISION;
        $rapporteurReport->reviewed_by = Auth::id();
        $rapporteurReport->reviewed_at = now();
        $rapporteurReport->chief_feedback = $data['feedback'];
        $rapporteurReport->recordHistory('returned_for_revision', $data['feedback']);
        $rapporteurReport->save();

        $this->safelyNotify(fn () => $this->emailService->sendRapporteurReportReturned($rapporteurReport, $data['feedback']));

        return redirect()->route('chief-rapporteur.show', $rapporteurReport)
            ->with('success', 'Report returned to the rapporteur for revision.');
    }

    /**
     * Compile all approved reports into a single conference report.
     * Supports ?format=pdf (default), word (editable .doc), or html (preview in browser).
     */
    public function compiled(Request $request)
    {
        $reports = RapporteurReport::with(['session', 'user', 'rapporteur2'])
            ->where('status', RapporteurReport::STATUS_APPROVED)
            ->get()
            ->sortBy([
                [fn ($r) => $r->subtheme ?? 'zzz', 'asc'],
                [fn ($r) => $r->session->schedule_days[0] ?? 'zzz', 'asc'],
                [fn ($r) => optional($r->session->start_time)->format('H:i') ?? '', 'asc'],
            ])
            ->values();

        $view = view('chief-rapporteur.compiled', [
            'grouped'     => $reports->groupBy(fn ($r) => $r->subtheme ?: 'Unassigned Subtheme'),
            'total'       => $reports->count(),
            'generatedAt' => now(),
        ]);

        return $this->renderDocument($view, $request->query('format', 'pdf'), config('conference.file_prefix') . '-Consolidated-Rapporteur-Report');
    }

    /**
     * Download a single session report. ?format=pdf (default), word, or html.
     */
    public function download(Request $request, RapporteurReport $rapporteurReport)
    {
        $rapporteurReport->load(['session', 'user', 'rapporteur2']);

        $view = view('chief-rapporteur.single', ['report' => $rapporteurReport]);

        $slug = \Illuminate\Support\Str::slug($rapporteurReport->session->name ?? 'session-report') ?: 'session-report';

        return $this->renderDocument($view, $request->query('format', 'pdf'), config('conference.file_prefix') . '-' . $slug);
    }

    /**
     * Export reports as a spreadsheet (CSV, opens in Excel).
     * ?type=reports (one row per report, default) or recommendations (one row per recommendation).
     */
    public function export(Request $request)
    {
        $type = $request->query('type') === 'recommendations' ? 'recommendations' : 'reports';

        $reports = RapporteurReport::with(['session', 'user', 'rapporteur2'])
            ->orderBy('subtheme')
            ->get();

        $filename = config('conference.file_prefix') . '-rapporteur-' . $type . '-' . now()->format('Y-m-d') . '.csv';

        $callback = function () use ($reports, $type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents correctly

            if ($type === 'recommendations') {
                fputcsv($out, ['Session', 'Subtheme', 'Day(s)', 'Rapporteur', 'Status', 'Recommendation', 'Basis', 'Priority', 'Timeline', 'Target Audience']);
                foreach ($reports as $r) {
                    foreach (($r->recommendations ?? []) as $rec) {
                        if (empty($rec['recommendation'])) {
                            continue;
                        }
                        fputcsv($out, [
                            $r->session->name ?? '',
                            $r->subtheme ?? '',
                            implode(', ', $r->session->schedule_days ?? []),
                            $this->personName($r->user),
                            $r->statusLabel(),
                            $rec['recommendation'] ?? '',
                            $rec['basis'] ?? '',
                            $rec['priority'] ?? '',
                            $rec['timeline'] ?? '',
                            implode('; ', $rec['target_audience'] ?? []),
                        ]);
                    }
                }
            } else {
                fputcsv($out, ['Session', 'Subtheme', 'Day(s)', 'Time', 'Rapporteur', 'Co-Rapporteur', 'Status', '# Presentations', '# Recommendations', 'Most Important Finding', 'Submitted', 'Approved']);
                foreach ($reports as $r) {
                    fputcsv($out, [
                        $r->session->name ?? '',
                        $r->subtheme ?? '',
                        implode(', ', $r->session->schedule_days ?? []),
                        $r->session?->start_time ? $r->session->start_time->format('H:i') . '–' . optional($r->session->end_time)->format('H:i') : '',
                        $this->personName($r->user),
                        $r->rapporteur2 ? $this->personName($r->rapporteur2) : '',
                        $r->statusLabel(),
                        collect($r->presentations ?? [])->filter(fn ($p) => !empty($p['presenter']) || !empty($p['title']))->count(),
                        collect($r->recommendations ?? [])->filter(fn ($rec) => !empty($rec['recommendation']))->count(),
                        $r->most_important_finding ?? '',
                        optional($r->submitted_at)->format('Y-m-d H:i'),
                        optional($r->approved_at)->format('Y-m-d H:i'),
                    ]);
                }
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Render a Blade document view as PDF, editable Word (.doc), or raw HTML preview.
     */
    private function renderDocument($view, string $format, string $basename)
    {
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

    private function personName($user): string
    {
        if (! $user) {
            return '';
        }

        return trim(($user->title ?? '') . ' ' . ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->email ?? '');
    }

    /**
     * Compare incoming form values with the stored report and list the human-readable
     * field names that changed (used for the review-history audit trail).
     */
    private function changedFields(RapporteurReport $report, array $validated): array
    {
        $labels = [
            'subtheme'               => 'Subtheme',
            'rapporteur2_user_id'    => 'Rapporteur 2',
            'presentations'          => 'Presentations',
            'discussion_questions'   => 'Discussion',
            'areas_of_agreement'     => 'Areas of agreement',
            'areas_of_debate'        => 'Areas of debate',
            'follow_up_issues'       => 'Follow-up issues',
            'scientific_message_1'   => 'Scientific message 1',
            'scientific_message_2'   => 'Scientific message 2',
            'scientific_message_3'   => 'Scientific message 3',
            'most_important_finding' => 'Most important finding',
            'evidence_nature'        => 'Nature of evidence',
            'evidence_status'        => 'Status of evidence',
            'important_method'       => 'Important method',
            'main_limitation'        => 'Main limitation',
            'recommendations'        => 'Recommendations',
        ];

        $changed = [];
        foreach ($labels as $field => $label) {
            $new = $validated[$field] ?? null;
            $old = $report->getAttribute($field);
            // Normalise arrays/scalars for a stable comparison.
            if (json_encode($new) !== json_encode($old)) {
                $changed[] = $label;
            }
        }

        return $changed;
    }

    private function safelyNotify(callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning('Chief rapporteur notification failed: ' . $e->getMessage());
        }
    }
}
