{{--
    A justified photo grid: every row is filled edge to edge and each photo keeps
    its shape. Each link opens the lightbox (resources/js/gallery.js).
    Expects $photos with album and photographer loaded.
--}}
<div data-gallery class="flex flex-wrap gap-2 sm:gap-3">
    @foreach ($photos as $photo)
        @php [$width, $height] = $photo->displaySize(); @endphp
        <a id="photo-{{ $photo->id }}" href="{{ $photo->url('display') }}"
           data-pswp-width="{{ $width }}" data-pswp-height="{{ $height }}"
           data-download="{{ $photo->downloadUrl() }}"
           data-removal="{{ route('gallery.photos.removal', $photo) }}"
           data-caption="{{ $photo->caption() }}"
           style="--ratio: {{ round($photo->ratio(), 4) }}; flex-grow: {{ round($photo->ratio() * 100) }}"
           class="group relative block w-[calc(var(--ratio)*150px)] overflow-hidden rounded-xl bg-brand-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/40 sm:w-[calc(var(--ratio)*230px)]">
            <span class="block" style="padding-bottom: {{ round(100 / $photo->ratio(), 3) }}%"></span>
            <img src="{{ $photo->url() }}" alt="Photo from {{ $photo->album->title }}" loading="lazy" decoding="async"
                 class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
        </a>
    @endforeach
    {{-- Stops the last row stretching. --}}
    <span aria-hidden="true" class="grow-[1000000]"></span>
</div>
