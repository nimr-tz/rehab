@props(['place', 'size' => 'md'])

{{-- A round place marker: gold, silver and bronze in the brand colours. --}}
@php
    $tone = match ((int) $place) {
        1 => 'bg-sun-400 text-ink-900',
        2 => 'bg-brand-100 text-brand-800',
        default => 'bg-coral-200 text-coral-900',
    };
    $ordinal = match ((int) $place) { 1 => '1st', 2 => '2nd', default => '3rd' };
@endphp

<span {{ $attributes->class([
    'grid shrink-0 place-items-center rounded-full font-extrabold',
    'h-8 w-8 text-[11px]' => $size === 'sm',
    'h-11 w-11 text-xs' => $size === 'md',
    $tone,
]) }} aria-hidden="true">{{ $ordinal }}</span>
