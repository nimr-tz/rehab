@extends('layouts.app')

@section('title', 'Review Your Proceedings Entry')

@push('styles')
<style>
    /* The preview pane mirrors the proceedings volume's own typography so authors
       see the entry as it will be printed, not as a web form. */
    .book-preview {
        font-family: Arial, 'DejaVu Sans', sans-serif;
        color: #1a1a2e;
        font-size: 0.9rem;
        line-height: 1.45;
    }
    .book-preview .preview-code {
        display: inline-block;
        font-weight: 700;
        font-size: 0.75rem;
        letter-spacing: 0.06em;
        color: #152b5e;
        border: 1px solid #c89b3c;
        border-radius: 0.25rem;
        padding: 0.1rem 0.5rem;
        margin-bottom: 0.5rem;
    }
    .book-preview .preview-title {
        font-weight: 700;
        font-size: 1.02rem;
        color: #152b5e;
        line-height: 1.25;
        margin-bottom: 0.35rem;
    }
    .book-preview .preview-authors { margin-bottom: 0.5rem; }
    .book-preview .preview-authors sup { font-size: 0.62rem; vertical-align: super; color: #c89b3c; font-weight: 700; }
    .book-preview .preview-affiliations {
        border-left: 2px solid #c89b3c;
        padding-left: 0.6rem;
        margin-bottom: 0.6rem;
        font-size: 0.76rem;
        color: #444;
    }
    .book-preview .abstract-section { text-align: justify; margin: 0 0 0.5rem 0; }
    .book-preview .abstract-section strong { color: #152b5e; }
    .book-preview .preview-keywords {
        font-size: 0.78rem;
        border-top: 1px solid #e0d5c0;
        padding-top: 0.4rem;
        margin-top: 0.6rem;
    }
    .dark .book-preview { color: #e2e8f0; }
    .dark .book-preview .preview-title,
    .dark .book-preview .abstract-section strong { color: #93b4f5; }
    .dark .book-preview .preview-affiliations { color: #cbd5e1; }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ $backUrl }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
            &larr; {{ $asAdmin ? 'Back to proceedings entries' : 'Back to abstract' }}
        </a>
        <h1 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">
            {{ $asAdmin ? 'Edit proceedings entry' : 'Review your proceedings entry' }}
        </h1>
    </div>

    @if($asAdmin)
    {{-- Admin acting on the author's behalf --}}
    <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/60 dark:bg-amber-900/20">
        <div class="text-sm text-amber-900 dark:text-amber-100 space-y-1">
            <p class="font-bold">
                Editing on behalf of {{ \App\Support\TitleFormatter::personName($abstract->author_name) }}
                @if($abstract->user?->email) &middot; {{ $abstract->user->email }} @endif
                &middot; <span class="font-mono">{{ $abstract->conference_code }}</span>
            </p>
            <p>This is exactly how the entry will appear in the conference proceedings. Your changes are saved immediately and recorded under your account.</p>
        </div>
    </div>
    @else
    {{-- Proceedings note --}}
    <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/60 dark:bg-amber-900/20">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <div class="text-sm text-amber-900 dark:text-amber-100 space-y-2">
                <p class="font-bold">Conference proceedings</p>
                <p>
                    This is exactly how your abstract will appear in the
                    {{ config('conference.short_name') }} {{ config('conference.year', date('Y')) }} Conference Proceedings.
                    Correct any errors in your title, abstract text, keywords, affiliation or co-authors &mdash;
                    changes save straight away, with no re-review.
                </p>
                <p>
                    You chose whether to be included when you submitted.
                    If you want to change that, scroll to the bottom of this page.
                </p>
                @if($closesAt)
                    <p class="font-semibold">
                        Corrections close on {{ $closesAt->timezone(config('app.timezone'))->format('l, j F Y') }}.
                    </p>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-800/60 dark:bg-rose-900/20">
            <p class="font-bold text-sm text-rose-900 dark:text-rose-100 mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside text-sm text-rose-800 dark:text-rose-200 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('abstracts.proceedings.update', $abstract) }}" id="proceedings-form">
        @csrf
        @method('PUT')
        @if($asAdmin)
            <input type="hidden" name="return_to" value="{{ $backUrl }}">
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- ── Editor ─────────────────────────────────────────────── --}}
            <div class="space-y-6">

                {{-- Title --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6">
                    <label for="title" class="block text-sm font-bold text-slate-900 dark:text-white mb-2">Title</label>
                    <textarea name="title" id="title" rows="3" maxlength="255" required
                        class="w-full rounded-xl border border-slate-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('title', $current['title']) }}</textarea>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Titles written in ALL CAPS are converted to sentence case in the proceedings; known acronyms (HIV, TB, {{ config('conference.host_short') }}&hellip;) keep their capitalisation.
                    </p>
                </div>

                {{-- Presenting author --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Presenting author</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="author_name" class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Full name</label>
                            <input type="text" name="author_name" id="author_name" maxlength="255" required
                                value="{{ old('author_name', $current['author_name']) }}"
                                class="w-full rounded-xl border border-slate-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="author_institute" class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Affiliation</label>
                            <input type="text" name="author_institute" id="author_institute" maxlength="255" required
                                value="{{ old('author_institute', $current['author_institute']) }}"
                                class="w-full rounded-xl border border-slate-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                {{-- Co-authors --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Co-authors</h2>
                        <button type="button" id="add-coauthor"
                            class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                            + Add co-author
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                        Listed in this order in the proceedings. Authors sharing an affiliation share a superscript number.
                    </p>
                    <div id="coauthors-list" class="space-y-3">
                        @php $formCoauthors = old('coauthors', $current['coauthors']); @endphp
                        @foreach($formCoauthors as $i => $coauthor)
                            <div class="coauthor-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 items-start">
                                <input type="text" name="coauthors[{{ $i }}][name]" placeholder="Full name" maxlength="255"
                                    value="{{ $coauthor['name'] ?? '' }}"
                                    class="rounded-xl border border-slate-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <input type="text" name="coauthors[{{ $i }}][institute]" placeholder="Affiliation" maxlength="255"
                                    value="{{ $coauthor['institute'] ?? '' }}"
                                    class="rounded-xl border border-slate-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <button type="button" class="remove-coauthor px-3 py-2 text-slate-400 hover:text-rose-500 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-900/20" title="Remove">
                                    &times;
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <p id="no-coauthors-msg" class="text-xs text-slate-400 italic {{ count($formCoauthors) ? 'hidden' : '' }}">
                        No co-authors listed.
                    </p>
                </div>

                {{-- Abstract body --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6">
                    <label for="description" class="block text-sm font-bold text-slate-900 dark:text-white mb-2">Abstract text</label>
                    <textarea name="description" id="description" rows="16" maxlength="10000" required
                        class="w-full rounded-xl border border-slate-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-4 py-3 text-sm leading-relaxed focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('description', $current['description']) }}</textarea>
                    <div class="mt-2 flex items-start justify-between gap-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Start a section with one of these labels followed by a colon to have it set as a bold heading:
                            <span class="font-semibold">{{ implode(', ', $sectionLabels) }}</span>.
                            Check the preview — anything not recognised stays as plain text.
                        </p>
                        <span id="desc-count" class="text-xs font-mono text-slate-400 shrink-0"></span>
                    </div>
                </div>

                {{-- Keywords --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6">
                    <label for="keywords" class="block text-sm font-bold text-slate-900 dark:text-white mb-2">Keywords</label>
                    <input type="text" name="keywords" id="keywords" maxlength="500" required
                        value="{{ old('keywords', $current['keywords']) }}"
                        class="w-full rounded-xl border border-slate-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Separate with commas.</p>
                </div>

                {{-- Author consent for full-text publication in the proceedings volume. --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6">
                    @php $optedIn = (bool) old('include_in_proceedings', $current['include_in_proceedings']); @endphp

                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Conference proceedings</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                        @if($asAdmin)
                            Currently <strong class="text-slate-700 dark:text-slate-200">{{ $optedIn ? 'included' : 'not included' }}</strong>
                            in the proceedings. Change it only if the author has asked you to.
                        @else
                            At submission you chose
                            <strong class="text-slate-700 dark:text-slate-200">{{ $optedIn ? 'to be included' : 'not to be included' }}</strong>.
                            Change it here if you need to.
                        @endif
                    </p>
                    <div class="space-y-2">
                        <label class="flex items-start gap-3 rounded-xl border p-3 cursor-pointer transition-colors {{ $optedIn ? 'border-blue-400 bg-blue-50 dark:border-blue-600 dark:bg-blue-900/20' : 'border-slate-200 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-700/40' }}">
                            <input type="radio" name="include_in_proceedings" value="1" {{ $optedIn ? 'checked' : '' }}
                                   class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm">
                                <span class="font-bold text-slate-900 dark:text-white">Include my abstract</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">Publish the full text in the conference proceedings.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 rounded-xl border p-3 cursor-pointer transition-colors {{ !$optedIn ? 'border-blue-400 bg-blue-50 dark:border-blue-600 dark:bg-blue-900/20' : 'border-slate-200 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-700/40' }}">
                            <input type="radio" name="include_in_proceedings" value="0" {{ !$optedIn ? 'checked' : '' }}
                                   class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm">
                                <span class="font-bold text-slate-900 dark:text-white">Do not include my abstract</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">Leave it out of the proceedings volume.</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ── Live book preview ──────────────────────────────────── --}}
            <div class="lg:sticky lg:top-6 lg:self-start">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-900/50">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Proceedings preview</h2>
                        <span id="preview-state" class="text-xs text-slate-400"></span>
                    </div>
                    <div class="p-6 max-h-[70vh] overflow-y-auto">
                        <div id="book-preview" class="book-preview">
                            <p class="text-sm text-slate-400 italic">Loading preview&hellip;</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row gap-3">
                    <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 px-5 py-3 text-sm font-bold text-white transition-colors">
                        Save corrections
                    </button>
                    <a href="{{ $backUrl }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 dark:border-gray-600 px-5 py-3 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form        = document.getElementById('proceedings-form');
    const previewBox  = document.getElementById('book-preview');
    const previewNote = document.getElementById('preview-state');
    const list        = document.getElementById('coauthors-list');
    const emptyMsg    = document.getElementById('no-coauthors-msg');
    const description = document.getElementById('description');
    const counter     = document.getElementById('desc-count');
    const previewUrl  = @json(route('abstracts.proceedings.preview', $abstract));
    const csrf        = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        || form.querySelector('input[name="_token"]').value;

    let coauthorIndex = {{ count($formCoauthors) }};
    let timer = null;
    let inFlight = null;

    // ── Co-author rows ──────────────────────────────────────────────────
    function syncEmptyMessage() {
        emptyMsg.classList.toggle('hidden', list.querySelectorAll('.coauthor-row').length > 0);
    }

    document.getElementById('add-coauthor').addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'coauthor-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 items-start';
        row.innerHTML =
            '<input type="text" name="coauthors[' + coauthorIndex + '][name]" placeholder="Full name" maxlength="255" ' +
            'class="rounded-xl border border-slate-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">' +
            '<input type="text" name="coauthors[' + coauthorIndex + '][institute]" placeholder="Affiliation" maxlength="255" ' +
            'class="rounded-xl border border-slate-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">' +
            '<button type="button" class="remove-coauthor px-3 py-2 text-slate-400 hover:text-rose-500 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-900/20" title="Remove">&times;</button>';
        list.appendChild(row);
        coauthorIndex++;
        syncEmptyMessage();
        row.querySelector('input').focus();
        schedulePreview();
    });

    list.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-coauthor');
        if (!button) return;
        button.closest('.coauthor-row').remove();
        syncEmptyMessage();
        schedulePreview();
    });

    // ── Live preview ────────────────────────────────────────────────────
    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function renderPreview(data) {
        let html = '';
        if (data.conference_code) {
            html += '<div class="preview-code">' + escapeHtml(data.conference_code) + '</div>';
        }
        html += '<div class="preview-title">' + escapeHtml(data.title) + '</div>';
        // authors and body_html are built server-side by the same formatter the
        // book uses; their dynamic parts are escaped there.
        html += '<div class="preview-authors">' + data.authors + '</div>';

        if (data.affiliations && data.affiliations.length) {
            html += '<div class="preview-affiliations">';
            data.affiliations.forEach(function (affiliation) {
                html += '<div>' + affiliation.id + '. ' + escapeHtml(affiliation.name) + '</div>';
            });
            html += '</div>';
        }

        html += data.body_html;

        if (data.keywords) {
            html += '<div class="preview-keywords"><strong>Keywords:</strong> ' + escapeHtml(data.keywords) + '</div>';
        }

        previewBox.innerHTML = html;
    }

    function refreshPreview() {
        if (inFlight) inFlight.abort();
        inFlight = new AbortController();
        previewNote.textContent = 'Updating…';

        const body = new FormData(form);
        body.delete('_method');

        fetch(previewUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: body,
            signal: inFlight.signal,
        })
            .then(function (response) {
                if (response.status === 422) {
                    previewNote.textContent = 'Fill in the required fields to preview';
                    return null;
                }
                if (!response.ok) throw new Error('Preview failed');
                return response.json();
            })
            .then(function (data) {
                if (!data) return;
                renderPreview(data);
                previewNote.textContent = 'Up to date';
            })
            .catch(function (error) {
                if (error.name === 'AbortError') return;
                previewNote.textContent = 'Preview unavailable';
            });
    }

    function schedulePreview() {
        clearTimeout(timer);
        timer = setTimeout(refreshPreview, 450);
    }

    form.addEventListener('input', function (event) {
        if (event.target.name === '_token') return;
        schedulePreview();
    });

    // ── Character counter ───────────────────────────────────────────────
    function updateCount() {
        counter.textContent = description.value.length.toLocaleString() + ' / 10,000';
    }
    description.addEventListener('input', updateCount);

    syncEmptyMessage();
    updateCount();
    refreshPreview();
})();
</script>
@endpush
