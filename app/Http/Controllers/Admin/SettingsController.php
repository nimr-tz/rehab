<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Services\EditionService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Summit $summit): View
    {
        $edition = $summit->edition() ?? new Edition([
            'year' => now()->year + 1,
            'name' => config('summit.name'),
            'short_name' => config('summit.short_name'),
            'ordinal' => config('summit.edition'),
            'country' => config('summit.country'),
        ]);

        return view('admin.settings', [
            'edition' => $edition,
            'yearLocked' => $edition->exists && $this->hasActivity($edition),
            'categories' => $edition->exists ? $edition->categories : collect(),
            'topics' => $edition->exists ? $edition->topics : collect(),
            'editions' => Edition::withCount('registrations', 'abstracts')->orderByDesc('year')->get(),
            'next' => [
                'year' => max($edition->year ?? now()->year, (int) Edition::max('year')) + 1,
                'name' => $edition->name,
                'short_name' => $edition->short_name,
                'ordinal' => EditionService::nextOrdinal($edition->ordinal),
            ],
        ]);
    }

    /** Start next year's summit and make it current. Last year's stays as it was. */
    public function startNext(Request $request, Summit $summit, EditionService $editions): RedirectResponse
    {
        $current = $summit->edition();
        abort_unless($current, 404);

        $data = $request->validate([
            'next_year' => ['required', 'integer', 'between:2020,2100', Rule::unique('editions', 'year')],
            'next_name' => ['required', 'string', 'max:120'],
            'next_short_name' => ['required', 'string', 'max:60'],
            'next_ordinal' => ['required', 'string', 'max:10'],
            'copy_categories' => ['boolean'],
            'copy_topics' => ['boolean'],
            'confirm_next' => ['accepted'],
        ], [
            'next_year.unique' => 'There is already a summit for that year.',
            'confirm_next.accepted' => 'Confirm that the portal should move to the new summit.',
        ]);

        $next = $editions->startNext($current, [
            'year' => (int) $data['next_year'],
            'name' => $data['next_name'],
            'short_name' => $data['next_short_name'],
            'ordinal' => $data['next_ordinal'],
        ], (bool) ($data['copy_categories'] ?? false), (bool) ($data['copy_topics'] ?? false));

        $summit->refresh();

        return redirect()->route('admin.settings.edit')
            ->with('status', 'The '.$next->year.' summit is now current. Set its dates and venue, then open registration when ready. The '.$current->year.' summit and its records are kept.');
    }

    /** Go back to another year's summit, for example after starting the next one too early. */
    public function makeCurrent(Edition $edition, Summit $summit, EditionService $editions): RedirectResponse
    {
        $editions->makeCurrent($edition);
        $summit->refresh();

        return redirect()->route('admin.settings.edit')->with('status', 'The portal now works on the '.$edition->year.' summit.');
    }

    /** Once people have registered or submitted, an edition's year is fixed: it is that year's summit. */
    private function hasActivity(Edition $edition): bool
    {
        return $edition->registrations()->exists() || $edition->abstracts()->exists();
    }

    public function update(Request $request, Summit $summit): RedirectResponse
    {
        $edition = $summit->edition();

        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2020,2100', Rule::unique('editions', 'year')->ignore($edition?->id)],
            'name' => ['required', 'string', 'max:120'],
            'short_name' => ['required', 'string', 'max:60'],
            'ordinal' => ['required', 'string', 'max:10'],
            'theme' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'registration_target' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'registration_open' => ['boolean'],
            'abstracts_open' => ['boolean'],
            'abstract_deadline' => ['nullable', 'date'],
            'review_deadline' => ['nullable', 'date'],
            'session_role_deadline' => ['nullable', 'date'],
            'presentation_deadline' => ['nullable', 'date'],
            'categories' => ['array'],
            'categories.*.id' => ['nullable', 'integer'],
            'categories.*.name' => ['required', 'string', 'max:120'],
            'categories.*.currency' => ['required', Rule::in(['TZS', 'USD'])],
            'categories.*.amount' => ['nullable', 'numeric', 'min:0'],
            'categories.*.is_student' => ['boolean'],
            'topics' => ['array'],
            'topics.*.id' => ['nullable', 'integer'],
            'topics.*.name' => ['required', 'string', 'max:160'],
            'topics.*.code' => ['required', 'string', 'max:6', 'alpha'],
        ], [
            'topics.*.code.alpha' => 'Topic codes use letters only, for example HBR.',
        ]);

        if ($edition && (int) $data['year'] !== $edition->year && $this->hasActivity($edition)) {
            return back()->withInput()->withErrors(['year' => 'People have already registered or submitted abstracts for '.$edition->year.', so its year cannot change. Start the next summit below instead.']);
        }

        $codes = collect($data['topics'] ?? [])->map(fn ($topic) => Str::upper($topic['code']));
        if ($codes->duplicates()->isNotEmpty()) {
            return back()->withInput()->withErrors(['topics' => 'Each topic needs its own code.']);
        }

        DB::transaction(function () use ($edition, $data) {
            $fields = collect($data)->except(['categories', 'topics'])->all();

            if (! $edition) {
                $edition = Edition::create($fields + ['is_current' => true]);
            } else {
                $edition->update($fields);
            }

            $this->syncRows($edition->categories(), $data['categories'] ?? [], fn ($row, $i) => [
                'name' => $row['name'],
                'currency' => $row['currency'],
                'amount' => $row['amount'] ?? null,
                'is_student' => (bool) ($row['is_student'] ?? false),
                'sort' => $i,
            ], 'registrations');

            $this->syncRows($edition->topics(), $data['topics'] ?? [], fn ($row, $i) => [
                'name' => $row['name'],
                'code' => Str::upper($row['code']),
                'sort' => $i,
            ], 'abstracts');
        });

        $summit->refresh();

        return redirect()->route('admin.settings.edit')->with('status', 'Summit settings saved.');
    }

    /**
     * Update rows that kept their id, create new ones, and delete removed ones
     * unless something already refers to them.
     */
    private function syncRows($relation, array $rows, callable $map, string $inUseBy): void
    {
        $keep = [];

        foreach (array_values($rows) as $i => $row) {
            $model = ! empty($row['id']) ? (clone $relation)->find($row['id']) : null;
            $model ? $model->update($map($row, $i)) : $model = (clone $relation)->create($map($row, $i));
            $keep[] = $model->id;
        }

        (clone $relation)->whereNotIn('id', $keep)->doesntHave($inUseBy)->delete();
    }
}
