@extends('layouts.app')

@section('title', 'Proceedings Entries')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">Proceedings entries</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Correct any entry on the author's behalf. Changes are recorded under your account.
            </p>
        </div>
        <div class="flex items-center gap-5 text-sm">
            <div><span class="font-black text-slate-900 dark:text-white">{{ $counts['total'] }}</span> <span class="text-slate-500 dark:text-slate-400">entries</span></div>
            <div><span class="font-black text-emerald-600 dark:text-emerald-400">{{ $counts['included'] }}</span> <span class="text-slate-500 dark:text-slate-400">included</span></div>
            <div><span class="font-black text-violet-600 dark:text-violet-400">{{ $counts['corrected'] }}</span> <span class="text-slate-500 dark:text-slate-400">corrected</span></div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-900/20 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-800/60 dark:bg-rose-900/20 dark:text-rose-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.proceedings.entries') }}"
          class="flex flex-col md:flex-row gap-3 rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}"
               placeholder="Search code, title, author or email"
               class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-900/10 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        <select name="inclusion" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
            <option value="">All inclusion</option>
            <option value="included" @selected(($filters['inclusion'] ?? '') === 'included')>Included</option>
            <option value="excluded" @selected(($filters['inclusion'] ?? '') === 'excluded')>Not included</option>
        </select>
        <select name="corrected" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
            <option value="">Corrected or not</option>
            <option value="yes" @selected(($filters['corrected'] ?? '') === 'yes')>Corrected</option>
            <option value="no" @selected(($filters['corrected'] ?? '') === 'no')>Never corrected</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">Filter</button>
            @if(array_filter($filters))
                <a href="{{ route('admin.proceedings.entries') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Clear</a>
            @endif
        </div>
    </form>

    {{-- Entries --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Title &amp; author</th>
                        <th class="px-4 py-3">Proceedings</th>
                        <th class="px-4 py-3">Last corrected</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3 align-top">
                                <span class="inline-block rounded bg-[#152b5e] px-2 py-0.5 font-mono text-[11px] font-bold tracking-wide text-white">{{ $entry->conference_code }}</span>
                            </td>
                            <td class="px-4 py-3 align-top max-w-xl">
                                <div class="font-semibold text-slate-900 dark:text-slate-100 line-clamp-2">{{ \App\Support\TitleFormatter::sentenceCase($entry->title) }}</div>
                                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ \App\Support\TitleFormatter::personName($entry->author_name) }}
                                    @if($entry->user?->email) &middot; {{ $entry->user->email }} @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top whitespace-nowrap">
                                @if($entry->include_in_proceedings)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Included</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">Not included</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">
                                @if($entry->proceedings_corrected_at)
                                    {{ $entry->proceedings_corrected_at->format('j M Y') }}
                                    <div class="text-[11px]">
                                        @if($entry->proceedings_corrected_by === $entry->user_id)
                                            by the author
                                        @elseif($entry->proceedingsCorrectedBy)
                                            by {{ $entry->proceedingsCorrectedBy->first_name }} {{ $entry->proceedingsCorrectedBy->last_name }}
                                        @endif
                                    </div>
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-right">
                                <a href="{{ route('abstracts.proceedings.edit', $entry) }}"
                                   class="inline-flex items-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">No entries match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($entries->hasPages())
        <div>{{ $entries->links() }}</div>
    @endif
</div>
@endsection
