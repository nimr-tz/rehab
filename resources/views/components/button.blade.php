@props([
    'variant' => 'primary', // primary | secondary | ghost | danger | success
    'size' => 'md',         // sm | md | lg
    'href' => null,
    'icon' => null,
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-control font-semibold transition focus:outline-none focus-visible:ring-4 disabled:pointer-events-none disabled:opacity-50',
        'h-9 px-3.5 text-[13px]' => $size === 'sm',
        'h-11 px-5 text-sm' => $size === 'md',
        'h-12 px-6 text-[15px]' => $size === 'lg',
        'bg-brand-700 text-white shadow-soft hover:bg-brand-800 focus-visible:ring-brand-500/30' => $variant === 'primary',
        'border border-ink-200 bg-white text-ink-800 hover:border-ink-300 hover:bg-ink-50 focus-visible:ring-brand-500/20' => $variant === 'secondary',
        'text-brand-700 hover:bg-brand-50 focus-visible:ring-brand-500/20' => $variant === 'ghost',
        'bg-red-700 text-white hover:bg-red-800 focus-visible:ring-red-500/30' => $variant === 'danger',
        'bg-emerald-700 text-white hover:bg-emerald-800 focus-visible:ring-emerald-500/30' => $variant === 'success',
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon) <x-icon :name="$icon" class="h-4 w-4" /> @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class($classes) }}>
        @if ($icon) <x-icon :name="$icon" class="h-4 w-4" /> @endif
        {{ $slot }}
    </button>
@endif
