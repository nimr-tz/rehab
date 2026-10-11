{{-- The 2027 logo: built up once, then its dots orbit in a loop (see brandLogo in app.js). --}}
<div x-data="brandLogo('{{ asset('images/brand/logo-2027-intro.webp') }}', '{{ asset('images/brand/logo-2027-loop.webp') }}')"
     {{ $attributes->merge(['class' => 'relative aspect-square w-full']) }}>
    {{ $slot }}
    <img src="{{ asset('images/brand/logo-2027-still.webp') }}" alt="{{ $summit->get('organiser') }} {{ $summit->get('year') }} Events Portal"
         :class="{ 'opacity-0': stillHidden }" class="relative h-full w-full object-contain">
    <img x-ref="anim" alt="" aria-hidden="true" style="display: none" :style="{ display: animated ? 'block' : 'none' }"
         class="absolute inset-0 h-full w-full object-contain">
</div>
