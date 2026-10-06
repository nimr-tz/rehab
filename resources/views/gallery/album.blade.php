@php $edition = $album->edition; @endphp

<x-layouts.public class="bg-white" :title="$album->title.' · Gallery · '.$edition->name.' '.$edition->year"
    :description="$album->description ?: 'Photos from '.$album->title.' at the '.$edition->name.' '.$edition->year.'.'">
    <x-public.nav />

    <header class="bg-gradient-to-b from-white to-brand-50">
        <div class="wrap pb-12 pt-10">
            <a href="{{ route('gallery.index', ['year' => $edition->year]) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800">
                <x-icon name="arrow-left" class="h-4 w-4" /> All albums
            </a>
            <p class="eyebrow mt-6">{{ $dayLabel ?? $edition->name.' '.$edition->year }}</p>
            <h1 class="section-title">{{ $album->title }}</h1>
            @if ($album->description)
                <p class="mt-4 max-w-3xl text-lg text-ink-600">{{ $album->description }}</p>
            @endif
            <p class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-ink-600">
                <span class="inline-flex items-center gap-2"><x-icon name="photo" class="h-5 w-5 text-brand-600" />{{ number_format($photos->total()) }} {{ Str::plural('photo', $photos->total()) }}</span>
                @if ($credits)
                    <span class="inline-flex items-center gap-2"><x-icon name="camera" class="h-5 w-5 text-brand-600" />Photos by {{ $credits }}</span>
                @endif
                @if ($album->session)
                    <a href="{{ route('programme') }}" class="inline-flex items-center gap-2 font-semibold text-brand-700 hover:text-brand-800">
                        <x-icon name="calendar" class="h-5 w-5" />
                        {{ $album->session->title }} · {{ $album->session->starts_at->format('H:i') }}@if ($album->session->hall), {{ $album->session->hall }}@endif
                    </a>
                @endif
            </p>
        </div>
    </header>

    <main class="wrap py-12">
        @if (session('status'))
            <x-alert tone="success" class="mb-8">{{ session('status') }}</x-alert>
        @endif

        @include('gallery.partials.photos', ['photos' => $photos])

        @if ($photos->hasPages())
            <div class="mt-8">{{ $photos->links() }}</div>
        @endif

        <p class="mt-10 flex items-start gap-2.5 text-sm text-ink-500">
            <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
            <span>Open a photo to see it full screen. The download button saves it in full resolution. If you are in a photo and would like it taken down, open it and choose the flag.</span>
        </p>

        @if ($more->isNotEmpty())
            <section aria-labelledby="more-albums" class="mt-20">
                <h2 id="more-albums" class="text-2xl font-extrabold tracking-tight text-brand-700">More from the summit</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($more as $other)
                        @include('gallery.partials.album-card', ['album' => $other])
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    @include('gallery.partials.removal-form')

    <x-public.footer />
</x-layouts.public>
