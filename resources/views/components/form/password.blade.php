@props([
    'name' => 'password',
    'label' => 'Password',
    'hint' => null,
    'icon' => 'lock',
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
@endphp

<div x-data="{ show: false }">
    <label for="{{ $id }}" class="label">{{ $label }}</label>
    <div class="relative">
        <x-icon :name="$icon" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
        <input id="{{ $id }}" name="{{ $name }}" type="password" :type="show ? 'text' : 'password'"
               @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
               {{ $attributes->except('id')->class([
                   'field pl-12 pr-12',
                   'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $error,
               ]) }}>
        <button type="button" @click="show = ! show"
                :aria-pressed="show.toString()" :aria-label="show ? 'Hide password' : 'Show password'" aria-label="Show password"
                class="absolute right-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg text-ink-400 transition hover:bg-ink-100 hover:text-ink-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40">
            <x-icon name="eye" x-show="! show" />
            <x-icon name="eye-off" x-show="show" x-cloak />
        </button>
    </div>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-red-700">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-ink-500">{{ $hint }}</p>
    @endif
</div>
