@extends('layouts.app')

@section('title', isset($report) ? 'Edit Rapporteur Report' : 'New Rapporteur Report')

@section('content')
@php
    $user = Auth::user();
    $isEdit = isset($report);
    $isChief = $isChief ?? false;

    // Rapporteur 1 is always the report's author — when the Chief edits in place we must
    // show the original rapporteur, not the logged-in chief.
    $author = $isEdit ? ($report->user ?? $user) : $user;

    $action = $isChief
        ? route('chief-rapporteur.update', $report)
        : ($isEdit ? route('rapporteur-reports.update', $report) : route('rapporteur-reports.store'));
    $backUrl = $isChief ? route('chief-rapporteur.show', $report) : route('rapporteur-reports.index');
    $backLabel = $isChief ? 'Back to Report' : 'My Reports';
    $selectedR2 = old('rapporteur2_user_id', $isEdit ? $report->rapporteur2_user_id : null);

    // Pre-fill from existing report or blank defaults
    $v = fn($field, $default = '') => old($field, $isEdit ? ($report->{$field} ?? $default) : $default);

    $SUBTHEMES = \App\Support\ConferenceTopics::names();

    $EVIDENCE_NATURE_OPTIONS = [
        'Empirical research data',
        'Routine surveillance / programme data',
        'Clinical or laboratory findings',
        'Modelling or forecasting outputs',
        'Implementation experience',
        'Policy / strategic discussion',
        'Methodological or conceptual presentation',
        'Product / innovation demonstration',
        'Systematic/Desk/Scooping review',
    ];

    $EVIDENCE_STATUS_OPTIONS = [
        'Established findings',
        'Emerging findings',
        'Preliminary / pilot findings',
        'Descriptive experience',
        'Expert opinion / strategic reflection',
        'Not applicable',
    ];

    $TARGET_AUDIENCE_OPTIONS = [
        'Rehab Health',
        'Ministry of Health',
        'PORALG',
        'Other Government Ministries, Departments, or Agencies',
        'Researchers / academic institutions',
        'Healthcare providers / professional associations',
        'Laboratory and surveillance systems',
        'Programme implementers',
        'Regulators / ethics bodies',
        'Development partners / funders',
        'Private sector',
        'Community and civil society organizations',
        'Traditional medicine practitioners',
        'Animal, environmental, agriculture, livestock, wildlife, or One Health sectors',
        'Regional / district health management teams',
    ];

    $selectedSession = $isEdit ? $report->conference_session_id : old('conference_session_id');
    $existingPresentation = $isEdit ? ($report->presentations ?? []) : [];
    $existingQuestions = $isEdit ? ($report->discussion_questions ?? []) : [];
    $existingRecs = $isEdit ? ($report->recommendations ?? []) : [];
    $existingEvidenceNature = $isEdit ? ($report->evidence_nature ?? []) : [];
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8"
     x-data="rapporteurForm({{ json_encode($sessions->map(fn($s) => [
         'id' => $s->id,
         'name' => $s->name,
         'type' => $s->session_type ?? '',
         'subtheme' => $s->subtheme ?? '',
         'days' => $s->schedule_days ?? [],
         'room' => $s->room_location ?? '',
         'chair' => $s->session_chair ?? '',
         'start' => $s->start_time?->format('H:i') ?? '',
         'end' => $s->end_time?->format('H:i') ?? '',
     ])->values()) }},
     {{ $selectedSession ?? 'null' }},
     {{ json_encode($existingPresentation ?: [['abstract_id'=>'','presenter'=>'','institution'=>'','title'=>'','study_setting'=>'','method'=>'','key_finding'=>'','implication'=>'']]) }},
     {{ json_encode($existingQuestions ?: [['question'=>'','raised_by'=>'','response'=>'','resolved'=>'']]) }},
     {{ json_encode($existingRecs ?: [['recommendation'=>'','basis'=>'','target_audience'=>[],'timeline'=>'','priority'=>'']]) }}
     )">

    {{-- Back link --}}
    <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600 mb-6 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ $backLabel }}
    </a>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
            {{ $isChief ? 'Edit Report (Chief Rapporteur)' : ($isEdit ? 'Edit Report' : 'New Rapporteur Report') }}
        </h1>
        @if($isChief)
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Editing on behalf of {{ trim(($author->title ?? '') . ' ' . ($author->first_name ?? '') . ' ' . ($author->last_name ?? '')) ?: ($author->email ?? 'rapporteur') }}. Your changes are recorded in the review history.
        </p>
        @else
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ config('conference.edition') }} {{ config('conference.name') }} — {{ config('conference.short_name') }} {{ config('conference.year') }}</p>
        @endif
    </div>

    @if($errors->any())
    <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 dark:bg-red-900/20 dark:border-red-700 dark:text-red-400">
        <strong>Please fix the following:</strong>
        <ul class="mt-1 list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ $action }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- ─────────────────────────────────────────────────── --}}
        {{-- TABLE 1: SESSION IDENTIFICATION --}}
        {{-- ─────────────────────────────────────────────────── --}}
        <div class="rr-section">
            <h2 class="rr-section-title">
                <span class="rr-section-num">1</span>
                Session Identification
            </h2>

            {{-- Session picker --}}
            <div class="rr-field">
                <label class="rr-label">Session <span class="text-red-500">*</span></label>
                <select name="conference_session_id" x-model="sessionId" @change="fillFromSession()" required
                        class="rr-input">
                    <option value="">— Select a session —</option>
                    @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ (string)$selectedSession === (string)$s->id ? 'selected' : '' }}>
                        {{ $s->name }}
                        @if($s->session_type) ({{ ucfirst(str_replace('_',' ',$s->session_type)) }}) @endif
                        @if(!empty($s->schedule_days)) — {{ implode(', ', $s->schedule_days) }} @endif
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Auto-filled session details (read-only preview) --}}
            <div x-show="sessionId" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-3 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700 text-sm" x-cloak>
                <div>
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Type</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300" x-text="selectedSession?.type || '—'"></span>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Day(s)</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300" x-text="(selectedSession?.days || []).join(', ') || '—'"></span>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Time</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300" x-text="selectedSession?.start ? selectedSession.start + '–' + selectedSession.end : '—'"></span>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Room</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300" x-text="selectedSession?.room || '—'"></span>
                </div>
                <div class="col-span-2">
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Session Chair</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300" x-text="selectedSession?.chair || '—'"></span>
                </div>
            </div>

            {{-- Rapporteur 1 (report author — read-only display) --}}
            <div class="mt-4 p-4 bg-brand-50 dark:bg-brand-900/10 border border-brand-200 dark:border-brand-800 rounded-lg">
                <p class="text-xs font-black uppercase tracking-widest text-brand-600 dark:text-brand-400 mb-2">Rapporteur 1 {{ $isChief ? '' : '(You)' }}</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                    <div><span class="text-slate-400">Name:</span> <span class="font-medium text-slate-700 dark:text-slate-300">{{ $author->title ?? '' }} {{ $author->first_name }} {{ $author->last_name }}</span></div>
                    <div><span class="text-slate-400">Email:</span> <span class="font-medium text-slate-700 dark:text-slate-300">{{ $author->email }}</span></div>
                    <div><span class="text-slate-400">Institution:</span> <span class="font-medium text-slate-700 dark:text-slate-300">{{ $author->affiliation ?? '—' }}</span></div>
                    <div><span class="text-slate-400">Phone:</span> <span class="font-medium text-slate-700 dark:text-slate-300">{{ $author->phone ?? '—' }}</span></div>
                </div>
            </div>

            {{-- Rapporteur 2 --}}
            <div class="mt-4">
                <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Rapporteur 2 (Optional)</p>
                <div class="rr-field">
                    <label class="rr-label">Select co-rapporteur</label>
                    <select name="rapporteur2_user_id" class="rr-input">
                        <option value="">— None —</option>
                        @foreach($users->reject(fn($u) => $u->id === $author->id) as $u)
                        <option value="{{ $u->id }}" {{ (string)$selectedR2 === (string)$u->id ? 'selected' : '' }}>
                            {{ trim(($u->title ?? '') . ' ' . $u->first_name . ' ' . $u->last_name) }}
                            @if($u->affiliation) — {{ $u->affiliation }} @endif
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ─────────────────────────────────────────────────── --}}
        {{-- TABLE 2: SUBTHEME --}}
        {{-- ─────────────────────────────────────────────────── --}}
        <div class="rr-section">
            <h2 class="rr-section-title">
                <span class="rr-section-num">2</span>
                {{ config('conference.short_name') }} Subtheme
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">Select the primary subtheme for this session.</p>
            <div class="space-y-2">
                @foreach($SUBTHEMES as $theme)
                <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer hover:border-brand-300 dark:hover:border-brand-600 transition-all has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50 dark:has-[:checked]:bg-brand-900/20">
                    <input type="radio" name="subtheme" value="{{ $theme }}"
                           {{ $v('subtheme') === $theme ? 'checked' : '' }}
                           class="mt-0.5 accent-brand-500 flex-shrink-0">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ $theme }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- ─────────────────────────────────────────────────── --}}
        {{-- TABLE 3: PRESENTATION RECORD --}}
        {{-- ─────────────────────────────────────────────────── --}}
        <div class="rr-section">
            <h2 class="rr-section-title">
                <span class="rr-section-num">3</span>
                Presentation Record
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">One row per presentation. For panel discussions, one row per panellist.</p>

            <div class="space-y-4">
                <template x-for="(row, idx) in presentations" :key="idx">
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 bg-slate-50 dark:bg-slate-800/50 relative">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-400" x-text="'Presentation ' + (idx + 1)"></span>
                            <button type="button" @click="removePresentation(idx)"
                                    x-show="presentations.length > 1"
                                    class="text-xs text-red-500 hover:text-red-700 font-semibold transition-colors">
                                Remove
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="rr-field">
                                <label class="rr-label">Abstract ID</label>
                                <input type="text" :name="'presentations[' + idx + '][abstract_id]'" x-model="row.abstract_id" class="rr-input" placeholder="e.g. A-042">
                            </div>
                            <div class="rr-field">
                                <label class="rr-label">Presenter / Panellist</label>
                                <input type="text" :name="'presentations[' + idx + '][presenter]'" x-model="row.presenter" class="rr-input" placeholder="Full name">
                            </div>
                            <div class="rr-field">
                                <label class="rr-label">Institution</label>
                                <input type="text" :name="'presentations[' + idx + '][institution]'" x-model="row.institution" class="rr-input" placeholder="Institution">
                            </div>
                            <div class="rr-field">
                                <label class="rr-label">Study Setting / Scope</label>
                                <input type="text" :name="'presentations[' + idx + '][study_setting]'" x-model="row.study_setting" class="rr-input" placeholder="e.g. National, district level">
                            </div>
                            <div class="rr-field sm:col-span-2">
                                <label class="rr-label">Presentation Title / Topic</label>
                                <input type="text" :name="'presentations[' + idx + '][title]'" x-model="row.title" class="rr-input" placeholder="Full title">
                            </div>
                            <div class="rr-field">
                                <label class="rr-label">Main Method or Approach</label>
                                <input type="text" :name="'presentations[' + idx + '][method]'" x-model="row.method" class="rr-input" placeholder="e.g. Cross-sectional survey">
                            </div>
                            <div class="rr-field">
                                <label class="rr-label">Key Finding / Message / Innovation</label>
                                <textarea :name="'presentations[' + idx + '][key_finding]'" x-model="row.key_finding" rows="2" class="rr-input" placeholder="Summarise the key finding"></textarea>
                            </div>
                            <div class="rr-field sm:col-span-2">
                                <label class="rr-label">Main Implication (policy, practice, research, or health systems)</label>
                                <textarea :name="'presentations[' + idx + '][implication]'" x-model="row.implication" rows="2" class="rr-input" placeholder="What should change or be acted upon?"></textarea>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="addPresentation()"
                    class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 dark:bg-brand-900/20 dark:text-brand-400 rounded-lg border border-brand-200 dark:border-brand-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Presentation
            </button>
        </div>

        {{-- ─────────────────────────────────────────────────── --}}
        {{-- TABLE 4: DISCUSSION --}}
        {{-- ─────────────────────────────────────────────────── --}}
        <div class="rr-section">
            <h2 class="rr-section-title">
                <span class="rr-section-num">4</span>
                Discussion, Questions, and Responses
            </h2>

            <div class="space-y-3 mb-4">
                <template x-for="(q, idx) in questions" :key="idx">
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 bg-slate-50 dark:bg-slate-800/50">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-400" x-text="'Q' + (idx + 1)"></span>
                            <button type="button" @click="removeQuestion(idx)"
                                    x-show="questions.length > 1"
                                    class="text-xs text-red-500 hover:text-red-700 font-semibold transition-colors">Remove</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="rr-field sm:col-span-2">
                                <label class="rr-label">Question / Comment Raised</label>
                                <textarea :name="'discussion_questions[' + idx + '][question]'" x-model="q.question" rows="2" class="rr-input" placeholder="Write the question or comment"></textarea>
                            </div>
                            <div class="rr-field">
                                <label class="rr-label">Raised By (if known)</label>
                                <input type="text" :name="'discussion_questions[' + idx + '][raised_by]'" x-model="q.raised_by" class="rr-input" placeholder="Name or affiliation">
                            </div>
                            <div class="rr-field">
                                <label class="rr-label">Was the Issue Resolved?</label>
                                <select :name="'discussion_questions[' + idx + '][resolved]'" x-model="q.resolved" class="rr-input">
                                    <option value="">— Select —</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                    <option value="Partially">Partially</option>
                                </select>
                            </div>
                            <div class="rr-field sm:col-span-2">
                                <label class="rr-label">Response by Presenter / Panellist</label>
                                <textarea :name="'discussion_questions[' + idx + '][response]'" x-model="q.response" rows="2" class="rr-input" placeholder="Summarise the response"></textarea>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="addQuestion()"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 dark:bg-brand-900/20 dark:text-brand-400 rounded-lg border border-brand-200 dark:border-brand-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Question
            </button>

            <div class="grid grid-cols-1 gap-4 mt-5">
                <div class="rr-field">
                    <label class="rr-label">Areas of Agreement</label>
                    <textarea name="areas_of_agreement" rows="3" class="rr-input" placeholder="What did participants broadly agree on?">{{ $v('areas_of_agreement') }}</textarea>
                </div>
                <div class="rr-field">
                    <label class="rr-label">Areas of Debate, Uncertainty, or Disagreement</label>
                    <textarea name="areas_of_debate" rows="3" class="rr-input" placeholder="What remained contested or unresolved?">{{ $v('areas_of_debate') }}</textarea>
                </div>
                <div class="rr-field">
                    <label class="rr-label">Issues Requiring Follow-Up Beyond the Session</label>
                    <textarea name="follow_up_issues" rows="3" class="rr-input" placeholder="List items that need further action or investigation">{{ $v('follow_up_issues') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ─────────────────────────────────────────────────── --}}
        {{-- TABLE 5: SCIENTIFIC SYNTHESIS --}}
        {{-- ─────────────────────────────────────────────────── --}}
        <div class="rr-section">
            <h2 class="rr-section-title">
                <span class="rr-section-num">5</span>
                Scientific and Technical Synthesis
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Summarise the overall scientific contribution of the session — do not repeat per-presentation details.</p>

            <div class="space-y-4">
                <div class="rr-field">
                    <label class="rr-label">Three Main Scientific / Technical Messages</label>
                    <textarea name="scientific_message_1" rows="2" class="rr-input mb-2" placeholder="Message 1">{{ $v('scientific_message_1') }}</textarea>
                    <textarea name="scientific_message_2" rows="2" class="rr-input mb-2" placeholder="Message 2">{{ $v('scientific_message_2') }}</textarea>
                    <textarea name="scientific_message_3" rows="2" class="rr-input" placeholder="Message 3">{{ $v('scientific_message_3') }}</textarea>
                </div>

                <div class="rr-field">
                    <label class="rr-label">Most Important Finding or Insight from the Session</label>
                    <textarea name="most_important_finding" rows="3" class="rr-input" placeholder="What was the single most significant takeaway?">{{ $v('most_important_finding') }}</textarea>
                </div>

                {{-- Nature of evidence --}}
                <div class="rr-field">
                    <label class="rr-label">Nature of Evidence Presented <span class="text-xs font-normal text-slate-400">(tick all that apply)</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                        @foreach($EVIDENCE_NATURE_OPTIONS as $opt)
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" name="evidence_nature[]" value="{{ $opt }}"
                                   {{ in_array($opt, $existingEvidenceNature) ? 'checked' : '' }}
                                   class="accent-brand-500 rounded">
                            <span class="text-slate-700 dark:text-slate-300">{{ $opt }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Status of evidence --}}
                <div class="rr-field">
                    <label class="rr-label">Status of Evidence</label>
                    <div class="space-y-2 mt-2">
                        @foreach($EVIDENCE_STATUS_OPTIONS as $opt)
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" name="evidence_status" value="{{ $opt }}"
                                   {{ $v('evidence_status') === $opt ? 'checked' : '' }}
                                   class="accent-brand-500">
                            <span class="text-slate-700 dark:text-slate-300">{{ $opt }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="rr-field">
                    <label class="rr-label">Important Method, Tool, Innovation, or Approach Highlighted</label>
                    <textarea name="important_method" rows="2" class="rr-input" placeholder="Describe any notable methodology or tool">{{ $v('important_method') }}</textarea>
                </div>

                <div class="rr-field">
                    <label class="rr-label">Main Limitation or Caution for Interpretation</label>
                    <textarea name="main_limitation" rows="2" class="rr-input" placeholder="What caveats should readers bear in mind?">{{ $v('main_limitation') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ─────────────────────────────────────────────────── --}}
        {{-- TABLE 6: RECOMMENDATIONS --}}
        {{-- ─────────────────────────────────────────────────── --}}
        <div class="rr-section">
            <h2 class="rr-section-title">
                <span class="rr-section-num">6</span>
                Recommendations
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Up to 3 specific, actionable, session-level recommendations linked to evidence discussed.</p>

            <div class="space-y-5">
                <template x-for="(rec, idx) in recommendations" :key="idx">
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-5 bg-slate-50 dark:bg-slate-800/50">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4" x-text="'Recommendation ' + (idx + 1)"></p>

                        <div class="space-y-4">
                            <div class="rr-field">
                                <label class="rr-label">Recommendation</label>
                                <textarea :name="'recommendations[' + idx + '][recommendation]'" x-model="rec.recommendation" rows="3" class="rr-input" placeholder="State a specific, actionable recommendation"></textarea>
                            </div>
                            <div class="rr-field">
                                <label class="rr-label">Basis from Session Evidence or Discussion</label>
                                <textarea :name="'recommendations[' + idx + '][basis]'" x-model="rec.basis" rows="2" class="rr-input" placeholder="Which finding or discussion supports this?"></textarea>
                            </div>

                            {{-- Target audience --}}
                            <div class="rr-field">
                                <label class="rr-label">Target Audience <span class="text-xs font-normal text-slate-400">(tick all that apply)</span></label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                                    @foreach($TARGET_AUDIENCE_OPTIONS as $aud)
                                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox"
                                               :name="'recommendations[' + idx + '][target_audience][]'"
                                               value="{{ $aud }}"
                                               :checked="rec.target_audience && rec.target_audience.includes('{{ $aud }}')"
                                               @change="toggleAudience(rec, '{{ $aud }}', $event.target.checked)"
                                               class="accent-brand-500 rounded">
                                        <span class="text-slate-700 dark:text-slate-300">{{ $aud }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="rr-field">
                                    <label class="rr-label">Timeline</label>
                                    <select :name="'recommendations[' + idx + '][timeline]'" x-model="rec.timeline" class="rr-input">
                                        <option value="">— Select —</option>
                                        <option value="Immediate">Immediate</option>
                                        <option value="6-12 months">6–12 months</option>
                                        <option value="1-3 years">1–3 years</option>
                                        <option value="Long-term">Long-term</option>
                                    </select>
                                </div>
                                <div class="rr-field">
                                    <label class="rr-label">Priority</label>
                                    <select :name="'recommendations[' + idx + '][priority]'" x-model="rec.priority" class="rr-input">
                                        <option value="">— Select —</option>
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Low">Low</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="addRecommendation()"
                    x-show="recommendations.length < 3"
                    class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 dark:bg-brand-900/20 dark:text-brand-400 rounded-lg border border-brand-200 dark:border-brand-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Recommendation
            </button>
        </div>

        {{-- ─────────────────────────────────────────────────── --}}
        {{-- SUBMIT BUTTONS --}}
        {{-- ─────────────────────────────────────────────────── --}}
        <div class="sticky bottom-4 z-10">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                @if($isChief)
                <p class="text-xs text-slate-400 text-center sm:text-left">
                    Your edits are saved against this report and logged in the review history. Return to the report to approve or send back for revision.
                </p>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <a href="{{ $backUrl }}"
                       class="px-5 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600 transition-all">
                        Cancel
                    </a>
                    <button type="submit" name="action" value="save"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-md hover:shadow-sky-500/30 transition-all">
                        Save Changes
                    </button>
                </div>
                @else
                <p class="text-xs text-slate-400 text-center sm:text-left">
                    Save as draft at any time. Submit only when complete — submitted reports go to the Chief Rapporteur for review.
                </p>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <button type="submit" name="action" value="draft"
                            class="px-5 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600 transition-all">
                        Save Draft
                    </button>
                    <button type="submit" name="action" value="submit"
                            onclick="return confirm('Submit this report for review? You can still revise it if the Chief Rapporteur returns it.')"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-brand-500 hover:bg-brand-600 rounded-lg shadow-md hover:shadow-brand-500/30 transition-all">
                        Submit Report
                    </button>
                </div>
                @endif
            </div>
        </div>

    </form>
</div>

@push('styles')
<style>
.rr-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.25rem;
}
.dark .rr-section {
    background: #1e293b;
    border-color: #334155;
}
.rr-section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 1.25rem;
}
.dark .rr-section-title { color: white; }
.rr-section-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    background: #3a86ff;
    color: white;
    font-size: 0.75rem;
    font-weight: 900;
    border-radius: 0.5rem;
    flex-shrink: 0;
}
.rr-field { display: flex; flex-direction: column; gap: 0.25rem; }
.rr-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.dark .rr-label { color: #94a3b8; }
.rr-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #1e293b;
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    outline: none;
    transition: border-color 0.15s;
}
.dark .rr-input { background: #0f172a; border-color: #334155; color: white; }
.rr-input:focus { border-color: #3a86ff; box-shadow: 0 0 0 3px rgba(58,134,255,0.1); }
textarea.rr-input { resize: vertical; }
select.rr-input { cursor: pointer; }
[x-cloak] { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
function rapporteurForm(sessions, selectedId, initPresentations, initQuestions, initRecs) {
    return {
        sessions,
        sessionId: selectedId ? String(selectedId) : '',
        presentations: initPresentations,
        questions: initQuestions,
        recommendations: initRecs,

        get selectedSession() {
            return this.sessions.find(s => String(s.id) === String(this.sessionId)) || null;
        },

        fillFromSession() {
            // auto-selects subtheme radio if session has one
            const s = this.selectedSession;
            if (s && s.subtheme) {
                const radios = document.querySelectorAll('input[name="subtheme"]');
                radios.forEach(r => { r.checked = r.value === s.subtheme; });
            }
        },

        addPresentation() {
            this.presentations.push({ abstract_id:'', presenter:'', institution:'', title:'', study_setting:'', method:'', key_finding:'', implication:'' });
        },
        removePresentation(idx) {
            if (this.presentations.length > 1) this.presentations.splice(idx, 1);
        },

        addQuestion() {
            this.questions.push({ question:'', raised_by:'', response:'', resolved:'' });
        },
        removeQuestion(idx) {
            if (this.questions.length > 1) this.questions.splice(idx, 1);
        },

        addRecommendation() {
            if (this.recommendations.length < 3) {
                this.recommendations.push({ recommendation:'', basis:'', target_audience:[], timeline:'', priority:'' });
            }
        },

        toggleAudience(rec, value, checked) {
            if (!rec.target_audience) rec.target_audience = [];
            if (checked) {
                if (!rec.target_audience.includes(value)) rec.target_audience.push(value);
            } else {
                rec.target_audience = rec.target_audience.filter(v => v !== value);
            }
        },
    };
}
</script>
@endpush
@endsection
