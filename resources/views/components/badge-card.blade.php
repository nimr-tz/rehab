{{--
    The participant's summit ID, as it prints (resources/views/pdf/badge.blade.php
    draws the same badge for the PDF). Locked until the registration is confirmed:
    the details show, the check-in code does not.
--}}
@props([
    'registration' => null,
    'user' => null,
    'size' => 'lg', // lg | sm
])

@php
    $user ??= $registration?->user ?? auth()->user();
    $confirmed = $registration?->isConfirmed() ?? false;
    $role = $registration?->badgeRole() ?? 'Participant';
    $name = $registration?->displayName() ?? $user->name;
    $initials = collect(preg_split('/\s+/', trim($user->first_name.' '.$user->last_name)))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
    $ribbon = match ($role) {
        'Presenter' => 'bg-brand-700 text-white',
        'Student' => 'bg-olive-700 text-white',
        default => 'bg-ember-500 text-white',
    };
    $sm = $size === 'sm';
@endphp

<div {{ $attributes->class(['relative mx-auto flex w-full flex-col overflow-hidden bg-white ring-1 ring-ink-100', 'max-w-[360px] rounded-[26px] shadow-lift' => ! $sm, 'max-w-[260px] rounded-[20px] shadow-soft' => $sm]) }}
     role="img" aria-label="Summit badge for {{ $name }}, {{ $role }}">
    {{-- Lanyard slot --}}
    <span @class(['absolute left-1/2 z-10 -translate-x-1/2 rounded-full bg-white/80', 'top-3 h-2 w-14' => ! $sm, 'top-2 h-1.5 w-10' => $sm])></span>

    <div @class(['flex items-center gap-3 bg-brand-700 text-white', 'px-6 pb-5 pt-8' => ! $sm, 'px-4 pb-3 pt-6' => $sm])>
        <span @class(['grid shrink-0 place-items-center rounded-xl bg-white', 'h-12 w-12' => ! $sm, 'h-9 w-9' => $sm])><img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="" @class(['h-9' => ! $sm, 'h-6' => $sm])></span>
        <span class="min-w-0 leading-tight">
            <span @class(['block font-bold', 'text-[15px]' => ! $sm, 'text-xs' => $sm])>{{ $summit->title() }}</span>
            <span @class(['mt-0.5 block uppercase tracking-[0.14em] text-brand-200', 'text-[11px]' => ! $sm, 'text-[9px]' => $sm])>{{ $summit->dateRange() ?? $summit->get('year') }}{{ $summit->get('city') ? ' · '.$summit->get('city') : '' }}</span>
        </span>
    </div>
    <div class="grid h-1.5 grid-cols-4"><span class="bg-ember-500"></span><span class="bg-coral-400"></span><span class="bg-olive-700"></span><span class="bg-sun-400"></span></div>

    <div @class(['flex flex-1 flex-col items-center text-center', 'px-6 pb-6 pt-7' => ! $sm, 'px-4 pb-4 pt-4' => $sm])>
        <span @class(['grid place-items-center rounded-full bg-brand-50 font-extrabold text-brand-700 ring-4 ring-brand-100', 'h-20 w-20 text-2xl' => ! $sm, 'h-12 w-12 text-base' => $sm])>{{ $initials }}</span>
        <p @class(['font-extrabold leading-tight tracking-tight text-ink-900', 'mt-4 text-[26px]' => ! $sm, 'mt-2.5 text-lg' => $sm])>{{ $name }}</p>
        @if ($user->profession)
            <p @class(['font-semibold text-brand-700', 'mt-1.5 text-[15px]' => ! $sm, 'mt-0.5 text-xs' => $sm])>{{ $user->profession }}</p>
        @endif
        @if ($user->institution)
            <p @class(['text-ink-600', 'mt-1 text-sm' => ! $sm, 'text-[11px]' => $sm])>{{ $user->institution }}</p>
        @endif
        @if ($user->countryName())
            <p @class(['text-ink-500', 'text-xs' => ! $sm, 'text-[10px]' => $sm])>{{ $user->countryName() }}</p>
        @endif

        <div @class(['mt-auto flex w-full items-end justify-between gap-3 text-left', 'pt-6' => ! $sm, 'pt-3' => $sm])>
            <div>
                <p @class(['font-bold uppercase tracking-[0.16em] text-ink-400', 'text-[10px]' => ! $sm, 'text-[8px]' => $sm])>Badge no.</p>
                <p @class(['font-mono font-bold tracking-wider text-ink-800', 'text-sm' => ! $sm, 'text-[11px]' => $sm])>{{ $registration?->reference ?? '—' }}</p>
            </div>
            <div @class(['relative shrink-0 rounded-lg bg-white ring-1 ring-ink-100', 'h-[84px] w-[84px] p-1' => ! $sm, 'h-14 w-14 p-0.5' => $sm])>
                @if ($registration)
                    <img src="{{ \App\Support\Qr::dataUri($registration->qr_token, 4) }}" alt="{{ $confirmed ? 'Check-in code' : '' }}" @class(['h-full w-full', 'opacity-20 blur-[2px]' => ! $confirmed])>
                @endif
                @unless ($confirmed)
                    <span class="absolute inset-0 grid place-items-center"><x-icon name="lock" @class(['text-ink-500', 'h-6 w-6' => ! $sm, 'h-4 w-4' => $sm]) /></span>
                @endunless
            </div>
        </div>
    </div>

    <div @class([$ribbon, 'text-center font-bold uppercase', 'py-3.5 text-sm tracking-[0.3em]' => ! $sm, 'py-2 text-[10px] tracking-[0.25em]' => $sm])>{{ $role }}</div>
</div>
