@php
    use App\Enums\AbstractStatus;

    // Comments reach the authors once the committee has decided or asked for a revision.
    $showFeedback = $abstract->status->isDecided() || $abstract->revision_requested_at;
    // Second-round comments only once the final decision is made.
    $feedback = $showFeedback
        ? $abstract->reviews->filter(fn ($r) => $r->isComplete() && ($r->round === 1 || $abstract->status->isDecided()))->sortBy('round')->groupBy('round')
        : collect();
    $canWithdraw = $abstract->status->isEditable() || $abstract->status === AbstractStatus::RevisionRequested;
@endphp

<x-layouts.portal :title="$abstract->title">
    <x-slot:header>
        <x-page-header :eyebrow="$abstract->code ?? 'Abstract'" :title="$abstract->title" :back="route('abstracts.index')">
            @if ($abstract->status === AbstractStatus::RevisionRequested)
                <x-button :href="route('abstracts.revision.edit', $abstract)" icon="pencil">Revise abstract</x-button>
            @elseif ($abstract->status->isEditable() && $abstract->edition->acceptsAbstracts())
                <x-button variant="secondary" :href="route('abstracts.edit', $abstract)" icon="pencil">Edit</x-button>
            @endif
        </x-page-header>
    </x-slot:header>

    @if ($abstract->status === AbstractStatus::Accepted)
        <x-alert tone="success">
            <span class="font-semibold">{{ $abstract->decision_type->acceptedLabel() }}.</span>
            Your conference code is <span class="font-mono font-semibold">{{ $abstract->code }}</span>.
            @if ($abstract->sessions->isNotEmpty())
                Scheduled in "{{ $abstract->sessions->first()->title }}", {{ $abstract->sessions->first()->starts_at->format('l j F, H:i') }}.
            @endif
        </x-alert>
    @elseif ($abstract->status === AbstractStatus::Rejected)
        <x-alert tone="warning"><span class="font-semibold">Not accepted this year.</span> Thank you for submitting. The reviewers' comments are below.</x-alert>
    @elseif ($abstract->status === AbstractStatus::RevisionRequested)
        <x-alert :tone="$abstract->isRevisionOverdue() ? 'danger' : 'warning'">
            <span class="font-semibold">Revisions requested.</span>
            @if ($abstract->isRevisionOverdue())
                The revised version was due on {{ $abstract->revision_due_on->format('j F') }}. Please send it as soon as you can, or contact the scientific committee.
            @else
                Please send a revised version by {{ $abstract->revision_due_on->format('l j F Y') }}. The reviewers' comments are below.
            @endif
        </x-alert>
    @elseif ($abstract->status === AbstractStatus::Revised)
        <x-alert>Thank you for revising your abstract. The revised version is with the reviewers.</x-alert>
    @elseif ($abstract->status === AbstractStatus::Draft)
        <x-alert>This is a draft. Submit it before {{ \App\Support\Summit::formatDate($abstract->edition->abstract_deadline) }} for it to be reviewed.</x-alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
        <x-card>
            <div class="space-y-6 text-[15px] leading-relaxed text-ink-700">
                @foreach (['background' => 'Background', 'methods' => 'Methods', 'results' => 'Results', 'conclusions' => 'Conclusions'] as $field => $label)
                    <section>
                        <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-700">{{ $label }}</h2>
                        <p class="mt-1.5 whitespace-pre-line">{{ $abstract->{$field} ?: '—' }}</p>
                    </section>
                @endforeach
            </div>
        </x-card>

        <div class="space-y-6">
            <x-card title="Details">
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-ink-500">Status</dt><dd class="mt-1"><x-status :tone="$abstract->status->tone()">{{ $abstract->status->label() }}</x-status></dd></div>
                    <div><dt class="text-ink-500">Topic</dt><dd class="mt-1 font-medium text-ink-900">{{ $abstract->topic->name }}</dd></div>
                    @if (\App\Enums\PresentationType::postersEnabled())<div><dt class="text-ink-500">Preferred type</dt><dd class="mt-1 font-medium text-ink-900">{{ $abstract->preferred_type->label() }}</dd></div>@endif
                    <div><dt class="text-ink-500">Words</dt><dd class="mt-1 font-medium text-ink-900">{{ $abstract->wordCount() }}</dd></div>
                    @if ($abstract->submitted_at)
                        <div><dt class="text-ink-500">Submitted</dt><dd class="mt-1 font-medium text-ink-900">{{ $abstract->submitted_at->format('j M Y, H:i') }}</dd></div>
                    @endif
                    @if ($abstract->keywords)
                        <div><dt class="text-ink-500">Keywords</dt><dd class="mt-1 text-ink-900">{{ $abstract->keywords }}</dd></div>
                    @endif
                </dl>
            </x-card>

            <x-card title="Authors">
                <ol class="space-y-3 text-sm">
                    @foreach ($abstract->authors as $author)
                        <li>
                            <span class="font-medium text-ink-900">{{ $author->name }}</span>
                            @if ($author->is_presenter) <x-status tone="info" class="ml-1">Presenter</x-status> @endif
                            <span class="block text-xs text-ink-500">{{ $author->affiliation }}</span>
                        </li>
                    @endforeach
                </ol>
            </x-card>

            @if ($canWithdraw)
                <form method="POST" action="{{ route('abstracts.withdraw', $abstract) }}" onsubmit="return confirm('Withdraw this abstract? This cannot be undone.')">
                    @csrf
                    <x-button variant="secondary" class="w-full text-red-700" icon="x-circle">Withdraw abstract</x-button>
                </form>
            @endif
        </div>
    </div>

    @if ($showFeedback)
        <x-card title="Feedback from the reviewers" description="Reviewers are anonymous.">
            @foreach (array_filter([$abstract->decision_note, $abstract->revision_note]) as $note)
                <div class="mb-5 rounded-2xl bg-brand-50 p-4 text-sm text-brand-900">
                    <p class="font-semibold">From the scientific committee</p>
                    <p class="mt-1 whitespace-pre-line">{{ $note }}</p>
                </div>
            @endforeach
            <div class="space-y-6">
                @forelse ($feedback as $round => $reviews)
                    <div class="space-y-4">
                        @if ($feedback->count() > 1)
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-ink-500">{{ $round === 1 ? 'First review' : 'Review of your revised version' }}</p>
                        @endif
                        @foreach ($reviews as $review)
                            <div class="rounded-2xl border border-ink-100 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Reviewer {{ $loop->iteration }}</p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink-700">{{ $review->comments_for_author }}</p>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No written feedback was given.</p>
                @endforelse
            </div>
        </x-card>
    @endif
</x-layouts.portal>
