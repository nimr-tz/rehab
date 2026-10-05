@props(['title', 'eyebrow' => null, 'description' => null, 'back' => null])

{{-- The page heading. Portal pages place it in the top bar via <x-slot:header>. --}}
<div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-1 inline-flex items-center gap-1.5 text-xs font-semibold text-ink-500 hover:text-ink-800">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back
            </a>
        @endif
        @if ($eyebrow)
            <p class="truncate text-[11px] font-bold uppercase tracking-[0.18em] text-ember-600">{{ $eyebrow }}</p>
        @endif
        <h1 class="truncate text-[1.45rem] font-extrabold leading-tight tracking-tight text-brand-700 sm:text-[1.7rem]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-0.5 line-clamp-2 max-w-2xl text-sm text-ink-500">{{ $description }}</p>
        @endif
    </div>
    @if (! $slot->isEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
