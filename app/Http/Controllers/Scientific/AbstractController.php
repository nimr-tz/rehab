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
            ->when($status === 'ready', fn ($q) => $q->where('status', AbstractStatus::UnderReview)
                ->whereHas('reviews')->whereDoesntHave('reviews', fn ($q) => $q->whereNull('completed_at')))
            ->when($status && $status !== 'ready', fn ($q) => $q->where('status', $status))
            ->when($topic, fn ($q) => $q->where('topic_id', $topic))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhereHas('submitter', fn ($q) => $q->where('last_name', 'like', "%{$search}%"))));

        $counts = AbstractSubmission::where('edition_id', $edition?->id)
            ->where('status', '!=', AbstractStatus::Draft)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $counts['ready'] = AbstractSubmission::where('edition_id', $edition?->id)->where('status', AbstractStatus::UnderReview)
            ->whereHas('reviews')->whereDoesntHave('reviews', fn ($q) => $q->whereNull('completed_at'))->count();

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

        return view('scientific.abstracts.show', [
            'abstract' => $abstract,
            'eligible' => in_array($abstract->status, [AbstractStatus::Submitted, AbstractStatus::UnderReview], true)
                ? $service->eligibleReviewers($abstract)
                : collect(),
        ]);
    }

    public function assign(Request $request, AbstractSubmission $abstract, AbstractService $service): RedirectResponse
    {
        $data = $request->validate([
            'reviewer_id' => ['required', 'exists:users,id'],
            'due_on' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $reviewer = User::findOrFail($data['reviewer_id']);
        $service->assign($abstract, $reviewer, $request->user(), $data['due_on'] ?? null);

        return back()->with('status', $reviewer->name.' has been asked to review this abstract.');
    }

    public function unassign(AbstractSubmission $abstract, ReviewAssignment $assignment, AbstractService $service): RedirectResponse
    {
        abort_unless($assignment->abstract_id === $abstract->id, 404);

        $service->unassign($assignment);

        return back()->with('status', 'Reviewer removed.');
    }

    public function decide(Request $request, AbstractSubmission $abstract, AbstractService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['oral', 'poster', 'reject'])],
            'decision_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->decide($abstract, $data['decision'], $data['decision_note'] ?? null);

        return redirect()->route('scientific.abstracts.show', $abstract)
            ->with('status', 'Decision recorded and the author has been notified.');
    }
}
