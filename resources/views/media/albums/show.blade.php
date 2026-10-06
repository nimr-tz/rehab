@php
    $tabs = ['all' => 'All photos', 'unpublished' => 'Not published', 'published' => 'Published', 'mine' => 'Mine'];
    $user = auth()->user();
    $manageable = $photos->getCollection()->filter->isManageableBy($user)->modelKeys();
    $meta = collect([
        $album->day?->format('l j F'),
        $album->session ? $album->session->starts_at->format('H:i').' · '.$album->session->title : null,
    ])->filter()->implode(' · ');
@endphp

<x-layouts.portal :title="$album->title">
    <x-slot:header>
        <x-page-header eyebrow="Photo gallery" :title="$album->title" :back="route('media.albums.index')" :description="$meta ?: null">
            @if ($album->published_count)
                <x-button :href="route('gallery.album', $album)" variant="secondary" icon="eye" target="_blank">View in gallery</x-button>
            @endif
            @if ($canEdit)
                <x-button :href="route('media.albums.edit', $album)" variant="secondary" icon="pencil">Edit album</x-button>
            @endif
        </x-page-header>
    </x-slot:header>

    {{-- Uploads --}}
    <x-card title="Upload photos"
        :description="'JPEG or PNG, up to '.config('gallery.max_photo_mb').' MB each. Choose as many as you like: they upload two at a time and resume after a dropped connection.'">
        <div x-data="photoUploader({ url: @js(route('media.albums.uploads', $album)), chunkSize: {{ config('gallery.chunk_kb') * 1024 }}, maxBytes: {{ config('gallery.max_photo_mb') * 1024 * 1024 }} })">
            <label x-on:dragover.prevent="dragging = true" x-on:dragleave.prevent="dragging = false" x-on:drop.prevent="drop($event)"
                   :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-ink-200 bg-canvas hover:border-brand-300'"
                   class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-10 text-center transition focus-within:ring-4 focus-within:ring-brand-500/20">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white text-brand-600 shadow-soft"><x-icon name="upload" class="h-7 w-7" /></span>
                <span class="mt-4 font-semibold text-ink-900">Drop photos here or <span class="text-brand-700 underline underline-offset-2">choose files</span></span>
                <span class="mt-1 text-sm text-ink-500">Photos are turned upright and their location data is removed automatically.</span>
                <input type="file" multiple accept="image/jpeg,image/png" class="sr-only" x-on:change="pick($event)">
            </label>

            <label class="mt-4 flex items-start gap-2.5 text-sm text-ink-700">
                <input type="checkbox" x-model="publish" class="mt-0.5 h-4 w-4 shrink-0 accent-brand-700">
                <span>
                    <span class="font-medium text-ink-800">Publish photos as soon as they upload</span>
                    <span class="block text-xs text-ink-500">Leave this off to look through your photos here first, then publish the ones you choose.</span>
                </span>
            </label>

            <div x-show="items.length" x-cloak class="mt-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-ink-800" x-text="summary" aria-live="polite"></p>
                    <div class="flex flex-wrap gap-2">
                        <x-button type="button" size="sm" variant="secondary" x-show="failed" x-on:click="retryFailed()">Retry failed</x-button>
                        <x-button type="button" size="sm" variant="ghost" x-show="finished" x-on:click="clearFinished()">Clear finished</x-button>
                        <x-button type="button" size="sm" icon="photo" x-show="added && ! pending" x-on:click="window.location.reload()">
                            <span x-text="`Show ${added} new ${added === 1 ? 'photo' : 'photos'}`"></span>
                        </x-button>
                    </div>
                </div>

                <ul class="mt-3 max-h-80 divide-y divide-ink-100 overflow-y-auto rounded-2xl border border-ink-100">
                    <template x-for="item in items" :key="item.key">
                        <li class="flex items-center gap-3 px-4 py-2.5">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full"
                                  :class="{
                                      'bg-emerald-50 text-emerald-600': item.status === 'done',
                                      'bg-red-50 text-red-600': item.status === 'failed',
                                      'bg-ink-100 text-ink-500': item.status === 'duplicate' || item.status === 'waiting',
                                      'bg-brand-50 text-brand-600': item.status === 'uploading' || item.status === 'processing',
                                  }">
                                <x-icon name="check" class="h-4 w-4" x-show="item.status === 'done'" />
                                <x-icon name="x" class="h-4 w-4" x-show="item.status === 'failed'" />
                                <x-icon name="clock" class="h-4 w-4" x-show="item.status === 'waiting' || item.status === 'duplicate'" />
                                <x-icon name="upload" class="h-4 w-4 animate-pulse" x-show="item.status === 'uploading' || item.status === 'processing'" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-ink-800" x-text="item.name"></span>
                                <span class="block text-xs" :class="item.status === 'failed' ? 'text-red-700' : 'text-ink-500'" x-text="statusLabel(item)"></span>
                            </span>
                            <span x-show="item.status === 'uploading' || item.status === 'processing'" class="hidden h-1.5 w-28 overflow-hidden rounded-full bg-ink-100 sm:block">
                                <span class="block h-full rounded-full bg-brand-600 transition-all" :style="`width: ${item.progress}%`"></span>
                            </span>
                            <x-button type="button" size="sm" variant="ghost" x-show="item.status === 'failed' && item.retryable" x-on:click="retry(item)">Retry</x-button>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </x-card>

    {{-- Photos --}}
    <x-card :padding="false">
        <form method="POST" action="{{ route('media.albums.photos', $album) }}" x-data="{ selected: [], manageable: @js($manageable) }">
            @csrf
            <div class="flex flex-col gap-3 border-b border-ink-100 p-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                    @foreach ($tabs as $value => $label)
                        <a href="{{ route('media.albums.show', [$album, 'show' => $value === 'all' ? null : $value]) }}"
                           @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-brand-700 text-white' => $filter === $value, 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50' => $filter !== $value])>
                            {{ $label }}
                            @if ($value === 'all')<span class="ml-1 opacity-70">{{ $album->photos_count }}</span>@endif
                            @if ($value === 'published')<span class="ml-1 opacity-70">{{ $album->published_count }}</span>@endif
                            @if ($value === 'unpublished')<span class="ml-1 opacity-70">{{ $album->photos_count - $album->published_count }}</span>@endif
                        </a>
                    @endforeach
                </div>

                @if ($manageable)
                    <label class="flex items-center gap-2 text-sm font-medium text-ink-700">
                        <input type="checkbox" class="h-4 w-4 accent-brand-700"
                               :checked="selected.length && selected.length === manageable.length"
                               x-on:change="selected = $event.target.checked ? manageable.map(String) : []">
                        Select all on this page
                    </label>
                @endif
            </div>

            @error('photos') <x-alert tone="danger" class="m-4">{{ $message }}</x-alert> @enderror

            {{-- Actions for the selection --}}
            <div x-show="selected.length" x-cloak class="sticky top-[76px] z-20 flex flex-wrap items-center gap-2 border-b border-ink-100 bg-brand-50/95 px-4 py-3 backdrop-blur">
                <span class="mr-2 text-sm font-semibold text-brand-900" x-text="`${selected.length} selected`"></span>
                <x-button name="action" value="publish" size="sm" variant="success" icon="eye">Publish</x-button>
                <x-button name="action" value="unpublish" size="sm" variant="secondary" icon="eye-slash">Unpublish</x-button>
                <x-button name="action" value="feature" size="sm" variant="secondary" icon="star">Add to highlights</x-button>
                <x-button name="action" value="unfeature" size="sm" variant="ghost">Remove from highlights</x-button>
                <x-button name="action" value="delete" size="sm" variant="danger" icon="trash"
                    x-on:click="if (! confirm(`Delete ${selected.length} photos? This cannot be undone.`)) $event.preventDefault()">Delete</x-button>
            </div>

            @if ($photos->isEmpty())
                <x-empty icon="photo" :title="$filter === 'all' ? 'No photos in this album yet' : 'No photos here'">
                    {{ $filter === 'all' ? 'Upload photos above. They appear here once they finish.' : 'Try another tab.' }}
                </x-empty>
            @else
                <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                    @foreach ($photos as $photo)
                        @php $mine = in_array($photo->id, $manageable, true); @endphp
                        <div class="group relative overflow-hidden rounded-2xl bg-brand-50"
                             :class="selected.includes('{{ $photo->id }}') ? 'ring-4 ring-brand-500' : ''">
                            <label @class(['block aspect-square', 'cursor-pointer' => $mine])>
                                <img src="{{ $photo->url() }}" alt="{{ $photo->original_name }}" loading="lazy" class="h-full w-full object-cover">
                                @if ($mine)
                                    <input type="checkbox" name="photos[]" value="{{ $photo->id }}" x-model="selected"
                                           class="absolute left-2.5 top-2.5 h-5 w-5 rounded accent-brand-700 shadow-soft"
                                           aria-label="Select {{ $photo->original_name }}">
                                @endif
                            </label>
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 bg-gradient-to-t from-ink-950/75 to-transparent px-2.5 pb-2 pt-8 text-[11px] text-white">
                                <span class="min-w-0 truncate">{{ $photo->photographer?->name ?? 'Former photographer' }}</span>
                                <span class="flex shrink-0 items-center gap-1">
                                    @if ($photo->is_featured)<x-icon name="star" class="h-3.5 w-3.5 text-sun-400" />@endif
                                    <span @class(['rounded-full px-1.5 py-0.5 font-semibold', 'bg-emerald-500/90' => $photo->isPublished(), 'bg-sun-400 text-ink-900' => ! $photo->isPublished()])>
                                        {{ $photo->isPublished() ? 'Live' : 'Draft' }}
                                    </span>
                                </span>
                            </div>
                            <a href="{{ $photo->url('display') }}" target="_blank" rel="noopener" title="Open full size"
                               class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-full bg-white/90 text-ink-700 opacity-0 shadow-soft transition hover:text-brand-700 focus:opacity-100 group-hover:opacity-100">
                                <x-icon name="eye" class="h-4 w-4" />
                                <span class="sr-only">Open {{ $photo->original_name }} full size</span>
                            </a>
                        </div>
                    @endforeach
                </div>
                @if ($photos->hasPages())
                    <div class="border-t border-ink-100 px-5 py-3">{{ $photos->links() }}</div>
                @endif
            @endif
        </form>
    </x-card>

    <p class="flex items-start gap-2.5 text-sm text-ink-500">
        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
        <span>You can publish, feature or delete your own photos. Photos by other photographers in this album are shown for reference; ask them or an administrator to change those.</span>
    </p>
</x-layouts.portal>
