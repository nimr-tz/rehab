@php $editing = $album->exists; @endphp

<x-layouts.portal :title="$editing ? 'Edit album' : 'New album'">
    <x-slot:header>
        <x-page-header eyebrow="Photo gallery" :title="$editing ? 'Edit album' : 'New album'"
            :back="$editing ? route('media.albums.show', $album) : route('media.albums.index')"
            description="An album can cover a day, a session from the programme, or an occasion such as the gala dinner." />
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <x-card>
            <form method="POST" action="{{ $editing ? route('media.albums.update', $album) : route('media.albums.store') }}" class="space-y-5">
                @csrf
                @if ($editing) @method('PUT') @endif

                <x-form.input name="title" label="Album title" :value="$album->title" required maxlength="120"
                    hint="For example: Official opening, Poster session I, Gala dinner." />

                <x-form.select name="programme_session_id" label="Programme session (optional)" :options="$sessions"
                    :value="$album->programme_session_id" placeholder="Not linked to a session" />

                @if ($days)
                    <x-form.select name="day" label="Day" :options="$days" :value="$album->day?->toDateString()"
                        placeholder="Same day as the session, or none" />
                @else
                    <x-form.input name="day" type="date" label="Day (optional)" :value="$album->day?->toDateString()" />
                @endif

                <x-form.textarea name="description" label="Description (optional)" :value="$album->description" rows="3" maxlength="1000"
                    hint="One or two sentences shown at the top of the album." />

                <div class="flex flex-wrap gap-3 pt-2">
                    <x-button icon="check">{{ $editing ? 'Save album' : 'Create album' }}</x-button>
                    <x-button variant="secondary" :href="$editing ? route('media.albums.show', $album) : route('media.albums.index')">Cancel</x-button>
                </div>
            </form>
        </x-card>

        <div class="space-y-6">
            <x-card title="How albums appear">
                <ul class="space-y-3 text-sm text-ink-600">
                    <li class="flex gap-2.5"><x-icon name="calendar" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />The public gallery groups albums by day.</li>
                    <li class="flex gap-2.5"><x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />Linking a session shows its time and hall, and links the album to the programme.</li>
                    <li class="flex gap-2.5"><x-icon name="eye" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />An album appears in the gallery once it has a published photo.</li>
                </ul>
            </x-card>

            @if ($editing)
                <x-card title="Delete album">
                    @error('album') <x-alert tone="danger" class="mb-4">{{ $message }}</x-alert> @enderror
                    <p class="text-sm text-ink-600">Deleting an album also deletes its photos. Shared links to it stop working.</p>
                    <form method="POST" action="{{ route('media.albums.destroy', $album) }}" class="mt-4"
                          x-data x-on:submit="if (! confirm('Delete this album and all its photos? This cannot be undone.')) $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <x-button variant="danger" size="sm" icon="trash">Delete album</x-button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.portal>
