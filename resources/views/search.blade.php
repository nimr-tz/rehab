<x-layouts.portal title="Search">
    <x-slot:header>
        <x-page-header title="Search" :description="$q !== '' ? 'Results for “'.$q.'”' : 'Find people, abstracts, payments and sessions'" />
    </x-slot:header>

    <form method="GET" action="{{ route('search') }}" class="relative md:hidden" role="search">
        <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
        <input name="q" value="{{ $q }}" placeholder="Search" class="field pl-12" autofocus>
    </form>

    @if (mb_strlen($q) < 2)
        <x-card><x-empty icon="search" title="Type at least two characters">Search by name, reference, transaction, title or code.</x-empty></x-card>
    @elseif ($results->isEmpty())
        <x-card><x-empty icon="search" title="Nothing found">Try a surname, a reference such as RH27-000101, or a code such as OR-HBR-01.</x-empty></x-card>
    @else
        @foreach ($results as $group => $items)
            <x-card :title="$group" :padding="false">
                <ul class="divide-y divide-ink-100">
                    @foreach ($items as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="flex items-center justify-between gap-4 px-6 py-3.5 hover:bg-ink-50">
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold text-ink-900">{{ $item['title'] }}</span>
                                    <span class="block truncate text-xs text-ink-500">{{ $item['meta'] }}</span>
                                </span>
                                <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-ink-400" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endforeach
    @endif
</x-layouts.portal>
