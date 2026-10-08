@php
    use App\Enums\RegistrationStatus;
    $confirmed = $registration?->isConfirmed() ?? false;
@endphp

<x-layouts.portal title="Badge & check-in">
    <x-slot:header>
        <x-page-header title="Badge & check-in" :description="$confirmed ? 'Your badge is your summit ID. Print it, or show it on your phone.' : 'Your badge unlocks once your payment is verified.'" />
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        {{-- The badge, as it prints --}}
        <x-badge-card :registration="$registration" />

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
                            Pay {{ $registration->formattedDue() }} with reference {{ $registration->reference }} to unlock your badge.
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
