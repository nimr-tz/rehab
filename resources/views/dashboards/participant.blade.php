@php
    use App\Enums\AbstractStatus;
    use App\Enums\PaymentStatus;
    use App\Enums\RegistrationStatus;

    $greeting = $user->title ? $user->title.' '.$user->last_name : $user->first_name;
    $confirmed = $registration?->isConfirmed() ?? false;
@endphp

<x-layouts.portal title="Overview">
    <x-slot:header>
        <x-page-header :title="'Welcome back, '.$greeting" :description="now()->format('l j F').' · here is where your Summit stands'" />
    </x-slot:header>

    {{-- Journey + countdowns --}}
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <section class="relative overflow-hidden rounded-[26px] bg-brand-700 p-6 text-white shadow-lift sm:p-8">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/5"></div>
            <div class="relative flex flex-wrap items-start justify-between gap-3">
                <div class="max-w-xl">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-sun-400">Your summit journey</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-[1.9rem]">{{ $headline }}</h2>
                    <p class="mt-2 text-[15px] leading-relaxed text-brand-100">{{ $detail }}</p>
                </div>
                <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold">Step {{ $current + 1 }} of {{ count($steps) }}</span>
            </div>

            <ol class="relative mt-8 grid grid-cols-2 gap-y-6 sm:grid-cols-5">
                @foreach ($steps as $i => [$label, $note, $done])
                    @php $now = $i === $current && ! $done; @endphp
                    <li class="relative pr-3">
                        <div class="flex items-center">
                            <span @class([
                                'z-10 grid h-9 w-9 shrink-0 place-items-center rounded-full text-sm font-bold',
                                'bg-sun-400 text-ink-900' => $done,
                                'bg-white text-ember-600 ring-4 ring-ember-500/40' => $now,
                                'border-2 border-white/30 text-white/70' => ! $done && ! $now,
                            ])>
                                @if ($done) <x-icon name="check" class="h-4 w-4" /> @else {{ $i + 1 }} @endif
                            </span>
                            @unless ($loop->last)
                                <span class="ml-2 hidden h-0.5 flex-1 sm:block {{ $done ? 'bg-sun-400' : 'bg-white/20' }}"></span>
                            @endunless
                        </div>
                        <p class="mt-3 text-sm font-bold {{ $done || $now ? 'text-white' : 'text-white/60' }}">{{ $label }}</p>
                        <p class="text-xs {{ $done || $now ? 'text-brand-100' : 'text-white/45' }}">{{ $note }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="relative mt-7 flex flex-wrap gap-2">
                @if (! $registration)
                    <x-button :href="route('registration.show')" class="!bg-sun-400 !text-ink-900 hover:!bg-sun-300">Register now</x-button>
                @elseif ($registration->status === RegistrationStatus::PendingPayment)
                    <x-button :href="route('registration.show')" class="!bg-sun-400 !text-ink-900 hover:!bg-sun-300" icon="banknotes">Pay {{ $registration->formattedAmount() }}</x-button>
                @elseif ($confirmed)
                    <x-button :href="route('registration.badge')" class="!bg-sun-400 !text-ink-900 hover:!bg-sun-300" icon="download">Download badge</x-button>
                    <x-button :href="route('programme')" variant="ghost" class="!text-white hover:!bg-white/10" icon="calendar">Plan your sessions</x-button>
                @endif
            </div>
        </section>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-1">
            @forelse ($countdowns as $countdown)
                <div class="rounded-card border border-ink-100 bg-white p-6 shadow-soft">
                    <div class="flex items-start justify-between gap-3">
                        <p><span class="text-[2.6rem] font-extrabold leading-none tracking-tight" style="color: {{ $countdown['color'] }}">{{ $countdown['days'] }}</span>
                            <span class="text-lg font-semibold text-ink-500">days</span></p>
                        <span class="text-sm font-semibold text-ink-600">{{ $countdown['date'] }}</span>
                    </div>
                    <p class="mt-2 font-semibold text-ink-800">{{ $countdown['label'] }}</p>
                    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full" style="width: {{ $countdown['progress'] }}%; background: {{ $countdown['color'] }}"></div></div>
                </div>
            @empty
                <div class="rounded-card border border-ink-100 bg-white p-6 shadow-soft">
                    <p class="font-semibold text-ink-900">{{ $summit->title() }}</p>
                    <p class="mt-1 text-sm text-ink-500">{{ $summit->dateRange() ?? 'Dates to be announced' }}</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Payment + badge --}}
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-ink-900">Registration payment</h2>
                @if ($registration)
                    <x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status>
                @endif
            </div>

            @if ($registration)
                <dl class="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-3">
                    <div><dt class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink-500">Amount</dt><dd class="mt-1.5 text-2xl font-extrabold tracking-tight text-ink-900">{{ $registration->formattedAmount() }}</dd></div>
                    <div><dt class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink-500">Category</dt><dd class="mt-1.5 font-semibold text-ink-900">{{ $registration->category->name }}</dd></div>
                    <div><dt class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink-500">Payment reference</dt><dd class="mt-1.5 font-mono font-bold text-brand-700">{{ $registration->reference }}</dd></div>
                    @if ($latestPayment)
                        <div class="sm:col-span-3"><dt class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink-500">{{ $latestPayment->channel() }} transaction</dt><dd class="mt-1.5 font-mono font-semibold text-ink-900">{{ $latestPayment->transaction_reference }}</dd></div>
                    @endif
                </dl>
                <ul class="mt-5 space-y-3 border-t border-ink-100 pt-5 text-sm">
                    <li class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-olive-700"></span><span class="flex-1 text-ink-700">Payment reference issued</span><span class="text-ink-500">{{ $registration->created_at->format('j M') }}</span></li>
                    @if ($latestPayment)
                        <li class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-olive-700"></span><span class="flex-1 text-ink-700">{{ $latestPayment->channel() }} payment submitted</span><span class="text-ink-500">{{ $latestPayment->created_at->format('j M H:i') }}</span></li>
                        <li class="flex items-center gap-3">
                            <span class="h-2.5 w-2.5 rounded-full {{ $latestPayment->status === PaymentStatus::Verified ? 'bg-olive-700' : ($latestPayment->status === PaymentStatus::Rejected ? 'bg-red-600' : 'bg-ember-500') }}"></span>
                            <span class="flex-1 text-ink-700">Finance verification</span>
                            <span class="{{ $latestPayment->status === PaymentStatus::Submitted ? 'font-semibold text-ember-600' : 'text-ink-500' }}">{{ $latestPayment->reviewed_at?->format('j M') ?? 'Pending' }}</span>
                        </li>
                    @else
                        <li class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-ember-500"></span><span class="flex-1 text-ink-700">Pay and upload your proof</span><x-button size="sm" :href="route('registration.show')">Pay now</x-button></li>
                    @endif
                </ul>
            @else
                <x-empty icon="ticket" title="You have not registered yet" class="!py-8">
                    Registration covers all sessions, materials, meals and your CPD certificate.
                    <x-slot:action><x-button :href="route('registration.show')">Register</x-button></x-slot:action>
                </x-empty>
            @endif
        </x-card>

        <x-card>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-ink-900">Badge &amp; check-in</h2>
                <span class="text-sm font-semibold {{ $confirmed ? 'text-emerald-700' : 'text-ink-500' }}">{{ $confirmed ? 'Ready' : 'Locked' }}</span>
            </div>
            <a href="{{ route('registration.badge.show') }}" class="mt-5 flex items-center gap-4 rounded-2xl border border-sun-200 bg-gradient-to-br from-sun-50 to-ember-50 p-5 transition hover:shadow-soft">
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-ember-600">{{ $registration?->category->is_student ? 'Student' : 'Participant' }}</p>
                    <p class="mt-1 truncate text-xl font-extrabold text-ink-900">{{ $registration?->displayName() ?? $user->name }}</p>
                    <p class="truncate text-sm text-ink-600">{{ $user->institution ?? $user->countryName() }}</p>
                    <p class="mt-2 font-mono text-xs font-bold text-brand-700">{{ $registration?->reference ?? 'Not registered' }}</p>
                </div>
                <div class="grid h-24 w-24 shrink-0 place-items-center rounded-xl bg-white p-1.5 {{ $confirmed ? '' : 'opacity-40 blur-[1.5px]' }}">
                    @if ($registration)
                        <img src="{{ \App\Support\Qr::dataUri($registration->qr_token, 4) }}" alt="{{ $confirmed ? 'Your check-in code' : '' }}" class="h-full w-full">
                    @else
                        <x-icon name="qr" class="h-12 w-12 text-ink-300" />
                    @endif
                </div>
            </a>
            <p class="mt-4 text-sm text-ink-600">
                @if ($confirmed)
                    Show the QR code at the registration desk{{ $edition?->start_date ? ' on '.$edition->start_date->format('j F') : '' }}, or print the badge at home.
                @else
                    Your QR badge activates once payment is verified. Scan it at the registration desk or print it at home.
                @endif
            </p>
        </x-card>
    </div>

    {{-- Abstracts + quick actions --}}
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <x-card>
            <div class="flex items-center justify-between gap-3 border-b border-ink-100 pb-4">
                <h2 class="text-lg font-bold text-ink-900">My abstracts</h2>
                @if ($edition?->acceptsAbstracts())
                    <x-button size="sm" :href="route('abstracts.create')" icon="plus">New abstract</x-button>
                @endif
            </div>
            @forelse ($abstracts as $abstract)
                @php
                    $total = $abstract->reviews->count();
                    $doneReviews = $abstract->reviews->filter->isComplete()->count();
                    $progress = match ($abstract->status) {
                        AbstractStatus::Draft => 15, AbstractStatus::Submitted => 30,
                        AbstractStatus::UnderReview => 40 + ($total ? $doneReviews / $total * 45 : 0),
                        default => 100,
                    };
                @endphp
                <div class="border-b border-ink-100 py-4 last:border-0 last:pb-0">
                    <div class="flex items-start gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-semibold text-ink-500">{{ $abstract->code ?? $abstract->blindId() }}</span>
                                <x-status :tone="$abstract->status->tone()">{{ $abstract->status->label() }}</x-status>
                            </div>
                            <a href="{{ route('abstracts.show', $abstract) }}" class="mt-1.5 block font-semibold text-ink-900 hover:text-brand-700">{{ $abstract->title }}</a>
                            <div class="mt-2 flex items-center gap-3">
                                <div class="h-1.5 w-40 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full {{ $abstract->status === AbstractStatus::Rejected ? 'bg-red-500' : ($abstract->status === AbstractStatus::Accepted ? 'bg-olive-700' : 'bg-ember-500') }}" style="width: {{ $progress }}%"></div></div>
                                <span class="text-xs text-ink-500">
                                    @if ($abstract->status === AbstractStatus::UnderReview) {{ $doneReviews }} of {{ $total }} reviews in · @endif
                                    @if ($abstract->sessions->isNotEmpty()) {{ $abstract->sessions->first()->starts_at->format('D j M, H:i') }} · @endif
                                    {{ $abstract->topic->name }}
                                </span>
                            </div>
                        </div>
                        <x-button variant="secondary" size="sm" :href="$abstract->status === AbstractStatus::Draft ? route('abstracts.edit', $abstract) : route('abstracts.show', $abstract)">
                            {{ $abstract->status === AbstractStatus::Draft ? 'Continue' : 'View' }}
                        </x-button>
                    </div>
                </div>
            @empty
                <x-empty icon="document" title="No abstracts yet" class="!py-8">
                    {{ $edition?->acceptsAbstracts() ? 'Submission is open until '.\App\Support\Summit::formatDate($edition->abstract_deadline).'.' : 'Abstract submission is closed.' }}
                </x-empty>
            @endforelse
        </x-card>

        <div class="space-y-3">
            @php
                $letterReady = $confirmed && $registration->needs_invitation_letter;
                $actions = [
                    ['bg-olive-700', 'Invitation letter',
                        $letterReady ? 'Ready to download' : ($registration?->needs_invitation_letter ? 'Ready once payment is verified' : 'Request one when you register'),
                        $letterReady ? 'Download' : 'Open', $letterReady ? route('registration.letter') : route('registration.show')],
                    ['bg-sun-400', 'Badge', $confirmed ? 'Print it or show it on your phone' : 'Unlocks after payment', $confirmed ? 'Download' : 'Open', $confirmed ? route('registration.badge') : route('registration.badge.show')],
                    ['bg-brand-700', 'Programme', $summit->dateRange() ?? 'Published after review', 'View', route('programme')],
                    ['bg-coral-400', 'Profile', 'Name, institution and contacts on your badge', 'Edit', route('profile.edit')],
                ];
            @endphp
            @foreach ($actions as [$bar, $label, $hint, $cta, $href])
                <a href="{{ $href }}" class="flex items-center gap-4 rounded-2xl border border-ink-100 bg-white p-4 shadow-soft transition hover:border-brand-200">
                    <span class="h-10 w-1.5 shrink-0 rounded-full {{ $bar }}"></span>
                    <span class="min-w-0 flex-1"><span class="block font-semibold text-ink-900">{{ $label }}</span><span class="block truncate text-sm text-ink-500">{{ $hint }}</span></span>
                    <span class="text-sm font-bold text-brand-700">{{ $cta }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Agenda + CPD --}}
    @if ($agenda->isNotEmpty())
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <x-card x-data="{ day: 0 }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-ink-900">Summit agenda</h2>
                    <div class="flex gap-1 rounded-full bg-ink-100 p-1">
                        @foreach ($agenda->keys() as $i => $date)
                            <button type="button" @click="day = {{ $i }}" :class="day === {{ $i }} ? 'bg-brand-700 text-white' : 'text-ink-600'" class="rounded-full px-3.5 py-1.5 text-xs font-bold">{{ \Carbon\Carbon::parse($date)->format('D j') }}</button>
                        @endforeach
                    </div>
                </div>
                @foreach ($agenda->values() as $i => $sessions)
                    <ul x-show="day === {{ $i }}" @if ($i) x-cloak @endif class="mt-4 space-y-2">
                        @foreach ($sessions->take(6) as $session)
                            @php $isMine = $mySessions->contains($session->id); @endphp
                            <li class="flex items-center gap-4 rounded-2xl px-4 py-3 {{ $isMine ? 'bg-sun-50 ring-1 ring-sun-200' : 'bg-canvas' }}">
                                <span class="w-12 shrink-0 text-sm font-bold tabular-nums text-ink-900">{{ $session->starts_at->format('H:i') }}</span>
                                <span class="h-9 w-1 shrink-0 rounded-full {{ ['plenary' => 'bg-ember-500', 'parallel' => 'bg-brand-600', 'posters' => 'bg-olive-700', 'panel' => 'bg-sun-400'][$session->kind] ?? 'bg-ink-300' }}"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold text-ink-900">{{ $session->title }}</span>
                                    <span class="block text-xs text-ink-500">{{ $session->hall }}@if ($isMine) · <span class="font-bold text-ember-600">You present</span>@endif</span>
                                </span>
                                <span class="hidden rounded-full border border-ink-200 bg-white px-2.5 py-1 text-xs font-semibold text-ink-600 sm:inline">{{ $session->kindLabel() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
                <a href="{{ route('programme') }}" class="mt-4 inline-block text-sm font-bold text-brand-700 hover:underline">Full programme →</a>
            </x-card>

            <section class="self-start rounded-card border border-sun-200 bg-sun-50 p-6">
                <h2 class="text-lg font-bold text-ink-900">CPD certificate</h2>
                <p class="mt-4"><span class="text-[2.6rem] font-extrabold leading-none text-ember-600">0</span> <span class="font-semibold text-ink-700">of {{ $cpdSessions }} sessions attended</span></p>
                <p class="mt-3 text-sm leading-relaxed text-ink-600">Each session you attend is scanned at the door and counts toward your certificate. It is issued in the portal after the Summit.</p>
                <div class="mt-5 grid grid-cols-9 gap-1">
                    @for ($i = 0; $i < min(9, $cpdSessions); $i++)<span class="h-1.5 rounded-full bg-sun-200"></span>@endfor
                </div>
            </section>
        </div>
    @endif
</x-layouts.portal>
