@props([
    'data' => [],        // list of ['label' => string, 'value' => int, 'title' => ?string]
    'highlight' => 0,    // the last N columns are drawn in the strong colour
    'height' => 190,
    'unit' => '',
])

{{-- Column chart. One series; the last column (today) is accented. Hover shows the value. --}}
@php
    $values = array_column($data, 'value');
    $max = max(1, ...($values ?: [1]));
    $n = max(1, count($data));
    $w = 100 / $n;
    $peak = $values ? max($values) : 0;
@endphp

<div {{ $attributes->class(['w-full']) }}>
    <svg viewBox="0 0 100 {{ $height }}" preserveAspectRatio="none" class="block w-full" style="height: {{ $height }}px" role="img"
         aria-label="Column chart, peak {{ $peak }}{{ $unit }}">
        <line x1="0" y1="{{ $height - 0.5 }}" x2="100" y2="{{ $height - 0.5 }}" stroke="#dfe4eb" stroke-width="1" vector-effect="non-scaling-stroke" />
        @foreach ($data as $i => $point)
            @php
                $h = max(2, $point['value'] / $max * ($height - 8));
                $last = $i === $n - 1;
                $strong = $i >= $n - $highlight;
                $fill = $last ? '#bd520a' : ($strong ? '#024f6d' : '#b0dbe9');
            @endphp
            <rect x="{{ $i * $w + $w * 0.14 }}" y="{{ $height - $h }}" width="{{ $w * 0.72 }}" height="{{ $h }}" rx="0.6" fill="{{ $fill }}">
                <title>{{ $point['title'] ?? $point['label'] }}: {{ $point['value'] }}{{ $unit }}</title>
            </rect>
        @endforeach
    </svg>
    @if ($data)
        <div class="mt-2 flex justify-between text-[11px] text-ink-500">
            <span>{{ $data[0]['label'] }}</span>
            <span>{{ $data[intdiv(count($data), 2)]['label'] }}</span>
            <span>{{ end($data)['label'] }}</span>
        </div>
    @endif
</div>
