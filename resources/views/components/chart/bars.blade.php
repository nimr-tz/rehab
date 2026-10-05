@props([
    'rows' => [],          // list of ['label' => string, 'value' => int|float, 'display' => ?string, 'color' => ?string, 'href' => ?string]
    'max' => null,
    'color' => '#1b7fa3',
])

{{-- Horizontal bar list: label, bar, value. Values are always printed, so colour never carries meaning alone. --}}
@php $max = max(1, $max ?? (collect($rows)->max('value') ?: 1)); @endphp

<ul {{ $attributes->class(['space-y-3']) }}>
    @foreach ($rows as $row)
        <li>
            <div class="flex items-baseline justify-between gap-3 text-sm">
                @if (! empty($row['href']))
                    <a href="{{ $row['href'] }}" class="truncate font-medium text-ink-800 hover:text-brand-700">{{ $row['label'] }}</a>
                @else
                    <span class="truncate font-medium text-ink-800">{{ $row['label'] }}</span>
                @endif
                <span class="shrink-0 font-bold text-ink-900">{{ $row['display'] ?? number_format($row['value']) }}</span>
            </div>
            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink-100" title="{{ $row['label'] }}: {{ $row['display'] ?? number_format($row['value']) }}">
                <div class="h-full rounded-full" style="width: {{ max(1.5, $row['value'] / $max * 100) }}%; background: {{ $row['color'] ?? $color }}"></div>
            </div>
        </li>
    @endforeach
</ul>
