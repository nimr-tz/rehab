@props(['icon' => 'document', 'title'])

<div {{ $attributes->class(['flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-brand-50 text-brand-600">
        <x-icon :name="$icon" class="h-7 w-7" />
    </span>
    <p class="mt-4 font-semibold text-ink-900">{{ $title }}</p>
    @if (! $slot->isEmpty())
        <div class="mt-1 max-w-sm text-sm text-ink-500">{{ $slot }}</div>
    @endif
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
