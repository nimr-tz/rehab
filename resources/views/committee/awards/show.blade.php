@php
    [, $stageLabel, $stageTone] = $category->stage();
    $announced = $category->isAnnounced();
    $placeOptions = collect(range(1, $category->places))->mapWithKeys(fn ($p) => [$p => $category->placeLabel($p)])->all();
    $hasPlaces = $standings->whereNotNull('place')->isNotEmpty();
    // How many people nominated each nominee, matched by name.
    $nominationCounts = $standings->countBy(fn ($entry) => Str::lower(trim($entry->name)));
@endphp

<x-layouts.portal :title="$category->name">
    <x-slot:header>
        <x-page-header eyebrow="Awards" :title="$category->name" :back="route('committee.awards.index')"
            :description="$category->eligibility().' · '.$category->places.' '.Str::plural('place', $category->places)">
            <x-status :tone="$stageTone">{{ $stageLabel }}</x-status>
            <x-button variant="secondary" size="sm" icon="pencil" :href="route('committee.awards.edit', $category)">Edit</x-button>
        </x-page-header>
    </x-slot:header>

    @foreach (['entries', 'places', 'judges'] as $field)
        @error($field) <x-alert tone="danger">{{ $message }}</x-alert> @enderror
    @endforeach

    @if ($announced)
        <x-alert tone="success">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span>Announced on {{ $category->announced_at->format('j F Y \a\t H:i') }}. The winners are on the <a href="{{ route('awards.index') }}#award-{{ $category->id }}" class="font-semibold underline">public awards page</a> and have been emailed.</span>
                <form method="POST" action="{{ route('committee.awards.withdraw', $category) }}"
                      x-data x-on:submit="if (! confirm('Withdraw the announcement? The winners disappear from the public page until you announce again.')) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <x-button variant="secondary" size="sm">Withdraw announcement</x-button>
                </form>
            </div>
        </x-alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="min-w-0 space-y-6">
            {{-- Finalists or nominees, with the committee's choice of places --}}
            <x-card :title="$category->isPresentation() ? 'Finalists and scores' : ($category->nominations_close_on ? 'Nominations' : 'Recipients')"
                    :description="$category->isPresentation() ? 'Ranked by the average of the judges\' scores, out of '.$max.'. Choose the winners, then announce.' : 'Choose who receives the award, then announce.'"
                    :padding="false">
                @if ($standings->isEmpty())
                    <x-empty icon="trophy" :title="$category->isPresentation() ? 'No finalists yet' : ($category->acceptsNominations() ? 'No nominations yet' : 'No recipient yet')" class="!py-10">
                        @if ($category->isPresentation())
                            Shortlist accepted abstracts below. Judges then score them at the summit.
                        @elseif ($category->acceptsNominations())
                            Participants can nominate people until {{ $category->nominations_close_on->format('j F Y') }}. You can also add a recipient yourself.
                        @else
                            Add the recipient below, with the citation to read at the ceremony.
                        @endif
                    </x-empty>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                                <tr>
                                    @if ($category->isPresentation())
                                        <th class="w-12 px-5 py-3">Rank</th><th class="px-5 py-3">Finalist</th><th class="px-5 py-3">Judges</th><th class="px-5 py-3">Average</th>
                                    @else
                                        <th class="px-5 py-3">Nominee</th><th class="px-5 py-3">Nominated by</th>
                                    @endif
                                    <th class="w-44 px-5 py-3">Place</th>
                                    <th class="w-12 px-5 py-3"><span class="sr-only">Remove</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                @foreach ($standings as $rank => $entry)
                                    @php $average = $entry->averageScore(); @endphp
                                    <tr @class(['align-top', 'bg-sun-50/60' => $entry->place])>
                                        @if ($category->isPresentation())
                                            <td class="px-5 py-4 text-base font-extrabold text-ink-400">{{ $average !== null ? $rank + 1 : '–' }}</td>
                                            <td class="max-w-md px-5 py-4">
                                                <p class="font-semibold text-ink-900">{{ $entry->abstract?->title }}</p>
                                                <p class="mt-0.5 text-xs text-ink-500">
                                                    <span class="font-mono">{{ $entry->abstract?->code }}</span> · {{ $entry->name }}@if ($entry->institution) · {{ $entry->institution }}@endif
                                                </p>
                                                @if ($entry->scores->isNotEmpty())
                                                    <details class="mt-2">
                                                        <summary class="cursor-pointer text-xs font-semibold text-brand-700">Scores by judge</summary>
                                                        <ul class="mt-2 space-y-2">
                                                            @foreach ($entry->scores as $score)
                                                                <li class="rounded-xl bg-canvas px-3 py-2 text-xs">
                                                                    <p class="flex justify-between gap-3"><span class="font-semibold text-ink-800">{{ $score->judge->name }}</span><span class="font-bold tabular-nums text-ink-900">{{ $score->total }}/{{ $max }}</span></p>
                                                                    <p class="mt-0.5 text-ink-500">
                                                                        @foreach (\App\Support\AwardRubric::criteria() as $key => $criterion){{ $criterion['label'] }} {{ $score->scores[$key] ?? '–' }}@if (! $loop->last) · @endif @endforeach
                                                                    </p>
                                                                    @if ($score->comments)<p class="mt-1 text-ink-700">“{{ $score->comments }}”</p>@endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </details>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-ink-700">{{ $entry->scores->count() }}/{{ $category->judges->reject(fn ($judge) => $entry->conflictsWith($judge))->count() }}</td>
                                            <td class="px-5 py-4">
                                                @if ($average !== null)
                                                    <span class="text-lg font-extrabold tabular-nums text-brand-700">{{ $average }}</span><span class="text-xs font-semibold text-ink-400">/{{ $max }}</span>
                                                @else
                                                    <span class="text-ink-400">Not scored</span>
                                                @endif
                                            </td>
                                        @else
                                            <td class="max-w-md px-5 py-4">
                                                <p class="font-semibold text-ink-900">{{ $entry->name }}
                                                    @if (($nominationCounts[Str::lower(trim($entry->name))] ?? 1) > 1)
                                                        <span class="ml-1 rounded-full bg-ember-50 px-2 py-0.5 text-[11px] font-bold text-ember-700">Nominated {{ $nominationCounts[Str::lower(trim($entry->name))] }} times</span>
                                                    @endif
                                                </p>
                                                <p class="mt-0.5 text-xs text-ink-500">{{ collect([$entry->institution, $entry->email])->filter()->implode(' · ') ?: 'No institution given' }}</p>
                                                @if ($entry->citation)
                                                    <p class="mt-2 text-sm leading-relaxed text-ink-700">{{ $entry->citation }}</p>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-ink-700">
                                                {{ $entry->nominator?->name ?? 'The committee' }}
                                                <p class="text-xs text-ink-500">{{ $entry->created_at->format('j M Y') }}</p>
                                            </td>
                                        @endif
                                        <td class="px-5 py-4">
                                            @if ($announced)
                                                @if ($entry->place)
                                                    <span class="flex items-center gap-2 font-semibold text-ink-900"><x-award.medal :place="$entry->place" size="sm" />{{ $category->placeLabel($entry->place) }}</span>
                                                @else
                                                    <span class="text-ink-400">—</span>
                                                @endif
                                            @else
                                                <label class="sr-only" for="place-{{ $entry->id }}">Place for {{ $entry->name }}</label>
                                                <select id="place-{{ $entry->id }}" name="places[{{ $entry->id }}]" form="places-form" class="field h-10 appearance-auto py-0 text-sm">
                                                    <option value="">No place</option>
                                                    @foreach ($placeOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected($entry->place === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            @unless ($announced)
                                                <button type="submit" form="remove-{{ $entry->id }}" aria-label="Remove {{ $entry->name }}"
                                                        class="grid h-9 w-9 place-items-center rounded-lg text-ink-400 transition hover:bg-red-50 hover:text-red-700">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                </button>
                                            @endunless
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @unless ($announced)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 px-5 py-4">
                            <p class="text-sm text-ink-500">
                                @if ($hasPlaces)
                                    Winners chosen. Announcing publishes them and emails each winner.
                                @else
                                    Give each place to one {{ $category->isPresentation() ? 'finalist' : 'nominee' }}, then save.
                                @endif
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <x-button form="places-form" variant="secondary" size="sm" icon="check">Save places</x-button>
                                @if ($hasPlaces)
                                    <form method="POST" action="{{ route('committee.awards.announce', $category) }}"
                                          x-data x-on:submit="if (! confirm('Announce the winners? They appear on the public awards page and each winner is emailed.')) $event.preventDefault()">
                                        @csrf
                                        <x-button variant="success" size="sm" icon="trophy">Announce winners</x-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endunless
                @endif
            </x-card>

            {{-- Forms the table's controls submit, kept outside the table --}}
            <form id="places-form" method="POST" action="{{ route('committee.awards.places', $category) }}" class="hidden">@csrf @method('PUT')</form>
            @foreach ($standings as $entry)
                <form id="remove-{{ $entry->id }}" method="POST" action="{{ route('committee.awards.entries.destroy', [$category, $entry]) }}" class="hidden"
                      x-data x-on:submit="if (! confirm({{ Js::from('Remove '.$entry->name.'?'.($entry->scores->isNotEmpty() ? ' Their judges’ scores are deleted too.' : '')) }})) $event.preventDefault()">
                    @csrf @method('DELETE')
                </form>
            @endforeach

            {{-- Shortlisting --}}
            @if ($category->isPresentation() && ! $announced)
                <x-card title="Shortlist finalists" :description="'Accepted abstracts that fit this award ('.Str::lower($category->eligibility()).'), best reviewed first.'" :padding="false">
                    @if ($eligible->isEmpty())
                        <x-empty icon="clipboard" title="No more abstracts to shortlist" class="!py-10">
                            Every accepted abstract that fits this award is already on the shortlist, or none has been accepted yet.
                        </x-empty>
                    @else
                        <form method="POST" action="{{ route('committee.awards.entries.store', $category) }}" x-data="{ picked: [] }">
                            @csrf
                            <div class="max-h-[30rem] overflow-auto">
                                <table class="w-full min-w-[680px] text-left text-sm">
                                    <thead class="sticky top-0 border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                                        <tr><th class="w-12 px-5 py-3"><span class="sr-only">Pick</span></th><th class="px-5 py-3">Abstract</th><th class="px-5 py-3">Topic</th><th class="px-5 py-3">Review score</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-ink-100">
                                        @foreach ($eligible as $abstract)
                                            <tr class="hover:bg-ink-50/60">
                                                <td class="px-5 py-3"><input type="checkbox" name="abstracts[]" value="{{ $abstract->id }}" x-model="picked" id="abstract-{{ $abstract->id }}" class="h-4 w-4 rounded border-ink-300 accent-brand-700"></td>
                                                <td class="max-w-md px-5 py-3">
                                                    <label for="abstract-{{ $abstract->id }}" class="block cursor-pointer font-medium text-ink-900">{{ $abstract->title }}</label>
                                                    <p class="text-xs text-ink-500"><span class="font-mono">{{ $abstract->code }}</span> · {{ $abstract->presenter()?->name }} · {{ $abstract->decision_type?->label() }}</p>
                                                </td>
                                                <td class="px-5 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $abstract->topic->chipClasses() }}">{{ $abstract->topic->code }}</span></td>
                                                <td class="px-5 py-3 font-semibold {{ \App\Support\Rubric::scoreClass($abstract->averageScore()) }}">{{ $abstract->averageScore() ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-ink-100 px-5 py-4">
                                <p class="text-sm text-ink-500"><span x-text="picked.length">0</span> selected</p>
                                <x-button size="sm" icon="plus" x-bind:disabled="picked.length === 0">Add to shortlist</x-button>
                            </div>
                        </form>
                    @endif
                </x-card>
            @endif

            {{-- Direct recipient for honours --}}
            @if (! $category->isPresentation() && ! $announced)
                <x-card title="Add a recipient" description="Name the person or organisation the committee has chosen, with the citation for the ceremony.">
                    <form method="POST" action="{{ route('committee.awards.entries.store', $category) }}" class="space-y-5">
                        @csrf
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="name" label="Name" required maxlength="120" />
                            <x-form.input name="institution" label="Institution (optional)" maxlength="160" />
                        </div>
                        <x-form.input name="email" type="email" label="Email (optional)" maxlength="160"
                            hint="If they have a portal account with this email, they can download their certificate there." />
                        <x-form.textarea name="citation" label="Citation" rows="4" required maxlength="2000" />
                        <x-button size="sm" icon="plus">Add recipient</x-button>
                    </form>
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card title="About this award">
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-ink-500">Kind</dt><dd class="font-semibold text-ink-900">{{ $category->kind->label() }}</dd></div>
                    <div><dt class="text-ink-500">Who can win</dt><dd class="font-semibold text-ink-900">{{ $category->eligibility() }}</dd></div>
                    @if ($category->nominations_close_on)
                        <div><dt class="text-ink-500">Nominations</dt><dd class="font-semibold text-ink-900">{{ $category->acceptsNominations() ? 'Open until' : 'Closed on' }} {{ $category->nominations_close_on->format('j F Y') }}</dd></div>
                    @endif
                    @if ($category->prize)
                        <div><dt class="text-ink-500">Prize</dt><dd class="font-semibold text-ink-900">{{ $category->prize }}</dd></div>
                    @endif
                    @if ($category->description)
                        <div><dt class="text-ink-500">Description</dt><dd class="text-ink-700">{{ $category->description }}</dd></div>
                    @endif
                </dl>
            </x-card>

            @if ($category->isPresentation())
                <x-card title="Judges" :description="$progress['done'].' of '.$progress['expected'].' scores in'">
                    <div class="mb-4 h-1.5 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full bg-brand-600" style="width: {{ $progress['expected'] ? $progress['done'] / $progress['expected'] * 100 : 0 }}%"></div></div>
                    @if ($judges->isEmpty())
                        <p class="text-sm text-ink-600">
                            Nobody has the awards judge role yet.
                            @role('admin') Give it in <a href="{{ route('admin.users.index') }}" class="font-semibold text-brand-700 underline">Users &amp; roles</a>. @else Ask an administrator to give it. @endrole
                        </p>
                    @else
                        <form method="POST" action="{{ route('committee.awards.judges', $category) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            @foreach ($judges as $judge)
                                @php $scored = $standings->filter(fn ($e) => $e->scores->contains('judge_id', $judge->id))->count(); @endphp
                                <label class="flex items-start gap-2.5 text-sm">
                                    <input type="checkbox" name="judges[]" value="{{ $judge->id }}" @checked($category->judges->contains($judge)) @disabled($announced)
                                           class="mt-0.5 h-4 w-4 shrink-0 rounded border-ink-300 accent-brand-700">
                                    <span class="min-w-0">
                                        <span class="block font-medium text-ink-800">{{ $judge->name }}</span>
                                        <span class="block text-xs text-ink-500">{{ $judge->institution }}@if ($category->judges->contains($judge)) · {{ $scored }} scored @endif</span>
                                    </span>
                                </label>
                            @endforeach
                            @unless ($announced)
                                <x-button variant="secondary" size="sm" icon="check">Save judges</x-button>
                            @endunless
                        </form>
                    @endif
                </x-card>
            @endif

            @if ($announced && $category->entries()->whereNotNull('place')->exists())
                <x-card title="Certificates">
                    <ul class="space-y-2">
                        @foreach ($standings->whereNotNull('place')->sortBy('place') as $winner)
                            <li>
                                <a href="{{ route('committee.awards.entries.certificate', [$category, $winner]) }}" class="flex items-center gap-3 rounded-xl px-2 py-2 text-sm hover:bg-ink-50">
                                    <x-award.medal :place="$winner->place" size="sm" />
                                    <span class="min-w-0 flex-1 truncate font-medium text-ink-900">{{ $winner->name }}</span>
                                    <x-icon name="download" class="h-4 w-4 text-brand-700" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.portal>
