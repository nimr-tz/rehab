@php
    $tiles = [
        ['Registered', $stats['registered'], '#024f6d'],
        ['Confirmed', $stats['confirmed'], '#45582e'],
        ['Badges printed', $stats['printed'], '#1b7fa3'],
        ['Ready to print', $stats['ready'], '#d69a00'],
        ['Checked in', $stats['checkedIn'], '#bd520a'],
        ['Awaiting payment', $stats['awaiting'], '#d9574b'],
    ];
    $catMax = max(1, $byCategory->max() ?? 1);
@endphp

<x-layouts.portal title="Registration desk">
    <x-slot:header>
        <x-page-header title="Registration desk" description="Badge printing and check-in readiness">
            <x-button size="sm" :href="route('desk.queue')" icon="printer">Print queue</x-button>
        </x-page-header>
    </x-slot:header>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        @foreach ($tiles as [$label, $value, $colour])
            <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-soft" style="border-top: 4px solid {{ $colour }}">
                <p class="text-2xl font-extrabold tracking-tight text-ink-900">{{ number_format($value) }}</p>
                <p class="text-sm font-medium text-ink-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    @error('check_in') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

    <div class="grid gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <x-card :padding="false">
            <div class="flex flex-col gap-3 px-6 pb-4 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-bold text-ink-900">Registry</h2>
                <form method="GET" class="relative sm:w-80">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" />
                    <input name="q" value="{{ $search }}" autofocus placeholder="Name, institution, reference or QR" class="field h-11 pl-11 text-sm">
                </form>
            </div>
            <div class="flex flex-wrap gap-2 px-6 pb-4">
                @foreach ($filters as $key => $label)
                    <a href="{{ route('desk.index', array_filter(['filter' => $key === 'all' ? null : $key, 'q' => $search])) }}"
                       @class(['rounded-full px-3.5 py-1.5 text-xs font-bold transition', 'bg-brand-700 text-white' => $filter === $key, 'bg-ink-100 text-ink-700 hover:bg-ink-200' => $filter !== $key])>{{ $label }}</a>
                @endforeach
            </div>

            @if ($registry->isEmpty())
                <x-empty icon="search" title="No one found" class="!py-8">Check the spelling, or search by email, reference or QR code.</x-empty>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left text-sm">
                        <thead class="border-y border-ink-100 text-[11px] font-bold uppercase tracking-[0.14em] text-ink-500">
                            <tr><th class="px-6 py-3">Name</th><th class="px-3 py-3">Reference</th><th class="px-3 py-3">Status</th><th class="px-6 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($registry as $registration)
                                <tr>
                                    <td class="max-w-[16rem] px-6 py-3.5">
                                        <p class="truncate font-semibold text-ink-900">{{ $registration->user->name }}</p>
                                        <p class="truncate text-xs text-ink-500">{{ $registration->category->name }} · {{ $registration->user->institution }}</p>
                                    </td>
                                    <td class="px-3 py-3.5 font-mono text-xs font-bold text-brand-700">{{ $registration->reference }}</td>
                                    <td class="px-3 py-3.5">
                                        @if ($registration->checked_in_at)
                                            <x-status tone="success">Checked in {{ $registration->checked_in_at->format('D H:i') }}</x-status>
                                        @elseif ($registration->badge_printed_at)
                                            <x-status tone="info">Printed</x-status>
                                        @else
                                            <x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3.5">
                                        @if ($registration->isConfirmed())
                                            <div class="flex justify-end gap-1.5">
                                                <x-button variant="secondary" size="sm" :href="route('desk.badge', $registration)" icon="printer" :title="$registration->badge_printed_at ? 'Reprint badge' : 'Print badge'">{{ $registration->badge_printed_at ? 'Reprint' : 'Print' }}</x-button>
                                                @unless ($registration->checked_in_at)
                                                    <form method="POST" action="{{ route('desk.check-in', $registration) }}">
                                                        @csrf
                                                        <x-button variant="success" size="sm" icon="check">Check in</x-button>
                                                    </form>
                                                @endunless
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 px-6 py-3">{{ $registry->links() }}</div>
            @endif
        </x-card>

        <div class="space-y-5">
            <x-card title="Print queue">
                <div class="flex items-center justify-between gap-3 rounded-2xl bg-canvas p-4">
                    <div>
                        <p class="font-semibold text-ink-900">Confirmed, not yet printed</p>
                        <p class="text-sm text-ink-500">{{ number_format($stats['ready']) }} badges</p>
                    </div>
                    <x-button size="sm" :href="route('desk.queue')" icon="printer">Open</x-button>
                </div>
            </x-card>

            <section class="rounded-card bg-brand-700 p-6 text-white shadow-lift">
                <h2 class="text-lg font-bold">Check-in readiness</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($readiness as [$label, $ok, $note])
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full {{ $ok ? 'bg-sun-400 text-ink-900' : 'bg-coral-400 text-ink-900' }}">
                                <x-icon :name="$ok ? 'check' : 'warning'" class="h-3.5 w-3.5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold">{{ $label }}</span>
                                <span class="block text-xs text-brand-100">{{ $note }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <x-card title="By category">
                <x-chart.bars :rows="$byCategory->map(fn ($n, $name) => ['label' => $name, 'value' => $n])->values()->all()" :max="$catMax" color="#024f6d" />
            </x-card>
        </div>
    </div>
</x-layouts.portal>
