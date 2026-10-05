@php
    use App\Enums\RegistrationStatus;
    use App\Enums\PaymentStatus;

    $steps = [
        ['Registered', (bool) $registration],
        ['Payment submitted', $registration && in_array($registration->status, [RegistrationStatus::PaymentSubmitted, RegistrationStatus::Confirmed], true)],
        ['Confirmed', $registration?->isConfirmed() ?? false],
    ];
    $mobileAllowed = $registration && $registration->currency === config('payments.mobile_money.currency') && ! empty($mobileProviders);
@endphp

<x-layouts.portal title="Registration & payment">
    <x-page-header :eyebrow="$summit->title()" title="Registration & payment"
        :description="$registration ? null : 'Register once for the whole summit: all sessions, materials, meals and your CPD certificate.'" />

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
            </form>
        @endif
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
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Fee</dt><dd class="font-bold text-ink-900">{{ $registration->formattedAmount() }}</dd></div>
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
                <x-card title="Pay {{ $registration->formattedAmount() }}" description="Quote your reference {{ $registration->reference }} with the payment.">
                    @if ($registration->payments->first()?->status === PaymentStatus::Rejected)
                        <x-alert tone="danger" class="mb-5">
                            <span class="font-semibold">Your last payment could not be verified.</span>
                            {{ $registration->payments->first()->rejection_reason }}
                        </x-alert>
                    @endif

                    <div x-data="{ method: '{{ old('method', 'bank_transfer') }}' }" class="space-y-6">
                        <div class="inline-flex rounded-xl bg-ink-100 p-1 text-sm font-semibold" role="tablist">
                            <button type="button" role="tab" @click="method = 'bank_transfer'" :class="method === 'bank_transfer' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-500'" class="rounded-lg px-4 py-2">Bank transfer</button>
                            @if ($mobileAllowed)
                                <button type="button" role="tab" @click="method = 'mobile_money'" :class="method === 'mobile_money' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-500'" class="rounded-lg px-4 py-2">Mobile money</button>
                            @endif
                        </div>

                        <div x-show="method === 'bank_transfer'" class="rounded-2xl bg-canvas p-4 text-sm">
                            @php $account = $bankAccounts[$registration->currency] ?? null; @endphp
                            @if ($account)
                                <dl class="grid gap-x-6 gap-y-2 sm:grid-cols-2">
                                    <div><dt class="text-ink-500">Bank</dt><dd class="font-semibold text-ink-900">{{ $account['bank_name'] }}</dd></div>
                                    <div><dt class="text-ink-500">Account name</dt><dd class="font-semibold text-ink-900">{{ $account['account_name'] }}</dd></div>
                                    <div><dt class="text-ink-500">Account number ({{ $registration->currency }})</dt><dd class="font-mono font-semibold text-ink-900">{{ $account['account_number'] }}</dd></div>
                                    <div><dt class="text-ink-500">Branch · SWIFT</dt><dd class="font-semibold text-ink-900">{{ $account['branch'] }} · {{ $account['swift_code'] }}</dd></div>
                                </dl>
                            @else
                                <p class="text-ink-600">Bank details for {{ $registration->currency }} payments are not available yet. Please contact {{ $summit->get('contact_email') }}.</p>
                            @endif
                        </div>

                        @if ($mobileAllowed)
                            <div x-show="method === 'mobile_money'" x-cloak class="grid gap-2 sm:grid-cols-2">
                                @foreach ($mobileProviders as $key => $provider)
                                    <div class="rounded-2xl bg-canvas p-4 text-sm">
                                        <p class="font-semibold text-ink-900">{{ $provider['label'] }}</p>
                                        <p class="text-ink-600">Pay number <span class="font-mono font-semibold text-ink-900">{{ $provider['pay_number'] }}</span></p>
                                        <p class="text-xs text-ink-500">{{ $provider['account_name'] }} · use {{ $registration->reference }} as reference</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('registration.payments.store') }}" enctype="multipart/form-data" class="space-y-5 border-t border-ink-100 pt-6">
                            @csrf
                            <input type="hidden" name="method" :value="method">
                            <p class="font-semibold text-ink-900">After paying, send us the details</p>

                            <div x-show="method === 'mobile_money'" x-cloak>
                                <x-form.select name="provider" label="Operator" placeholder="Select" :options="collect($mobileProviders)->map(fn ($p) => $p['label'])->all()" />
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-form.input name="transaction_reference" label="Transaction reference" placeholder="From your slip or SMS" required />
                                <x-form.input name="paid_on" type="date" label="Date paid" :value="today()->toDateString()" :max="today()->toDateString()" required />
                                <x-form.input name="payer_name" label="Paid by" :value="auth()->user()->name" required />
                                <x-form.input name="payer_phone" type="tel" label="Phone (optional)" :value="auth()->user()->phone" />
                            </div>

                            <div>
                                <label for="proof" class="label">Proof of payment</label>
                                <input id="proof" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required
                                       class="block w-full rounded-control border border-dashed border-ink-300 bg-canvas px-4 py-4 text-sm text-ink-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-brand-400">
                                <p class="mt-1.5 text-xs text-ink-500">Bank slip or mobile money screenshot. PDF, JPG or PNG, up to 5 MB.</p>
                                @error('proof') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                                @error('method') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                            </div>

                            <x-button icon="upload">Submit payment</x-button>
                        </form>
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
                                    <td class="px-5 py-3.5 font-mono text-ink-700">{{ $payment->transaction_reference }}</td>
                                    <td class="px-5 py-3.5 text-ink-900">{{ $payment->formattedAmount() }}</td>
                                    <td class="px-5 py-3.5">
                                        <x-status :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-status>
                                        @if ($payment->rejection_reason)<p class="mt-1 text-xs text-red-700">{{ $payment->rejection_reason }}</p>@endif
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
