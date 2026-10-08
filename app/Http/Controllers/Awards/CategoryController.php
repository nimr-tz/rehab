<?php

namespace App\Http\Controllers\Awards;

use App\Enums\AwardKind;
use App\Enums\PresentationType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AwardCategory;
use App\Models\User;
use App\Services\AwardService;
use App\Support\AwardRubric;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

/** The committee sets up the summit's awards and assigns their judges. */
class CategoryController extends Controller
{
    public function index(Summit $summit): View
    {
        $edition = $summit->edition();
        $categories = $edition
            ? $edition->awardCategories()->with('judges', 'entries.scores', 'entries.abstract.authors')->get()
            : collect();

        return view('committee.awards.index', [
            'edition' => $edition,
            'categories' => $categories,
            'judgeCount' => User::role(Role::Judge->value)->count(),
        ]);
    }

    public function create(Summit $summit): View|RedirectResponse
    {
        if (! $summit->edition()) {
            return redirect()->route('committee.awards.index');
        }

        return view('committee.awards.form', ['category' => new AwardCategory(['places' => 1, 'kind' => AwardKind::Presentation])]);
    }

    public function store(Request $request, Summit $summit): RedirectResponse
    {
        $edition = $summit->edition();
        abort_unless($edition, 404);

        $category = $edition->awardCategories()->create($this->validated($request) + [
            'sort' => (int) $edition->awardCategories()->max('sort') + 10,
        ]);

        return redirect()->route('committee.awards.show', $category)->with('status', 'Award created.');
    }

    public function suggested(Summit $summit, AwardService $awards): RedirectResponse
    {
        $edition = $summit->edition();
        abort_unless($edition, 404);

        $added = $awards->addSuggested($edition);

        return redirect()->route('committee.awards.index')->with('status', $added
            ? 'Added '.$added.' suggested '.str('award')->plural($added).'. Adjust the names, places and prizes as you need.'
            : 'This summit already has every suggested award.');
    }

    public function show(AwardCategory $category, AwardService $awards): View
    {
        $category->load('judges', 'edition');

        return view('committee.awards.show', [
            'category' => $category,
            'standings' => $awards->standings($category),
            'eligible' => $category->isAnnounced() ? collect() : $awards->eligibleAbstracts($category),
            'judges' => User::role(Role::Judge->value)->orderBy('last_name')->get(),
            'progress' => $category->scoringProgress(),
            'max' => AwardRubric::max(),
        ]);
    }

    public function edit(AwardCategory $category): View
    {
        return view('committee.awards.form', ['category' => $category]);
    }

    public function update(Request $request, AwardCategory $category): RedirectResponse
    {
        $data = $this->validated($request, $category);

        if ($category->entries()->exists() && $data['kind'] !== $category->kind) {
            throw ValidationException::withMessages(['kind' => 'This award already has entries, so its kind cannot change.']);
        }
        if ($category->entries()->where('place', '>', $data['places'])->exists()) {
            throw ValidationException::withMessages(['places' => 'A place above '.$data['places'].' has been given. Change the winners first.']);
        }

        $category->update($data);

        return redirect()->route('committee.awards.show', $category)->with('status', 'Award details saved.');
    }

    public function destroy(AwardCategory $category): RedirectResponse
    {
        if ($category->isAnnounced()) {
            return back()->withErrors(['category' => 'Withdraw the announcement before deleting this award.']);
        }

        $category->delete();

        return redirect()->route('committee.awards.index')->with('status', 'The '.$category->name.' has been deleted.');
    }

    public function judges(Request $request, AwardCategory $category, AwardService $awards): RedirectResponse
    {
        $data = $request->validate(['judges' => ['array'], 'judges.*' => ['integer']]);

        try {
            $awards->syncJudges($category, $data['judges'] ?? []);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['judges' => $e->getMessage()]);
        }

        return back()->with('status', 'Judges saved.');
    }

    private function validated(Request $request, ?AwardCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'kind' => ['required', Rule::enum(AwardKind::class)],
            'presentation_type' => ['nullable', Rule::in(array_column(PresentationType::decisions(), 'value'))],
            'students_only' => ['boolean'],
            'places' => ['required', 'integer', 'between:1,3'],
            'prize' => ['nullable', 'string', 'max:160'],
            'nominations_close_on' => ['nullable', 'date'],
        ]);

        $data['kind'] = AwardKind::from($data['kind']);

        // Each kind keeps only the settings that apply to it.
        if ($data['kind'] === AwardKind::Presentation) {
            $data['nominations_close_on'] = null;
            $data['presentation_type'] ??= null;
            $data['students_only'] = (bool) ($data['students_only'] ?? false);
        } else {
            $data['presentation_type'] = null;
            $data['students_only'] = false;
            $data['nominations_close_on'] ??= null;
        }

        return $data;
    }
}
