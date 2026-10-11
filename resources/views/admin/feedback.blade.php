<x-layouts.portal title="Feedback">
    <x-slot:header>
        <x-page-header eyebrow="Administration" title="Feedback"
            :description="($open ? 'The survey is open until '.$closes->format('j F Y').'. ' : '').'Answers are anonymous. Everyone who attended was invited by email the day after the summit.'">
            @if ($responses)
                <x-button variant="secondary" size="sm" :href="route('admin.feedback.export')" icon="download">Export CSV</x-button>
            @endif
        </x-page-header>
    </x-slot:header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Responses" :value="$responses" :hint="$attended ? round($responses / $attended * 100).'% of '.$attended.' who attended' : null" icon="clipboard" />
        <x-stat label="Would recommend (0–10)" :value="$recommend ?? '—'" :hint="$nps !== null ? 'Net promoter score '.($nps > 0 ? '+' : '').$nps : null" icon="star" tint="bg-sun-100 text-sun-800" />
        <x-stat label="Plan to come next year" :value="$nextYear['yes'] ?? 0" :hint="($nextYear['maybe'] ?? 0).' maybe · '.($nextYear['no'] ?? 0).' no'" icon="calendar" tint="bg-emerald-50 text-emerald-700" />
    </div>

    @if (! $responses)
        <x-card><x-empty icon="clipboard" title="No answers yet">The survey opens on the summit's last day. Results appear here as people answer.</x-empty></x-card>
    @else
        <x-card title="Ratings" description="Average out of 5, and how many gave each score.">
            <div class="space-y-4">
                @foreach ($ratings as $rating)
                    <div class="grid items-center gap-3 sm:grid-cols-[14rem_4rem_1fr]">
                        <p class="text-sm font-semibold text-ink-900">{{ $rating['label'] }}</p>
                        <p class="text-lg font-extrabold text-brand-700">{{ $rating['average'] }}</p>
                        <div class="flex h-6 overflow-hidden rounded-lg bg-ink-50" role="img" aria-label="{{ $rating['spread']->map(fn ($n, $score) => $n.' gave '.$score)->implode(', ') }}">
                            @foreach ($rating['spread'] as $score => $n)
                                @continue($n === 0)
                                <div class="grid place-items-center text-[11px] font-bold {{ $score >= 4 ? 'bg-emerald-500 text-white' : ($score === 3 ? 'bg-amber-300 text-ink-900' : 'bg-red-400 text-white') }}"
                                     style="width: {{ $n / $responses * 100 }}%" title="{{ $n }} gave {{ $score }}">{{ $n / $responses >= 0.08 ? $score : '' }}</div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Comments" :padding="false">
            @if ($comments->isEmpty())
                <p class="px-6 py-5 text-sm text-ink-500">Nobody has written a comment yet.</p>
            @else
                <ul class="divide-y divide-ink-100">
                    @foreach ($comments as $response)
                        <li class="space-y-2 px-6 py-4 text-sm">
                            @foreach (config('feedback.comments') as $key => $question)
                                @if (! empty($response->comments[$key]))
                                    <p><span class="block text-xs font-semibold uppercase tracking-wider text-ink-500">{{ $question }}</span><span class="whitespace-pre-line text-ink-800">{{ $response->comments[$key] }}</span></p>
                                @endif
                            @endforeach
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-ink-100 px-5 py-3">{{ $comments->links() }}</div>
            @endif
        </x-card>
    @endif
</x-layouts.portal>
