{{-- The participant chooses how to pay, then follows that channel: an instant M-Pesa prompt, or pay and upload the proof. --}}
@php
    use App\Enums\PaymentStatus;
    use App\Services\MpesaPaymentService;

    $channels = \App\Support\PaymentChannels::for($registration);
    $selected = array_key_exists(old('channel'), $channels) ? old('channel') : array_key_first($channels);
    $lastPayment = $registration->payments->first();
    $routes = collect($channels)->map(fn ($c) => ['mode' => $c['mode'], 'method' => $c['method'], 'provider' => $c['provider']]);
@endphp

<x-card title="Pay {{ $registration->formattedDue() }}" description="Choose how you would like to pay. Your reference is {{ $registration->reference }}.">
    @if ($lastPayment?->status === PaymentStatus::Rejected)
        <x-alert tone="danger" class="mb-5">
            <span class="font-semibold">Your last payment could not be verified.</span>
            {{ $lastPayment->rejection_reason }}
        </x-alert>
    @elseif ($lastPayment?->status === PaymentStatus::Failed && ! $errors->has('mpesa_phone'))
        <x-alert tone="danger" class="mb-5">
            <span class="font-semibold">Your last M-Pesa payment did not go through.</span>
            {{ MpesaPaymentService::message($lastPayment) }}
        </x-alert>
    @endif

    @if (empty($channels))
        <x-empty icon="banknotes" title="Payment details are not available yet">
            Please contact {{ $summit->get('contact_email') }} and we will tell you how to pay.
        </x-empty>
    @else
        <div x-data="{ channel: @js($selected), routes: @js($routes) }" class="space-y-6">
            <fieldset>
                <legend class="label">How would you like to pay?</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($channels as $channel)
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition"
                               :class="channel === @js($channel['key']) ? 'border-brand-600 bg-brand-50 ring-2 ring-brand-600/20' : 'border-ink-200 bg-white hover:border-ink-300'">
                            <input type="radio" value="{{ $channel['key'] }}" x-model="channel" @checked($channel['key'] === $selected) class="mt-1 h-4 w-4 accent-brand-700">
                            <span>
                                <span class="block font-semibold text-ink-900">{{ $channel['label'] }}</span>
                                <span @class(['mt-0.5 block text-xs', 'font-semibold text-emerald-700' => $channel['mode'] === 'instant', 'text-ink-500' => $channel['mode'] !== 'instant'])>{{ $channel['timing'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            @foreach ($channels as $channel)
                <div x-show="channel === @js($channel['key'])" @if ($channel['key'] !== $selected) x-cloak @endif class="border-t border-ink-100 pt-6">
                    @if ($channel['mode'] === 'instant')
                        <form method="POST" action="{{ route('registration.mpesa.store') }}"
                              x-data="mpesaPay({ storeUrl: @js(route('registration.mpesa.store')), statusUrl: @js(route('registration.mpesa.status')) })"
                              @submit.prevent="pay($el)" class="space-y-5">
                            @csrf
                            <ol class="grid gap-2 text-sm text-ink-600 sm:grid-cols-3">
                                <li class="rounded-xl bg-canvas px-3 py-2"><span class="font-semibold text-ink-900">1.</span> Enter your M-Pesa number</li>
                                <li class="rounded-xl bg-canvas px-3 py-2"><span class="font-semibold text-ink-900">2.</span> A payment request appears on your phone</li>
                                <li class="rounded-xl bg-canvas px-3 py-2"><span class="font-semibold text-ink-900">3.</span> Enter your PIN: your place is confirmed at once</li>
                            </ol>

                            <div x-show="state === 'idle' || state === 'failed'" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                                <div class="flex-1">
                                    <x-form.input name="mpesa_phone" type="tel" label="M-Pesa number" icon="phone" :value="old('mpesa_phone', auth()->user()->phone)" placeholder="0754 123 456" required />
                                </div>
                                <x-button icon="phone" class="sm:mt-7">Send payment request</x-button>
                            </div>

                            <div x-show="busy" x-cloak class="flex items-start gap-4 rounded-2xl bg-sun-50 p-4" role="status" aria-live="polite">
                                <span class="mt-0.5 h-6 w-6 shrink-0 animate-spin rounded-full border-[3px] border-sun-200 border-t-ember-600"></span>
                                <div class="text-sm">
                                    <p class="font-semibold text-ink-900">Check your phone and enter your M-Pesa PIN</p>
                                    <p class="mt-1 text-ink-600" x-show="state === 'sending'">The request for {{ $registration->formattedDue() }} is on its way. Keep this page open. <span class="tabular-nums" x-text="seconds + ' s'"></span></p>
                                    <p class="mt-1 text-ink-600" x-show="state === 'pending'" x-cloak>Waiting for M-Pesa to confirm. This page updates by itself. <span class="tabular-nums" x-text="seconds + ' s'"></span></p>
                                </div>
                            </div>

                            <div x-show="state === 'confirmed'" x-cloak class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-900" role="status">
                                <p class="font-semibold" x-text="message"></p>
                                <p class="mt-1">M-Pesa transaction <span class="font-mono font-semibold" x-text="transaction"></span>. Loading your badge…</p>
                            </div>

                            <p x-show="state === 'failed'" x-cloak x-text="message" class="rounded-2xl bg-red-50 p-4 text-sm font-medium text-red-800" role="alert"></p>

                            <div x-show="state === 'unknown'" x-cloak class="rounded-2xl bg-sun-50 p-4 text-sm text-ink-800" role="status">
                                <p x-text="message"></p>
                                <a href="{{ route('registration.show') }}" class="mt-2 inline-block font-semibold text-brand-700 hover:underline">Check again</a>
                            </div>
                        </form>
                    @else
                        <div class="rounded-2xl bg-canvas p-4 text-sm">
                            <p class="font-semibold text-ink-900">Pay {{ $registration->formattedDue() }} by {{ $channel['label'] }}</p>
                            <dl class="mt-3 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                                @if ($channel['method'] === 'bank_transfer')
                                    <div><dt class="text-ink-500">Bank</dt><dd class="font-semibold text-ink-900">{{ $channel['details']['bank_name'] }}</dd></div>
                                    <div><dt class="text-ink-500">Account name</dt><dd class="font-semibold text-ink-900">{{ $channel['details']['account_name'] }}</dd></div>
                                    <div><dt class="text-ink-500">Account number ({{ $registration->currency }})</dt><dd class="font-mono font-semibold text-ink-900">{{ $channel['details']['account_number'] }}</dd></div>
                                    <div><dt class="text-ink-500">Branch · SWIFT</dt><dd class="font-semibold text-ink-900">{{ $channel['details']['branch'] }} · {{ $channel['details']['swift_code'] }}</dd></div>
                                @else
                                    <div><dt class="text-ink-500">Pay number</dt><dd class="font-mono font-semibold text-ink-900">{{ $channel['details']['pay_number'] }}</dd></div>
                                    <div><dt class="text-ink-500">Account name</dt><dd class="font-semibold text-ink-900">{{ $channel['details']['account_name'] }}</dd></div>
                                @endif
                                <div><dt class="text-ink-500">Reference</dt><dd class="font-mono font-semibold text-ink-900">{{ $registration->reference }}</dd></div>
                            </dl>
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- One form for every pay-and-upload channel; the chosen channel sets the method and operator. --}}
            <form method="POST" action="{{ route('registration.payments.store') }}" enctype="multipart/form-data"
                  x-show="routes[channel]?.mode === 'manual'" @if (($channels[$selected]['mode'] ?? null) !== 'manual') x-cloak @endif class="space-y-5">
                @csrf
                <input type="hidden" name="channel" :value="channel">
                <input type="hidden" name="method" :value="routes[channel]?.method">
                <input type="hidden" name="provider" :value="routes[channel]?.provider ?? ''">
                <p class="font-semibold text-ink-900">After paying, send us the details</p>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="transaction_reference" label="Transaction reference" placeholder="From your slip or SMS" required />
                    <x-form.input name="paid_on" type="date" label="Date paid" :value="old('paid_on', today()->toDateString())" :max="today()->toDateString()" required />
                    <x-form.input name="payer_name" label="Paid by" :value="old('payer_name', auth()->user()->name)" required />
                    <x-form.input name="payer_phone" type="tel" label="Phone (optional)" :value="old('payer_phone', auth()->user()->phone)" />
                </div>

                <div>
                    <label for="proof" class="label">Proof of payment</label>
                    <input id="proof" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required
                           class="block w-full rounded-control border border-dashed border-ink-300 bg-canvas px-4 py-4 text-sm text-ink-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-brand-400">
                    <p class="mt-1.5 text-xs text-ink-500">Bank slip or mobile money confirmation (screenshot or SMS). PDF, JPG or PNG, up to 5 MB.</p>
                    @error('proof') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                    @error('method') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                    @error('provider') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                </div>

                <x-button icon="upload">Submit payment</x-button>
            </form>
        </div>
    @endif
</x-card>
