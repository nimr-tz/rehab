@props(['label', 'value', 'hint' => null, 'href' => null, 'tint' => 'bg-brand-50 text-brand-700', 'icon' => null])

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['flex items-start gap-4 rounded-card border border-ink-100 bg-white p-5 shadow-soft', 'transition hover:border-brand-200' => $href]) }}>
    @if ($icon)
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $tint }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    @endif
    <span class="min-w-0">
        <span class="block text-xs font-medium text-ink-500">{{ $label }}</span>
        <span class="mt-1 block text-2xl font-bold tracking-tight text-ink-900">{{ $value }}</span>
        @if ($hint)
            <span class="mt-0.5 block text-xs text-ink-500">{{ $hint }}</span>
        @endif
    </span>
</{{ $tag }}>
