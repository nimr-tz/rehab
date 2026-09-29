@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between">
        {{-- Previous Page Link --}}
        <div class="flex-1 flex justify-start">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-300 cursor-default">
                    ‹ Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition">
                    ‹ Prev
                </a>
            @endif
        </div>

        {{-- Pagination Elements --}}
        <div class="flex-1 flex justify-center">
            <div class="inline-flex items-center gap-1 rounded-2xl bg-white border border-slate-200 px-2 py-1 shadow-sm">
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="px-2 py-1 text-[10px] font-semibold text-slate-400">
                            {{ $element }}
                        </span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white text-xs font-black">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Next Page Link --}}
        <div class="flex-1 flex justify-end">
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition">
                    Next ›
                </a>
            @else
                <span class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-300 cursor-default">
                    Next ›
                </span>
            @endif
        </div>
    </nav>
@endif

