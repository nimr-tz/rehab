@props([
    'label',
    'value',
    'hint' => null,
    'hintTone' => 'muted',  // muted | warning | danger | good
    'progress' => null,     // 0-100 for the thin bar under the number
    'bar' => '#024f6d',
    'href' => null,
    'tint' => 'bg-white',
])

{{-- Headline number tile: small caps label, big value, a line of context and an optional progress bar. --}}
@php
    $tag = $href ? 'a' : 'div';
    $hintClass = match ($hintTone) {
        'warning' => 'text-sun-700', 'danger' => 'text-red-700', 'good' => 'text-emerald-700', default => 'text-ink-500',
    };
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['block rounded-card border border-ink-100 p-5 shadow-soft', $tint, 'transition hover:border-brand-200 hover:shadow-lift' => $href]) }}>
    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink-500">{{ $label }}</p>
    <p class="mt-2 text-[2rem] font-extrabold leading-none tracking-tight text-brand-700">{{ $value }}</p>
    @if ($hint)
        <p class="mt-2 text-sm font-medium {{ $hintClass }}">{{ $hint }}</p>
    @endif
    @if ($progress !== null)
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-ink-100">
            <div class="h-full rounded-full" style="width: {{ max(0, min(100, $progress)) }}%; background: {{ $bar }}"></div>
        </div>
    @endif
</{{ $tag }}>
