@php
    $presentation = $categories->filter->isPresentation();
    $progress = $presentation->map->scoringProgress();
    $done = $progress->sum('done');
    $expected = $progress->sum('expected');
    $announced = $categories->filter->isAnnounced()->count();
@endphp

<x-layouts.portal title="Awards">
    <x-slot:header>
        <x-page-header eyebrow="Scientific committee" title="Awards"
            description="Set up the summit's awards, shortlist finalists, assign judges, choose the winners and announce them.">
            @if ($edition)
                <x-button variant="secondary" size="sm" icon="eye" :href="route('awards.index')">Public page</x-button>
                <x-button size="sm" icon="plus" :href="route('committee.awards.create')">New award</x-button>
            @endif
        </x-page-header>
    </x-slot:header>

    @if (! $edition)
        <x-card><x-empty icon="trophy" title="No summit set up yet">Create the summit edition in Summit settings first.</x-empty></x-card>
    @elseif ($categories->isEmpty())
        <x-card>
            <x-empty icon="trophy" title="No awards for the {{ $edition->year }} summit yet">
                Start from the suggested set and adjust it, or create your own awards.
                <x-slot:action>
                    <div class="flex flex-wrap justify-center gap-2">
                        <form method="POST" action="{{ route('committee.awards.suggested') }}">
                            @csrf
                            <x-button icon="plus">Add the suggested awards</x-button>
                        </form>
                        <x-button variant="secondary" :href="route('committee.awards.create')">Create your own</x-button>
                    </div>
                </x-slot:action>
            </x-empty>
            <ul class="mx-auto grid max-w-3xl gap-3 pb-6 sm:grid-cols-2">
                @foreach (config('awards.suggested') as $suggestion)
                    <li class="rounded-2xl bg-canvas p-4">
                        <p class="font-semibold text-ink-900">{{ $suggestion['name'] }}</p>
                        <p class="mt-1 text-sm text-ink-500">{{ $suggestion['description'] }}</p>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-kpi label="Awards" :value="$categories->count()" :hint="$presentation->count().' judged · '.($categories->count() - $presentation->count()).' honours'" />
            <x-kpi label="Finalists & nominees" :value="$categories->sum(fn ($c) => $c->entries->count())" hint="Across every award" />
            <x-kpi label="Judges' scores" :value="$done.' / '.$expected" :progress="$expected ? $done / $expected * 100 : 0"
                :hint="$expected && $done === $expected ? 'Every finalist scored' : 'Scores still to come'" :hint-tone="$expected && $done === $expected ? 'good' : 'muted'" />
            <x-kpi label="Announced" :value="$announced.' of '.$categories->count()" :progress="$announced / $categories->count() * 100" bar="#45582e" />
        </div>

        @if ($judgeCount === 0)
            <x-alert tone="warning">
                Nobody has the awards judge role yet, so judges cannot be assigned.
                @role('admin') Give the role in <a href="{{ route('admin.users.index') }}" class="font-semibold underline">Users &amp; roles</a>. @else Ask an administrator to give the role. @endrole
            </x-alert>
        @endif

        <x-card :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr>
                            <th class="px-5 py-3">Award</th><th class="px-5 py-3">Stage</th><th class="px-5 py-3">Entries</th>
                            <th class="px-5 py-3">Judging</th><th class="px-5 py-3">Winners</th><th class="px-5 py-3"><span class="sr-only">Open</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($categories as $category)
                            @php
                                [, $stageLabel, $stageTone] = $category->stage();
                                $winners = $category->entries->whereNotNull('place')->sortBy('place');
                                $entryNoun = $category->isPresentation() ? 'finalist' : ($category->nominations_close_on ? 'nomination' : 'recipient');
                            @endphp
                            <tr class="hover:bg-ink-50/60">
                                <td class="max-w-xs px-5 py-3.5">
                                    <a href="{{ route('committee.awards.show', $category) }}" class="font-semibold text-ink-900 hover:text-brand-700">{{ $category->name }}</a>
                                    <p class="text-xs text-ink-500">{{ $category->eligibility() }} · {{ $category->places }} {{ Str::plural('place', $category->places) }}</p>
                                </td>
                                <td class="px-5 py-3.5"><x-status :tone="$stageTone">{{ $stageLabel }}</x-status></td>
                                <td class="px-5 py-3.5 text-ink-700">{{ $category->entries->count() }} {{ Str::plural($entryNoun, $category->entries->count()) }}</td>
                                <td class="px-5 py-3.5">
                                    @if ($category->isPresentation())
                                        @php ['done' => $d, 'expected' => $e] = $category->scoringProgress(); @endphp
                                        <div class="flex items-center gap-2.5">
                                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full bg-brand-600" style="width: {{ $e ? $d / $e * 100 : 0 }}%"></div></div>
                                            <span class="text-xs text-ink-500">{{ $d }}/{{ $e }} · {{ $category->judges->count() }} {{ Str::plural('judge', $category->judges->count()) }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-ink-500">Committee decides</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-ink-700">
                                    @forelse ($winners as $winner)
                                        <span class="flex items-center gap-1.5"><x-award.medal :place="$winner->place" size="sm" class="!h-5 !w-5 !text-[8px]" />{{ $winner->name }}</span>
                                    @empty
                                        <span class="text-ink-400">Not chosen</span>
                                    @endforelse
                                </td>
                                <td class="px-5 py-3.5 text-right"><x-button variant="ghost" size="sm" :href="route('committee.awards.show', $category)">Open</x-button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="How awards work">
            <ol class="grid gap-5 text-sm text-ink-600 md:grid-cols-4">
                @foreach ([
                    ['Shortlist', 'Pick finalists from accepted abstracts, best reviewed first. Honours collect nominations or a committee choice.'],
                    ['Assign judges', 'Give people the awards judge role, then choose the judges for each award.'],
                    ['Judge', 'Judges score each finalist at the summit on four criteria. Nobody scores their own abstract.'],
                    ['Announce', 'Choose the places from the ranking and announce. Winners are emailed and download a certificate.'],
                ] as $n => [$step, $body])
                    <li>
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">{{ $n + 1 }}</span>
                        <p class="mt-2 font-semibold text-ink-900">{{ $step }}</p>
                        <p class="mt-1">{{ $body }}</p>
                    </li>
                @endforeach
            </ol>
        </x-card>
    @endif
</x-layouts.portal>
