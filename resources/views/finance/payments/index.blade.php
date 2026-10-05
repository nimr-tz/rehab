@php
    use App\Enums\PaymentStatus;
    $tabs = ['submitted' => 'To verify', 'verified' => 'Verified', 'rejected' => 'Rejected', 'all' => 'All'];
@endphp

<x-layouts.portal title="Payments">
    <x-page-header eyebrow="Finance" title="Payments"
        description="Check each payment against the bank statement or mobile money account, then verify or reject it. Participants are emailed either way." />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Waiting for verification" :value="$counts['submitted'] ?? 0" icon="clock" tint="bg-sun-100 text-sun-800" />
        <x-stat label="Verified (TZS)" :value="'TZS '.number_format((float) ($verifiedTotals['TZS'] ?? 0))" icon="banknotes" tint="bg-emerald-50 text-emerald-700" />
        <x-stat label="Verified (USD)" :value="'USD '.number_format((float) ($verifiedTotals['USD'] ?? 0))" icon="banknotes" tint="bg-emerald-50 text-emerald-700" />
    </div>

    <x-card :padding="false">
        <div class="flex flex-col gap-3 border-b border-ink-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-2">
                @foreach ($tabs as $value => $label)
                    <a href="{{ route('finance.payments.index', array_filter(['status' => $value, 'q' => $search])) }}"
                       @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-brand-700 text-white' => $status === $value, 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50' => $status !== $value])>
                        {{ $label }}
                        @if ($value !== 'all')<span class="ml-1 opacity-70">{{ $counts[$value] ?? 0 }}</span>@endif
                    </a>
                @endforeach
            </div>
            <form method="GET" class="relative lg:w-80">
                <input type="hidden" name="status" value="{{ $status }}">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
                <input name="q" value="{{ $search }}" placeholder="Reference, payer or transaction" class="field h-11 pl-12">
            </form>
        </div>

        @if ($payments->isEmpty())
            <x-empty icon="check-circle" title="{{ $status === 'submitted' ? 'Nothing waiting' : 'No payments here' }}">
                {{ $status === 'submitted' ? 'Every submitted payment has been reviewed.' : 'Try another tab or search.' }}
            </x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr><th class="px-5 py-3">Participant</th><th class="px-5 py-3">Registration</th><th class="px-5 py-3">Channel</th><th class="px-5 py-3">Transaction</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($payments as $payment)
                            <tr class="hover:bg-ink-50/60">
                                <td class="px-5 py-3.5"><p class="font-medium text-ink-900">{{ $payment->registration->user->name }}</p><p class="text-xs text-ink-500">{{ $payment->registration->category->name }}</p></td>
                                <td class="px-5 py-3.5 font-mono text-ink-700">{{ $payment->registration->reference }}</td>
                                <td class="px-5 py-3.5 text-ink-700">{{ $payment->channel() }}<p class="text-xs text-ink-500">{{ $payment->paid_on->format('j M Y') }}</p></td>
                                <td class="px-5 py-3.5 font-mono text-ink-700">{{ $payment->transaction_reference }}</td>
                                <td class="px-5 py-3.5 font-semibold text-ink-900">{{ $payment->formattedAmount() }}</td>
                                <td class="px-5 py-3.5"><x-status :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-status></td>
                                <td class="px-5 py-3.5 text-right">
                                    <x-button :variant="$payment->status === PaymentStatus::Submitted ? 'primary' : 'ghost'" size="sm" :href="route('finance.payments.show', $payment)">
                                        {{ $payment->status === PaymentStatus::Submitted ? 'Review' : 'Open' }}
                                    </x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-ink-100 px-5 py-3">{{ $payments->links() }}</div>
        @endif
    </x-card>
</x-layouts.portal>
