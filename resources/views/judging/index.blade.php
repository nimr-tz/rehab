@php
    use App\Support\AwardRubric;

    $max = AwardRubric::max();
@endphp

<x-layouts.portal title="Finalists to score">
    <x-slot:header>
        <x-page-header eyebrow="Awards judging" title="Finalists to score"
            description="Score each finalist on the four criteria, ideally straight after their presentation. Only the awards committee sees your scores." />
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-6">
            @forelse ($categories as $category)
                @php
                    $entries = $category->entries->sortBy('id');
                    $mine = $entries->reject(fn ($entry) => $entry->conflictsWith($judge));
                    $scored = $mine->filter(fn ($entry) => $entry->scores->contains('judge_id', $judge->id))->count();
                @endphp
                <x-card :title="$category->name" :description="$category->eligibility().' · '.$category->places.' '.Str::plural('place', $category->places)" :padding="false">
                    <x-slot:actions>
                        @if ($category->isAnnounced())
                            <x-status tone="success">Winners announced</x-status>
                        @else
                            <span class="text-sm font-semibold {{ $mine->isNotEmpty() && $scored === $mine->count() ? 'text-emerald-700' : 'text-ink-600' }}">{{ $scored }} of {{ $mine->count() }} scored</span>
                        @endif
                    </x-slot:actions>

                    @if ($entries->isEmpty())
                        <x-empty icon="clipboard" title="No finalists yet" class="!py-8">The committee is shortlisting finalists for this award.</x-empty>
                    @else
                        <ul class="divide-y divide-ink-100">
                            @foreach ($entries as $entry)
                                @php
                                    $conflict = $entry->conflictsWith($judge);
                                    $score = $entry->scores->firstWhere('judge_id', $judge->id);
                                    $session = $entry->abstract?->sessions->first();
                                @endphp
                                <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs font-semibold text-ink-500">{{ $entry->abstract?->code }}</span>
                                            @if ($entry->abstract?->topic)
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $entry->abstract->topic->chipClasses() }}">{{ $entry->abstract->topic->code }}</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 font-semibold text-ink-900">{{ $entry->abstract?->title ?? $entry->name }}</p>
                                        <p class="mt-0.5 text-xs text-ink-500">
                                            {{ $entry->name }}@if ($entry->institution) · {{ $entry->institution }}@endif
                                            @if ($session) · {{ $session->starts_at->format('D j M, H:i') }}@if ($session->hall), {{ $session->hall }}@endif @endif
                                        </p>
                                    </div>
                                    @if ($conflict)
                                        <x-status>Your own work: another judge scores it</x-status>
                                    @elseif ($score)
                                        <span class="text-right leading-tight">
                                            <span class="text-lg font-extrabold tabular-nums text-brand-700">{{ $score->total }}</span><span class="text-xs font-semibold text-ink-400">/{{ $max }}</span>
                                            <span class="block text-[11px] font-semibold text-ink-500">Your score</span>
                                        </span>
                                        @unless ($category->isAnnounced())
                                            <x-button variant="ghost" size="sm" :href="route('judging.edit', $entry)">Change</x-button>
                                        @endunless
                                    @elseif (! $category->isAnnounced())
                                        <x-button size="sm" icon="star" :href="route('judging.edit', $entry)">Score</x-button>
                                    @else
                                        <x-status>Not scored</x-status>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @empty
                <x-card>
                    <x-empty icon="trophy" title="No awards assigned to you yet">
                        The awards committee assigns judges to each award. Your finalists appear here, with the time and hall of each presentation.
                    </x-empty>
                </x-card>
            @endforelse
        </div>

        <div class="space-y-6">
            <x-card title="How to score">
                <p class="text-sm text-ink-600">Give each criterion 1 to {{ AwardRubric::perCriterion() }} points, {{ $max }} in all.</p>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach (AwardRubric::criteria() as $criterion)
                        <li>
                            <p class="font-semibold text-ink-900">{{ $criterion['label'] }}</p>
                            <p class="text-ink-500">{{ $criterion['hint'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <x-card title="Fair judging">
                <ul class="space-y-3 text-sm text-ink-600">
                    <li class="flex gap-2.5"><x-icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />You never score an abstract you wrote or co-wrote.</li>
                    <li class="flex gap-2.5"><x-icon name="eye" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />Attend each presentation or poster before you score it.</li>
                    <li class="flex gap-2.5"><x-icon name="pencil" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />You can change your scores until the winners are announced.</li>
                </ul>
            </x-card>
        </div>
    </div>
</x-layouts.portal>
