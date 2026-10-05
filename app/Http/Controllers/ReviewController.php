<?php

namespace App\Http\Controllers;

use App\Enums\Recommendation;
use App\Models\ReviewAssignment;
use App\Services\AbstractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        return view('reviews.edit', [
            'assignment' => $assignment->load('abstract.topic', 'abstract.edition'),
            'recommendations' => Recommendation::cases(),
        ]);
    }

    public function update(Request $request, ReviewAssignment $assignment, AbstractService $service): RedirectResponse
    {
        $this->authorizeReviewer($request, $assignment);

        $score = ['required', 'integer', 'between:1,5'];
        $data = $request->validate([
            'score_relevance' => $score,
            'score_originality' => $score,
            'score_methods' => $score,
            'score_clarity' => $score,
            'recommendation' => ['required', Rule::enum(Recommendation::class)],
            'comments_for_author' => ['required', 'string', 'min:20', 'max:3000'],
            'comments_for_committee' => ['nullable', 'string', 'max:3000'],
        ], [
            'comments_for_author.min' => 'Please give the author at least a sentence or two of feedback.',
        ]);

        $service->review($assignment, $data);

        return redirect()->route('reviews.index')->with('status', 'Review saved for '.$assignment->abstract->blindId().'. Thank you.');
    }

    private function authorizeReviewer(Request $request, ReviewAssignment $assignment): void
    {
        abort_unless($assignment->reviewer_id === $request->user()->id, 404);
    }
}
