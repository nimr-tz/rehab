<x-layouts.public class="bg-white" :title="'Programme · '.$summit->title()">
    <x-public.nav />

    <header class="bg-gradient-to-b from-white to-brand-50">
        <div class="wrap pb-14 pt-12">
            <p class="eyebrow">Programme</p>
            <h1 class="section-title">{{ $summit->title() }}</h1>
            <p class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-lg text-ink-600">
                <span class="inline-flex items-center gap-2"><x-icon name="calendar" class="h-5 w-5 text-brand-600" />{{ $summit->dateRange() ?? 'Dates to be announced' }}</span>
                <span class="inline-flex items-center gap-2"><x-icon name="map-pin" class="h-5 w-5 text-brand-600" />{{ $summit->venueLine() ?? 'Venue to be announced' }}</span>
            </p>
        </div>
    </header>

    <main class="wrap py-14" x-data="{ day: 0 }">
        @if ($days->isEmpty())
            <x-empty icon="calendar" title="The programme is being prepared">
                Sessions are published after the abstract review. Check back soon.
            </x-empty>
        @else
            <div role="tablist" aria-label="Summit days" class="flex flex-wrap gap-2">
                @foreach ($days->keys() as $i => $date)
                    <button type="button" role="tab" @click="day = {{ $i }}" :aria-selected="(day === {{ $i }}).toString()"
                            :class="day === {{ $i }} ? 'bg-brand-700 text-white' : 'bg-white text-brand-700 hover:bg-brand-50'"
                            class="whitespace-nowrap rounded-full border-[1.5px] border-brand-700 px-5 py-3 text-base font-bold transition">
                        Day {{ $i + 1 }} · {{ \Carbon\Carbon::parse($date)->format('D j M') }}
                    </button>
                @endforeach
            </div>

            @foreach ($days->values() as $i => $sessions)
                <div x-show="day === {{ $i }}" @if ($i > 0) x-cloak @endif role="tabpanel" class="mt-8 space-y-4">
                    @foreach ($sessions as $session)
                        @if ($session->kind === 'break')
                            <div class="flex items-center gap-4 px-2 text-sm text-ink-500">
                                <span class="w-28 shrink-0 font-semibold tabular-nums">{{ $session->starts_at->format('H:i') }}</span>
                                <span class="h-px flex-1 bg-ink-200"></span>
                                <span>{{ $session->title }}</span>
                                <span class="h-px flex-1 bg-ink-200"></span>
                            </div>
                        @else
                            <article class="grid gap-4 rounded-card border border-ink-100 bg-white p-6 shadow-soft sm:grid-cols-[7rem_1fr]">
                                <div class="text-sm font-semibold tabular-nums text-ink-900">
                                    {{ $session->starts_at->format('H:i') }}<span class="text-ink-400">–{{ $session->ends_at->format('H:i') }}</span>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="text-xs font-bold uppercase tracking-[0.12em] {{ $session->kindClasses() }}">{{ $session->kindLabel() }}</span>
                                        @if ($session->hall)<span class="text-xs text-ink-500">{{ $session->hall }}</span>@endif
                                        @if ($session->topic)<span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $session->topic->chipClasses() }}">{{ $session->topic->name }}</span>@endif
                                    </div>
                                    <h2 class="mt-1.5 text-xl font-bold text-ink-900">{{ $session->title }}</h2>
                                    @if ($session->description)<p class="mt-1 text-ink-600">{{ $session->description }}</p>@endif
                                    @if ($session->chair)<p class="mt-1 text-sm text-ink-500">Chair: {{ $session->chair }}</p>@endif

                                    @if ($session->abstracts->isNotEmpty())
                                        <ol class="mt-4 divide-y divide-ink-100 rounded-2xl border border-ink-100">
                                            @foreach ($session->abstracts as $abstract)
                                                <li class="grid gap-1 px-4 py-3 sm:grid-cols-[6.5rem_1fr]">
                                                    <span class="font-mono text-xs font-semibold text-brand-700">{{ $abstract->code }}</span>
                                                    <span>
                                                        <span class="block text-sm font-semibold text-ink-900">{{ $abstract->title }}</span>
                                                        <span class="block text-xs text-ink-500">{{ $abstract->presenter()?->name }} · {{ $abstract->presenter()?->affiliation }}</span>
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ol>
                                    @endif
                                </div>
                            </article>
                        @endif
                    @endforeach
                </div>
            @endforeach
        @endif
    </main>

    <x-public.footer />
</x-layouts.public>
