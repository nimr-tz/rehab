@props(['percent' => 0, 'label' => 'complete', 'size' => 132, 'color' => '#f2b302', 'track' => 'rgb(255 255 255 / 0.15)'])

{{-- Progress ring with the percentage in the middle. --}}
@php $pct = max(0, min(100, round($percent))); @endphp

<div {{ $attributes->class(['relative shrink-0']) }} style="width: {{ $size }}px; height: {{ $size }}px" role="img" aria-label="{{ $pct }}% {{ $label }}">
    <svg viewBox="0 0 42 42" class="h-full w-full">
        <circle cx="21" cy="21" r="15.9155" fill="none" stroke="{{ $track }}" stroke-width="5" />
        <circle cx="21" cy="21" r="15.9155" fill="none" stroke="{{ $color }}" stroke-width="5" stroke-linecap="round"
                stroke-dasharray="{{ $pct }} {{ 100 - $pct }}" stroke-dashoffset="25" />
    </svg>
    <div class="absolute inset-0 grid place-items-center text-center">
        <div>
            <p class="text-2xl font-extrabold">{{ $pct }}%</p>
            <p class="text-[11px] opacity-80">{{ $label }}</p>
        </div>
    </div>
</div>
