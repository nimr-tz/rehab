@extends('layouts.app')

@section('title', 'Conference Codes — Assigned')

@section('content')
<div class="min-h-screen bg-[#F4F6FB] dark:bg-gray-950 pb-12">

    {{-- ───── Header ───── --}}
    <div class="bg-[#06153D] dark:bg-[#040e29] px-6 pt-8 pb-6 shadow-xl">
        <div class="max-w-[96%] mx-auto">

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 text-xs text-blue-300/70 mb-4 tracking-widest uppercase font-semibold">
                <a href="{{ route('admin.conference-program.index') }}" class="hover:text-blue-200 transition-colors">Programme</a>
                <span class="opacity-40">/</span>
                <span class="text-blue-100/60">
                    @if($status === 'pending') Pending Codes
                    @elseif($status === 'assigned') Assigned Codes
                    @else All Accepted Abstracts
                    @endif
                </span>
            </div>

            <div class="flex items-start justify-between gap-6 flex-wrap">
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight">
                        @if($status === 'pending') Pending Conference Codes
                        @elseif($status === 'assigned') Assigned Conference Codes
                        @else Conference Code Management
                        @endif
                    </h1>
                    <div class="mt-2 flex items-center gap-4 text-sm text-blue-200/70 font-medium flex-wrap">
                        <span>{{ $totalAccepted }} accepted</span>
                        <span class="w-px h-3 bg-blue-300/20"></span>
                        <span class="text-emerald-300">{{ $totalAssigned }} assigned</span>
                        <span class="w-px h-3 bg-blue-300/20"></span>
                        <span class="text-amber-300">{{ $totalPending }} pending</span>
                        <span class="w-px h-3 bg-blue-300/20"></span>
                        <span class="text-cyan-300">{{ $topicStats['with_topic'] }} topics ready</span>
                        @if($subthemeRecommendationCount > 0)
                            <span class="w-px h-3 bg-blue-300/20"></span>
                            <span class="text-fuchsia-300">{{ $subthemeRecommendationCount }} flagged</span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <form method="POST" action="{{ route('admin.conference-program.detect-topics', request()->query()) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-sm font-semibold rounded-lg transition-colors shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.351a3.75 3.75 0 01-5.303-5.303l.348-.349z"/>
                            </svg>
                            Detect Topics
                        </button>
                    </form>

                    <a href="{{ request()->fullUrlWithQuery(['export_csv' => 1]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold rounded-lg transition-colors border border-white/10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export CSV
                    </a>

                    <button onclick="window.print()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold rounded-lg transition-colors border border-white/10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print
                    </button>

                    <button onclick="saveAllChanges()"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 hover:bg-amber-400 text-white text-sm font-bold rounded-lg transition-colors shadow-lg save-btn">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span class="save-label">Save Changes</span>
                    </button>

                    <a href="{{ route('admin.conference-program.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold rounded-lg transition-colors border border-white/10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back
                    </a>
                </div>
            </div>

            {{-- Stat strip --}}
            <div class="mt-6 grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div class="rounded-xl bg-white/5 border border-white/10 px-4 py-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-blue-300/60">Matching</div>
                    <div class="mt-1 text-2xl font-black text-white">{{ $filteredTotal }}</div>
                </div>
                <div class="rounded-xl bg-white/5 border border-white/10 px-4 py-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-emerald-300/80">Assigned</div>
                    <div class="mt-1 text-2xl font-black text-emerald-300">{{ $filteredAssigned }}</div>
                </div>
                <div class="rounded-xl bg-white/5 border border-white/10 px-4 py-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-amber-300/80">Pending</div>
                    <div class="mt-1 text-2xl font-black text-amber-300">{{ $filteredPending }}</div>
                </div>
                <div class="rounded-xl bg-white/5 border border-white/10 px-4 py-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-blue-300/80">Oral</div>
                    <div class="mt-1 text-2xl font-black text-blue-200">{{ $filteredOral }}</div>
                </div>
                <div class="rounded-xl bg-white/5 border border-white/10 px-4 py-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-violet-300/80">Poster / Audio</div>
                    <div class="mt-1 text-2xl font-black text-violet-300">{{ $filteredPoster + $filteredAudioPoster }}</div>
                    @if($filteredAudioPoster > 0)
                        <div class="text-[10px] text-violet-300/60 mt-0.5">{{ $filteredPoster }} + {{ $filteredAudioPoster }} audio</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-[96%] mx-auto px-4 mt-6 space-y-4">

        {{-- ───── Filter bar ───── --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 px-4 py-3 shadow-sm">
            <div class="flex flex-col md:flex-row items-center justify-between gap-3 flex-wrap">

                {{-- Status tabs --}}
                <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1 shrink-0">
                    @foreach(['all' => 'All', 'assigned' => 'Assigned', 'pending' => 'Pending'] as $val => $label)
                        <a href="{{ request()->fullUrlWithQuery(['assignment_status' => $val]) }}"
                           class="px-4 py-1.5 text-sm font-semibold rounded-md transition-all
                               {{ $status === $val
                                   ? 'bg-white dark:bg-gray-700 shadow-sm text-[#06153D] dark:text-white'
                                   : 'text-gray-500 hover:text-gray-800 dark:hover:text-white' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                {{-- Filters --}}
                <form method="GET" class="flex items-center gap-2 flex-wrap justify-end">
                    <input type="hidden" name="assignment_status" value="{{ $status }}">

                    <select name="subtheme" onchange="this.form.submit()"
                            class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Subthemes</option>
                        @foreach($allSubthemes as $st)
                            <option value="{{ $st }}" {{ request('subtheme') == $st ? 'selected' : '' }}>
                                {{ \Illuminate\Support\Str::limit($st, 32) }}
                            </option>
                        @endforeach
                    </select>

                    <select name="mode" onchange="this.form.submit()"
                            class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Modes</option>
                        <option value="Oral" {{ request('mode') == 'Oral' ? 'selected' : '' }}>Oral</option>
                        <option value="Poster" {{ request('mode') == 'Poster' ? 'selected' : '' }}>Poster</option>
                    </select>

                    <select name="selected" onchange="this.form.submit()"
                            class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="yes" {{ request('selected') == 'yes' ? 'selected' : '' }}>Selected</option>
                        <option value="no" {{ request('selected') == 'no' ? 'selected' : '' }}>Not Selected</option>
                    </select>

                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..."
                           class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-lg w-44 focus:ring-2 focus:ring-blue-500">

                    <button type="submit"
                            class="px-4 py-1.5 bg-[#06153D] hover:bg-blue-900 text-white text-sm font-semibold rounded-lg transition-colors">
                        Search
                    </button>

                    @if(request()->hasAny(['search', 'subtheme', 'mode', 'selected']))
                        <a href="{{ route('admin.conference-program.assigned', ['assignment_status' => $status]) }}"
                           class="px-4 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-lg transition-colors">
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- ───── Alert strips ───── --}}
        @if($genericCodeCount > 0)
            <div class="flex items-start justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800/40 dark:bg-amber-900/10 px-5 py-3.5">
                <div>
                    <div class="text-[10px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400">Generic Codes</div>
                    <div class="mt-0.5 text-sm font-semibold text-amber-900 dark:text-amber-100">
                        {{ $genericCodeCount }} abstract{{ $genericCodeCount !== 1 ? 's' : '' }} still carry a generic {{ config('conference.short_name') }} code.
                        Run "Detect Topics" to reassign by subtheme, or update their subtheme manually.
                    </div>
                </div>
            </div>
        @endif

        @if($subthemeRecommendationCount > 0)
            <div class="flex items-center justify-between gap-4 rounded-xl border border-fuchsia-200 bg-fuchsia-50 dark:border-fuchsia-800/40 dark:bg-fuchsia-900/10 px-5 py-3.5">
                <div>
                    <div class="text-[10px] font-black uppercase tracking-widest text-fuchsia-700 dark:text-fuchsia-400">Reviewer Recommendations</div>
                    <div class="mt-0.5 text-sm font-semibold text-fuchsia-900 dark:text-fuchsia-100">
                        {{ $subthemeRecommendationCount }} abstract{{ $subthemeRecommendationCount !== 1 ? 's' : '' }} have reviewer subtheme recommendations on file.
                    </div>
                </div>
                <a href="{{ request()->fullUrlWithQuery(['subtheme_recommendation' => 'yes']) }}"
                   class="shrink-0 px-4 py-2 bg-fuchsia-700 hover:bg-fuchsia-600 text-white text-sm font-semibold rounded-lg transition-colors">
                    View Flags
                </a>
            </div>
        @endif

        @php($hasActiveProgramFilters = request()->hasAny(['search', 'subtheme', 'mode', 'selected', 'subtheme_recommendation']) || $status !== 'all')

        {{-- ───── Main content: table + subtheme sidebar ───── --}}
        <div class="grid grid-cols-1 xl:grid-cols-[1fr_260px] gap-4 items-start">

            {{-- Table card --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto" style="max-height: calc(100vh - 320px); overflow-y: auto;">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-2.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 w-32">Code</th>
                                <th class="px-3 py-2.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Title</th>
                                <th class="px-3 py-2.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 w-36">Author</th>
                                <th class="px-3 py-2.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 w-52">Subtheme</th>
                                <th class="px-3 py-2.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 w-44">Topic</th>
                                <th class="px-3 py-2.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 w-28">Mode</th>
                                <th class="px-3 py-2.5 text-center text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 w-20">Selected</th>
                                <th class="px-3 py-2.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 w-28">Assigned</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($abstracts as $abstract)
                            @php($subthemeRecommendation = $abstract->getSubthemeRecommendationSummary())
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors"
                                data-id="{{ $abstract->id }}"
                                data-title="{{ addslashes($abstract->title) }}"
                                data-author="{{ addslashes($abstract->author_name) }}"
                                data-date="{{ $abstract->code_assigned_at ? $abstract->code_assigned_at->format('Y-m-d') : '' }}">

                                {{-- Code --}}
                                <td class="px-3 py-2">
                                    <input type="text"
                                           class="code-input w-full px-2 py-1.5 text-sm border rounded-lg font-mono font-bold transition-colors
                                               {{ $abstract->conference_code
                                                   ? 'border-gray-200 dark:border-gray-700 text-[#06153D] dark:text-blue-300'
                                                   : 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300' }}
                                               dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           data-abstract-id="{{ $abstract->id }}"
                                           data-original="{{ $abstract->conference_code ?? '' }}"
                                           value="{{ $abstract->conference_code ?? '' }}"
                                           placeholder="Pending"
                                           onchange="markAsModified(this)">
                                </td>

                                {{-- Title --}}
                                <td class="px-3 py-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2" title="{{ $abstract->title }}">
                                        {{ $abstract->title }}
                                    </div>
                                </td>

                                {{-- Author --}}
                                <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ \App\Support\TitleFormatter::personName($abstract->author_name) }}
                                </td>

                                {{-- Subtheme --}}
                                <td class="px-3 py-2">
                                    <div class="space-y-1.5">
                                        <select class="subtheme-select w-full px-2 py-1.5 text-xs border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                data-abstract-id="{{ $abstract->id }}"
                                                data-original="{{ $abstract->normalized_subtheme }}"
                                                onchange="markAsModified(this)">
                                            @foreach($allSubthemes as $st)
                                                <option value="{{ $st }}" {{ $abstract->normalized_subtheme === $st ? 'selected' : '' }}>
                                                    {{ \Illuminate\Support\Str::limit($st, 36) }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @if($subthemeRecommendation['has_recommendation'])
                                            <div class="rounded-lg border border-fuchsia-200 dark:border-fuchsia-800/40 bg-fuchsia-50 dark:bg-fuchsia-900/10 px-2 py-1.5">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="text-[9px] font-black uppercase tracking-widest text-fuchsia-700 dark:text-fuchsia-400">Reviewer Rec</span>
                                                    <span class="text-[9px] font-bold text-fuchsia-600 dark:text-fuchsia-400">×{{ $subthemeRecommendation['count'] }}</span>
                                                </div>
                                                <div class="mt-0.5 text-[11px] font-semibold text-slate-700 dark:text-slate-200 leading-tight">
                                                    {{ $subthemeRecommendation['primary_suggestion'] }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Topic --}}
                                <td class="px-3 py-2">
                                    <div class="space-y-1">
                                        <input type="text"
                                               class="topic-input w-full px-2 py-1.5 text-xs border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                                               data-abstract-id="{{ $abstract->id }}"
                                               data-original="{{ $abstract->session_topic ?? '' }}"
                                               value="{{ $abstract->session_topic ?? '' }}"
                                               placeholder="Detect or enter"
                                               onchange="markAsModified(this)">

                                        <div class="flex items-center gap-1.5">
                                            @if($abstract->session_topic_source === 'manual')
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Manual</span>
                                            @elseif($abstract->session_topic)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">Auto</span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">Unset</span>
                                            @endif
                                            @if($abstract->session_topic_detected_at)
                                                <span class="text-[9px] text-gray-400 dark:text-gray-500">{{ $abstract->session_topic_detected_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Mode --}}
                                <td class="px-3 py-2">
                                    <select class="mode-select w-full px-2 py-1.5 text-xs border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            data-abstract-id="{{ $abstract->id }}"
                                            data-original="{{ $abstract->presentation_mode }}"
                                            onchange="markAsModified(this)">
                                        <option value="Oral" {{ (strtolower($abstract->presentation_mode) === 'oral') ? 'selected' : '' }}>Oral</option>
                                        <option value="Poster" {{ (strtolower($abstract->presentation_mode) === 'poster') ? 'selected' : '' }}>Poster</option>
                                        <option value="Audio Poster" {{ (str_replace('_', ' ', strtolower($abstract->presentation_mode)) === 'audio poster') ? 'selected' : '' }}>Audio Poster</option>
                                    </select>
                                </td>

                                {{-- Selected --}}
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox"
                                           class="program-select w-4 h-4 rounded accent-[#06153D] border-gray-300 cursor-pointer"
                                           data-abstract-id="{{ $abstract->id }}"
                                           data-original="{{ $abstract->committee_selected ? '1' : '0' }}"
                                           {{ $abstract->committee_selected ? 'checked' : '' }}
                                           onchange="markAsModified(this)">
                                </td>

                                {{-- Assigned date --}}
                                <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    @if($abstract->code_assigned_at)
                                        {{ $abstract->code_assigned_at->format('M j, Y') }}
                                    @else
                                        <span class="text-amber-500 font-medium">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($abstracts->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                        {{ $abstracts->withQueryString()->links() }}
                    </div>
                @endif
            </div>

            {{-- Subtheme sidebar --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-4 sticky top-4">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Subtheme Tally</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        {{ $filteredSubthemeCounts->count() }}
                    </span>
                </div>

                @if($filteredSubthemeCounts->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-4 text-sm text-gray-400 dark:text-gray-500 text-center">
                        No results match current filters.
                    </div>
                @else
                    <div class="space-y-1.5 max-h-[70vh] overflow-y-auto pr-0.5">
                        @foreach($filteredSubthemeCounts as $subthemeName => $subthemeTotal)
                            <div class="flex items-start justify-between gap-2 rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 px-3 py-2">
                                <div class="text-xs font-semibold text-gray-700 dark:text-gray-200 leading-snug">
                                    {{ $subthemeName ?: 'Unspecified' }}
                                </div>
                                <span class="shrink-0 text-xs font-bold text-gray-500 dark:text-gray-400">{{ $subthemeTotal }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($hasActiveProgramFilters ?? false)
                    <div class="mt-3 rounded-lg border border-blue-100 dark:border-blue-900/30 bg-blue-50 dark:bg-blue-900/10 px-3 py-2 text-[10px] font-semibold text-blue-700 dark:text-blue-300">
                        Filtered view active
                    </div>
                @endif
            </div>
        </div>

        {{-- Bottom action bar --}}
        <div class="flex items-center justify-between gap-4 pt-1">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Topic detection is non-destructive — manual edits are preserved. Only modified rows are sent on save.
                <span id="modifiedCount" class="hidden">0</span>
            </p>
            <button onclick="saveAllChanges()"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-bold rounded-lg transition-colors shadow-md save-btn">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                <span class="save-label">Save Changes</span>
            </button>
        </div>

    </div>
</div>

{{-- ───── Save Confirmation Modal ───── --}}
<div id="saveModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700">
        <div class="p-6">

            <div class="flex items-center justify-center w-14 h-14 mx-auto bg-[#06153D] rounded-full mb-4">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
            </div>

            <h3 class="text-lg font-black text-gray-900 dark:text-white text-center">Confirm Save</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-1 mb-5">
                Saving <span class="font-bold text-[#06153D] dark:text-blue-300" id="modalChangeCount">0</span> modified records.
            </p>

            <div class="rounded-xl border border-blue-100 dark:border-blue-900/40 bg-blue-50 dark:bg-blue-900/10 px-4 py-3 mb-5">
                <p class="text-xs text-blue-800 dark:text-blue-300 font-medium">
                    Only rows you edited will be updated. Unchanged rows are skipped.
                </p>
            </div>

            <div class="flex gap-3">
                <button onclick="closeSaveModal()"
                        class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl font-semibold transition-colors">
                    Cancel
                </button>
                <button onclick="confirmSave()"
                        class="flex-1 px-4 py-2.5 bg-[#06153D] hover:bg-blue-900 text-white rounded-xl font-bold transition-colors shadow-md">
                    Save Now
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let modifiedRecords = new Set();

function markAsModified(element) {
    const abstractId = element.dataset.abstractId;
    const originalValue = element.dataset.original;
    const currentValue = element.type === 'checkbox' ? (element.checked ? '1' : '0') : element.value;

    if (currentValue !== originalValue) {
        modifiedRecords.add(abstractId);
    } else {
        if (checkAllFieldsMatch(abstractId)) {
            modifiedRecords.delete(abstractId);
        }
    }

    updateModifiedCount();
}

function checkAllFieldsMatch(abstractId) {
    const codeInput      = document.querySelector(`.code-input[data-abstract-id="${abstractId}"]`);
    const subthemeSelect = document.querySelector(`.subtheme-select[data-abstract-id="${abstractId}"]`);
    const topicInput     = document.querySelector(`.topic-input[data-abstract-id="${abstractId}"]`);
    const modeSelect     = document.querySelector(`.mode-select[data-abstract-id="${abstractId}"]`);
    const checkbox       = document.querySelector(`.program-select[data-abstract-id="${abstractId}"]`);

    return codeInput.value         === codeInput.dataset.original &&
           subthemeSelect.value    === subthemeSelect.dataset.original &&
           topicInput.value        === topicInput.dataset.original &&
           modeSelect.value        === modeSelect.dataset.original &&
           (checkbox.checked ? '1' : '0') === checkbox.dataset.original;
}

function updateModifiedCount() {
    const count = modifiedRecords.size;
    document.getElementById('modifiedCount').textContent = count;

    const label = count > 0 ? `Save Changes (${count})` : 'Save Changes';
    document.querySelectorAll('.save-label').forEach(el => { el.textContent = label; });
}

function saveAllChanges() {
    if (modifiedRecords.size === 0) {
        showNotification('No changes to save.', 'info');
        return;
    }

    const changes = [];
    modifiedRecords.forEach(abstractId => {
        const codeInput      = document.querySelector(`.code-input[data-abstract-id="${abstractId}"]`);
        const subthemeSelect = document.querySelector(`.subtheme-select[data-abstract-id="${abstractId}"]`);
        const topicInput     = document.querySelector(`.topic-input[data-abstract-id="${abstractId}"]`);
        const modeSelect     = document.querySelector(`.mode-select[data-abstract-id="${abstractId}"]`);
        const checkbox       = document.querySelector(`.program-select[data-abstract-id="${abstractId}"]`);

        changes.push({
            abstract_id:        abstractId,
            conference_code:    codeInput.value.trim().toUpperCase(),
            subtheme:           subthemeSelect.value,
            session_topic:      topicInput.value.trim(),
            presentation_mode:  modeSelect.value,
            committee_selected: checkbox.checked
        });
    });

    window.pendingChanges = changes;
    document.getElementById('modalChangeCount').textContent = changes.length;
    document.getElementById('saveModal').classList.remove('hidden');
}

function closeSaveModal() {
    document.getElementById('saveModal').classList.add('hidden');
}

function confirmSave() {
    closeSaveModal();

    const changes = window.pendingChanges;
    if (!changes || changes.length === 0) return;

    showNotification('Saving changes…', 'info');

    fetch('{{ route('admin.conference-program.update-codes') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ changes })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification(`Updated ${data.updated} abstracts — reloading…`, 'success');
            setTimeout(() => window.location.reload(), 700);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(() => showNotification('A network error occurred.', 'error'));
}

function exportToCSV() {
    const data = [
        ['Conference Code', 'Title', 'Author', 'Subtheme', 'Session Topic', 'Presentation Mode', 'Selected', 'Assigned Date']
    ];

    document.querySelectorAll('tbody tr[data-id]').forEach(row => {
        const abstractId    = row.dataset.id;
        const codeInput     = row.querySelector('.code-input');
        const subthemeSelect= row.querySelector('.subtheme-select');
        const topicInput    = row.querySelector('.topic-input');
        const modeSelect    = row.querySelector('.mode-select');
        const checkbox      = row.querySelector('.program-select');

        data.push([
            (codeInput?.value?.trim() || ''),
            (row.dataset.title   || '').replace(/"/g, '""'),
            (row.dataset.author  || '').replace(/"/g, '""'),
            (subthemeSelect?.value || '').replace(/"/g, '""'),
            (topicInput?.value   || '').replace(/"/g, '""'),
            (modeSelect?.value   || ''),
            checkbox?.checked ? 'Yes' : 'No',
            (row.dataset.date    || '')
        ]);
    });

    const csv  = data.map(row => row.map(f => `"${f}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url  = window.URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'conference_codes_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function showNotification(message, type = 'info') {
    const bg = { success: 'bg-emerald-600', error: 'bg-red-600', info: 'bg-[#06153D]' }[type] || 'bg-gray-700';
    const el = document.createElement('div');
    el.className = `fixed top-4 right-4 ${bg} text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-xl z-50 transition-all`;
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3200);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.code-input').forEach(input => {
        input.addEventListener('input', function () { this.value = this.value.toUpperCase(); });
    });
});
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endpush
@endsection
