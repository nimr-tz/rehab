<x-layouts.portal title="Fee waivers">
    <x-slot:header>
        <x-page-header eyebrow="Finance" title="Fee waivers"
            description="Waive the whole registration fee, which confirms the registration at once, or part of it, which leaves the rest to pay. The participant is emailed either way.">
            <x-button variant="secondary" size="sm" :href="route('finance.waivers.export')" icon="download">Export CSV</x-button>
        </x-page-header>
    </x-slot:header>

    @error('waiver') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Full waivers" :value="$stats['full']" icon="check-circle" tint="bg-emerald-50 text-emerald-700" />
        <x-stat label="Partial waivers" :value="$stats['partial']" icon="banknotes" tint="bg-sun-100 text-sun-800" />
        <x-stat label="Fees waived" :value="collect(['TZS', 'USD'])->filter(fn ($c) => isset($stats['totals'][$c]))->map(fn ($c) => $c.' '.number_format((float) $stats['totals'][$c]))->implode(' · ') ?: 'None yet'" icon="ticket" tint="bg-brand-50 text-brand-700" />
    </div>

    {{-- Find the participant --}}
    <x-card title="Waive a fee" description="Find the participant by name, email or registration reference. Only registrations still awaiting payment can be waived.">
        <form method="GET" class="relative max-w-xl">
            <input type="hidden" name="show" value="{{ $show }}">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
            <input name="q" value="{{ $search }}" placeholder="e.g. Neema Mushi or RH27-000123" class="field h-11 pl-12" autofocus>
        </form>

        @if ($search !== '')
            @if ($candidates->isEmpty())
                <p class="mt-4 text-sm text-ink-500">No registration awaiting payment matches “{{ $search }}”. Someone who has paid, or whose payment is being verified, cannot have their fee waived.</p>
            @else
                <ul class="mt-4 divide-y divide-ink-100 rounded-2xl border border-ink-100">
                    @foreach ($candidates as $registration)
                        <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-ink-900">{{ $registration->user->name }} <span class="font-mono text-xs font-semibold text-ink-500">· {{ $registration->reference }}</span></p>
                                <p class="text-xs text-ink-500">{{ $registration->user->institution }} · {{ $registration->category->name }} · {{ $registration->formattedAmount() }}</p>
                            </div>
                            <x-button size="sm" :href="route('finance.waivers.create', $registration)">Waive fee</x-button>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </x-card>

    {{-- Waivers given --}}
    <x-card :padding="false">
        <div class="flex flex-wrap gap-2 border-b border-ink-100 p-4">
            @foreach (['active' => 'Active', 'withdrawn' => 'Withdrawn'] as $value => $label)
                <a href="{{ route('finance.waivers.index', ['show' => $value]) }}"
                   @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-brand-700 text-white' => $show === $value, 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50' => $show !== $value])>{{ $label }}</a>
            @endforeach
        </div>

        @if ($waivers->isEmpty())
            <x-empty icon="ticket" :title="$show === 'active' ? 'No waivers yet' : 'No withdrawn waivers'">
                {{ $show === 'active' ? 'Waivers you give appear here.' : 'Waivers that were withdrawn appear here, with the reason.' }}
            </x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr><th class="px-5 py-3">Participant</th><th class="px-5 py-3">Waived</th><th class="px-5 py-3">Still due</th><th class="px-5 py-3">Reason</th><th class="px-5 py-3">{{ $show === 'active' ? 'Granted' : 'Withdrawn' }}</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($waivers as $waiver)
                            @php $registration = $waiver->registration; @endphp
                            <tr class="align-top">
                                <td class="px-5 py-3.5">
                                    <p class="font-medium text-ink-900">{{ $registration->user->name }}</p>
                                    <p class="text-xs text-ink-500"><span class="font-mono">{{ $registration->reference }}</span> · {{ $registration->category->name }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-ink-900">{{ $waiver->formattedAmount() }}</p>
                                    <p class="text-xs text-ink-500">{{ $waiver->isFull() ? 'Full fee' : 'of '.$registration->formattedAmount() }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if ($waiver->isActive())
                                        @if ($registration->amountDue() > 0)
                                            <p class="font-medium text-ink-900">{{ $registration->formattedDue() }}</p>
                                        @endif
                                        <x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status>
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="max-w-xs px-5 py-3.5">
                                    <p class="font-medium text-ink-900">{{ $waiver->reason->label() }}</p>
                                    @if ($waiver->note)<p class="text-xs text-ink-500">{{ $waiver->note }}</p>@endif
                                </td>
                                <td class="px-5 py-3.5 text-ink-700">
                                    @if ($waiver->isActive())
                                        {{ $waiver->created_at->format('j M Y') }}<p class="text-xs text-ink-500">by {{ $waiver->grantedBy?->name ?? '—' }}</p>
                                    @else
                                        {{ $waiver->revoked_at->format('j M Y') }}<p class="text-xs text-ink-500">by {{ $waiver->revokedBy?->name ?? '—' }}: {{ $waiver->revoke_reason }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if ($waiver->isActive())
                                        <details class="group inline-block text-left">
                                            <summary class="cursor-pointer list-none text-xs font-semibold text-red-700 hover:underline">Withdraw</summary>
                                            <form method="POST" action="{{ route('finance.waivers.withdraw', $waiver) }}" class="mt-2 w-64 space-y-2">
                                                @csrf
                                                <input name="revoke_reason" required minlength="5" maxlength="255" class="field h-10 text-sm" placeholder="Why? The participant is told.">
                                                <x-button size="sm" variant="secondary" class="w-full text-red-700">Withdraw waiver</x-button>
                                            </form>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-ink-100 px-5 py-3">{{ $waivers->links() }}</div>
        @endif
    </x-card>
</x-layouts.portal>
