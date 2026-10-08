@php $user = $registration->user; @endphp

<x-layouts.portal :title="$user->name">
    <x-slot:header>
        <x-page-header eyebrow="Participant" :title="$user->name" :back="route('admin.participants.index')">
            <x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status>
            @if ($registration->isConfirmed())
                <x-button variant="secondary" size="sm" :href="route('desk.badge', $registration)" icon="download">Badge</x-button>
            @endif
        </x-page-header>
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Contact">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-ink-500">Email</dt><dd class="mt-0.5 text-ink-900">{{ $user->email }}</dd></div>
                <div><dt class="text-ink-500">Phone</dt><dd class="mt-0.5 text-ink-900">{{ $user->phone }}</dd></div>
                <div><dt class="text-ink-500">Country</dt><dd class="mt-0.5 text-ink-900">{{ $user->countryName() }}</dd></div>
                <div><dt class="text-ink-500">Institution</dt><dd class="mt-0.5 text-ink-900">{{ $user->institution ?? '—' }}</dd></div>
                <div><dt class="text-ink-500">Profession</dt><dd class="mt-0.5 text-ink-900">{{ $user->profession ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Registration">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-ink-500">Reference</dt><dd class="mt-0.5 font-mono font-semibold text-ink-900">{{ $registration->reference }}</dd></div>
                <div><dt class="text-ink-500">Category · fee</dt><dd class="mt-0.5 text-ink-900">{{ $registration->category->name }} · {{ $registration->formattedAmount() }}</dd></div>
                @if ($registration->activeWaiver)
                    <div><dt class="text-ink-500">Fee waiver</dt><dd class="mt-0.5 text-ink-900">{{ $registration->isFullyWaived() ? 'Full fee' : $registration->activeWaiver->formattedAmount().' waived · pays '.$registration->formattedDue() }}<span class="block text-xs text-ink-500">{{ $registration->activeWaiver->reason->label() }}@if ($registration->activeWaiver->note) · {{ $registration->activeWaiver->note }}@endif</span></dd></div>
                @endif
                <div><dt class="text-ink-500">Registered</dt><dd class="mt-0.5 text-ink-900">{{ $registration->created_at->format('j M Y') }}</dd></div>
                <div><dt class="text-ink-500">Confirmed</dt><dd class="mt-0.5 text-ink-900">{{ $registration->confirmed_at?->format('j M Y') ?? '—' }}</dd></div>
                <div><dt class="text-ink-500">Checked in</dt><dd class="mt-0.5 text-ink-900">{{ $registration->checked_in_at?->format('j M Y, H:i') ?? 'Not yet' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Needs">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-ink-500">Name on badge</dt><dd class="mt-0.5 text-ink-900">{{ $registration->displayName() }}</dd></div>
                <div><dt class="text-ink-500">Invitation letter</dt><dd class="mt-0.5 text-ink-900">{{ $registration->needs_invitation_letter ? 'Yes · passport '.$registration->passport_number : 'No' }}</dd></div>
                <div><dt class="text-ink-500">Dietary</dt><dd class="mt-0.5 text-ink-900">{{ $registration->dietary_needs ?? '—' }}</dd></div>
                <div><dt class="text-ink-500">Accessibility</dt><dd class="mt-0.5 text-ink-900">{{ $registration->accessibility_needs ?? '—' }}</dd></div>
            </dl>
        </x-card>
    </div>

    <x-card title="Payments" :padding="false">
        @if ($registration->payments->isEmpty())
            <x-empty icon="banknotes" title="No payment submitted yet" class="!py-8" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr><th class="px-5 py-3">Submitted</th><th class="px-5 py-3">Channel</th><th class="px-5 py-3">Transaction</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($registration->payments as $payment)
                            <tr>
                                <td class="px-5 py-3.5 text-ink-600">{{ $payment->created_at->format('j M Y') }}</td>
                                <td class="px-5 py-3.5 text-ink-900">{{ $payment->channel() }}</td>
                                <td class="px-5 py-3.5 font-mono text-ink-700">{{ $payment->transaction_reference }}</td>
                                <td class="px-5 py-3.5 font-semibold text-ink-900">{{ $payment->formattedAmount() }}</td>
                                <td class="px-5 py-3.5"><x-status :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-status></td>
                                <td class="px-5 py-3.5 text-right"><x-button variant="ghost" size="sm" :href="route('finance.payments.show', $payment)">Open</x-button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card title="Abstracts" :padding="false">
        @forelse ($abstracts as $abstract)
            <a href="{{ route('scientific.abstracts.show', $abstract) }}" class="flex items-center justify-between gap-3 border-b border-ink-100 px-5 py-3.5 last:border-0 hover:bg-ink-50 sm:px-6">
                <span class="min-w-0">
                    <span class="block truncate font-medium text-ink-900">{{ $abstract->title }}</span>
                    <span class="block text-xs text-ink-500">{{ $abstract->code ?? $abstract->topic->name }}</span>
                </span>
                <x-status :tone="$abstract->status->tone()">{{ $abstract->status->label() }}</x-status>
            </a>
        @empty
            <x-empty icon="document" title="No abstracts" class="!py-8" />
        @endforelse
    </x-card>
</x-layouts.portal>
