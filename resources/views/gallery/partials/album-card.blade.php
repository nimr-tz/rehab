<a href="{{ route('gallery.album', $album) }}"
   class="group flex flex-col overflow-hidden rounded-card border border-ink-100 bg-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-lift focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/30">
    <div class="aspect-[3/2] overflow-hidden bg-brand-50">
        @if ($album->cover)
            <img src="{{ $album->cover->url() }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-1.5 p-5">
        @if ($album->session?->topic)
            <span class="w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $album->session->topic->chipClasses() }}">{{ $album->session->topic->name }}</span>
        @endif
        <h3 class="text-lg font-bold leading-snug text-ink-900 group-hover:text-brand-700">{{ $album->title }}</h3>
        <p class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-500">
            <span class="inline-flex items-center gap-1.5"><x-icon name="photo" class="h-4 w-4 text-brand-600" />{{ $album->published_count }} {{ Str::plural('photo', $album->published_count) }}</span>
            @if ($album->session)
                <span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="h-4 w-4 text-brand-600" />{{ $album->session->starts_at->format('H:i') }}@if ($album->session->hall) · {{ $album->session->hall }}@endif</span>
            @endif
        </p>
    </div>
</a>
