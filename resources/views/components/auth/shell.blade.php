@props([
    'title',
    'heading',
    'intro' => null,
    'wide' => false,
])

{{-- Shared frame for the sign-in screens: backdrop, top bar, brand panel and the card. --}}
<x-layouts.public class="min-h-screen bg-canvas" :title="$title.' · '.$summit->get('organiser').' Events Portal'">
    <x-auth.backdrop />

    <header class="relative z-10 flex items-center justify-between px-5 py-5 sm:px-10">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 lg:invisible">
            <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="" class="h-9 w-auto">
            <span class="leading-tight">
                <span class="block text-sm font-bold text-ink-900">{{ $summit->get('organiser') }}</span>
                <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700">Events Portal</span>
            </span>
        </a>
        <p class="text-sm text-ink-500">
            <span class="hidden sm:inline">Need help?</span>
            <a href="mailto:{{ $summit->get('contact_email') }}" class="font-semibold text-brand-700 hover:text-brand-800">Contact support</a>
        </p>
    </header>

    <main class="relative z-10 mx-auto grid max-w-7xl items-center gap-10 px-5 pb-14 sm:px-10 lg:min-h-[calc(100vh-88px)] lg:grid-cols-[1fr_1fr] lg:gap-16 lg:pb-10">

        <section class="hidden flex-col items-center text-center lg:flex">
            <a href="{{ route('home') }}" class="relative block aspect-square w-full max-w-[26rem]"
               x-data="brandLogo('{{ asset('images/brand/logo-2027-animated.webp') }}')">
                <div class="absolute inset-[12%] rounded-full bg-white/70 blur-2xl"></div>
                <img src="{{ asset('images/brand/logo-2027-still.webp') }}" alt="{{ $summit->get('organiser') }} {{ $summit->get('year') }} Events Portal"
                     :class="{ 'opacity-0': stillHidden }" class="relative h-full w-full object-contain">
                <img x-ref="anim" alt="" aria-hidden="true" style="display: none" :style="{ display: animated ? 'block' : 'none' }"
                     class="absolute inset-0 h-full w-full object-contain">
            </a>
            <p class="mt-8 flex items-center gap-5 text-lg font-semibold tracking-[0.32em] text-brand-800">
                PEOPLE <span class="h-2 w-2 rounded-full bg-ember-500"></span>
                CARE <span class="h-2 w-2 rounded-full bg-olive-700"></span>
                TOGETHER
            </p>
            <p class="mt-3 text-sm tracking-[0.3em] text-ink-500">A healthier tomorrow, together.</p>
        </section>

        <section @class(['mx-auto w-full', 'max-w-[34rem]' => $wide, 'max-w-[30rem]' => ! $wide])>
            <div class="rounded-card border border-white bg-white/90 p-7 shadow-lift backdrop-blur sm:p-10">
                <div class="text-center">
                    <a href="{{ route('home') }}"><img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="{{ $summit->get('organiser') }}" class="mx-auto h-16 w-auto sm:h-20"></a>
                    <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-ink-900 sm:text-[2.1rem]">{{ $heading }}</h1>
                    @if ($intro)
                        <p class="mx-auto mt-2 max-w-sm text-[15px] leading-relaxed text-ink-500">{{ $intro }}</p>
                    @endif
                </div>

                <div class="mt-8">
                    {{ $slot }}
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-ink-400">© {{ now()->year }} {{ $summit->get('organiser') }} · Transforming Lives</p>
        </section>
    </main>
</x-layouts.public>
