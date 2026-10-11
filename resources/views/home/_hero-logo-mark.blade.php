{{-- The 2027 logo, animated in a loop over the still (see DESIGN_SYSTEM.md). --}}
<div x-data="brandLogo('{{ asset('images/brand/logo-2027-animated.webp') }}')" class="relative aspect-square w-full">
    <img src="{{ asset('images/brand/logo-2027-still.webp') }}" alt="{{ $summit->get('organiser') }} {{ $summit->get('year') }} Events Portal"
         :class="{ 'opacity-0': stillHidden }" class="h-full w-full object-contain">
    <img x-ref="anim" alt="" aria-hidden="true" style="display: none" :style="{ display: animated ? 'block' : 'none' }"
         class="absolute inset-0 h-full w-full object-contain">
</div>
