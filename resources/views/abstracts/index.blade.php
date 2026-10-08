<x-layouts.portal title="My abstracts">
    <x-slot:header>
        <x-page-header :eyebrow="$summit->title()" title="My abstracts"
            :description="$edition?->acceptsAbstracts()
                ? 'Submission is open until '.\App\Support\Summit::formatDate($edition->abstract_deadline).'. You can edit a submitted abstract until review starts.'
                : 'Abstract submission is closed.'">
            @if ($edition?->acceptsAbstracts())
                <x-button :href="route('abstracts.create')" icon="plus">New abstract</x-button>
            @endif
        </x-page-header>
    </x-slot:header>

    <x-card :padding="false">
        @if ($abstracts->isEmpty())
            <x-empty icon="document" title="You have no abstracts yet">
                Share your research, practice or innovation in rehabilitation. Abstracts are reviewed double-blind by the scientific committee.
                @if ($edition?->acceptsAbstracts())
                    <x-slot:action><x-button :href="route('abstracts.create')" icon="plus">Start an abstract</x-button></x-slot:action>
                @endif
            </x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr><th class="px-5 py-3">Title</th><th class="px-5 py-3">Topic</th>@if (\App\Enums\PresentationType::postersEnabled())<th class="px-5 py-3">Type</th>@endif<th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($abstracts as $abstract)
                            <tr class="hover:bg-ink-50/60">
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('abstracts.show', $abstract) }}" class="font-medium text-ink-900 hover:text-brand-700">{{ $abstract->title }}</a>
                                    @if ($abstract->code)<p class="font-mono text-xs text-ink-500">{{ $abstract->code }}</p>@endif
                                </td>
                                <td class="px-5 py-3.5"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $abstract->topic->chipClasses() }}">{{ $abstract->topic->code }}</span></td>
                                @if (\App\Enums\PresentationType::postersEnabled())<td class="px-5 py-3.5 text-ink-600">{{ ($abstract->decision_type ?? $abstract->preferred_type)->label() }}</td>@endif
                                <td class="px-5 py-3.5"><x-status :tone="$abstract->status->tone()">{{ $abstract->status->label() }}</x-status></td>
                                <td class="px-5 py-3.5 text-right"><x-button variant="ghost" size="sm" :href="route('abstracts.show', $abstract)">Open</x-button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layouts.portal>
