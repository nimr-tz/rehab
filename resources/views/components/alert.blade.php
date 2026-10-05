@props(['tone' => 'info'])

@php
    [$box, $iconColour, $icon] = match ($tone) {
        'success' => ['border-emerald-100 bg-emerald-50 text-emerald-900', 'text-emerald-600', 'check-circle'],
        'warning' => ['border-amber-100 bg-amber-50 text-amber-900', 'text-amber-600', 'warning'],
        'danger' => ['border-red-100 bg-red-50 text-red-900', 'text-red-600', 'warning'],
        default => ['border-brand-100 bg-brand-50 text-brand-900', 'text-brand-600', 'info'],
    };
@endphp

<div role="{{ in_array($tone, ['danger', 'warning']) ? 'alert' : 'status' }}"
     {{ $attributes->class(['flex gap-3 rounded-2xl border p-4 text-sm', $box]) }}>
    <x-icon :name="$icon" class="mt-0.5 h-5 w-5 shrink-0 {{ $iconColour }}" />
    <div>{{ $slot }}</div>
</div>
