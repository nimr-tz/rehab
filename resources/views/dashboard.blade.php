@php
    use App\Enums\RegistrationStatus;

    $greeting = $user->title ? $user->title.' '.$user->last_name : $user->first_name;
    $isParticipant = $user->isParticipant();

    // The single most useful next step for a participant.
    $next = match (true) {
        ! $isParticipant => null,
        ! $registration && $edition?->registration_open => ['ticket', 'bg-sun-100 text-sun-800', 'Register for the '.$summit->title(), 'Choose your category. Fees start from '.($edition->categories->filter->hasFee()->where('currency', $user->country === 'TZ' ? 'TZS' : 'USD')->sortBy('amount')->first()?->formattedFee() ?? '—').'.', 'Register now', route('registration.show')],
        $registration?->status === RegistrationStatus::PendingPayment => ['banknotes', 'bg-sun-100 text-sun-800', 'Pay your registration fee', $registration->formattedAmount().' · reference '.$registration->reference.'. Pay by bank transfer or mobile money, then upload the proof.', 'Pay now', route('registration.show')],
        $registration?->status === RegistrationStatus::PaymentSubmitted => ['clock', 'bg-brand-100 text-brand-800', 'Your payment is being verified', 'Our finance team usually confirms payments within one working day. We will email you.', 'View registration', route('registration.show')],
        $abstracts->isEmpty() && $edition?->acceptsAbstracts() => ['document', 'bg-coral-100 text-coral-800', 'Submit an abstract', 'Share your work with the summit. Submissions close '.\App\Support\Summit::formatDate($edition->abstract_deadline).'.', 'Start an abstract', route('abstracts.create')],
        $registration?->isConfirmed() => ['check-circle', 'bg-emerald-100 text-emerald-700', 'You are all set', 'Your registration is confirmed. Download your badge and bring it to the registration desk.', 'Download badge', route('registration.badge')],
        default => null,
    };
@endphp

<x-layouts.portal title="Dashboard">
    <x-page-header :eyebrow="$summit->title()" :title="'Karibu, '.$greeting">
        <x-button variant="secondary" size="sm" :href="route('programme')" icon="calendar">Programme</x-button>
    </x-page-header>

    @if ($next)
        <div class="flex flex-col gap-5 rounded-card border border-ink-100 bg-white p-6 shadow-soft sm:flex-row sm:items-center">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl {{ $next[1] }}">
                <x-icon :name="$next[0]" class="h-6 w-6" />
            </div>
            <div class="flex-1">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Next step</p>
                <p class="mt-1 text-lg font-semibold text-ink-900">{{ $next[2] }}</p>
                <p class="text-sm text-ink-500">{{ $next[3] }}</p>
            </div>
            <x-button :href="$next[5]">{{ $next[4] }}</x-button>
        </div>
    @endif

    {{-- Staff work queues --}}
    @if ($user->isStaff())
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @if ($staff['reviews'] !== null)
                <x-stat label="Reviews waiting for you" :value="$staff['reviews']" icon="star" tint="bg-sun-100 text-sun-800" :href="route('reviews.index')" />
            @endif
            @if ($staff['toAssign'] !== null)
                <x-stat label="Abstracts to assign" :value="$staff['toAssign']" icon="clipboard" tint="bg-coral-100 text-coral-800" :href="route('scientific.abstracts.index', ['status' => 'submitted'])" />
                <x-stat label="Ready for a decision" :value="$staff['toDecide']" icon="check-circle" tint="bg-olive-100 text-olive-800" :href="route('scientific.abstracts.index', ['status' => 'under_review'])" />
            @endif
            @if ($staff['payments'] !== null)
                <x-stat label="Payments to verify" :value="$staff['payments']" icon="banknotes" tint="bg-ember-100 text-ember-800" :href="route('finance.payments.index')" />
            @endif
            @if ($staff['isDesk'])
                <x-stat label="Registration desk" value="Check-in" icon="qr" :href="route('desk.index')" />
            @endif
            @if ($staff['isAdmin'])
                <x-stat label="Summit overview" value="Reports" icon="chart" :href="route('admin.overview')" />
            @endif
        </div>
    @endif

    @if ($isParticipant)
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            {{-- Registration --}}
            <x-card title="My registration">
                <x-slot:actions>
                    <x-button variant="ghost" size="sm" :href="route('registration.show')">Open</x-button>
                </x-slot:actions>

                @if ($registration)
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div><dt class="text-ink-500">Status</dt><dd class="mt-1"><x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status></dd></div>
                        <div><dt class="text-ink-500">Reference</dt><dd class="mt-1 font-semibold text-ink-900">{{ $registration->reference }}</dd></div>
                        <div><dt class="text-ink-500">Category</dt><dd class="mt-1 font-medium text-ink-900">{{ $registration->category->name }}</dd></div>
                        <div><dt class="text-ink-500">Fee</dt><dd class="mt-1 font-medium text-ink-900">{{ $registration->formattedAmount() }}</dd></div>
                    </dl>
                    @if ($registration->isConfirmed())
                        <div class="mt-5 flex flex-wrap gap-2 border-t border-ink-100 pt-5">
                            <x-button size="sm" :href="route('registration.badge')" icon="download">Badge</x-button>
                            @if ($registration->needs_invitation_letter)
                                <x-button size="sm" variant="secondary" :href="route('registration.letter')" icon="download">Invitation letter</x-button>
                            @endif
                        </div>
                    @endif
                @else
                    <x-empty icon="ticket" title="You have not registered yet" class="!py-6">
                        Registration covers all sessions, materials, meals and your CPD certificate.
                        <x-slot:action><x-button size="sm" :href="route('registration.show')">Register</x-button></x-slot:action>
                    </x-empty>
                @endif
            </x-card>

            {{-- Abstracts --}}
            <x-card title="My abstracts">
                <x-slot:actions>
                    @if ($edition?->acceptsAbstracts())
                        <x-button variant="ghost" size="sm" :href="route('abstracts.create')" icon="plus">New</x-button>
                    @endif
                </x-slot:actions>

                @forelse ($abstracts->take(4) as $abstract)
                    <a href="{{ route('abstracts.show', $abstract) }}" class="-mx-2 flex items-center justify-between gap-3 rounded-xl px-2 py-2.5 hover:bg-ink-50">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-ink-900">{{ $abstract->title }}</span>
                            <span class="block text-xs text-ink-500">{{ $abstract->code ?? $abstract->topic->name }}</span>
                        </span>
                        <x-status :tone="$abstract->status->tone()">{{ $abstract->status->label() }}</x-status>
                    </a>
                @empty
                    <x-empty icon="document" title="No abstracts yet" class="!py-6">
                        @if ($edition?->acceptsAbstracts())
                            Submission is open until {{ \App\Support\Summit::formatDate($edition->abstract_deadline) }}.
                        @else
                            Abstract submission is closed.
                        @endif
                    </x-empty>
                @endforelse
            </x-card>
        </div>
    @endif

    {{-- Programme teaser --}}
    @if ($upcomingSessions->isNotEmpty())
        <x-card title="From the programme" :description="$summit->dateRange().' · '.$summit->venueLine()">
            <x-slot:actions>
                <x-button variant="ghost" size="sm" :href="route('programme')">Full programme</x-button>
            </x-slot:actions>
            <div class="divide-y divide-ink-100">
                @foreach ($upcomingSessions as $session)
                    <div class="grid gap-1 py-3 first:pt-0 last:pb-0 sm:grid-cols-[170px_1fr] sm:gap-6">
                        <span class="text-sm text-ink-500">{{ $session->starts_at->format('D j M · H:i') }}</span>
                        <span>
                            <span class="block text-sm font-semibold text-ink-900">{{ $session->title }}</span>
                            <span class="text-xs font-semibold uppercase tracking-wider {{ $session->kindClasses() }}">{{ $session->kindLabel() }}</span>
                            @if ($session->hall)<span class="text-xs text-ink-500"> · {{ $session->hall }}</span>@endif
                        </span>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif
</x-layouts.portal>
