@props([
    'name',
    'label',
    'options' => [],
    'placeholder' => null,
    'icon' => null,
    'value' => null,
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
    $selected = (string) old($name, $value);
@endphp

<div>
    <label for="{{ $id }}" class="label">{{ $label }}</label>
    <div class="relative">
        @if ($icon)
            <x-icon :name="$icon" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
        @endif
        <select id="{{ $id }}" name="{{ $name }}"
                @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
                {{ $attributes->except('id')->class([
                    'field appearance-none pr-10',
                    'pl-12' => $icon,
                    'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $error,
                ]) }}>
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
        <svg class="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
    </div>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-red-700">{{ $error }}</p>
    @endif
</div>
