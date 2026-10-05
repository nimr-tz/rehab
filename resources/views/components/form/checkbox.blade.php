@props(['name', 'label', 'checked' => false, 'hint' => null])

<div>
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="flex items-start gap-2.5 text-sm text-ink-700">
        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
               {{ $attributes->class(['mt-0.5 h-4 w-4 shrink-0 rounded border-ink-300 accent-brand-700']) }}>
        <span>
            <span class="font-medium text-ink-800">{{ $label }}</span>
            @if ($hint)
                <span class="block text-xs text-ink-500">{{ $hint }}</span>
            @endif
        </span>
    </label>
</div>
