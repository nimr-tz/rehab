@props(['tone' => 'neutral'])

{{-- Status pill. Tones: success, warning, danger, info, neutral. --}}
@php
    [$pill, $dot] = match ($tone) {
        'success' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'warning' => ['bg-amber-50 text-amber-800', 'bg-amber-500'],
        'danger' => ['bg-red-50 text-red-700', 'bg-red-500'],
        'info' => ['bg-brand-50 text-brand-800', 'bg-brand-500'],
        default => ['bg-ink-100 text-ink-600', 'bg-ink-400'],
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold', $pill]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>{{ $slot }}
</span>
