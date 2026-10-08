{{-- One person at the desk: their badge, where they stand, and the one thing to do next. --}}
@php
    use App\Enums\PaymentStatus;
    use App\Enums\RegistrationStatus;

    $user = $registration->user;
    $confirmed = $registration->isConfirmed();
    $paid = $registration->payments->firstWhere('status', PaymentStatus::Verified);
@endphp

<x-layouts.portal :title="$user->name">
    <x-slot:header>
        <x-page-header eyebrow="Registration desk" :title="$user->name" :back="route('desk.index')">
            @if ($registration->checked_in_at)
                <x-status tone="success">Checked in {{ $registration->checked_in_at->format('D j M, H:i') }}</x-status>
            @else
                <x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status>
            @endif
        </x-page-header>
    </x-slot:header>

    @error('check_in') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,360px)_minmax(0,1fr)]">
        <x-badge-card :registration="$registration" />

        <div class="space-y-6">
            {{-- The next step for this person --}}
            @if ($confirmed)
                <x-card>
                    <div class="flex items-start gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"><x-icon name="check-circle" class="h-7 w-7" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-bold text-ink-900">{{ $paid ? 'Paid' : 'Fee waived' }}: give them their badge</p>
                            <p class="text-sm text-ink-600">
                                @if ($paid)
                                    {{ $paid->formattedAmount() }} by {{ $paid->channel() }} · {{ $paid->transaction_reference }}
                                @else
                                    The organisers waived the fee.
                                @endif
                                · Badge {{ $registration->badge_printed_at ? 'printed '.$registration->badge_printed_at->format('j M, H:i') : 'not printed yet' }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <x-button :variant="$registration->badge_printed_at ? 'secondary' : 'primary'" :href="route('desk.badge', $registration)" icon="printer">{{ $registration->badge_printed_at ? 'Reprint badge' : 'Print badge' }}</x-button>
                        @unless ($registration->checked_in_at)
                            <form method="POST" action="{{ route('desk.check-in', $registration) }}">
                                @csrf
                                <x-button variant="success" icon="check">Check in</x-button>
                            </form>
                        @endunless
                    </div>
                </x-card>
            @elseif ($registration->status === RegistrationStatus::PaymentSubmitted)
                <x-card>
                    <x-empty icon="clock" title="Their payment is with finance">
                        They submitted a payment by {{ $registration->latestPayment?->channel() }}, which finance has not verified yet. Ask finance to verify it, then print the badge.
                    </x-empty>
                </x-card>
            @elseif ($mpesa || $pending)
                <x-card title="Take payment: {{ $registration->formattedDue() }}" description="An M-Pesa request goes to their phone. They enter their PIN and the registration is confirmed at once.">
                    @if ($pending)
                        <div x-data="mpesaPay({ storeUrl: @js(route('desk.mpesa', $registration)), statusUrl: @js(route('desk.mpesa.status', $registration)), initialState: 'pending' })">
                            <div class="flex items-start gap-4 rounded-2xl bg-sun-50 p-4" role="status" aria-live="polite">
                                <span x-show="busy" class="mt-0.5 h-6 w-6 shrink-0 animate-spin rounded-full border-[3px] border-sun-200 border-t-ember-600"></span>
                                <div class="text-sm">
                                    <p class="font-semibold text-ink-900">Waiting for M-Pesa: request sent to {{ $pending->payer_phone }}</p>
                                    <p class="mt-1 text-ink-600" x-show="busy">Ask them to enter their PIN. This page updates by itself. <span class="tabular-nums" x-text="seconds + ' s'"></span></p>
                                    <p class="mt-1 font-semibold" x-show="! busy" x-cloak x-text="message"></p>
                                </div>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('desk.mpesa', $registration) }}"
                              x-data="mpesaPay({ storeUrl: @js(route('desk.mpesa', $registration)), statusUrl: @js(route('desk.mpesa.status', $registration)) })"
                              @submit.prevent="pay($el)" class="space-y-4">
                            @csrf
                            <div x-show="state === 'idle' || state === 'failed'" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                                <div class="flex-1">
                                    <x-form.input name="mpesa_phone" type="tel" label="Their M-Pesa number" icon="phone" :value="$user->phone" required />
                                </div>
                                <x-button icon="phone" class="sm:mt-7">Send M-Pesa request</x-button>
                            </div>

                            <div x-show="busy" x-cloak class="flex items-start gap-4 rounded-2xl bg-sun-50 p-4" role="status" aria-live="polite">
                                <span class="mt-0.5 h-6 w-6 shrink-0 animate-spin rounded-full border-[3px] border-sun-200 border-t-ember-600"></span>
                                <div class="text-sm">
                                    <p class="font-semibold text-ink-900">Ask them to check their phone and enter their M-Pesa PIN</p>
                                    <p class="mt-1 text-ink-600">Waiting for M-Pesa… <span class="tabular-nums" x-text="seconds + ' s'"></span></p>
                                </div>
                            </div>

                            <div x-show="state === 'confirmed'" x-cloak class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-900" role="status">
                                <p class="font-semibold">Paid. M-Pesa transaction <span class="font-mono" x-text="transaction"></span>.</p>
                                <p class="mt-1">Loading the badge…</p>
                            </div>
                            <p x-show="state === 'failed'" x-cloak x-text="message" class="rounded-2xl bg-red-50 p-4 text-sm font-medium text-red-800" role="alert"></p>
                            <div x-show="state === 'unknown'" x-cloak class="rounded-2xl bg-sun-50 p-4 text-sm text-ink-800" role="status">
                                <p x-text="message"></p>
                                <a href="{{ route('desk.show', $registration) }}" class="mt-2 inline-block font-semibold text-brand-700 hover:underline">Check again</a>
                            </div>
                        </form>
                    @endif
                </x-card>
            @else
                <x-card>
                    <x-empty icon="banknotes" title="Not paid: no badge yet">
                        They owe {{ $registration->formattedDue() }}. M-Pesa from the desk is not available for this registration ({{ $registration->currency }}), so send them to the finance desk.
                    </x-empty>
                </x-card>
            @endif

            <x-card title="Details">
                <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-500">Badge no.</dt><dd class="mt-0.5 font-mono font-semibold text-ink-900">{{ $registration->reference }}</dd></div>
                    <div><dt class="text-ink-500">Category</dt><dd class="mt-0.5 text-ink-900">{{ $registration->category->name }} · {{ $registration->formattedAmount() }}</dd></div>
                    <div><dt class="text-ink-500">Email</dt><dd class="mt-0.5 break-all text-ink-900">{{ $user->email }}</dd></div>
                    <div><dt class="text-ink-500">Phone</dt><dd class="mt-0.5 text-ink-900">{{ $user->phone }}</dd></div>
                    <div><dt class="text-ink-500">Dietary needs</dt><dd @class(['mt-0.5', 'font-semibold text-ember-700' => $registration->dietary_needs, 'text-ink-500' => ! $registration->dietary_needs])>{{ $registration->dietary_needs ?: 'None' }}</dd></div>
                    <div><dt class="text-ink-500">Accessibility needs</dt><dd @class(['mt-0.5', 'font-semibold text-ember-700' => $registration->accessibility_needs, 'text-ink-500' => ! $registration->accessibility_needs])>{{ $registration->accessibility_needs ?: 'None' }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Attendance & CPD" :padding="false">
                @php $pts = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.'); @endphp
                <p class="px-6 pb-4 pt-1 text-sm text-ink-600">{{ $cpd['attended'] }} of {{ $cpd['sessions'] }} sessions · <span class="font-bold text-ink-900">{{ $pts($cpd['points']) }} of {{ $pts($cpd['available']) }} CPD points</span></p>
                @if ($cpd['attended'] > 0)
                    <ul class="divide-y divide-ink-100 border-t border-ink-100">
                        @foreach ($cpd['days'] as $date => $attendances)
                            @foreach ($attendances as $attendance)
                                <li class="flex items-center justify-between gap-3 px-6 py-2.5 text-sm">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-ink-900">{{ $attendance->session->title }}</span>
                                        <span class="block text-xs text-ink-500">{{ $attendance->session->starts_at->format('D j M') }} · scanned {{ $attendance->scanned_at->format('H:i') }}</span>
                                    </span>
                                    <span class="shrink-0 font-semibold text-ink-900">{{ $pts($attendance->session->points()) }} pts</span>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                @endif
            </x-card>

            @if ($registration->payments->isNotEmpty())
                <x-card title="Payments" :padding="false">
                    <ul class="divide-y divide-ink-100">
                        @foreach ($registration->payments as $payment)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-3 text-sm">
                                <span>
                                    <span class="font-semibold text-ink-900">{{ $payment->channel() }} · {{ $payment->formattedAmount() }}</span>
                                    <span class="block text-xs text-ink-500">
                                        {{ $payment->created_at->format('j M Y, H:i') }}{{ $payment->initiator ? ' · sent from the desk by '.$payment->initiator->name : '' }}
                                        @if ($payment->status === PaymentStatus::Failed) · {{ \App\Services\MpesaPaymentService::message($payment) }} @endif
                                    </span>
                                </span>
                                <x-status :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-status>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.portal>
