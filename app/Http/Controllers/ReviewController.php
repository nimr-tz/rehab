<?php

namespace App\Http\Controllers;

use App\Enums\Recommendation;
use App\Models\ReviewAssignment;
use App\Services\AbstractService;
use App\Support\Rubric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/** Double-blind: nothing here reveals the authors, their emails or affiliations. */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = $request->user()->reviewAssignments()
            ->with('abstract.topic', 'abstract.edition')
            ->orderByRaw('completed_at is not null')
            ->orderBy('due_on')
            ->get();

        return view('reviews.index', [
            'pending' => $assignments->reject->isComplete(),
            'completed' => $assignments->filter->isComplete(),
        ]);
    }

    public function edit(Request $request, ReviewAssignment $assignment): View
    {
        $this->authorizeReviewer($request, $assignment);

        $assignment->load('abstract.topic', 'abstract.edition', 'abstract.reviews');
        $queue = $request->user()->reviewAssignments()->orderBy('due_on')->orderBy('id')->get(['id', 'completed_at']);

        return view('reviews.edit', [
            'assignment' => $assignment,
            'recommendations' => Recommendation::offered($assignment->round),
            // In round 2, the reviewer's own first review, to compare against.
            'earlier' => $assignment->round === 2
                ? $assignment->abstract->roundReviews(1)->firstWhere('reviewer_id', $assignment->reviewer_id)
                : null,
            'queue' => [
                'done' => $queue->filter->isComplete()->count(),
                'total' => $queue->count(),
                'next' => $queue->reject->isComplete()->firstWhere('id', '!=', $assignment->id),
            ],
        ]);
    }

    public function update(Request $request, ReviewAssignment $assignment, AbstractService $service): RedirectResponse
    {
        $this->authorizeReviewer($request, $assignment);

        $scores = collect(Rubric::criteria())->mapWithKeys(fn (array $criterion, string $field) => [
            $field => ['required', 'integer', 'between:0,'.$criterion['max']],
        ]);
        $data = $request->validate($scores->all() + [
            'technical_checks' => ['nullable', 'array'],
            'technical_checks.*' => [Rule::in(array_keys(Rubric::checks()))],
            'recommendation' => ['required', Rule::in(array_column(Recommendation::offered($assignment->round), 'value'))],
            'comments_for_author' => ['required', 'string', 'min:20', 'max:3000'],
            'comments_for_committee' => ['nullable', 'string', 'max:3000'],
        ], [
            'score_*.required' => 'Give this criterion a score.',
            'score_*.between' => 'Use the scale from 0 to :max.',
            'comments_for_author.min' => 'Please give the author at least a sentence or two of feedback.',
        ]);

        try {
            $service->review($assignment, $data);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('reviews.edit', $assignment)->withErrors(['recommendation' => $e->getMessage()]);
        }

        $saved = 'Review saved for '.$assignment->abstract->blindId().'. Thank you.';
        $next = $request->user()->reviewAssignments()->whereNull('completed_at')->orderBy('due_on')->orderBy('id')->first();

        return $next
            ? redirect()->route('reviews.edit', $next)->with('status', $saved.' Here is your next abstract.')
            : redirect()->route('reviews.index')->with('status', $saved);
    }

    private function authorizeReviewer(Request $request, ReviewAssignment $assignment): void
    {
        abort_unless($assignment->reviewer_id === $request->user()->id, 404);
    }
}
