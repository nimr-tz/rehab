<?php

namespace App\Http\Controllers\Scientific;

use App\Enums\AbstractStatus;
use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\ReviewAssignment;
use App\Models\User;
use App\Services\AbstractService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class AbstractController extends Controller
{
    public function index(Request $request, Summit $summit): View
    {
        $edition = $summit->edition();
        $status = $request->query('status');
        $topic = $request->query('topic');
        $search = trim((string) $request->query('q'));

        $query = AbstractSubmission::query()
            ->where('edition_id', $edition?->id)
            ->where('status', '!=', AbstractStatus::Draft)
            ->with(['topic', 'submitter', 'reviews'])
            ->when($status === 'ready', fn ($q) => $q->awaitingDecision())
            ->when($status && $status !== 'ready', fn ($q) => $q->where('status', $status))
            ->when($topic, fn ($q) => $q->where('topic_id', $topic))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhereHas('submitter', fn ($q) => $q->where('last_name', 'like', "%{$search}%"))));

        $counts = AbstractSubmission::where('edition_id', $edition?->id)
            ->where('status', '!=', AbstractStatus::Draft)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $counts['ready'] = AbstractSubmission::where('edition_id', $edition?->id)->awaitingDecision()->count();

        return view('scientific.abstracts.index', [
            'abstracts' => $query->latest('submitted_at')->paginate(15)->withQueryString(),
            'counts' => $counts,
            'topics' => $edition?->topics ?? collect(),
            'statuses' => array_filter(AbstractStatus::cases(), fn ($s) => $s !== AbstractStatus::Draft),
            'filters' => compact('status', 'topic', 'search'),
        ]);
    }

    public function show(AbstractSubmission $abstract, AbstractService $service): View
    {
        $abstract->load('topic', 'authors', 'submitter', 'edition', 'reviews.reviewer');
        $seats = $service->openSeats($abstract);

        return view('scientific.abstracts.show', [
            'abstract' => $abstract,
            'seats' => $seats,
            'eligible' => $seats > 0 ? $service->eligibleReviewers($abstract) : collect(),
        ]);
    }

    public function assign(Request $request, AbstractSubmission $abstract, AbstractService $service): RedirectResponse
    {
        $data = $request->validate([
            'reviewer_id' => ['required', 'exists:users,id'],
            'due_on' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $reviewer = User::findOrFail($data['reviewer_id']);

        try {
            $service->assign($abstract, $reviewer, $request->user(), $data['due_on'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['reviewer_id' => $e->getMessage()]);
        }

        return back()->with('status', $reviewer->name.' has been asked to review this abstract.');
    }

    public function unassign(AbstractSubmission $abstract, ReviewAssignment $assignment, AbstractService $service): RedirectResponse
    {
        abort_unless($assignment->abstract_id === $abstract->id, 404);

        try {
            $service->unassign($assignment);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['reviewer_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Reviewer removed.');
    }

    public function decide(Request $request, AbstractSubmission $abstract, AbstractService $service): RedirectResponse
    {
        $abstract->load('reviews', 'topic');
        $allowed = $abstract->status === AbstractStatus::RevisionRequested ? ['reject'] : array_keys($abstract->decisionOptions());

        $data = $request->validate([
            'decision' => ['required', Rule::in($allowed)],
            'decision_note' => ['nullable', 'string', 'max:2000'],
            'revision_due_on' => ['nullable', 'date', 'after:today'],
        ]);

        try {
            $service->decide($abstract, $data['decision'], $data['decision_note'] ?? null, $data['revision_due_on'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()->route('scientific.abstracts.show', $abstract)->with('status', $data['decision'] === 'revise'
            ? 'Revisions requested. The author has been notified.'
            : 'Decision recorded and the author has been notified.');
    }

    public function extendRevision(Request $request, AbstractSubmission $abstract, AbstractService $service): RedirectResponse
    {
        $data = $request->validate(['revision_due_on' => ['required', 'date', 'after:today']]);

        try {
            $service->extendRevision($abstract, $data['revision_due_on']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['revision_due_on' => $e->getMessage()]);
        }

        return back()->with('status', 'The author now has until '.$abstract->fresh()->revision_due_on->format('j F').'.');
    }
}
