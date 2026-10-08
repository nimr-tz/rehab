<x-layouts.portal title="Programme & CPD">
    <x-slot:header>
        <x-page-header eyebrow="Scientific committee" title="Programme & CPD"
            description="The sessions of the summit and the CPD points each one earns. Attendance is scanned at each session by the staff app.">
            <x-button variant="secondary" :href="route('programme')" icon="eye">Public programme</x-button>
            <x-button :href="route('scientific.programme.create')" icon="plus">Add a session</x-button>
        </x-page-header>
    </x-slot:header>

    @error('session') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

    @if ($unpointed > 0)
        <x-alert tone="warning">{{ $unpointed }} {{ \Illuminate\Support\Str::plural('session', $unpointed) }} {{ $unpointed === 1 ? 'has' : 'have' }} no CPD points yet, so attending {{ $unpointed === 1 ? 'it' : 'them' }} earns nothing.</x-alert>
    @endif

    @if ($days->isEmpty())
        <x-card>
            <x-empty icon="calendar" title="No sessions yet">
                Add the summit's sessions, with their halls, times and CPD points.
                <x-slot:action><x-button :href="route('scientific.programme.create')" icon="plus">Add a session</x-button></x-slot:action>
            </x-empty>
        </x-card>
    @else
        <p class="text-sm text-ink-600">A participant who attends every session earns <span class="font-bold text-ink-900">{{ rtrim(rtrim(number_format($totalPoints, 2), '0'), '.') }} CPD points</span>.</p>

        @foreach ($days as $date => $sessions)
            <x-card :padding="false">
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-ink-100 px-6 py-4">
                    <h2 class="text-lg font-bold text-ink-900">Day {{ $loop->iteration }} · {{ \Illuminate\Support\Carbon::parse($date)->format('l j F') }}</h2>
                    <p class="text-sm text-ink-500">{{ rtrim(rtrim(number_format($sessions->sum(fn ($s) => $s->points()), 2), '0'), '.') }} CPD points available</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink-500">
                            <tr><th class="px-6 py-3">Time</th><th class="px-3 py-3">Session</th><th class="px-3 py-3">Hall</th><th class="px-3 py-3">CPD points</th><th class="px-3 py-3">Attended</th><th class="px-6 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100 border-t border-ink-100">
                            @foreach ($sessions as $session)
                                <tr @class(['bg-ink-50/60 text-ink-500' => ! $session->isScannable()])>
                                    <td class="whitespace-nowrap px-6 py-3.5 font-mono text-xs text-ink-700">{{ $session->starts_at->format('H:i') }}–{{ $session->ends_at->format('H:i') }}</td>
                                    <td class="max-w-[22rem] px-3 py-3.5">
                                        <p class="truncate font-semibold text-ink-900">{{ $session->title }}</p>
                                        <p class="truncate text-xs text-ink-500">{{ \App\Models\ProgrammeSession::KINDS[$session->kind] ?? $session->kind }}{{ $session->topic ? ' · '.$session->topic->name : '' }}{{ $session->chair ? ' · Chair: '.$session->chair : '' }}</p>
                                    </td>
                                    <td class="px-3 py-3.5 text-ink-700">{{ $session->hall ?? '—' }}</td>
                                    <td class="px-3 py-3.5">
                                        @if (! $session->isScannable())
                                            <span class="text-xs text-ink-400">Not scanned</span>
                                        @elseif ($session->cpd_points === null)
                                            <span class="text-xs font-semibold text-ember-700">Not set</span>
                                        @else
                                            <span class="font-bold text-ink-900">{{ rtrim(rtrim(number_format($session->points(), 2), '0'), '.') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3.5 text-ink-700">{{ $session->isScannable() ? number_format($session->attendances_count) : '' }}</td>
                                    <td class="px-6 py-3.5 text-right">
                                        <x-button variant="ghost" size="sm" :href="route('scientific.programme.edit', $session)" icon="pencil">Edit</x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endforeach
    @endif
</x-layouts.portal>
