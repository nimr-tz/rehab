@props([
    'points' => [],    // list of ['label' => string, 'value' => int], cumulative
    'target' => null,  // optional end value for a dashed straight "pace" line
    'height' => 200,
])

{{-- Cumulative line with a soft area, and an optional dashed pace line to a target. One y-scale. --}}
@php
    $n = max(2, count($points));
    $max = max(1, $target ?? 0, ...(array_column($points, 'value') ?: [1]));
    $x = fn ($i) => $i / ($n - 1) * 100;
    $y = fn ($v) => $height - 6 - ($v / $max * ($height - 14));
    $line = collect($points)->map(fn ($p, $i) => round($x($i), 2).','.round($y($p['value']), 2))->implode(' ');
    $last = end($points) ?: ['value' => 0, 'label' => ''];
@endphp

<div {{ $attributes->class(['w-full']) }}>
    <svg viewBox="0 0 100 {{ $height }}" preserveAspectRatio="none" class="block w-full overflow-visible" style="height: {{ $height }}px" role="img"
         aria-label="Trend reaching {{ $last['value'] }}{{ $target ? ' against a target of '.$target : '' }}">
        @foreach ([0.25, 0.5, 0.75] as $g)
            <line x1="0" x2="100" y1="{{ $y($max * $g) }}" y2="{{ $y($max * $g) }}" stroke="#eef1f5" stroke-width="1" vector-effect="non-scaling-stroke" />
        @endforeach
        @if ($points)
            <polygon points="0,{{ $height }} {{ $line }} 100,{{ $height }}" fill="#1b7fa3" fill-opacity="0.12" />
            @if ($target)
                <line x1="0" y1="{{ $y(0) }}" x2="100" y2="{{ $y($target) }}" stroke="#bd520a" stroke-width="2" stroke-dasharray="5 4" vector-effect="non-scaling-stroke" />
            @endif
            <polyline points="{{ $line }}" fill="none" stroke="#024f6d" stroke-width="2.5" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
            @foreach ($points as $i => $p)
                <rect x="{{ $x($i) - 50 / $n }}" y="0" width="{{ 100 / $n }}" height="{{ $height }}" fill="transparent">
                    <title>{{ $p['label'] }}: {{ number_format($p['value']) }}</title>
                </rect>
            @endforeach
        @endif
    </svg>
    @if ($points)
        <div class="mt-2 flex justify-between text-[11px] text-ink-500">
            <span>{{ $points[0]['label'] }}</span><span>{{ $last['label'] }}</span>
        </div>
    @endif
</div>
