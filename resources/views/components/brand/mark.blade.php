{{-- The small logo mark, its dots orbiting in a loop. A still for people who prefer less motion. --}}
@props(['alt' => ''])

<picture class="contents">
    <source srcset="{{ asset('images/brand/logo-mark-still.webp') }}" media="(prefers-reduced-motion: reduce)">
    <img src="{{ asset('images/brand/logo-mark-loop.webp') }}" alt="{{ $alt }}" width="160" height="128" {{ $attributes }}>
</picture>
