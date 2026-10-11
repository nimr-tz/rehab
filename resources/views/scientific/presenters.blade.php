@php use App\Services\PresenterService; @endphp

<x-layouts.portal title="Presenters">
    <x-slot:header>
        <x-page-header eyebrow="Scientific committee" title="Presenters"
            description="Every accepted abstract and whether its presenter has registered and paid. Presenters who are not confirmed by the summit cannot collect a badge.">
            <x-button variant="secondary" :href="route('scientific.presenters.export')" icon="download">Export CSV</x-button>
            @if ($total - ($counts[PresenterService::CONFIRMED] ?? 0) > 0)
                <form method="POST" action="{{ route('scientific.presenters.remind-all') }}" x-data @submit="if (! confirm('Email every presenter who is not confirmed yet?')) $event.preventDefault()">
                    @csrf
                    <x-button icon="mail">Remind all not confirmed</x-button>
                </form>
            @endif
        </x-page-header>
    </x-slot:header>

    @error('presenters') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

    <div class="grid gap-4 sm:grid-cols-4">
        @foreach (PresenterService::STATES as $key => [$label, $tone])
            <x-stat :label="$label" :value="$counts[$key] ?? 0" :href="route('scientific.presenters.index', ['state' => $key])"
                :tint="match ($tone) { 'success' => 'bg-emerald-50 text-emerald-700', 'danger' => 'bg-red-50 text-red-700', 'warning' => 'bg-amber-50 text-amber-700', default => 'bg-brand-50 text-brand-700' }"
                :icon="match ($key) { PresenterService::CONFIRMED => 'check-circle', PresenterService::NOT_REGISTERED => 'x-circle', default => 'clock' }" />
        @endforeach
    </div>

    <x-card :padding="false">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-4">
            <p class="text-sm text-ink-600">
                {{ $state ? PresenterService::STATES[$state][0].': '.$rows->count() : 'All '.$total.' accepted abstracts' }}
            </p>
            @if ($state)<a href="{{ route('scientific.presenters.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Show all</a>@endif
        </div>

        @if ($rows->isEmpty())
            <x-empty icon="users" title="No presenters here">Accepted abstracts appear here with their presenter.</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr><th class="px-5 py-3">Abstract</th><th class="px-5 py-3">Presenter</th><th class="px-5 py-3">Registration</th><th class="px-5 py-3">Session</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($rows as $row)
                            @php [$label, $tone] = PresenterService::STATES[$row['state']]; $abstract = $row['abstract']; @endphp
                            <tr class="hover:bg-ink-50/60">
                                <td class="max-w-[18rem] px-5 py-3.5">
                                    <a href="{{ route('scientific.abstracts.show', $abstract) }}" class="block truncate font-semibold text-ink-900 hover:text-brand-700"><span class="font-mono text-brand-700">{{ $abstract->code }}</span> · {{ $abstract->title }}</a>
                                    <span class="text-xs text-ink-500">{{ $abstract->topic->name }}</span>
                                </td>
                                <td class="px-5 py-3.5"><p class="font-medium text-ink-900">{{ $row['name'] }}</p><p class="text-xs text-ink-500">{{ $row['email'] ?? 'No email given' }}</p></td>
                                <td class="px-5 py-3.5">
                                    <x-status :tone="$tone">{{ $label }}</x-status>
                                    @if ($row['registration'])<p class="mt-1 font-mono text-xs text-ink-500">{{ $row['registration']->reference }}</p>@endif
                                </td>
                                <td class="px-5 py-3.5 text-ink-700">{{ $abstract->sessions->first()?->starts_at->format('D j M, H:i') ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-right">
                                    @if ($row['state'] !== PresenterService::CONFIRMED && $row['email'])
                                        <form method="POST" action="{{ route('scientific.presenters.remind', $abstract) }}">
                                            @csrf
                                            <x-button variant="ghost" size="sm" icon="mail">Remind</x-button>
                                        </form>
                                        @if ($abstract->presenter_reminded_at)<p class="text-xs text-ink-400">Last {{ $abstract->presenter_reminded_at->format('j M') }}</p>@endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layouts.portal>
