@php
    $title = $edition ? $edition->name.' '.$edition->year : $summit->title();
    $announced = $categories->filter->isAnnounced();
    $accentBorders = ['border-t-ember-500', 'border-t-coral-400', 'border-t-olive-700', 'border-t-sun-400'];
@endphp

<x-layouts.public class="bg-white" :title="'Awards · '.$title" :description="'Awards at the '.$title.': best presentations, posters, student research and service to rehabilitation.'">
    <x-public.nav />

    <header class="bg-gradient-to-b from-white to-brand-50">
        <div class="wrap pb-14 pt-12">
            <p class="eyebrow">Awards</p>
            <h1 class="section-title">{{ $title }} awards</h1>
            <p class="mt-4 max-w-3xl text-lg text-ink-600">
                The summit recognises outstanding research, presentation and service to rehabilitation.
                Finalists for the presentation awards are shortlisted from accepted abstracts and scored by a panel of judges during the summit.
                Winners are announced at the closing ceremony.
            </p>

            @if ($categories->isNotEmpty())
                <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4">
                    <div><dt class="text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Awards</dt><dd class="mt-1 text-2xl font-extrabold text-brand-700">{{ $categories->count() }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Winners announced</dt><dd class="mt-1 text-2xl font-extrabold text-brand-700">{{ $announced->count() }} of {{ $categories->count() }}</dd></div>
                </dl>
            @endif

            @if ($editions->count() > 1)
                <nav aria-label="Summit year" class="mt-7 flex flex-wrap gap-2">
                    @foreach ($editions as $option)
                        <a href="{{ route('awards.index', ['year' => $option->year]) }}" @if ($option->is($edition)) aria-current="page" @endif
                           @class([
                               'rounded-full border-[1.5px] border-brand-700 px-5 py-2.5 text-[15px] font-bold transition',
                               'bg-brand-700 text-white' => $option->is($edition),
                               'bg-white text-brand-700 hover:bg-brand-50' => ! $option->is($edition),
                           ])>{{ $option->year }}</a>
                    @endforeach
                </nav>
            @endif
        </div>
    </header>

    <main class="wrap py-14">
        @if ($categories->isEmpty())
            <x-empty icon="trophy" title="Awards to be announced">
                The award categories for this summit will be published here, together with how finalists are chosen.
            </x-empty>
        @else
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach ($categories as $category)
                    <article id="award-{{ $category->id }}" class="flex flex-col rounded-card border border-t-[5px] border-ink-100 bg-white p-7 shadow-soft {{ $accentBorders[$loop->index % 4] }}">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">{{ $category->eligibility() }}</span>
                            @if ($category->places > 1)
                                <span class="rounded-full bg-ink-100 px-3 py-1 text-xs font-bold text-ink-600">{{ $category->places }} places</span>
                            @endif
                        </div>
                        <h2 class="mt-4 text-2xl font-extrabold tracking-tight text-ink-900">{{ $category->name }}</h2>
                        @if ($category->description)
                            <p class="mt-2 text-base leading-relaxed text-ink-600">{{ $category->description }}</p>
                        @endif
                        @if ($category->prize)
                            <p class="mt-3 flex items-center gap-2 text-sm font-semibold text-ember-700"><x-icon name="trophy" class="h-4 w-4" /> {{ $category->prize }}</p>
                        @endif

                        <div class="mt-6 flex-1 border-t border-ink-100 pt-5">
                            @if ($category->isAnnounced())
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">{{ Str::plural('Winner', $category->winners->count()) }}</p>
                                <ul class="mt-3 space-y-4">
                                    @foreach ($category->winners as $winner)
                                        <li class="flex gap-4">
                                            <x-award.medal :place="$winner->place" />
                                            <div class="min-w-0">
                                                <p class="font-bold text-ink-900"><span class="sr-only">{{ $category->placeLabel($winner->place) }}: </span>{{ $winner->name }}</p>
                                                @if ($winner->institution)
                                                    <p class="text-sm text-ink-500">{{ $winner->institution }}</p>
                                                @endif
                                                @if ($winner->abstract)
                                                    <p class="mt-1 text-sm italic text-ink-700">“{{ $winner->abstract->title }}”</p>
                                                @elseif ($winner->citation)
                                                    <p class="mt-1 text-sm text-ink-700">{{ $winner->citation }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif ($category->acceptsNominations())
                                <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-sun-50 p-5">
                                    <div>
                                        <p class="font-bold text-ink-900">Nominations are open</p>
                                        <p class="text-sm text-ink-600">Until {{ $category->nominations_close_on->format('j F Y') }}</p>
                                    </div>
                                    <a href="{{ auth()->check() ? route('awards.mine', ['award' => $category->id]).'#nominate' : route('login') }}"
                                       class="btn-pill bg-brand-700 !px-5 !py-3 text-sm text-white hover:bg-brand-800 focus-visible:ring-brand-500/30">Nominate someone →</a>
                                </div>
                            @else
                                <p class="flex items-center gap-2.5 text-sm text-ink-500">
                                    <x-icon name="clock" class="h-4 w-4 text-brand-600" />
                                    Winners will be announced at the closing ceremony.
                                </p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </main>

    <x-public.footer />
</x-layouts.public>
