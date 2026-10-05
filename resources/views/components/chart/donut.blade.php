@props([
    'segments' => [], // list of ['label' => string, 'value' => int|float, 'color' => string]
    'total' => null,
    'caption' => null,
    'size' => 180,
])

{{-- Donut with a 2px surface gap between segments, the total in the middle, and a legend with values. --}}
@php
    $sum = array_sum(array_column($segments, 'value')) ?: 1;
    $r = 15.9155; // circumference 100
    $offset = 25;  // start at 12 o'clock
    $gap = count(array_filter($segments, fn ($s) => $s['value'] > 0)) > 1 ? 0.8 : 0;
@endphp

<div {{ $attributes->class(['flex flex-col items-center gap-5 sm:flex-row sm:items-center']) }}>
    <div class="relative shrink-0" style="width: {{ $size }}px; height: {{ $size }}px">
        <svg viewBox="0 0 42 42" class="h-full w-full -rotate-0" role="img" aria-label="{{ $caption ?? 'Breakdown' }}">
            <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="#eef1f5" stroke-width="7" />
            @foreach ($segments as $segment)
                @php $pct = $segment['value'] / $sum * 100; @endphp
                @if ($pct > 0)
                    <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="{{ $segment['color'] }}" stroke-width="7"
                            stroke-dasharray="{{ max(0, $pct - $gap) }} {{ 100 - max(0, $pct - $gap) }}" stroke-dashoffset="{{ $offset }}">
                        <title>{{ $segment['label'] }}: {{ number_format($segment['value']) }} ({{ round($pct) }}%)</title>
                    </circle>
                    @php $offset -= $pct; @endphp
                @endif
            @endforeach
        </svg>
        <div class="absolute inset-0 grid place-items-center text-center">
            <div>
                <p class="text-2xl font-extrabold tracking-tight text-brand-700">{{ $total ?? number_format($sum) }}</p>
                @if ($caption)<p class="text-xs text-ink-500">{{ $caption }}</p>@endif
            </div>
        </div>
    </div>
    <ul class="w-full space-y-2.5 text-sm">
        @foreach ($segments as $segment)
            <li class="flex items-center gap-2.5">
                <span class="h-3 w-3 shrink-0 rounded-[4px]" style="background: {{ $segment['color'] }}"></span>
                <span class="flex-1 text-ink-700">{{ $segment['label'] }}</span>
                <span class="font-bold text-ink-900">{{ number_format($segment['value']) }}</span>
                <span class="w-10 text-right text-xs text-ink-500">{{ round($segment['value'] / $sum * 100) }}%</span>
            </li>
        @endforeach
    </ul>
</div>
