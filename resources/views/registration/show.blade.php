@php
    use App\Enums\RegistrationStatus;
    use App\Enums\PaymentStatus;

    $steps = [
        ['Registered', (bool) $registration],
        ['Payment submitted', $registration && in_array($registration->status, [RegistrationStatus::PaymentSubmitted, RegistrationStatus::Confirmed], true)],
        ['Confirmed', $registration?->isConfirmed() ?? false],
    ];
    $pendingMpesa = $registration?->pendingGatewayPayment();
@endphp

<x-layouts.portal title="Registration & payment">
    <x-slot:header>
        <x-page-header :eyebrow="$summit->title()" title="Registration & payment"
            :description="$registration ? null : 'Register once for the whole summit: all sessions, materials, meals and your CPD certificate.'" />
    </x-slot:header>

    @if (! $registration)
        @if (! $edition?->registration_open || $categories->isEmpty())
            <x-card>
                <x-empty icon="ticket" title="Registration is not open yet">
                    We will email you as soon as registration for the {{ $summit->title() }} opens.
                </x-empty>
            </x-card>
        @else
            <form method="POST" action="{{ route('registration.store') }}" x-data="{ letter: {{ old('needs_invitation_letter') ? 'true' : 'false' }} }" class="space-y-6">
                @csrf

                <x-card title="1. Choose your category" description="Student rates need proof of enrolment at the registration desk.">
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($categories as $category)
                            <label class="relative flex cursor-pointer items-start gap-3 rounded-2xl border border-ink-200 p-4 transition hover:border-brand-300 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 has-[:checked]:ring-2 has-[:checked]:ring-brand-500/20">
                                <input type="radio" name="category" value="{{ $category->id }}" @checked(old('category') == $category->id) required class="mt-1 h-4 w-4 accent-brand-700">
                                <span class="flex-1">
                                    <span class="block font-semibold text-ink-900">{{ $category->name }}</span>
                                    <span class="mt-1 block text-xl font-bold tracking-tight text-brand-700">{{ $category->formattedFee() }}</span>
                                    @if ($category->is_student)
                                        <span class="mt-1 block text-xs text-ink-500">Bring your student ID</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('category') <p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                </x-card>

                <x-card title="2. Your details" description="These appear on your badge and certificate.">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="institution" label="Institution" :value="auth()->user()->institution" placeholder="e.g. Muhimbili National Hospital" required />
                        <x-form.input name="profession" label="Profession" :value="auth()->user()->profession" placeholder="e.g. Physiotherapist" required />
                        <x-form.input name="badge_name" label="Name on badge (optional)" :placeholder="auth()->user()->name" hint="Leave empty to use your full name." />
                        <x-form.input name="dietary_needs" label="Dietary needs (optional)" placeholder="e.g. Vegetarian" />
                        <div class="sm:col-span-2">
                            <x-form.input name="accessibility_needs" label="Accessibility needs (optional)" placeholder="e.g. Wheelchair access, sign language interpretation" />
                        </div>
                    </div>
                </x-card>

                <x-card title="3. Visa invitation letter">
                    <label class="flex items-start gap-2.5 text-sm text-ink-700">
                        <input type="hidden" name="needs_invitation_letter" value="0">
                        <input type="checkbox" name="needs_invitation_letter" value="1" x-model="letter" class="mt-0.5 h-4 w-4 accent-brand-700">
                        <span><span class="font-medium text-ink-900">I need an invitation letter for my visa application</span>
                            <span class="block text-xs text-ink-500">The letter is ready to download once your payment is verified.</span></span>
                    </label>
                    <div x-show="letter" x-cloak class="mt-4 max-w-sm">
                        <x-form.input name="passport_number" label="Passport number" />
                    </div>
                </x-card>

                <div class="flex flex-col gap-4 rounded-card border border-ink-100 bg-white p-6 shadow-soft sm:flex-row sm:items-center sm:justify-between">
                    <label class="flex items-start gap-2.5 text-sm text-ink-700">
                        <input type="checkbox" name="confirm" value="1" required class="mt-0.5 h-4 w-4 accent-brand-700">
                        <span>I confirm these details are correct and I will pay the fee for my category.</span>
                    </label>
                    <x-button size="lg" icon="check">Register</x-button>
                </div>
                @error('confirm') <p class="text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                <p class="flex items-start gap-2 text-xs text-ink-500">
                    <x-icon name="camera" class="h-4 w-4 shrink-0" />
                    <span>Photos are taken at the summit and published in the public <a href="{{ route('gallery.index') }}" class="font-semibold text-brand-700 hover:underline">gallery</a>. If you appear in one and would like it removed, open it in the gallery and choose the flag.</span>
                </p>
            </form>
        @endif
    @else
        @if ($registration->isConfirmed())
            @include('registration._confirmed')
        @else
        {{-- Progress --}}
        <ol class="grid grid-cols-3 gap-2">
            @foreach ($steps as $i => [$label, $done])
                <li class="flex items-center gap-2.5 rounded-2xl border px-4 py-3 text-sm {{ $done ? 'border-emerald-100 bg-emerald-50 text-emerald-800' : 'border-ink-100 bg-white text-ink-500' }}">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold {{ $done ? 'bg-emerald-600 text-white' : 'bg-ink-100 text-ink-500' }}">
                        @if ($done) <x-icon name="check" class="h-3.5 w-3.5" /> @else {{ $i + 1 }} @endif
                    </span>
                    <span class="font-semibold">{{ $label }}</span>
                </li>
            @endforeach
        </ol>

        <div class="grid gap-6 lg:grid-cols-[1fr_1.3fr]">
            <x-card title="Your registration">
                <dl class="space-y-4 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Status</dt><dd><x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Reference</dt><dd class="font-mono font-semibold text-ink-900">{{ $registration->reference }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Category</dt><dd class="text-right font-medium text-ink-900">{{ $registration->category->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Fee</dt><dd @class(['font-bold text-ink-900', 'line-through decoration-ink-400' => $registration->isFullyWaived()])>{{ $registration->formattedAmount() }}</dd></div>
                    @if ($registration->isWaived())
                        <div class="flex justify-between gap-4"><dt class="text-ink-500">Waived by the organisers</dt><dd class="font-semibold text-emerald-700">{{ $registration->isFullyWaived() ? 'Full fee' : '− '.$registration->currency.' '.number_format((float) $registration->waived_amount) }}</dd></div>
                        @unless ($registration->isFullyWaived())
                            <div class="flex justify-between gap-4"><dt class="text-ink-500">To pay</dt><dd class="font-bold text-ink-900">{{ $registration->formattedDue() }}</dd></div>
                        @endunless
                    @endif
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Name on badge</dt><dd class="text-right font-medium text-ink-900">{{ $registration->displayName() }}</dd></div>
                    @if ($registration->confirmed_at)
                        <div class="flex justify-between gap-4"><dt class="text-ink-500">Confirmed</dt><dd class="font-medium text-ink-900">{{ $registration->confirmed_at->format('j M Y') }}</dd></div>
                    @endif
                </dl>

                @if ($registration->isConfirmed())
                    <div class="mt-6 space-y-2 border-t border-ink-100 pt-5">
                        <x-button :href="route('registration.badge')" icon="download" class="w-full">Download badge</x-button>
                        @if ($registration->needs_invitation_letter)
                            <x-button variant="secondary" :href="route('registration.letter')" icon="download" class="w-full">Download invitation letter</x-button>
                        @endif
                    </div>
                @endif
            </x-card>

            @if ($registration->canSubmitPayment())
                @include('registration._payment')
            @elseif ($pendingMpesa)
                <x-card>
                    <div x-data="mpesaPay({ storeUrl: @js(route('registration.mpesa.store')), statusUrl: @js(route('registration.mpesa.status')), initialState: 'pending' })">
                        <x-empty icon="clock" title="Waiting for M-Pesa">
                            We sent a request for {{ $pendingMpesa->formattedAmount() }} to {{ $pendingMpesa->payer_phone }}. If you entered your PIN, M-Pesa will confirm it shortly. This page updates by itself, and you will not be charged twice.
                            <x-slot:action>
                                <p x-show="state === 'confirmed' || state === 'failed'" x-cloak x-text="message" class="mb-3 text-sm font-semibold" :class="state === 'confirmed' ? 'text-emerald-700' : 'text-red-700'"></p>
                                <form method="POST" action="{{ route('registration.mpesa.check') }}" x-show="state !== 'confirmed'">
                                    @csrf
                                    <x-button variant="secondary" icon="arrow-trend">Check the payment</x-button>
                                </form>
                            </x-slot:action>
                        </x-empty>
                    </div>
                </x-card>
            @elseif ($registration->status === RegistrationStatus::PaymentSubmitted)
                <x-card>
                    <x-empty icon="clock" title="We are verifying your payment">
                        A finance officer is checking your payment. This usually takes one working day, and we will email you when it is done.
                    </x-empty>
                </x-card>
            @else
                <x-card>
                    <x-empty icon="check-circle" title="Your place is confirmed">
                        See you at {{ $summit->venueLine() ?? 'the summit' }}{{ $summit->dateRange() ? ', '.$summit->dateRange() : '' }}. Bring your badge, printed or on your phone.
                        <x-slot:action><x-button variant="secondary" :href="route('programme')" icon="calendar">View the programme</x-button></x-slot:action>
                    </x-empty>
                </x-card>
            @endif
        </div>
        @endif

        @if ($registration->payments->isNotEmpty())
            <x-card title="Payment history" :padding="false">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                            <tr><th class="px-5 py-3">Submitted</th><th class="px-5 py-3">Channel</th><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($registration->payments as $payment)
                                <tr>
                                    <td class="px-5 py-3.5 text-ink-600">{{ $payment->created_at->format('j M Y') }}</td>
                                    <td class="px-5 py-3.5 font-medium text-ink-900">{{ $payment->channel() }}</td>
                                    {{-- An M-Pesa payment only has a transaction number once it is paid. --}}
                                    <td class="px-5 py-3.5 font-mono text-ink-700">{{ $payment->gateway && $payment->status !== PaymentStatus::Verified ? '—' : $payment->transaction_reference }}</td>
                                    <td class="px-5 py-3.5 text-ink-900">{{ $payment->formattedAmount() }}</td>
                                    <td class="px-5 py-3.5">
                                        <x-status :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-status>
                                        @if ($payment->rejection_reason)<p class="mt-1 text-xs text-red-700">{{ $payment->rejection_reason }}</p>@endif
                                        @if ($payment->status === PaymentStatus::Failed)<p class="mt-1 text-xs text-ink-500">Nothing was charged.</p>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    @endif
</x-layouts.portal>
