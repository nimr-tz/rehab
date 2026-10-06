<x-layouts.portal title="Removal requests">
    <x-slot:header>
        <x-page-header eyebrow="Photo gallery" title="Removal requests"
            description="People who appear in a photo can ask for it to be taken down. Decide on each request; the person is emailed the outcome." />
    </x-slot:header>

    <x-card title="Waiting for a decision" :padding="false">
        @if ($pending->isEmpty())
            <x-empty icon="check-circle" title="Nothing waiting">Every removal request has been dealt with.</x-empty>
        @else
            <ul class="divide-y divide-ink-100">
                @foreach ($pending as $removal)
                    <li class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:px-6">
                        @if ($removal->photo)
                            <a href="{{ $removal->photo->url('display') }}" target="_blank" rel="noopener" class="block w-full shrink-0 overflow-hidden rounded-2xl bg-brand-50 sm:w-48">
                                <img src="{{ $removal->photo->url() }}" alt="The photo in question" class="aspect-[3/2] w-full object-cover">
                            </a>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ink-900">{{ $removal->name }} <span class="font-normal text-ink-500">· {{ $removal->email }}</span></p>
                            <p class="mt-0.5 text-xs text-ink-500">
                                {{ $removal->created_at->diffForHumans() }} · Album: {{ $removal->album_title }}
                                @if ($removal->photo?->photographer) · Photo by {{ $removal->photo->photographer->name }} @endif
                            </p>
                            <p class="mt-3 rounded-2xl bg-canvas px-4 py-3 text-sm text-ink-700">{{ $removal->reason ?: 'No reason given.' }}</p>
                            <form method="POST" action="{{ route('media.removal-requests.update', $removal) }}" class="mt-4 flex flex-wrap gap-2">
                                @csrf
                                @method('PUT')
                                <x-button name="decision" value="remove" variant="danger" size="sm" icon="trash">Take the photo down</x-button>
                                <x-button name="decision" value="keep" variant="secondary" size="sm">Keep it</x-button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    @if ($resolved->isNotEmpty())
        <x-card title="Recently decided" :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr><th class="px-5 py-3">Requested by</th><th class="px-5 py-3">Album</th><th class="px-5 py-3">Decision</th><th class="px-5 py-3">Decided</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($resolved as $removal)
                            <tr>
                                <td class="px-5 py-3"><p class="font-medium text-ink-900">{{ $removal->name }}</p><p class="text-xs text-ink-500">{{ $removal->email }}</p></td>
                                <td class="px-5 py-3 text-ink-700">{{ $removal->album_title }}</td>
                                <td class="px-5 py-3"><x-status :tone="$removal->outcome === 'removed' ? 'danger' : 'neutral'">{{ $removal->outcome === 'removed' ? 'Taken down' : 'Kept' }}</x-status></td>
                                <td class="px-5 py-3 text-ink-500">{{ $removal->resolved_at->format('j M Y, H:i') }}@if ($removal->resolver) · {{ $removal->resolver->name }}@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-layouts.portal>
