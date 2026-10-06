@php
    $title = $edition ? $edition->name.' '.$edition->year : $summit->title();
    $albumCount = $days->flatten()->count();
@endphp

<x-layouts.public class="bg-white" :title="'Gallery · '.$title" :description="'Photos from the '.$title.'.'">
    <x-public.nav />

    <header class="bg-gradient-to-b from-white to-brand-50">
        <div class="wrap pb-14 pt-12">
            <p class="eyebrow">Gallery</p>
            <h1 class="section-title">{{ $title }} in pictures</h1>
            @if ($photoCount)
                <p class="mt-4 max-w-3xl text-lg text-ink-600">
                    {{ number_format($photoCount) }} {{ Str::plural('photo', $photoCount) }} in {{ $albumCount }} {{ Str::plural('album', $albumCount) }}.
                    Open any photo to see it full screen and download it in full resolution.
                </p>
            @endif

            @if ($editions->count() > 1)
                <nav aria-label="Summit year" class="mt-7 flex flex-wrap gap-2">
                    @foreach ($editions as $option)
                        <a href="{{ route('gallery.index', ['year' => $option->year]) }}" @if ($option->is($edition)) aria-current="page" @endif
                           @class([
                               'rounded-full border-[1.5px] border-brand-700 px-5 py-2.5 text-[15px] font-bold transition',
                               'bg-brand-700 text-white' => $option->is($edition),
                               'bg-white text-brand-700 hover:bg-brand-50' => ! $option->is($edition),
                           ])>{{ $option->year }}</a>
                    @endforeach
                </nav>
            @endif
        </div>
    </header>

    <main class="wrap py-14">
        @if (session('status'))
            <x-alert tone="success" class="mb-10">{{ session('status') }}</x-alert>
        @endif

        @if ($days->isEmpty())
            <x-empty icon="camera" title="Photos are on their way">
                Photos from the summit appear here during and after the event, as the photographers publish them.
            </x-empty>
        @else
            @if ($featured->isNotEmpty())
                <section aria-labelledby="highlights" class="mb-16">
                    <div class="mb-5 flex items-center gap-3">
                        <x-icon name="star" class="h-6 w-6 text-sun-500" />
                        <h2 id="highlights" class="text-2xl font-extrabold tracking-tight text-brand-700">Highlights</h2>
                    </div>
                    @include('gallery.partials.photos', ['photos' => $featured])
                </section>
            @endif

            <div x-data="{ day: 'all' }">
                @if ($days->count() > 1)
                    <div role="group" aria-label="Filter albums by day" class="flex flex-wrap gap-2">
                        @foreach (['all' => 'All days'] + collect($days->keys())->mapWithKeys(fn ($date) => [$date => $dayLabels[$date] ?? 'Other albums'])->all() as $value => $label)
                            <button type="button" x-on:click="day = @js((string) $value)" :aria-pressed="(day === @js((string) $value)).toString()"
                                    :class="day === @js((string) $value) ? 'bg-brand-700 text-white' : 'bg-white text-brand-700 hover:bg-brand-50'"
                                    class="whitespace-nowrap rounded-full border-[1.5px] border-brand-700 px-4 py-2 text-sm font-bold transition">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @foreach ($days as $date => $albums)
                    <section x-show="day === 'all' || day === @js((string) $date)" class="mt-12 first:mt-0" aria-labelledby="day-{{ $loop->index }}">
                        <h2 id="day-{{ $loop->index }}" class="text-xl font-bold text-ink-900">{{ $dayLabels[$date] ?? 'Other albums' }}</h2>
                        <div class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($albums as $album)
                                @include('gallery.partials.album-card')
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </main>

    @if ($featured->isNotEmpty())
        @include('gallery.partials.removal-form')
    @endif

    <x-public.footer />
</x-layouts.public>
