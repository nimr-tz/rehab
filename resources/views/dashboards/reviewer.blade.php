@php
    use App\Support\Palette;
    use App\Support\Rubric;

    $left = $pending->count();
    $bandMax = max(1, $bands->max('you'), $bands->max('panel'));
@endphp

<x-layouts.portal title="Review desk">
    <x-slot:header>
        <x-page-header title="Your review desk"
            :description="$left.' '.\Illuminate\Support\Str::plural('abstract', $left).' left'.($deadline ? ' · reviews due '.$deadline->format('j F') : '')" />
    </x-slot:header>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
        <section class="flex flex-col gap-6 rounded-[26px] bg-brand-700 p-7 text-white shadow-lift sm:flex-row sm:items-center">
            <x-chart.ring :percent="$percent" label="complete" :size="140" />
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-sun-400">{{ $deadline ? 'Reviews due '.$deadline->format('j M') : 'Your reviews' }}</p>
                <p class="mt-2 text-2xl font-extrabold leading-snug">
                    @if ($left === 0)
                        All caught up. Thank you!
                    @elseif ($daysLeft !== null)
                        {{ $daysLeft }} days left, {{ $left }} {{ \Illuminate\Support\Str::plural('abstract', $left) }} to go
                    @else
                        {{ $left }} {{ \Illuminate\Support\Str::plural('abstract', $left) }} to review
                    @endif
                </p>
                <p class="mt-2 text-sm text-brand-100">
                    @if ($left && $daysLeft)
                        About {{ max(1, (int) ceil($left / max(1, $daysLeft / 7))) }} a week keeps you on track.
                    @else
                        New assignments arrive by email and appear here.
                    @endif
                </p>
            </div>
        </section>

        <div class="grid grid-cols-2 gap-4">
            <x-kpi label="Assigned" :value="$assignments->count()" :hint="$assignments->pluck('abstract.topic.name')->unique()->take(1)->implode('')" />
            <x-kpi label="Completed" :value="$done->count()" :hint="$left.' remaining'" hint-tone="good" />
            <x-kpi label="Overdue" :value="$overdue" :hint="$overdue ? 'Past their due date' : 'Nothing overdue'" :hint-tone="$overdue ? 'danger' : 'muted'" />
            <x-kpi label="Your average" :value="$average !== null ? $average.'/'.Rubric::max() : '—'" :hint="$panelAverage !== null ? 'Panel average '.$panelAverage.'/'.Rubric::max() : null" hint-tone="warning" />
        </div>
    </div>

    <x-card :padding="false">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 pb-4 pt-6">
            <h2 class="text-lg font-bold text-ink-900">Assigned to you</h2>
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-500"><x-icon name="eye-slash" class="h-4 w-4" /> Blind review: author names hidden</span>
        </div>
        @if ($pending->isEmpty())
            <x-empty icon="check-circle" title="Nothing waiting" class="!py-8">New assignments will appear here.</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="border-y border-ink-100 text-[11px] font-bold uppercase tracking-[0.14em] text-ink-500">
                        <tr><th class="px-6 py-3">ID</th><th class="px-3 py-3">Title</th><th class="px-3 py-3">Due</th><th class="px-6 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($pending as $assignment)
                            @php $days = $assignment->due_on ? (int) today()->diffInDays($assignment->due_on, false) : null; @endphp
                            <tr>
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-ink-500">{{ $assignment->abstract->blindId() }}</td>
                                <td class="px-3 py-4">
                                    <p class="font-semibold text-ink-900">{{ $assignment->abstract->title }}</p>
                                    <p class="text-xs text-ink-500">{{ $assignment->abstract->topic->name }} · assigned {{ $assignment->created_at->format('j M') }}</p>
                                </td>
                                <td class="px-3 py-4 font-bold {{ $days !== null && $days < 0 ? 'text-red-700' : ($days !== null && $days <= 7 ? 'text-ember-600' : 'text-ink-700') }}">
                                    {{ $days === null ? '—' : ($days < 0 ? abs($days).' days overdue' : 'In '.$days.' days') }}
                                </td>
                                <td class="px-6 py-4 text-right"><x-button size="sm" :href="route('reviews.edit', $assignment)">Start</x-button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-card>
            <h2 class="text-lg font-bold text-ink-900">Your scores vs the panel</h2>
            <p class="mt-1 text-xs text-ink-500">Share of reviews in each total-score band (out of {{ Rubric::max() }})</p>
            <div class="mt-5 flex h-44 items-end gap-3">
                @foreach ($bands as $band)
                    <div class="flex flex-1 flex-col items-center gap-1.5">
                        <div class="flex h-36 w-full items-end justify-center gap-1">
                            <div class="w-1/2 rounded-t-[4px] bg-[#1b7fa3]" style="height: {{ max(2, $band['you'] / $bandMax * 100) }}%" title="You · {{ $band['label'] }}: {{ $band['you'] }}%"></div>
                            <div class="w-1/2 rounded-t-[4px] bg-[#d69a00]" style="height: {{ max(2, $band['panel'] / $bandMax * 100) }}%" title="Panel · {{ $band['label'] }}: {{ $band['panel'] }}%"></div>
                        </div>
                        <span class="text-[11px] text-ink-500">{{ $band['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 flex gap-4 text-xs text-ink-600">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#1b7fa3]"></span>You · avg {{ $average ?? '—' }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#d69a00]"></span>Panel · avg {{ $panelAverage ?? '—' }}</span>
            </p>
        </x-card>

        <x-card title="Scoring rubric">
            <ul class="space-y-4">
                @foreach (Rubric::criteria() as $criterion)
                    <li>
                        <div class="flex justify-between text-sm"><span class="font-medium text-ink-800">{{ $criterion['label'] }}</span><span class="font-bold text-ink-900">{{ $criterion['max'] }} pts</span></div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full" style="width: {{ $criterion['max'] / Rubric::max() * 100 }}%; background: {{ Palette::categorical($loop->index) }}"></div></div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-5 flex flex-wrap gap-1.5">
                @foreach (Rubric::bands() as $band)
                    <x-status :tone="$band['tone']">{{ $band['min'] > 0 ? '≥'.$band['min'] : '<'.Rubric::bands()[$loop->index - 1]['min'] }} · {{ $band['label'] }}</x-status>
                @endforeach
            </div>
        </x-card>

        <x-card title="Recently completed" :padding="false">
            <ul class="divide-y divide-ink-100">
                @forelse ($done->take(5) as $review)
                    <li>
                        <a href="{{ route('reviews.edit', $review) }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-ink-50">
                            <span>
                                <span class="block font-mono text-xs font-bold text-ink-700">{{ $review->abstract->blindId() }}</span>
                                <span class="block text-xs text-ink-500">{{ $review->recommendation->label() }} · {{ $review->completed_at->format('j M') }}</span>
                            </span>
                            <span class="text-xl font-extrabold tabular-nums {{ Rubric::scoreClass($review->totalScore()) }}">{{ $review->totalScore() }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-6 py-5 text-sm text-ink-500">Your completed reviews appear here.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-layouts.portal>
