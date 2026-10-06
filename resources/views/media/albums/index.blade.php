<x-layouts.portal title="Albums & uploads">
    <x-slot:header>
        <x-page-header eyebrow="Photo gallery" title="Albums & uploads"
            description="Upload into any album, then publish the photos you are happy with. Published photos appear in the public gallery straight away.">
            @if ($edition)
                <x-button :href="route('gallery.index')" variant="secondary" icon="eye" target="_blank">Public gallery</x-button>
                <x-button :href="route('media.albums.create')" icon="plus">New album</x-button>
            @endif
        </x-page-header>
    </x-slot:header>

    @if (! $edition)
        <x-card>
            <x-empty icon="calendar" title="No summit is set up yet">
                Albums belong to a summit. An administrator sets up the summit in Summit settings.
            </x-empty>
        </x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat label="Published in the gallery" :value="number_format($stats['published'])" icon="photo" tint="bg-emerald-50 text-emerald-700" />
            <x-stat label="Your uploads" :value="number_format($stats['mine'])" icon="upload" />
            <x-stat label="Your photos not yet published" :value="number_format($stats['waiting'])" icon="eye-slash" tint="bg-sun-100 text-sun-800"
                :hint="$stats['waiting'] ? 'Open an album to review and publish them.' : null" />
        </div>

        @if ($albums->isEmpty())
            <x-card>
                <x-empty icon="camera" title="No albums yet">
                    Create an album for a day, a session or an occasion such as the gala dinner, then upload your photos into it.
                    <x-slot:action><x-button :href="route('media.albums.create')" icon="plus">New album</x-button></x-slot:action>
                </x-empty>
            </x-card>
        @else
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($albums as $album)
                    @php $preview = $album->cover ?? $album->latestPhoto; @endphp
                    <a href="{{ route('media.albums.show', $album) }}"
                       class="group flex flex-col overflow-hidden rounded-card border border-ink-100 bg-white shadow-soft transition hover:border-brand-200 hover:shadow-lift">
                        <div class="relative aspect-[16/9] overflow-hidden bg-brand-50">
                            @if ($preview)
                                <img src="{{ $preview->url() }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="grid h-full place-items-center text-brand-300"><x-icon name="photo" class="h-10 w-10" /></span>
                            @endif
                            @if ($album->day)
                                <span class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-bold text-brand-800 shadow-soft">{{ $album->day->format('D j M') }}</span>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col gap-2 p-5">
                            <h2 class="font-bold leading-snug text-ink-900 group-hover:text-brand-700">{{ $album->title }}</h2>
                            @if ($album->session)
                                <p class="truncate text-xs text-ink-500">{{ $album->session->starts_at->format('H:i') }} · {{ $album->session->title }}</p>
                            @endif
                            <div class="mt-auto flex flex-wrap gap-1.5 pt-2">
                                <x-status tone="success">{{ $album->published_count }} published</x-status>
                                @if ($album->photos_count - $album->published_count)
                                    <x-status tone="warning">{{ $album->photos_count - $album->published_count }} not published</x-status>
                                @endif
                                @if ($album->mine_count)
                                    <x-status tone="info">{{ $album->mine_count }} yours</x-status>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</x-layouts.portal>
