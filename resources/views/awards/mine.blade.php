<x-layouts.portal title="Awards">
    <x-slot:header>
        <x-page-header eyebrow="Summit awards" title="Awards"
            description="Your shortlisted presentations, awards and certificates, and nominations for the summit's honours.">
            <x-button variant="secondary" size="sm" icon="trophy" :href="route('awards.index')">All awards</x-button>
        </x-page-header>
    </x-slot:header>

    <x-card title="Your awards" :padding="false">
        @if ($entries->isEmpty())
            <x-empty icon="trophy" title="No awards yet">
                When one of your accepted abstracts is shortlisted for a presentation award, it appears here.
                Winners download their certificate here once the winners are announced.
            </x-empty>
        @else
            <ul class="divide-y divide-ink-100">
                @foreach ($entries as $entry)
                    @php
                        $category = $entry->category;
                        [$label, $tone] = match (true) {
                            $entry->isWinner() => [$category->placeLabel($entry->place), 'success'],
                            $category->isAnnounced() => ['Finalist', 'neutral'],
                            default => ['Shortlisted', 'info'],
                        };
                    @endphp
                    <li class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:px-6">
                        @if ($entry->isWinner())
                            <x-award.medal :place="$entry->place" />
                        @else
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700"><x-icon name="trophy" class="h-5 w-5" /></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-ink-900">{{ $category->name }}</p>
                                <x-status :tone="$tone">{{ $label }}</x-status>
                                @unless ($category->edition->is($edition))
                                    <span class="text-xs font-semibold text-ink-500">{{ $category->edition->year }}</span>
                                @endunless
                            </div>
                            @if ($entry->abstract)
                                <p class="mt-1 text-sm text-ink-700">
                                    <a href="{{ route('abstracts.show', $entry->abstract) }}" class="hover:text-brand-700">“{{ $entry->abstract->title }}”</a>
                                    <span class="font-mono text-xs text-ink-500">· {{ $entry->abstract->code }}</span>
                                </p>
                            @endif
                            <p class="mt-1 text-xs text-ink-500">
                                @if ($entry->isWinner())
                                    Announced {{ $category->announced_at->format('j F Y') }}. Congratulations{{ $entry->user_id === auth()->id() ? '' : ' to '.$entry->name }}.
                                @elseif ($category->isAnnounced())
                                    Shortlisted from all accepted abstracts. Well done.
                                @else
                                    Presented by {{ $entry->name }}. Judges score the finalists during the summit, and winners are announced at the closing ceremony.
                                @endif
                            </p>
                        </div>
                        @if ($entry->isWinner())
                            <x-button size="sm" icon="download" :href="route('awards.certificate', $entry)">Certificate</x-button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    @if ($open->isNotEmpty())
        @php $selected = old('award_category_id', request('award', $open->count() === 1 ? $open->first()->id : null)); @endphp
        <div id="nominate" class="grid scroll-mt-28 gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <x-card title="Nominate someone" description="Tell the committee who deserves recognition and why. Nominations are confidential.">
                <form method="POST" action="{{ route('awards.nominate') }}" class="space-y-5">
                    @csrf
                    <x-form.select name="award_category_id" label="Award" :options="$open->pluck('name', 'id')->all()" :value="$selected"
                        :placeholder="$open->count() > 1 ? 'Choose an award' : null" required />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="name" label="Name of the person or organisation" required maxlength="120" />
                        <x-form.input name="institution" label="Institution or community (optional)" maxlength="160" />
                    </div>
                    <x-form.input name="email" type="email" label="Their email (optional)" maxlength="160"
                        hint="Helps the committee reach them. They are not told who nominated them." />
                    <x-form.textarea name="citation" label="Why do they deserve this award?" rows="6" required maxlength="2000"
                        hint="What they have done, for whom, and the difference it has made. A few sentences are enough." />
                    <x-button icon="check">Send nomination</x-button>
                </form>
            </x-card>

            <div class="space-y-6">
                @foreach ($open as $category)
                    <x-card>
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-ember-600">Open until {{ $category->nominations_close_on->format('j F Y') }}</p>
                        <p class="mt-1.5 font-bold text-ink-900">{{ $category->name }}</p>
                        @if ($category->description)
                            <p class="mt-1.5 text-sm text-ink-600">{{ $category->description }}</p>
                        @endif
                    </x-card>
                @endforeach
            </div>
        </div>
    @endif

    @if ($nominations->isNotEmpty())
        <x-card title="Your nominations" :padding="false">
            <ul class="divide-y divide-ink-100">
                @foreach ($nominations as $nomination)
                    <li class="flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-center sm:gap-4 sm:px-6">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ink-900">{{ $nomination->name }}@if ($nomination->institution) <span class="font-normal text-ink-500">· {{ $nomination->institution }}</span>@endif</p>
                            <p class="text-sm text-ink-500">{{ $nomination->category->name }} · sent {{ $nomination->created_at->format('j M Y') }}</p>
                        </div>
                        @if ($nomination->category->isAnnounced())
                            <x-status :tone="$nomination->place ? 'success' : 'neutral'">{{ $nomination->place ? 'Received the award' : 'Winners announced' }}</x-status>
                        @else
                            <x-status tone="info">With the committee</x-status>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-layouts.portal>
