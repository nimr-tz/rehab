{{-- After payment: the participant's badge is their summit ID, with what to do next beside it. --}}
@php
    use App\Enums\PaymentStatus;

    $paid = $registration->payments->firstWhere('status', PaymentStatus::Verified);
@endphp

<div class="grid items-start gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
    <div class="lg:sticky lg:top-6">
        <x-badge-card :registration="$registration" />
        <p class="mx-auto mt-4 max-w-[360px] text-center text-xs text-ink-500">
            Your summit ID. Wear it throughout: staff scan the code at the entrance and at each session for your CPD certificate.
        </p>
    </div>

    <div class="space-y-6">
        <x-card>
            <div class="flex items-start gap-4">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"><x-icon name="check-circle" class="h-7 w-7" /></span>
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Registration confirmed</p>
                    <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-ink-900">You are going to the {{ $summit->title() }}</h2>
                    <p class="mt-1 text-sm text-ink-600">{{ collect([$summit->dateRange(), $summit->venueLine()])->filter()->implode(' · ') ?: 'Dates and venue to be announced.' }}</p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <x-button :href="route('registration.badge')" icon="download">Download badge (PDF)</x-button>
                @if ($registration->needs_invitation_letter)
                    <x-button variant="secondary" :href="route('registration.letter')" icon="download">Invitation letter</x-button>
                @endif
                <x-button variant="ghost" :href="route('programme')" icon="calendar">View the programme</x-button>
            </div>

            <dl class="mt-6 grid gap-x-6 gap-y-4 border-t border-ink-100 pt-5 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-500">Badge no.</dt><dd class="mt-0.5 font-mono font-semibold text-ink-900">{{ $registration->reference }}</dd></div>
                <div><dt class="text-ink-500">Category</dt><dd class="mt-0.5 font-medium text-ink-900">{{ $registration->category->name }}</dd></div>
                @if ($paid)
                    <div><dt class="text-ink-500">Paid</dt><dd class="mt-0.5 font-medium text-ink-900">{{ $paid->formattedAmount() }} by {{ $paid->channel() }}, {{ ($paid->reviewed_at ?? $paid->created_at)->format('j M Y') }}</dd></div>
                    <div><dt class="text-ink-500">{{ $paid->channel() }} transaction</dt><dd class="mt-0.5 font-mono font-semibold text-ink-900">{{ $paid->transaction_reference }}</dd></div>
                @elseif ($registration->isFullyWaived())
                    <div class="sm:col-span-2"><dt class="text-ink-500">Waived by the organisers</dt><dd class="mt-0.5 font-medium text-emerald-700">Full fee</dd></div>
                @endif
            </dl>
        </x-card>

        <x-card title="At the summit">
            <ul class="space-y-4 text-sm text-ink-700">
                <li class="flex gap-3"><x-icon name="printer" class="h-5 w-5 shrink-0 text-brand-600" /><span>Print your badge on A6 or A4 paper, or keep the PDF on your phone.</span></li>
                <li class="flex gap-3"><x-icon name="qr" class="h-5 w-5 shrink-0 text-brand-600" /><span>Have the code on your badge scanned at the entrance and at each session. Your CPD certificate counts these scans.</span></li>
                <li class="flex gap-3"><x-icon name="map-pin" class="h-5 w-5 shrink-0 text-brand-600" /><span>{{ $summit->venueLine() ?? 'The venue will be announced soon.' }}{{ $summit->dateRange() ? ', '.$summit->dateRange() : '' }}.</span></li>
                <li class="flex gap-3"><x-icon name="info" class="h-5 w-5 shrink-0 text-brand-600" /><span>Lost your badge? The registration desk reprints it from your badge number.</span></li>
            </ul>
            <p class="mt-5 border-t border-ink-100 pt-4 text-xs text-ink-500">
                Your badge shows the details in your profile. Something wrong? <a href="{{ route('profile.edit') }}" class="font-semibold text-brand-700 hover:underline">Update your profile</a> before you print it.
            </p>
        </x-card>
    </div>
</div>
