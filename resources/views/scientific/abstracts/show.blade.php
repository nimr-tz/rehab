@php
    use App\Enums\AbstractStatus;
    use App\Enums\Recommendation;
    use App\Support\Palette;
    use App\Support\Rubric;

    $round = $abstract->currentRound();
    $current = $abstract->roundReviews();
    $completed = $current->filter->isComplete();
    $recommendations = $completed->countBy(fn ($r) => $r->recommendation->value);
    $average = $abstract->averageScore();
    $averageBand = Rubric::band($average);
    $needed = $abstract->reviewersNeededInRound();
    $rounds = $abstract->reviews->groupBy('round')->sortKeys();
@endphp

<x-layouts.portal :title="$abstract->title">
    <x-slot:header>
        <x-page-header :eyebrow="($abstract->code ?? $abstract->blindId()).' · '.$abstract->topic->name" :title="$abstract->title" :back="route('scientific.abstracts.index')">
            <x-status :tone="$abstract->status->tone()">{{ $abstract->status->label() }}</x-status>
        </x-page-header>
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <x-card>
                <div class="space-y-6 text-[15px] leading-relaxed text-ink-700">
                    @foreach (\App\Models\AbstractSubmission::SECTIONS as $field => $label)
                        <section>
                            <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-700">{{ $label }}</h2>
                            <p class="mt-1.5 whitespace-pre-line">{{ $abstract->{$field} }}</p>
                        </section>
                    @endforeach
                    <p class="text-sm text-ink-500">{{ $abstract->wordCount() }} words @if (\App\Enums\PresentationType::postersEnabled())· prefers {{ strtolower($abstract->preferred_type->label()) }}@endif @if ($abstract->keywords) · {{ $abstract->keywords }}@endif</p>
                </div>
            </x-card>

            {{-- The revision: what the committee asked for, and what the authors changed --}}
            @if ($abstract->revision_requested_at)
                <x-card title="Revision" :description="'Requested '.$abstract->revision_requested_at->format('j M Y').($abstract->revised_at ? ' · revised '.$abstract->revised_at->format('j M Y') : ' · due '.$abstract->revision_due_on->format('j M Y'))">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl bg-canvas p-4 text-sm">
                            <p class="text-xs font-bold uppercase tracking-wider text-ink-500">Your note to the authors</p>
                            <p class="mt-2 whitespace-pre-line text-ink-700">{{ $abstract->revision_note ?: 'No note: the reviewers\' comments only.' }}</p>
                        </div>
                        <div class="rounded-2xl bg-brand-50 p-4 text-sm">
                            <p class="text-xs font-bold uppercase tracking-wider text-brand-800">The authors' response</p>
                            <p class="mt-2 whitespace-pre-line text-brand-900">{{ $abstract->revision_response ?: 'Not revised yet.' }}</p>
                        </div>
                    </div>
                    @if ($abstract->revised_at)
                        <x-abstract-comparison :abstract="$abstract" class="mt-6 border-t border-ink-100 pt-6" />
                    @endif
                </x-card>
            @endif

            {{-- Reviews, by round --}}
            <x-card title="Reviews" :description="$completed->count().' of '.max($needed, $current->count()).' in'.($round === 2 ? ' for the revised version' : '').($average !== null ? ' · average '.$average.'/'.Rubric::max() : '')">
                @forelse ($rounds as $number => $reviews)
                    @if ($rounds->count() > 1)
                        <p @class(['text-xs font-bold uppercase tracking-[0.16em] text-ink-500', 'mt-6 border-t border-ink-100 pt-6' => ! $loop->first])>
                            {{ $number === 1 ? 'First review' : 'Second review · the revised version' }}
                        </p>
                    @endif
                    @foreach ($reviews as $review)
                        <div class="border-b border-ink-100 py-4 last:border-0 last:pb-0">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-ink-900">{{ $review->reviewer->name }}</p>
                                    <p class="text-xs text-ink-500">
                                        @if ($review->isComplete()) Reviewed {{ $review->completed_at->format('j M Y') }}
                                        @else Assigned {{ $review->created_at->format('j M') }}@if ($review->due_on) · due {{ $review->due_on->format('j M') }}@endif
                                        @endif
                                    </p>
                                </div>
                                @if ($review->isComplete())
                                    <div class="flex items-center gap-3">
                                        <span class="text-right leading-tight">
                                            <span class="text-xl font-extrabold tabular-nums {{ Rubric::scoreClass($review->totalScore()) }}">{{ $review->totalScore() }}</span><span class="text-xs font-semibold text-ink-400">/{{ Rubric::max() }}</span>
                                            <span class="block text-[11px] font-semibold text-ink-500">{{ $review->band()['label'] }}</span>
                                        </span>
                                        <x-status :tone="$review->recommendation->tone()">{{ $review->recommendation->label() }}</x-status>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <x-status :tone="$review->due_on?->lt(today()) ? 'danger' : 'warning'">{{ $review->due_on?->lt(today()) ? 'Overdue' : 'Pending' }}</x-status>
                                        @if ($review->isOpen())
                                            <form method="POST" action="{{ route('scientific.abstracts.unassign', [$abstract, $review]) }}">
                                                @csrf @method('DELETE')
                                                <button class="text-xs font-semibold text-red-700 hover:underline">Remove</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @if ($review->isComplete())
                                <div class="mt-3 grid gap-x-5 gap-y-2.5 rounded-xl bg-canvas px-4 py-3 sm:grid-cols-2">
                                    @foreach (Rubric::criteria() as $field => $criterion)
                                        <div>
                                            <div class="flex justify-between text-xs">
                                                <span class="font-medium text-ink-600">{{ $criterion['label'] }}</span>
                                                <span class="font-bold tabular-nums text-ink-900">{{ $review->{$field} }}/{{ $criterion['max'] }}</span>
                                            </div>
                                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-white">
                                                <div class="h-full rounded-full" style="width: {{ round($review->{$field} / $criterion['max'] * 100) }}%; background: {{ Palette::categorical($loop->index) }}"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($review->technical_checks !== null)
                                    <p class="mt-2 text-xs text-ink-500">
                                        <span class="font-semibold text-ink-700">Technical checks met:</span>
                                        {{ collect(Rubric::checks())->only($review->technical_checks)->pluck('label')->implode(', ') ?: 'none' }}
                                    </p>
                                @endif
                                <p class="mt-3 whitespace-pre-line text-sm text-ink-700"><span class="font-semibold">For the authors:</span> {{ $review->comments_for_author }}</p>
                                @if ($review->comments_for_committee)
                                    <p class="mt-2 whitespace-pre-line rounded-xl bg-sun-50 px-3 py-2 text-sm text-sun-900"><span class="font-semibold">Confidential:</span> {{ $review->comments_for_committee }}</p>
                                @endif
                            @endif
                        </div>
                    @endforeach
                @empty
                    <x-empty icon="users" title="No reviewers yet" class="!py-6">Assign {{ \App\Models\AbstractSubmission::reviewersNeeded() }} reviewers from the panel on the right.</x-empty>
                @endforelse
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Authors" description="Visible to the committee only.">
                <ol class="space-y-3 text-sm">
                    @foreach ($abstract->authors as $author)
                        <li>
                            <span class="font-medium text-ink-900">{{ $author->name }}</span>
                            @if ($author->is_presenter) <x-status tone="info" class="ml-1">Presenter</x-status> @endif
                            <span class="block text-xs text-ink-500">{{ $author->affiliation }}@if ($author->email) · {{ $author->email }}@endif</span>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-4 border-t border-ink-100 pt-4 text-xs text-ink-500">Submitted by {{ $abstract->submitter->name }} on {{ $abstract->submitted_at?->format('j M Y') }}</p>
            </x-card>

            @if ($seats > 0)
                <x-card :title="$round === 2 ? 'Replace a reviewer' : 'Assign a reviewer'"
                        :description="$round === 2 ? 'The revised version needs '.$needed.' '.\Illuminate\Support\Str::plural('review', $needed).'.' : $current->count().' of '.$needed.' reviewers assigned.'">
                    @if ($eligible->isEmpty())
                        <p class="text-sm text-ink-500">Every available reviewer is already assigned or is an author.</p>
                    @else
                        <form method="POST" action="{{ route('scientific.abstracts.assign', $abstract) }}" class="space-y-4">
                            @csrf
                            <x-form.select name="reviewer_id" label="Reviewer" placeholder="Select a reviewer" required
                                :options="$eligible->mapWithKeys(fn ($r) => [$r->id => $r->name.' · '.$r->open_reviews_count.' open'])->all()" />
                            <x-form.input name="due_on" type="date" label="Due by" :value="$round === 2 ? today()->addDays(config('review.second_round_days'))->toDateString() : $abstract->edition->review_deadline?->toDateString()" />
                            <x-button class="w-full" icon="plus">Assign</x-button>
                        </form>
                    @endif
                </x-card>
            @endif

            @if ($abstract->awaitsDecision())
                {{-- Every review of the round is in, and not every reviewer accepted --}}
                <x-card title="Decision" :description="'Reviewers recommend: '.$recommendations->map(fn ($n, $r) => $n.'× '.strtolower(Recommendation::from($r)->label()))->implode(', ')">
                    @if ($averageBand)
                        <div class="mb-4 flex items-center gap-3 rounded-xl bg-canvas px-4 py-3">
                            <span class="text-2xl font-extrabold tabular-nums {{ Rubric::scoreClass($average) }}">{{ $average }}</span>
                            <span class="leading-tight">
                                <span class="block text-xs text-ink-500">Average of {{ $completed->count() }} {{ \Illuminate\Support\Str::plural('review', $completed->count()) }}, out of {{ Rubric::max() }}</span>
                                <x-status :tone="$averageBand['tone']" class="mt-1">{{ $averageBand['label'] }}</x-status>
                            </span>
                        </div>
                    @endif
                    @if ($round === 2)
                        <p class="mb-4 text-sm text-ink-500">This is the revised version. Only one revision is allowed, so accept or reject it.</p>
                    @endif
                    <form method="POST" action="{{ route('scientific.abstracts.decide', $abstract) }}" class="space-y-4" x-data="{ decision: @js(old('decision', '')) }">
                        @csrf
                        <div class="grid gap-2">
                            @foreach ($abstract->decisionOptions() as $value => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-ink-200 px-3 py-2.5 text-sm font-medium text-ink-800 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                                    <input type="radio" name="decision" value="{{ $value }}" x-model="decision" class="h-4 w-4 accent-brand-700" required> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('decision') <p class="text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                        <div x-show="decision === 'revise'" x-cloak class="space-y-1">
                            <x-form.input name="revision_due_on" type="date" label="Revised version due by" :value="today()->addDays(config('review.revision_days'))->toDateString()" />
                            <p class="text-xs text-ink-500">The authors see both reviewers' comments. The revised version goes back only to the reviewers who did not accept.</p>
                        </div>
                        <x-form.textarea name="decision_note" label="Note to the authors (optional)" rows="3" />
                        <x-button class="w-full" ::class="decision === 'reject' ? '!bg-red-700 hover:!bg-red-800' : ''">
                            @if (isset($abstract->decisionOptions()['revise']))
                                <span x-text="decision === 'revise' ? 'Request revisions' : 'Record decision'">Record decision</span>
                            @else
                                Record decision
                            @endif
                        </x-button>
                    </form>
                </x-card>
            @elseif ($abstract->status === AbstractStatus::RevisionRequested)
                <x-card title="Waiting for the authors" :description="'Revised version due '.$abstract->revision_due_on->format('j F Y')">
                    @if ($abstract->isRevisionOverdue())
                        <x-alert tone="danger" class="mb-4">The deadline has passed. Give the authors more time, or reject the abstract.</x-alert>
                    @endif
                    <form method="POST" action="{{ route('scientific.abstracts.revision-due', $abstract) }}" class="flex items-end gap-2">
                        @csrf @method('PUT')
                        <div class="flex-1"><x-form.input name="revision_due_on" type="date" label="New due date" :value="$abstract->revision_due_on->copy()->addWeek()->max(today()->addWeek())->toDateString()" /></div>
                        <x-button variant="secondary">Extend</x-button>
                    </form>
                    <form method="POST" action="{{ route('scientific.abstracts.decide', $abstract) }}" class="mt-4 border-t border-ink-100 pt-4"
                          onsubmit="return confirm('Reject this abstract without waiting for the revision?')">
                        @csrf
                        <input type="hidden" name="decision" value="reject">
                        <x-button variant="secondary" class="w-full text-red-700" icon="x-circle">Reject without a revision</x-button>
                    </form>
                </x-card>
            @elseif ($abstract->status->isInReview())
                <x-card title="Decision">
                    <p class="text-sm text-ink-600">
                        Waiting for reviews: <span class="font-semibold text-ink-900">{{ $completed->count() }} of {{ $needed }}</span> in.
                    </p>
                    <p class="mt-2 text-sm text-ink-500">
                        @if ($round === 2)
                            If {{ $needed > 1 ? 'both reviewers accept' : 'the reviewer accepts' }} the revised version, it is accepted automatically. Otherwise you decide.
                        @else
                            If both reviewers accept, the abstract is accepted automatically. Otherwise you decide.
                        @endif
                    </p>
                </x-card>
            @else
                <x-card title="Decision">
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-ink-500">Outcome</dt><dd class="mt-1"><x-status :tone="$abstract->status->tone()">{{ $abstract->status === AbstractStatus::Accepted ? $abstract->decision_type->acceptedLabel() : $abstract->status->label() }}</x-status></dd></div>
                        @if ($abstract->accepted_automatically)
                            <div><dt class="text-ink-500">How</dt><dd class="mt-1 text-ink-900">Accepted automatically: {{ $round === 2 ? 'the reviewers accepted the revised version' : 'both reviewers accepted' }}.</dd></div>
                        @endif
                        @if ($abstract->code)<div><dt class="text-ink-500">Conference code</dt><dd class="mt-1 font-mono font-semibold text-ink-900">{{ $abstract->code }}</dd></div>@endif
                        @if ($abstract->decided_at)<div><dt class="text-ink-500">Decided</dt><dd class="mt-1 text-ink-900">{{ $abstract->decided_at->format('j M Y') }}</dd></div>@endif
                        @if ($abstract->decision_note)<div><dt class="text-ink-500">Note</dt><dd class="mt-1 text-ink-900">{{ $abstract->decision_note }}</dd></div>@endif
                    </dl>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.portal>
