@php
    $abstract = $entry->abstract;
    $session = $abstract?->sessions->first();
    $initial = collect($criteria)->keys()->mapWithKeys(fn ($key) => [$key => ($v = old("scores.{$key}", $score?->scores[$key] ?? null)) === null ? null : (int) $v])->all();
@endphp

<x-layouts.portal :title="'Score · '.$entry->name">
    <x-slot:header>
        <x-page-header :eyebrow="$category->name.' · finalist '.$position.' of '.$count" :title="$entry->name" :back="route('judging.index')"
            :description="$entry->institution" />
    </x-slot:header>

    <form method="POST" action="{{ route('judging.update', $entry) }}"
          x-data="{ scores: @js($initial), max: {{ $max }}, get total() { return Object.values(this.scores).reduce((sum, v) => sum + (Number(v) || 0), 0) }, get complete() { return Object.values(this.scores).every(v => v) } }"
          class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            @if ($category->isAnnounced())
                <x-alert tone="warning">The winners of this award have been announced, so scores can no longer change.</x-alert>
            @endif
            @error('scores') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

            <x-card>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($abstract?->code)<span class="font-mono text-xs font-semibold text-ink-500">{{ $abstract->code }}</span>@endif
                    @if ($abstract?->topic)<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $abstract->topic->chipClasses() }}">{{ $abstract->topic->name }}</span>@endif
                    @if ($abstract?->decision_type && \App\Enums\PresentationType::postersEnabled())<x-status tone="info">{{ $abstract->decision_type->label() }}</x-status>@endif
                </div>
                <h2 class="mt-3 text-xl font-bold leading-snug text-ink-900">{{ $abstract?->title }}</h2>
                @if ($session)
                    <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-600">
                        <span class="flex items-center gap-1.5"><x-icon name="calendar" class="h-4 w-4 text-brand-600" />{{ $session->starts_at->format('l j F, H:i') }}–{{ $session->ends_at->format('H:i') }}</span>
                        @if ($session->hall)<span class="flex items-center gap-1.5"><x-icon name="map-pin" class="h-4 w-4 text-brand-600" />{{ $session->hall }}</span>@endif
                        <span class="text-ink-500">{{ $session->title }}</span>
                    </p>
                @endif
                @if ($abstract)
                    <details class="group mt-5 rounded-2xl bg-canvas p-4">
                        <summary class="cursor-pointer list-none text-sm font-semibold text-brand-700 [&::-webkit-details-marker]:hidden">
                            <span class="group-open:hidden">Read the abstract</span><span class="hidden group-open:inline">Hide the abstract</span>
                        </summary>
                        <dl class="mt-4 space-y-4 text-sm leading-relaxed text-ink-700">
                            @foreach (['background' => 'Background', 'methods' => 'Methods', 'results' => 'Results', 'conclusions' => 'Conclusions'] as $field => $label)
                                <div><dt class="font-semibold text-ink-900">{{ $label }}</dt><dd class="mt-1">{{ $abstract->{$field} }}</dd></div>
                            @endforeach
                            <div><dt class="font-semibold text-ink-900">Authors</dt><dd class="mt-1">{{ $abstract->authors->pluck('name')->join(', ') }}</dd></div>
                        </dl>
                    </details>
                @endif
            </x-card>

            <x-card title="Your scores" :description="'1 to '.$perCriterion.' points for each criterion.'">
                <div class="space-y-7">
                    @foreach ($criteria as $key => $criterion)
                        <fieldset>
                            <legend class="flex w-full flex-wrap items-baseline justify-between gap-2">
                                <span class="font-semibold text-ink-900">{{ $criterion['label'] }}</span>
                                <span class="text-sm font-bold tabular-nums text-brand-700" x-text="scores.{{ $key }} ? scores.{{ $key }} + ' / {{ $perCriterion }}' : 'Not scored'"></span>
                            </legend>
                            <p class="mt-0.5 text-sm text-ink-500">{{ $criterion['hint'] }}</p>
                            <div class="mt-3 grid grid-cols-5 gap-1.5 sm:grid-cols-10">
                                @for ($i = 1; $i <= $perCriterion; $i++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="scores[{{ $key }}]" value="{{ $i }}" x-model.number="scores.{{ $key }}" class="peer sr-only"
                                               @checked((int) ($initial[$key] ?? 0) === $i) @disabled($category->isAnnounced()) required>
                                        <span class="grid h-10 place-items-center rounded-xl border border-ink-200 text-sm font-bold text-ink-700 transition hover:border-brand-300 peer-checked:border-brand-700 peer-checked:bg-brand-700 peer-checked:text-white peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/25">{{ $i }}</span>
                                    </label>
                                @endfor
                            </div>
                            @error("scores.{$key}") <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                        </fieldset>
                    @endforeach

                    <x-form.textarea name="comments" label="Notes for the committee (optional)" rows="3" maxlength="2000" :value="$score?->comments"
                        hint="Anything that helps the committee decide, such as a strength or a concern." :disabled="$category->isAnnounced()" />
                </div>
            </x-card>
        </div>

        <div>
            <div class="space-y-4 lg:sticky lg:top-28">
                <section class="rounded-card bg-brand-700 p-6 text-white shadow-lift">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-sun-400">Total</p>
                    <p class="mt-2"><span class="text-5xl font-extrabold tabular-nums" x-text="total">{{ array_sum(array_filter($initial)) }}</span><span class="text-lg font-semibold text-brand-200"> / {{ $max }}</span></p>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/15"><div class="h-full rounded-full bg-sun-400 transition-all" style="width: {{ array_sum(array_filter($initial)) / $max * 100 }}%" :style="`width: ${total / max * 100}%`"></div></div>
                    <p class="mt-3 text-sm text-brand-100" x-show="! complete">Score every criterion to save.</p>
                    <p class="mt-3 text-sm text-brand-100" x-show="complete" x-cloak>{{ $score ? 'Saving replaces your earlier scores.' : 'Ready to save.' }}</p>
                </section>

                @unless ($category->isAnnounced())
                    <x-button class="w-full" icon="check" x-bind:disabled="! complete">{{ $score ? 'Update scores' : 'Save scores' }}</x-button>
                @endunless

                <div class="flex gap-2">
                    @if ($previous)
                        <x-button variant="secondary" size="sm" class="flex-1" icon="arrow-left" :href="route('judging.edit', $previous)">Previous</x-button>
                    @endif
                    @if ($next)
                        <x-button variant="secondary" size="sm" class="flex-1" :href="route('judging.edit', $next)">Next <x-icon name="arrow-right" class="h-4 w-4" /></x-button>
                    @endif
                </div>
            </div>
        </div>
    </form>
</x-layouts.portal>
