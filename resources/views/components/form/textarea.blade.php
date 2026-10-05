@props(['name', 'label', 'hint' => null, 'value' => null, 'rows' => 5])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
@endphp

<div>
    <label for="{{ $id }}" class="label">{{ $label }}</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
              @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
              {{ $attributes->except('id')->class([
                  'w-full rounded-control border border-ink-200 bg-white px-4 py-3 text-[15px] leading-relaxed text-ink-900 placeholder:text-ink-400 transition focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15',
                  'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $error,
              ]) }}>{{ old($name, $value) }}</textarea>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-red-700">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-ink-500">{{ $hint }}</p>
    @endif
</div>
