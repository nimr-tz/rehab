@php
    use App\Enums\AbstractStatus;
    use App\Models\ReviewAssignment;

    $open = in_array($abstract->status, [AbstractStatus::Submitted, AbstractStatus::UnderReview], true);
    $completed = $abstract->reviews->filter->isComplete();
    $recommendations = $completed->countBy(fn ($r) => $r->recommendation->value);
@endphp

<x-layouts.portal :title="$abstract->title">
    <x-page-header :eyebrow="($abstract->code ?? $abstract->blindId()).' · '.$abstract->topic->name" :title="$abstract->title" :back="route('scientific.abstracts.index')">
        <x-status :tone="$abstract->status->tone()">{{ $abstract->status->label() }}</x-status>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <x-card>
                <div class="space-y-6 text-[15px] leading-relaxed text-ink-700">
                    @foreach (['background' => 'Background', 'methods' => 'Methods', 'results' => 'Results', 'conclusions' => 'Conclusions'] as $field => $label)
                        <section>
                            <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-700">{{ $label }}</h2>
                            <p class="mt-1.5 whitespace-pre-line">{{ $abstract->{$field} }}</p>
                        </section>
                    @endforeach
                    <p class="text-sm text-ink-500">{{ $abstract->wordCount() }} words · prefers {{ strtolower($abstract->preferred_type->label()) }}@if ($abstract->keywords) · {{ $abstract->keywords }}@endif</p>
                </div>
            </x-card>

            {{-- Reviews --}}
            <x-card title="Reviews" :description="$completed->count().' of '.$abstract->reviews->count().' completed'.($abstract->averageScore() ? ' · average '.$abstract->averageScore().'/20' : '')">
                @forelse ($abstract->reviews as $review)
                    <div class="border-b border-ink-100 py-4 first:pt-0 last:border-0 last:pb-0">
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
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-ink-900">{{ $review->totalScore() }}/20</span>
                                    <x-status :tone="$review->recommendation->tone()">{{ $review->recommendation->label() }}</x-status>
                                </div>
                            @else
                                <div class="flex items-center gap-2">
                                    <x-status tone="warning">Pending</x-status>
                                    @if ($open)
                                        <form method="POST" action="{{ route('scientific.abstracts.unassign', [$abstract, $review]) }}">
                                            @csrf @method('DELETE')
                                            <button class="text-xs font-semibold text-red-700 hover:underline">Remove</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if ($review->isComplete())
                            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach (ReviewAssignment::CRITERIA as $field => $label)
                                    <div class="rounded-xl bg-canvas px-3 py-2">
                                        <p class="text-[11px] text-ink-500">{{ $label }}</p>
                                        <p class="font-bold text-ink-900">{{ $review->{$field} }}/5</p>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-3 whitespace-pre-line text-sm text-ink-700"><span class="font-semibold">For the author:</span> {{ $review->comments_for_author }}</p>
                            @if ($review->comments_for_committee)
                                <p class="mt-2 whitespace-pre-line rounded-xl bg-sun-50 px-3 py-2 text-sm text-sun-900"><span class="font-semibold">Confidential:</span> {{ $review->comments_for_committee }}</p>
                            @endif
                        @endif
                    </div>
                @empty
                    <x-empty icon="users" title="No reviewers yet" class="!py-6">Assign two or three reviewers from the panel on the right.</x-empty>
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

            @if ($open)
                <x-card title="Assign a reviewer">
                    @if ($eligible->isEmpty())
                        <p class="text-sm text-ink-500">Every available reviewer is already assigned or is an author.</p>
                    @else
                        <form method="POST" action="{{ route('scientific.abstracts.assign', $abstract) }}" class="space-y-4">
                            @csrf
                            <x-form.select name="reviewer_id" label="Reviewer" placeholder="Select a reviewer" required
                                :options="$eligible->mapWithKeys(fn ($r) => [$r->id => $r->name.' · '.$r->open_reviews_count.' open'])->all()" />
                            <x-form.input name="due_on" type="date" label="Due by" :value="$abstract->edition->review_deadline?->toDateString()" />
                            <x-button class="w-full" icon="plus">Assign</x-button>
                        </form>
                    @endif
                </x-card>

                <x-card title="Decision" :description="$completed->isEmpty() ? 'Wait for at least one review.' : 'Reviewers recommend: '.$recommendations->map(fn ($n, $r) => $n.'× '.strtolower(\App\Enums\Recommendation::from($r)->label()))->implode(', ')">
                    <form method="POST" action="{{ route('scientific.abstracts.decide', $abstract) }}" class="space-y-4" x-data="{ decision: '' }">
                        @csrf
                        <div class="grid gap-2">
                            @foreach (['oral' => 'Accept as oral', 'poster' => 'Accept as poster', 'reject' => 'Reject'] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-ink-200 px-3 py-2.5 text-sm font-medium text-ink-800 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                                    <input type="radio" name="decision" value="{{ $value }}" x-model="decision" class="h-4 w-4 accent-brand-700" required> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <x-form.textarea name="decision_note" label="Note to the author (optional)" rows="3" />
                        <x-button class="w-full" ::class="decision === 'reject' ? '!bg-red-700 hover:!bg-red-800' : ''" :disabled="$completed->isEmpty()">Record decision</x-button>
                    </form>
                </x-card>
            @else
                <x-card title="Decision">
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-ink-500">Outcome</dt><dd class="mt-1"><x-status :tone="$abstract->status->tone()">{{ $abstract->status === AbstractStatus::Accepted ? 'Accepted as '.strtolower($abstract->decision_type->label()) : $abstract->status->label() }}</x-status></dd></div>
                        @if ($abstract->code)<div><dt class="text-ink-500">Conference code</dt><dd class="mt-1 font-mono font-semibold text-ink-900">{{ $abstract->code }}</dd></div>@endif
                        @if ($abstract->decided_at)<div><dt class="text-ink-500">Decided</dt><dd class="mt-1 text-ink-900">{{ $abstract->decided_at->format('j M Y') }}</dd></div>@endif
                        @if ($abstract->decision_note)<div><dt class="text-ink-500">Note</dt><dd class="mt-1 text-ink-900">{{ $abstract->decision_note }}</dd></div>@endif
                    </dl>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.portal>
