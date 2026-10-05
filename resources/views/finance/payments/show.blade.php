@php
    use App\Enums\PaymentStatus;
    $registration = $payment->registration;
    $pending = $payment->status === PaymentStatus::Submitted;
    $amountMatches = (float) $payment->amount === (float) $registration->amount && $payment->currency === $registration->currency;
@endphp

<x-layouts.portal :title="'Payment '.$registration->reference">
    <x-page-header eyebrow="Finance" :title="$registration->user->name.' · '.$payment->formattedAmount()" :back="route('finance.payments.index')">
        <x-status :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-status>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
        <x-card title="Proof of payment" :padding="false">
            <x-slot:actions>
                @if ($payment->proof_path)
                    <x-button variant="ghost" size="sm" :href="route('finance.payments.proof', $payment)" icon="download" target="_blank">Open in new tab</x-button>
                @endif
            </x-slot:actions>
            @if (! $payment->proof_path)
                <x-empty icon="document" title="No proof uploaded" />
            @elseif ($proofIsImage)
                <div class="bg-ink-50 p-4"><img src="{{ route('finance.payments.proof', $payment) }}" alt="Proof of payment" class="mx-auto max-h-[640px] rounded-xl shadow-soft"></div>
            @else
                <iframe src="{{ route('finance.payments.proof', $payment) }}" title="Proof of payment" class="h-[640px] w-full rounded-b-card bg-ink-50"></iframe>
            @endif
        </x-card>

        <div class="space-y-6">
            <x-card title="Check against the statement">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Channel</dt><dd class="font-medium text-ink-900">{{ $payment->channel() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Transaction ref.</dt><dd class="font-mono font-semibold text-ink-900">{{ $payment->transaction_reference }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Paid on</dt><dd class="font-medium text-ink-900">{{ $payment->paid_on->format('j M Y') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Paid by</dt><dd class="text-right font-medium text-ink-900">{{ $payment->payer_name }}@if ($payment->payer_phone)<br><span class="text-xs text-ink-500">{{ $payment->payer_phone }}</span>@endif</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Amount</dt><dd class="font-bold text-ink-900">{{ $payment->formattedAmount() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Fee due</dt><dd class="font-medium text-ink-900">{{ $registration->formattedAmount() }}</dd></div>
                </dl>
                @unless ($amountMatches)
                    <x-alert tone="warning" class="mt-4">The amount differs from the fee due.</x-alert>
                @endunless
            </x-card>

            <x-card title="Participant">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Registration</dt><dd class="font-mono font-semibold text-ink-900">{{ $registration->reference }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Category</dt><dd class="text-right font-medium text-ink-900">{{ $registration->category->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Email</dt><dd class="text-right text-ink-900">{{ $registration->user->email }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Earlier attempts</dt><dd class="text-ink-900">{{ $registration->payments->count() - 1 }}</dd></div>
                </dl>
            </x-card>

            @if ($pending)
                <x-card title="Decision">
                    <form method="POST" action="{{ route('finance.payments.verify', $payment) }}">
                        @csrf
                        <x-button variant="success" class="w-full" icon="check">Verify payment</x-button>
                    </form>
                    <form method="POST" action="{{ route('finance.payments.reject', $payment) }}" class="mt-5 space-y-3 border-t border-ink-100 pt-5" x-data="{ open: {{ $errors->has('rejection_reason') ? 'true' : 'false' }} }">
                        @csrf
                        <button type="button" x-show="! open" @click="open = true" class="w-full text-sm font-semibold text-red-700 hover:underline">Reject this payment…</button>
                        <div x-show="open" x-cloak class="space-y-3">
                            <x-form.textarea name="rejection_reason" label="Reason (sent to the participant)" rows="3" placeholder="e.g. The transaction reference does not appear on our statement." />
                            <x-button variant="danger" class="w-full" icon="x-circle">Reject payment</x-button>
                        </div>
                    </form>
                </x-card>
            @else
                <x-card title="Decision">
                    <p class="text-sm text-ink-700">
                        {{ $payment->status->label() }} by <span class="font-semibold">{{ $payment->reviewer?->name ?? '—' }}</span>
                        on {{ $payment->reviewed_at?->format('j M Y, H:i') }}.
                    </p>
                    @if ($payment->rejection_reason)
                        <p class="mt-2 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-800">{{ $payment->rejection_reason }}</p>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.portal>
