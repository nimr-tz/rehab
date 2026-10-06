<?php

namespace App\Http\Controllers\Awards;

use App\Http\Controllers\Controller;
use App\Models\AwardEntry;
use App\Services\AwardService;
use App\Support\AwardRubric;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Judges score the finalists of the awards they are assigned to, usually in
 * the room while the finalist presents. Judges see who presents, unlike
 * abstract reviewers, because they watch the presentation.
 */
class JudgingController extends Controller
{
    public function index(Request $request, Summit $summit): View
    {
        $judge = $request->user();

        $categories = $judge->judgedAwards()
            ->where('edition_id', $summit->edition()?->id)
            ->ordered()
            ->with(['entries.abstract.topic', 'entries.abstract.authors', 'entries.abstract.sessions', 'entries.scores'])
            ->get();

        return view('judging.index', [
            'judge' => $judge,
            'categories' => $categories,
        ]);
    }

    public function edit(Request $request, AwardEntry $entry): View
    {
        $judge = $request->user();
        $category = $entry->category;
        abort_unless($category->judges()->whereKey($judge->id)->exists(), 404);
        abort_if($entry->conflictsWith($judge), 403, 'You wrote or co-wrote this abstract, so another judge scores it.');

        $entry->load('abstract.topic', 'abstract.authors', 'abstract.sessions', 'abstract.edition');
        $siblings = $category->entries()->with('abstract.authors', 'scores')->orderBy('id')->get()
            ->reject(fn (AwardEntry $other) => $other->conflictsWith($judge))->values();
        $position = $siblings->search(fn (AwardEntry $other) => $other->is($entry));

        return view('judging.edit', [
            'entry' => $entry,
            'category' => $category,
            'score' => $entry->scores()->where('judge_id', $judge->id)->first(),
            'criteria' => AwardRubric::criteria(),
            'perCriterion' => AwardRubric::perCriterion(),
            'max' => AwardRubric::max(),
            'previous' => $position > 0 ? $siblings[$position - 1] : null,
            'next' => $siblings[$position + 1] ?? null,
            'position' => $position + 1,
            'count' => $siblings->count(),
        ]);
    }

    public function update(Request $request, AwardEntry $entry, AwardService $awards): RedirectResponse
    {
        $judge = $request->user();
        $category = $entry->category;
        abort_unless($category->judges()->whereKey($judge->id)->exists(), 404);

        $rules = ['comments' => ['nullable', 'string', 'max:2000']];
        foreach (array_keys(AwardRubric::criteria()) as $criterion) {
            $rules["scores.{$criterion}"] = ['required', 'integer', 'between:1,'.AwardRubric::perCriterion()];
        }
        $data = $request->validate($rules, ['scores.*.required' => 'Score every criterion.']);

        try {
            $awards->score($entry, $judge, $data['scores'], $data['comments'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['scores' => $e->getMessage()]);
        }

        // On to the next finalist this judge has not scored yet.
        $next = $category->entries()->with('abstract.authors')->orderBy('id')->get()
            ->first(fn (AwardEntry $other) => ! $other->conflictsWith($judge)
                && ! $other->scores()->where('judge_id', $judge->id)->exists());

        return $next
            ? redirect()->route('judging.edit', $next)->with('status', 'Scores saved for '.$entry->name.'. Next: '.$next->name.'.')
            : redirect()->route('judging.index')->with('status', 'Scores saved. You have scored every finalist for the '.$category->name.'.');
    }
}
