@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-3 text-sm">
        <p class="text-ink-500">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg px-3 py-1.5 text-ink-300">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg px-3 py-1.5 font-semibold text-brand-700 hover:bg-brand-50">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-ink-400">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="grid h-8 min-w-8 place-items-center rounded-lg bg-brand-700 px-2 font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="hidden h-8 min-w-8 place-items-center rounded-lg px-2 text-ink-600 hover:bg-ink-100 sm:grid">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg px-3 py-1.5 font-semibold text-brand-700 hover:bg-brand-50">Next</a>
            @else
                <span class="rounded-lg px-3 py-1.5 text-ink-300">Next</span>
            @endif
        </div>
    </nav>
@endif
