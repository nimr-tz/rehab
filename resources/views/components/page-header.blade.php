@props(['title', 'eyebrow' => null, 'description' => null, 'back' => null])

<div class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-3 inline-flex items-center gap-1.5 text-sm font-semibold text-ink-500 hover:text-ink-800">
                <x-icon name="arrow-left" class="h-4 w-4" /> Back
            </a>
        @endif
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-1 text-2xl font-bold text-ink-900">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-2xl text-sm text-ink-500">{{ $description }}</p>
        @endif
    </div>
    @if (! $slot->isEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
