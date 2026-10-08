@php
    use App\Models\AbstractSubmission;
    use App\Support\Palette;
    use App\Support\Rubric;

    $abstract = $assignment->abstract;
    $locked = ! $assignment->isOpen();
    $lockedReason = match (true) {
        $abstract->status->isDecided() => 'A decision has been made on this abstract, so the review is closed.',
        $abstract->status === \App\Enums\AbstractStatus::Withdrawn => 'The authors withdrew this abstract, so the review is closed.',
        default => 'The committee asked the authors to revise this abstract, so the first review is closed.',
    };
    $round2 = $assignment->round === 2;
    $criteria = Rubric::criteria();
    $colours = collect(array_keys($criteria))->mapWithKeys(fn ($field, $i) => [$field => Palette::categorical($i)]);
    $levels = array_reverse(Rubric::LEVELS, true); // lowest first
    $levelStops = array_keys($levels);
    $words = $abstract->wordCount();
    $limit = AbstractSubmission::WORD_LIMIT;

    // Band colours on the dark teal hero, and tick marks on the ring at each band boundary.
    $heroColours = ['accept' => '#34d399', 'revise' => '#f2b302', 'reject' => '#fd9a8f'];
    $bandSummary = collect(Rubric::bands())->map(fn ($b) => ($b['min'] > 0 ? '≥'.$b['min'].'% ' : 'lower ').strtolower($b['label']))->implode(' · ');
    $ticks = collect(Rubric::bands())->filter(fn ($b) => $b['min'] > 0)->map(function ($b) {
        $angle = deg2rad($b['min'] / 100 * 360 - 90);

        return ['x1' => 21 + 12.6 * cos($angle), 'y1' => 21 + 12.6 * sin($angle), 'x2' => 21 + 19.2 * cos($angle), 'y2' => 21 + 19.2 * sin($angle), 'min' => $b['min']];
    });

    $state = [
        'rubric' => Rubric::forScript(),
        'scores' => collect(Rubric::fields())->mapWithKeys(fn ($f) => [$f => ($v = old($f, $assignment->{$f})) === null || $v === '' ? null : (int) $v])->all(),
        'checks' => array_values((array) old('technical_checks', $assignment->technical_checks ?? [])),
        'recommendation' => old('recommendation', $assignment->recommendation?->value ?? ''),
        'comment' => old('comments_for_author', $assignment->comments_for_author ?? ''),
        'note' => old('comments_for_committee', $assignment->comments_for_committee ?? ''),
        'locked' => $locked,
        'useDraft' => ! $locked && ! $assignment->isComplete() && ! $errors->any(),
        'draftKey' => 'review-draft:'.$assignment->id,
        'canRevise' => in_array(\App\Enums\Recommendation::Revise, $recommendations, true),
    ];

    $choices = [
        'accept_oral' => ['icon' => 'users', 'blurb' => $round2 ? 'The revision answers your concerns' : 'Strong enough to present from the podium'],
        'accept_poster' => ['icon' => 'document', 'blurb' => 'Worth sharing, best as a poster'],
        'revise' => ['icon' => 'pencil', 'blurb' => 'Worth accepting once the authors make the changes in your comments'],
        'reject' => ['icon' => 'x-circle', 'blurb' => $round2 ? 'The revision does not answer your concerns' : 'Not ready for this summit'],
    ];
    $choiceStyles = [
        'reject' => ['border-red-300 bg-red-50/60 ring-2 ring-red-500/30', 'bg-red-50 text-red-700'],
        'revise' => ['border-amber-300 bg-amber-50/60 ring-2 ring-amber-500/30', 'bg-amber-50 text-amber-700'],
    ];
@endphp

<x-layouts.portal :title="'Review '.$abstract->blindId()">
    <x-slot:header>
        <x-page-header :eyebrow="'Review '.$abstract->blindId()" :title="$abstract->title" :back="route('reviews.index')">
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $abstract->topic->chipClasses() }}">{{ $abstract->topic->name }}</span>
        </x-page-header>
    </x-slot:header>

    <div x-data="scorecard(@js($state))" class="space-y-6">

        {{-- Queue progress --}}
        @if ($queue['total'] > 1)
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 rounded-2xl border border-ink-100 bg-white px-5 py-3 shadow-soft">
                <p class="text-sm font-semibold text-ink-800">Your queue <span class="font-normal text-ink-500">· {{ $queue['done'] }} of {{ $queue['total'] }} reviewed</span></p>
                <div class="h-1.5 min-w-32 flex-1 overflow-hidden rounded-full bg-ink-100">
                    <div class="h-full rounded-full bg-brand-600" style="width: {{ round($queue['done'] / $queue['total'] * 100) }}%"></div>
                </div>
                @if ($queue['next'])
                    <a href="{{ route('reviews.edit', $queue['next']) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">Next abstract <x-icon name="arrow-right" class="h-4 w-4" /></a>
                @endif
            </div>
        @endif

        <div x-show="restored" x-cloak class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-900">
            <span class="inline-flex items-center gap-2"><x-icon name="clock" class="h-5 w-5 text-brand-600" /> We restored the draft you started in this browser.</span>
            <button type="button" @click="discardDraft()" class="font-semibold text-brand-700 hover:underline">Start over</button>
        </div>

        {{-- Round 2: what changed since the first review --}}
        @if ($round2)
            <section class="rounded-card border border-ink-100 bg-white shadow-soft">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">Revised version · second review</p>
                        <h2 class="mt-1 text-lg font-bold text-ink-900">What the authors changed</h2>
                    </div>
                    <span class="text-xs font-medium text-ink-500">Revised {{ $abstract->revised_at->format('j M Y') }}</span>
                </header>
                <div class="grid gap-5 px-6 py-5 lg:grid-cols-2">
                    <div class="rounded-2xl bg-brand-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-brand-800">The authors' response</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-brand-900">{{ $abstract->revision_response }}</p>
                    </div>
                    @if ($earlier)
                        <div class="rounded-2xl border border-ink-100 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-bold uppercase tracking-wider text-ink-500">Your first review</p>
                                <span class="flex items-center gap-2">
                                    <span class="text-sm font-extrabold tabular-nums {{ Rubric::scoreClass($earlier->totalScore()) }}">{{ $earlier->totalScore() }}/{{ Rubric::max() }}</span>
                                    <x-status :tone="$earlier->recommendation->tone()">{{ $earlier->recommendation->label() }}</x-status>
                                </span>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink-700">{{ $earlier->comments_for_author }}</p>
                        </div>
                    @endif
                </div>
                <x-abstract-comparison :abstract="$abstract" class="border-t border-ink-100 px-6 py-5" />
            </section>
        @endif

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.08fr)]">

            {{-- The abstract, pinned while the reviewer scores --}}
            <article class="overflow-hidden rounded-card border border-ink-100 bg-white shadow-soft lg:sticky lg:top-28 lg:flex lg:max-h-[calc(100vh-8.5rem)] lg:flex-col">
                <header class="border-b border-ink-100 px-6 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-500"><x-icon name="eye-slash" class="h-4 w-4" /> Double-blind · authors hidden</span>
                        @if (\App\Enums\PresentationType::postersEnabled())<span class="text-xs font-medium text-ink-500">Prefers {{ strtolower($abstract->preferred_type->label()) }}</span>@endif
                    </div>
                    <div class="mt-3 flex items-center gap-3">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-ink-100">
                            <div @class(['h-full rounded-full', 'bg-emerald-500' => $words <= $limit, 'bg-red-500' => $words > $limit]) style="width: {{ min(100, round($words / $limit * 100)) }}%"></div>
                        </div>
                        <span @class(['text-xs font-semibold tabular-nums', 'text-ink-600' => $words <= $limit, 'text-red-700' => $words > $limit])>{{ $words }} / {{ $limit }} words</span>
                    </div>
                </header>
                <div class="space-y-6 overflow-y-auto px-6 py-6 text-[15px] leading-7 text-ink-700">
                    @foreach (['background' => 'Background', 'methods' => 'Methods', 'results' => 'Results', 'conclusions' => 'Conclusions'] as $field => $label)
                        <section>
                            <h2 class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-brand-700">
                                <span class="h-px w-4 bg-brand-300"></span>{{ $label }}
                            </h2>
                            <p class="mt-2 whitespace-pre-line">{{ $abstract->{$field} }}</p>
                        </section>
                    @endforeach
                    @if ($abstract->keywords)
                        <div class="flex flex-wrap gap-1.5 border-t border-ink-100 pt-5">
                            @foreach (array_filter(array_map('trim', explode(',', $abstract->keywords))) as $keyword)
                                <span class="rounded-full bg-canvas px-2.5 py-1 text-xs font-medium text-ink-600">{{ $keyword }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>

            {{-- The scorecard --}}
            <form method="POST" action="{{ route('reviews.update', $assignment) }}" @submit="clearDraft()" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Live total and verdict --}}
                <section x-ref="hero" class="relative overflow-hidden rounded-[26px] bg-brand-700 p-6 text-white shadow-lift sm:p-7">
                    <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-brand-500/30 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-24 left-10 h-56 w-56 rounded-full bg-sun-400/10 blur-3xl"></div>

                    <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div class="relative h-[148px] w-[148px] shrink-0" role="img" :aria-label="`Total ${total} out of {{ Rubric::max() }}`">
                            <svg viewBox="0 0 42 42" class="h-full w-full">
                                <circle cx="21" cy="21" r="15.9155" fill="none" stroke="rgb(255 255 255 / 0.12)" stroke-width="3.6" />
                                <circle cx="21" cy="21" r="15.9155" fill="none" stroke-width="3.6" stroke-linecap="round" transform="rotate(-90 21 21)"
                                        class="score-ring-arc" :stroke="band ? @js($heroColours)[band.key] : 'transparent'" :stroke-dasharray="dash" />
                                @foreach ($ticks as $tick)
                                    <line x1="{{ round($tick['x1'], 2) }}" y1="{{ round($tick['y1'], 2) }}" x2="{{ round($tick['x2'], 2) }}" y2="{{ round($tick['y2'], 2) }}" stroke="rgb(255 255 255 / 0.55)" stroke-width="0.5" />
                                @endforeach
                            </svg>
                            <div class="absolute inset-0 grid place-items-center text-center">
                                <div>
                                    <p class="text-[2.6rem] font-extrabold leading-none tracking-tight tabular-nums" x-text="shownTotal">0</p>
                                    <p class="mt-1 text-xs font-semibold text-brand-200">out of {{ Rubric::max() }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-sun-400" x-text="complete ? 'Rubric verdict' : (scored ? 'Provisional verdict' : 'Your scorecard')">Your scorecard</p>
                            <p class="mt-2 text-2xl font-extrabold leading-tight" x-text="band ? band.label : 'Start scoring below'">Start scoring below</p>
                            <p class="mt-1.5 text-sm text-brand-100" x-text="complete ? @js('All '.count($criteria).' criteria scored. '.$bandSummary.'.') : `${scored} of {{ count($criteria) }} criteria scored`"></p>

                            {{-- Contribution of each criterion to the total --}}
                            <div class="relative mt-5">
                                <div class="flex h-3 overflow-hidden rounded-full bg-white/12">
                                    @foreach ($criteria as $field => $c)
                                        <div class="h-full transition-[width] duration-500" :style="`width: ${(scores['{{ $field }}'] ?? 0)}%; background: {{ $colours[$field] }}`"></div>
                                    @endforeach
                                </div>
                                @foreach ($ticks as $tick)
                                    <span class="absolute -top-1 h-5 w-px bg-white/60" style="left: {{ $tick['min'] }}%"></span>
                                    <span class="absolute top-5 -translate-x-1/2 text-[10px] font-semibold text-brand-200" style="left: {{ $tick['min'] }}%">{{ $tick['min'] }}</span>
                                @endforeach
                            </div>
                            <ul class="mt-7 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-brand-100">
                                @foreach ($criteria as $field => $c)
                                    <li class="inline-flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full" style="background: {{ $colours[$field] }}"></span>
                                        {{ $c['short'] }} <span class="font-semibold text-white tabular-nums" x-text="`${scores['{{ $field }}'] ?? '–'}/{{ $c['max'] }}`"></span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>

                {{-- One card per criterion --}}
                @foreach ($criteria as $field => $c)
                    @php $n = $loop->iteration; @endphp
                    <section class="relative overflow-hidden rounded-card border bg-white p-5 shadow-soft transition sm:p-6"
                             :class="scores['{{ $field }}'] === null ? 'border-ink-100' : 'border-transparent ring-1 ring-ink-100'">
                        <span class="absolute inset-y-0 left-0 w-1 transition-opacity" style="background: {{ $colours[$field] }}" :class="scores['{{ $field }}'] === null ? 'opacity-0' : 'opacity-100'"></span>

                        <header class="flex items-start gap-4">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-sm font-extrabold transition"
                                  :style="scores['{{ $field }}'] === null ? 'background: var(--color-ink-50); color: var(--color-ink-400)' : 'background: {{ $colours[$field] }}; color: #fff'">
                                <span x-show="scores['{{ $field }}'] === null">{{ str_pad($n, 2, '0', STR_PAD_LEFT) }}</span>
                                <x-icon name="check" class="h-5 w-5" x-show="scores['{{ $field }}'] !== null" x-cloak />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                    <h3 class="text-[17px] font-bold text-ink-900">{{ $c['label'] }}</h3>
                                    <p class="tabular-nums">
                                        <span class="text-[1.7rem] font-extrabold leading-none" style="color: {{ $colours[$field] }}" x-text="scores['{{ $field }}'] ?? '–'">–</span>
                                        <span class="text-sm font-semibold text-ink-400">/ {{ $c['max'] }}</span>
                                    </p>
                                </div>
                                <div class="mt-0.5 flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm text-ink-500">{{ $c['question'] }}</p>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold transition"
                                          :class="scores['{{ $field }}'] === null ? 'bg-ink-50 text-ink-400' : 'bg-ink-900 text-white'"
                                          x-text="level('{{ $field }}')">Not scored</span>
                                </div>
                            </div>
                        </header>

                        {{-- What to look for --}}
                        <div x-data="{ open: {{ $n === 1 ? 'true' : 'false' }} }" class="mt-4">
                            <button type="button" @click="open = ! open" class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-500 hover:text-ink-800" :aria-expanded="open.toString()">
                                <x-icon name="info" class="h-4 w-4" /> <span x-text="open ? 'Hide what to look for' : 'What to look for'">What to look for</span>
                            </button>
                            <ul x-show="open" x-transition.opacity class="mt-2 space-y-1.5 rounded-xl bg-canvas px-4 py-3 text-sm text-ink-700">
                                @foreach ($c['guidance'] as $point)
                                    <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full" style="background: {{ $colours[$field] }}"></span>{{ $point }}</li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Technical quality: a sub-checklist that suggests a score --}}
                        @if (! empty($c['checks']))
                            <div class="mt-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">Tick what this abstract does well</p>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach ($c['checks'] as $key => $check)
                                        <button type="button" @click="toggleCheck('{{ $key }}')" @disabled($locked)
                                                :aria-pressed="checks.includes('{{ $key }}').toString()"
                                                class="flex items-start gap-3 rounded-xl border px-3 py-2.5 text-left transition disabled:cursor-default"
                                                :class="checks.includes('{{ $key }}') ? 'border-transparent bg-brand-50 ring-2 ring-brand-500/40' : 'border-ink-200 hover:border-ink-300'">
                                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-md border transition"
                                                  :class="checks.includes('{{ $key }}') ? 'border-brand-600 bg-brand-600 text-white' : 'border-ink-300 text-transparent'">
                                                <x-icon name="check" class="h-3.5 w-3.5" />
                                            </span>
                                            <span>
                                                <span class="block text-sm font-semibold text-ink-900">{{ $check['label'] }}</span>
                                                <span class="block text-xs text-ink-500">{{ $check['hint'] }}</span>
                                            </span>
                                        </button>
                                        <input type="hidden" name="technical_checks[]" value="{{ $key }}" :disabled="! checks.includes('{{ $key }}')">
                                    @endforeach
                                </div>
                                <div x-show="checks.length && suggestion !== scores['{{ $field }}']" x-cloak class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-sun-50 px-4 py-2.5 text-sm text-sun-900">
                                    <span><span class="font-semibold" x-text="`${checks.length} of {{ count($c['checks']) }}`"></span> checks met, which suggests about <span class="font-bold" x-text="suggestion"></span> / {{ $c['max'] }}.</span>
                                    @unless ($locked)
                                        <button type="button" @click="scores['{{ $field }}'] = suggestion" class="rounded-lg bg-white px-3 py-1 text-xs font-bold text-sun-900 shadow-sm ring-1 ring-sun-200 hover:bg-sun-100">Use <span x-text="suggestion"></span></button>
                                    @endunless
                                </div>
                            </div>
                        @endif

                        {{-- The slider and its level scale --}}
                        <div class="mt-5">
                            <input type="range" min="0" max="{{ $c['max'] }}" step="1" @disabled($locked)
                                   aria-label="{{ $c['label'] }} score, 0 to {{ $c['max'] }}"
                                   :aria-valuetext="scores['{{ $field }}'] === null ? 'Not scored' : `${scores['{{ $field }}']} of {{ $c['max'] }}, ${level('{{ $field }}')}`"
                                   :value="scores['{{ $field }}'] ?? 0"
                                   @input="scores['{{ $field }}'] = +$event.target.value"
                                   @pointerup="if (scores['{{ $field }}'] === null) scores['{{ $field }}'] = +$event.target.value"
                                   class="rubric-range" :class="{ 'is-empty': scores['{{ $field }}'] === null }"
                                   style="--c: {{ $colours[$field] }}" :style="{ '--fill': pct('{{ $field }}') + '%' }">
                            <input type="hidden" name="{{ $field }}" :value="scores['{{ $field }}'] ?? ''">

                            <div class="mt-1 flex gap-1">
                                @foreach ($levels as $min => $label)
                                    @php $to = $levelStops[$loop->index + 1] ?? 100; @endphp
                                    <button type="button" @disabled($locked) style="flex: {{ $to - $min }} 1 0%"
                                            @click="scores['{{ $field }}'] = pointsForLevel('{{ $field }}', {{ $loop->index }})"
                                            class="group min-w-0 text-left disabled:cursor-default">
                                        <span class="block h-1 rounded-full transition"
                                              :style="level('{{ $field }}') === @js($label) ? 'background: {{ $colours[$field] }}' : 'background: var(--color-ink-100)'"></span>
                                        <span @class(['mt-1 block whitespace-nowrap text-[11px] font-semibold transition', 'text-right' => $loop->last])
                                              :class="level('{{ $field }}') === @js($label) ? 'text-ink-900' : 'text-ink-400 group-hover:text-ink-600'">{{ $label }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        @error($field) <p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                    </section>
                @endforeach

                {{-- Recommendation --}}
                <section class="rounded-card border border-ink-100 bg-white p-5 shadow-soft sm:p-6">
                    <h3 class="text-[17px] font-bold text-ink-900">Your recommendation</h3>
                    <p class="mt-0.5 text-sm text-ink-500">
                        @if ($round2)
                            Only one revision is allowed, so the choice now is to accept or reject.
                        @else
                            If both reviewers accept, the abstract is accepted. Otherwise the committee decides, and may ask the authors to revise.
                        @endif
                    </p>

                    <div @class(['mt-4 grid gap-3', 'sm:grid-cols-3' => count($recommendations) === 3, 'sm:grid-cols-2' => count($recommendations) !== 3])>
                        @foreach ($recommendations as $option)
                            @php
                                $choice = $choices[$option->value] ?? ['icon' => 'check', 'blurb' => ''];
                                [$selected, $badge] = $choiceStyles[$option->value] ?? ['border-brand-300 bg-brand-50 ring-2 ring-brand-500/30', 'bg-brand-50 text-brand-700'];
                            @endphp
                            <label class="relative flex cursor-pointer flex-col gap-2 rounded-2xl border p-4 transition has-[:disabled]:cursor-default has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-500/20"
                                   :class="recommendation === '{{ $option->value }}' ? '{{ $selected }}' : 'border-ink-200 hover:border-ink-300'">
                                <input type="radio" name="recommendation" value="{{ $option->value }}" x-model="recommendation" class="sr-only" @disabled($locked) required>
                                <span class="grid h-9 w-9 place-items-center rounded-xl {{ $badge }}">
                                    <x-icon :name="$choice['icon']" class="h-5 w-5" />
                                </span>
                                <span class="text-sm font-bold text-ink-900">{{ $option->label() }}</span>
                                <span class="text-xs leading-snug text-ink-500">{{ $choice['blurb'] }}</span>
                                <span x-show="fits('{{ $option->value }}')" x-cloak class="absolute right-3 top-3 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Fits score</span>
                            </label>
                        @endforeach
                    </div>
                    @error('recommendation') <p class="mt-2 text-xs font-medium text-red-700">{{ $message === 'The recommendation field is required.' ? 'Choose a recommendation.' : $message }}</p> @enderror

                    <div x-show="nudge" x-cloak x-transition.opacity class="mt-4 flex gap-3 rounded-2xl border p-4 text-sm"
                         :class="nudge?.tone === 'warning' ? 'border-amber-100 bg-amber-50 text-amber-900' : 'border-brand-100 bg-brand-50 text-brand-900'">
                        <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0" />
                        <p x-text="nudge?.text"></p>
                    </div>
                </section>

                {{-- Comments --}}
                <section class="space-y-5 rounded-card border border-ink-100 bg-white p-5 shadow-soft sm:p-6">
                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <label for="comments_for_author" class="label !mb-0">Comments for the author</label>
                            <span class="text-xs tabular-nums" :class="commentLength >= 20 ? 'text-emerald-700' : 'text-ink-400'" x-text="commentLength >= 20 ? '✓ Enough to send' : `${20 - commentLength} more characters`"></span>
                        </div>
                        <p x-show="weakest" x-cloak class="mb-2 mt-1 text-xs text-ink-500">
                            Start with what works, then the weakest area: <span class="font-semibold text-ink-700" x-text="weakest ? `${weakest.label} (${scores[weakest.field]}/${weakest.max})` : ''"></span>.
                        </p>
                        <textarea id="comments_for_author" name="comments_for_author" rows="6" x-model="comment" @disabled($locked) required
                                  placeholder="Shared anonymously after the decision. Be specific and constructive."
                                  @class(['mt-1.5 w-full rounded-control border bg-white px-4 py-3 text-[15px] leading-relaxed text-ink-900 placeholder:text-ink-400 transition focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15 disabled:bg-ink-50',
                                      'border-red-400' => $errors->has('comments_for_author'), 'border-ink-200' => ! $errors->has('comments_for_author')])></textarea>
                        @error('comments_for_author') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="comments_for_committee" class="label">Confidential note to the committee <span class="font-normal text-ink-400">(optional)</span></label>
                        <textarea id="comments_for_committee" name="comments_for_committee" rows="3" x-model="note" @disabled($locked)
                                  placeholder="Conflicts, concerns or context only the committee should see."
                                  class="w-full rounded-control border border-ink-200 bg-sun-50/40 px-4 py-3 text-[15px] leading-relaxed text-ink-900 placeholder:text-ink-400 transition focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15 disabled:bg-ink-50"></textarea>
                    </div>

                    @if ($locked)
                        <p class="rounded-xl bg-canvas px-4 py-3 text-sm text-ink-600">{{ $lockedReason }}</p>
                    @else
                        <div x-ref="footer" class="flex flex-col gap-3 border-t border-ink-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-ink-500">
                                <span x-show="! ready">Still needed: <span class="font-semibold text-ink-700" x-text="missing"></span></span>
                                <span x-show="ready" x-cloak class="inline-flex items-center gap-1.5 font-semibold text-emerald-700"><x-icon name="check-circle" class="h-5 w-5" /> Ready to submit</span>
                            </p>
                            <x-button size="lg" icon="check" ::disabled="! ready">{{ $assignment->isComplete() ? 'Update review' : 'Submit review' }}</x-button>
                        </div>
                    @endif
                </section>
            </form>
        </div>

        {{-- Floating total once the hero scrolls away --}}
        <button type="button" x-show="! heroVisible && ! footerVisible" x-cloak x-transition.opacity.duration.200ms
                @click="$refs.hero.scrollIntoView({ behavior: 'smooth', block: 'center' })"
                class="fixed bottom-5 right-5 z-40 flex items-center gap-3 rounded-full bg-brand-800 py-2 pl-2 pr-5 text-white shadow-lift">
            <span class="grid h-11 w-11 place-items-center rounded-full text-base font-extrabold tabular-nums text-ink-900"
                  :style="`background: ${band ? @js($heroColours)[band.key] : 'rgb(255 255 255 / 0.2)'}`" x-text="total"></span>
            <span class="text-left leading-tight">
                <span class="block text-sm font-bold" x-text="band ? band.label : 'Not scored yet'"></span>
                <span class="block text-xs text-brand-200" x-text="`${scored} of {{ count($criteria) }} scored`"></span>
            </span>
        </button>
    </div>
</x-layouts.portal>
