@php
    use App\Enums\PaymentStatus;
    $registration = $payment->registration;
    $pending = $payment->status === PaymentStatus::Submitted;
    $amountMatches = (float) $payment->amount === $registration->amountDue() && $payment->currency === $registration->currency;
@endphp

<x-layouts.portal :title="'Payment '.$registration->reference">
    <x-slot:header>
        <x-page-header eyebrow="Finance" :title="$registration->user->name.' · '.$payment->formattedAmount()" :back="route('finance.payments.index')">
            <x-status :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-status>
        </x-page-header>
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
        <x-card title="Proof of payment" :padding="false">
            <x-slot:actions>
                @if ($payment->proof_path)
                    <x-button variant="ghost" size="sm" :href="route('finance.payments.proof', $payment)" icon="download" target="_blank">Open in new tab</x-button>
                @endif
            </x-slot:actions>
            @if ($payment->gateway)
                <x-empty icon="phone" title="Paid with an M-Pesa prompt">
                    M-Pesa confirms these payments directly, so there is no proof to check. Transaction {{ $payment->transaction_reference }}.
                </x-empty>
            @elseif (! $payment->proof_path)
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
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Fee due</dt><dd class="text-right font-medium text-ink-900">{{ $registration->formattedDue() }}@if ($registration->isWaived())<span class="block text-xs font-normal text-ink-500">{{ $registration->formattedAmount() }} less a waiver</span>@endif</dd></div>
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
            @elseif ($payment->status === PaymentStatus::Pending && $payment->gateway === 'mpesa')
                <x-card title="Waiting for M-Pesa">
                    <p class="text-sm text-ink-700">
                        The request went to {{ $payment->payer_phone }} on {{ $payment->created_at->format('j M Y, H:i') }}.
                        @if ($payment->gateway_checked_at) Last asked M-Pesa at {{ $payment->gateway_checked_at->format('H:i') }}@if ($payment->gateway_code) ({{ $payment->gateway_code }})@endif. @endif
                        Until it is settled, the participant cannot pay another way.
                    </p>
                    <form method="POST" action="{{ route('finance.payments.mpesa.check', $payment) }}" class="mt-4">
                        @csrf
                        <x-button variant="secondary" class="w-full" icon="phone">Ask M-Pesa again</x-button>
                    </form>

                    @if (\App\Services\MpesaPaymentService::canSettleByHand($payment))
                        <div class="mt-5 space-y-5 border-t border-ink-100 pt-5" x-data="{ open: '{{ $errors->has('mpesa_reason') ? 'no' : ($errors->has('mpesa_transaction') ? 'yes' : '') }}' }">
                            <p class="text-sm text-ink-600">If M-Pesa does not answer, check the M-Pesa statement for {{ $payment->formattedAmount() }} from {{ $payment->payer_phone }}.</p>
                            <form method="POST" action="{{ route('finance.payments.mpesa.received', $payment) }}" class="space-y-3">
                                @csrf
                                <button type="button" x-show="open !== 'yes'" @click="open = 'yes'" class="w-full text-sm font-semibold text-emerald-700 hover:underline">It is on the statement…</button>
                                <div x-show="open === 'yes'" x-cloak class="space-y-3">
                                    <x-form.input name="mpesa_transaction" label="M-Pesa transaction ID" placeholder="QX7TD52KHB" />
                                    <x-button variant="success" class="w-full" icon="check">Record as paid</x-button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('finance.payments.mpesa.not-received', $payment) }}" class="space-y-3">
                                @csrf
                                <button type="button" x-show="open !== 'no'" @click="open = 'no'" class="w-full text-sm font-semibold text-red-700 hover:underline">It is not on the statement…</button>
                                <div x-show="open === 'no'" x-cloak class="space-y-3">
                                    <x-form.textarea name="mpesa_reason" label="Note for the record" rows="2" placeholder="e.g. Not on the M-Pesa statement for 12–13 March." />
                                    <x-button variant="danger" class="w-full" icon="x-circle">Mark as not received</x-button>
                                </div>
                            </form>
                        </div>
                    @else
                        <p class="mt-4 text-xs text-ink-500">If M-Pesa has still not answered {{ \App\Services\MpesaPaymentService::SETTLE_BY_HAND_AFTER_MINUTES }} minutes after the request, you can settle it here from the M-Pesa statement.</p>
                    @endif
                </x-card>
            @else
                <x-card title="Decision">
                    @if ($payment->gateway)
                        <p class="text-sm text-ink-700">
                            {{ $payment->status->label() }} · M-Pesa answered <span class="font-mono font-semibold">{{ $payment->gateway_code ?? '—' }}</span>
                            {{ $payment->gateway_message }}
                            @if ($payment->gateway_checked_at) <span class="text-ink-500">({{ $payment->gateway_checked_at->format('j M Y, H:i') }})</span> @endif
                        </p>
                    @else
                        <p class="text-sm text-ink-700">
                            {{ $payment->status->label() }} by <span class="font-semibold">{{ $payment->reviewer?->name ?? '—' }}</span>
                            on {{ $payment->reviewed_at?->format('j M Y, H:i') }}.
                        </p>
                    @endif
                    @if ($payment->rejection_reason)
                        <p class="mt-2 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-800">{{ $payment->rejection_reason }}</p>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.portal>
