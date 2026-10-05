@props([
    'name',
    'label',
    'type' => 'text',
    'icon' => null,
    'hint' => null,
    'value' => null,
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
@endphp

<div>
    <label for="{{ $id }}" class="label">{{ $label }}</label>
    <div class="relative">
        @if ($icon)
            <x-icon :name="$icon" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
        @endif
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
               @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
               {{ $attributes->except('id')->class([
                   'field',
                   'pl-12' => $icon,
                   'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $error,
               ]) }}>
    </div>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-red-700">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-ink-500">{{ $hint }}</p>
    @endif
</div>
