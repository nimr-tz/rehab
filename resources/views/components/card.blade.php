@props(['title' => null, 'description' => null, 'padding' => true])

<section {{ $attributes->class(['rounded-card border border-ink-100 bg-white shadow-soft']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-ink-100 px-5 py-4 sm:px-6">
            <div>
                @if ($title)
                    <h2 class="font-semibold text-ink-900">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-sm text-ink-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-5 sm:p-6' => $padding])>
        {{ $slot }}
    </div>
</section>
