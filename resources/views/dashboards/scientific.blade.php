@php
    use App\Support\Palette;

    $deadline = $summit->edition()?->abstract_deadline;
    $daysToDeadline = $deadline && $deadline->isFuture() ? (int) today()->diffInDays($deadline) : null;
    $peak = collect($daily)->max('value');
    $pipelineMax = max(1, $pipeline[0][1]);
    $pipelineColours = ['#024f6d', '#1b7fa3', '#d69a00', '#bd520a', '#45582e'];
    $heatMax = max(1, collect($topics)->flatMap(fn ($t) => array_values($t['cells']))->max());
@endphp

<x-layouts.portal title="Conference readiness">
    <x-slot:header>
        <x-page-header title="Conference readiness"
            :description="'Scientific programme'.($daysToDeadline !== null ? ' · '.$daysToDeadline.' days to the abstract deadline' : '')" />
    </x-slot:header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi label="Abstracts submitted" :value="number_format($submitted)" :hint="'+'.$thisWeek.' this week'" hint-tone="good" :progress="100" bar="#45582e" :href="route('scientific.abstracts.index')" />
        <x-kpi label="Assigned to reviewers" :value="number_format($assigned)" :hint="($submitted ? round($assigned / $submitted * 100) : 0).'% of submissions'" :progress="$submitted ? $assigned / $submitted * 100 : 0" />
        <x-kpi label="Reviews completed" :value="number_format($reviewsDone)" :hint="'of '.$reviewsTotal.' · '.($reviewsTotal ? round($reviewsDone / $reviewsTotal * 100) : 0).'%'" hint-tone="warning" :progress="$reviewsTotal ? $reviewsDone / $reviewsTotal * 100 : 0" bar="#d69a00" :href="route('scientific.reviewers')" />
        <x-kpi label="Decisions made" :value="number_format($decided)" :hint="$ready->count().' waiting in the queue'" :hint-tone="$ready->isEmpty() ? 'muted' : 'danger'" :progress="$submitted ? $decided / $submitted * 100 : 0" bar="#bd520a" :href="route('scientific.abstracts.index', ['status' => 'ready'])" />
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <x-card>
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-lg font-bold text-ink-900">Daily submissions, last 30 days</h2>
                <span class="text-sm text-ink-500">Peak: <span class="font-bold text-ember-600">{{ $peak }}</span></span>
            </div>
            <x-chart.columns class="mt-5" :data="$daily" :highlight="7" :height="200" />
            <p class="mt-3 flex gap-4 text-xs text-ink-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-200"></span>Earlier</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-700"></span>Last 7 days</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-ember-600"></span>Today</span>
            </p>
        </x-card>

        <x-card>
            <h2 class="text-lg font-bold text-ink-900">Review pipeline</h2>
            <ul class="mt-5 space-y-4">
                @foreach ($pipeline as $i => [$label, $value])
                    <li>
                        <div class="flex justify-between text-sm"><span class="font-medium text-ink-700">{{ $label }}</span><span class="font-bold text-ink-900">{{ $value }}</span></div>
                        <div class="mt-1.5 h-3 overflow-hidden rounded-md bg-ink-100" title="{{ $label }}: {{ $value }}">
                            <div class="h-full rounded-md" style="width: {{ max(2, $value / $pipelineMax * 100) }}%; background: {{ $pipelineColours[$i] }}"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>

    {{-- Decision queue --}}
    <x-card :padding="false">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 pb-4 pt-6">
            <div><h2 class="text-lg font-bold text-ink-900">Decision queue</h2><p class="text-sm text-ink-500">Fully reviewed, awaiting your decision</p></div>
            <a href="{{ route('scientific.abstracts.index', ['status' => 'ready']) }}" class="text-sm font-bold text-brand-700 hover:underline">{{ $ready->count() }} waiting →</a>
        </div>
        @if ($ready->isEmpty())
            <x-empty icon="check-circle" title="Nothing waiting" class="!py-8">Every fully reviewed abstract has a decision.</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="border-y border-ink-100 text-[11px] font-bold uppercase tracking-[0.14em] text-ink-500">
                        <tr><th class="px-6 py-3">ID</th><th class="px-3 py-3">Abstract</th><th class="px-3 py-3">Score</th><th class="px-3 py-3">Agreement</th><th class="px-6 py-3">Decision</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($ready->take(6) as $abstract)
                            @php
                                $score = $abstract->averageScore();
                                $band = \App\Support\Rubric::band($score);
                                [$agree, $dot] = $agreement($abstract);
                                $recs = $abstract->reviews->filter->isComplete()->map(fn ($r) => strtolower($r->recommendation->label()))->countBy()->map(fn ($n, $r) => $n.'× '.$r)->implode(', ');
                            @endphp
                            <tr>
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-ink-500">{{ $abstract->blindId() }}</td>
                                <td class="max-w-md px-3 py-4">
                                    <a href="{{ route('scientific.abstracts.show', $abstract) }}" class="font-semibold text-ink-900 hover:text-brand-700">{{ $abstract->title }}</a>
                                    <p class="text-xs text-ink-500">{{ $abstract->topic->name }} · {{ $recs }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="text-xl font-extrabold tabular-nums {{ \App\Support\Rubric::scoreClass($score) }}">{{ $score !== null ? round($score) : '—' }}</span>
                                    @if ($band)<span class="block text-[11px] font-semibold text-ink-500">{{ $band['label'] }}</span>@endif
                                </td>
                                <td class="px-3 py-4"><span class="inline-flex items-center gap-1.5 font-medium text-ink-700"><span class="h-2 w-2 rounded-full {{ $dot }}"></span>{{ $agree }}</span></td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-1.5">
                                        @foreach (['oral' => 'Oral', 'poster' => 'Poster', 'reject' => 'Reject'] as $value => $label)
                                            <form method="POST" action="{{ route('scientific.abstracts.decide', $abstract) }}">
                                                @csrf
                                                <input type="hidden" name="decision" value="{{ $value }}">
                                                <button @class([
                                                    'rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                                    'bg-olive-700 text-white hover:bg-olive-800' => $value !== 'reject',
                                                    'border border-ink-200 text-ink-600 hover:border-red-300 hover:text-red-700' => $value === 'reject',
                                                ])>{{ $label }}</button>
                                            </form>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <x-card title="Abstracts by topic and status">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] border-separate border-spacing-1 text-sm">
                    <thead>
                        <tr class="text-xs text-ink-500"><th></th>@foreach (array_keys($topics->first()['cells'] ?? []) as $col)<th class="pb-1 font-semibold">{{ $col }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @foreach ($topics as $topic)
                            <tr>
                                <th class="pr-3 text-left font-semibold text-brand-700">{{ \Illuminate\Support\Str::limit($topic['name'], 34) }}</th>
                                @foreach ($topic['cells'] as $col => $value)
                                    @php [$bg, $fg] = Palette::heat($value, $heatMax); @endphp
                                    <td class="h-10 rounded-lg text-center font-bold" style="background: {{ $bg }}; color: {{ $fg }}" title="{{ $topic['name'] }} · {{ $col }}: {{ $value }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="Live activity">
            <ul class="space-y-4">
                @forelse ($activity as $event)
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $event['dot'] }}"></span>
                        <a href="{{ $event['url'] }}" class="min-w-0 hover:text-brand-700">
                            <span class="block text-sm text-ink-800">{{ $event['text'] }}</span>
                            <span class="block text-xs text-ink-500">{{ $event['at']->diffForHumans() }}</span>
                        </a>
                    </li>
                @empty
                    <li class="text-sm text-ink-500">No activity yet.</li>
                @endforelse
            </ul>
        </x-card>
    </div>

    <x-card>
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-ink-900">Reviewer workload</h2>
            <a href="{{ route('scientific.reviewers') }}" class="text-sm font-bold text-brand-700 hover:underline">All reviewers →</a>
        </div>
        <div class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($workload as $reviewer)
                @php $pct = $reviewer->assigned ? $reviewer->done / $reviewer->assigned * 100 : 0; @endphp
                <div>
                    <div class="flex justify-between gap-2 text-sm">
                        <span class="truncate font-semibold text-ink-900">{{ $reviewer->name }}</span>
                        <span class="shrink-0 font-semibold {{ $reviewer->overdue ? 'text-red-700' : 'text-ink-700' }}">{{ $reviewer->done }} / {{ $reviewer->assigned }}@if ($reviewer->overdue) · overdue @endif</span>
                    </div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full {{ $reviewer->overdue ? 'bg-ember-600' : 'bg-brand-700' }}" style="width: {{ $pct }}%"></div></div>
                </div>
            @endforeach
        </div>
    </x-card>
</x-layouts.portal>
