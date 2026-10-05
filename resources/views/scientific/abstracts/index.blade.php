<x-layouts.portal title="Abstracts">
    <x-slot:header>
        <x-page-header eyebrow="Scientific committee" title="Abstracts"
            description="Assign reviewers, follow the reviews and record decisions. Reviewers never see the authors." />
    </x-slot:header>

    {{-- Status tabs --}}
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('scientific.abstracts.index', array_filter(['topic' => $filters['topic'], 'q' => $filters['search']])) }}"
           @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-brand-700 text-white' => ! $filters['status'], 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50' => $filters['status']])>
            All <span class="ml-1 opacity-70">{{ $counts->except('ready')->sum() }}</span>
        </a>
        <a href="{{ route('scientific.abstracts.index', array_filter(['status' => 'ready', 'topic' => $filters['topic'], 'q' => $filters['search']])) }}"
           @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-ember-600 text-white' => $filters['status'] === 'ready', 'bg-ember-50 text-ember-700 ring-1 ring-ember-200 hover:bg-ember-100' => $filters['status'] !== 'ready'])>
            Ready for decision <span class="ml-1 opacity-70">{{ $counts['ready'] ?? 0 }}</span>
        </a>
        @foreach ($statuses as $s)
            <a href="{{ route('scientific.abstracts.index', array_filter(['status' => $s->value, 'topic' => $filters['topic'], 'q' => $filters['search']])) }}"
               @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-brand-700 text-white' => $filters['status'] === $s->value, 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50' => $filters['status'] !== $s->value])>
                {{ $s->label() }} <span class="ml-1 opacity-70">{{ $counts[$s->value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-card :padding="false">
        <form method="GET" class="flex flex-col gap-3 border-b border-ink-100 p-4 sm:flex-row">
            @if ($filters['status']) <input type="hidden" name="status" value="{{ $filters['status'] }}"> @endif
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
                <input name="q" value="{{ $filters['search'] }}" placeholder="Search title, code or author surname" class="field h-11 pl-12">
            </div>
            <select name="topic" class="field h-11 sm:w-72" onchange="this.form.submit()">
                <option value="">All topics</option>
                @foreach ($topics as $topic)
                    <option value="{{ $topic->id }}" @selected($filters['topic'] == $topic->id)>{{ $topic->name }}</option>
                @endforeach
            </select>
            <x-button variant="secondary" icon="search">Search</x-button>
        </form>

        @if ($abstracts->isEmpty())
            <x-empty icon="clipboard" title="No abstracts match">Try another status, topic or search.</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr>
                            <th class="px-5 py-3">Abstract</th><th class="px-5 py-3">Topic</th><th class="px-5 py-3">Submitted by</th>
                            <th class="px-5 py-3">Reviews</th><th class="px-5 py-3">Score</th><th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($abstracts as $abstract)
                            @php $done = $abstract->reviews->filter->isComplete()->count(); @endphp
                            <tr class="hover:bg-ink-50/60">
                                <td class="max-w-sm px-5 py-3.5">
                                    <a href="{{ route('scientific.abstracts.show', $abstract) }}" class="font-medium text-ink-900 hover:text-brand-700">{{ $abstract->title }}</a>
                                    <p class="font-mono text-xs text-ink-500">{{ $abstract->code ?? $abstract->blindId() }}</p>
                                </td>
                                <td class="px-5 py-3.5"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $abstract->topic->chipClasses() }}">{{ $abstract->topic->code }}</span></td>
                                <td class="px-5 py-3.5 text-ink-700">{{ $abstract->submitter->name }}</td>
                                <td class="px-5 py-3.5 text-ink-700">{{ $done }}/{{ $abstract->reviews->count() }}</td>
                                <td class="px-5 py-3.5 font-semibold text-ink-900">{{ $abstract->averageScore() ?? '—' }}</td>
                                <td class="px-5 py-3.5"><x-status :tone="$abstract->status->tone()">{{ $abstract->status->label() }}</x-status></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-ink-100 px-5 py-3">{{ $abstracts->links() }}</div>
        @endif
    </x-card>
</x-layouts.portal>
