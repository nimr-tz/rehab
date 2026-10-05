@php
    use App\Enums\RegistrationStatus;
    $confirmed = $registration?->isConfirmed() ?? false;
@endphp

<x-layouts.portal title="Badge & check-in">
    <x-slot:header>
        <x-page-header title="Badge & check-in" :description="$confirmed ? 'Your badge is ready. Print it, or show the QR code on your phone.' : 'Your badge unlocks once your payment is verified.'" />
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        {{-- Badge preview, as it prints --}}
        <div class="mx-auto w-full max-w-sm overflow-hidden rounded-[22px] bg-white shadow-lift ring-1 ring-ink-100">
            <div class="flex items-center gap-3 bg-brand-700 px-5 py-4 text-white">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-white"><img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="" class="h-8"></span>
                <span class="leading-tight"><span class="block font-bold">{{ $summit->title() }}</span><span class="block text-[11px] uppercase tracking-[0.14em] text-brand-200">{{ $summit->dateRange() ?? $summit->get('year') }}</span></span>
            </div>
            <div class="grid h-1.5 grid-cols-4"><span class="bg-ember-500"></span><span class="bg-coral-400"></span><span class="bg-olive-700"></span><span class="bg-sun-400"></span></div>
            <div class="px-6 pb-8 pt-7 text-center">
                <p class="text-2xl font-extrabold leading-tight text-brand-700">{{ $registration?->displayName() ?? auth()->user()->name }}</p>
                <p class="mt-1 text-sm text-ink-600">{{ auth()->user()->institution }}</p>
                <p class="text-xs text-ink-500">{{ auth()->user()->countryName() }}</p>
                <div class="relative mx-auto mt-5 h-40 w-40">
                    @if ($registration)
                        <img src="{{ \App\Support\Qr::dataUri($registration->qr_token, 6) }}" alt="{{ $confirmed ? 'Check-in QR code' : '' }}" class="h-full w-full {{ $confirmed ? '' : 'opacity-25 blur-[2px]' }}">
                    @endif
                    @unless ($confirmed)
                        <span class="absolute inset-0 grid place-items-center"><span class="rounded-full bg-ink-900/80 px-3 py-1.5 text-xs font-bold text-white">Locked</span></span>
                    @endunless
                </div>
                <p class="mt-2 font-mono text-xs font-bold tracking-wider text-ink-500">{{ $registration?->reference ?? '—' }}</p>
            </div>
            <div class="bg-ember-50 py-3 text-center text-xs font-bold uppercase tracking-[0.2em] text-ember-700">{{ $registration?->category->is_student ? 'Student' : 'Participant' }}</div>
        </div>

        <div class="space-y-5">
            <x-card title="Your badge">
                @if ($confirmed)
                    <p class="text-sm text-ink-600">Print it on A6 or A4 paper, or keep the PDF on your phone. Staff scan the QR code at the entrance and at each session door to record your CPD attendance.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <x-button :href="route('registration.badge')" icon="download">Download badge (PDF)</x-button>
                        @if ($registration->needs_invitation_letter)
                            <x-button variant="secondary" :href="route('registration.letter')" icon="download">Invitation letter</x-button>
                        @endif
                    </div>
                @elseif ($registration)
                    <p class="text-sm text-ink-600">
                        @if ($registration->status === RegistrationStatus::PaymentSubmitted)
                            Your payment is being verified. The badge unlocks as soon as finance confirms it.
                        @else
                            Pay {{ $registration->formattedAmount() }} with reference {{ $registration->reference }} to unlock your badge.
                        @endif
                    </p>
                    <x-button class="mt-5" :href="route('registration.show')">Go to payment</x-button>
                @else
                    <p class="text-sm text-ink-600">Register for the {{ $summit->title() }} first. Your badge is generated from your registration.</p>
                    <x-button class="mt-5" :href="route('registration.show')">Register</x-button>
                @endif
            </x-card>

            <x-card title="At the venue">
                <ul class="space-y-3 text-sm text-ink-700">
                    <li class="flex gap-3"><x-icon name="map-pin" class="h-5 w-5 shrink-0 text-brand-600" />{{ $summit->venueLine() ?? 'Venue to be announced' }}</li>
                    <li class="flex gap-3"><x-icon name="calendar" class="h-5 w-5 shrink-0 text-brand-600" />{{ $summit->dateRange() ?? 'Dates to be announced' }}</li>
                    <li class="flex gap-3"><x-icon name="qr" class="h-5 w-5 shrink-0 text-brand-600" />Bring your badge to the registration desk. Lost it? The desk can reprint it from your reference.</li>
                </ul>
            </x-card>
        </div>
    </div>
</x-layouts.portal>
