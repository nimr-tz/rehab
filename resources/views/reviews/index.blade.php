<x-layouts.portal title="My reviews">
    <x-page-header eyebrow="Reviewing" title="My reviews"
        description="Reviews are double-blind: you see the abstract, never the authors. Score each one and recommend a decision." />

    <x-card title="Waiting for you" :padding="false">
        @if ($pending->isEmpty())
            <x-empty icon="check-circle" title="You are up to date">Thank you. New assignments will appear here and arrive by email.</x-empty>
        @else
            <ul class="divide-y divide-ink-100">
                @foreach ($pending as $assignment)
                    <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-xs font-semibold text-ink-500">{{ $assignment->abstract->blindId() }}</p>
                            <p class="font-semibold text-ink-900">{{ $assignment->abstract->title }}</p>
                            <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $assignment->abstract->topic->chipClasses() }}">{{ $assignment->abstract->topic->name }}</span>
                        </div>
                        @if ($assignment->due_on)
                            <x-status :tone="$assignment->due_on->isPast() ? 'danger' : 'warning'">Due {{ $assignment->due_on->format('j M') }}</x-status>
                        @endif
                        <x-button size="sm" :href="route('reviews.edit', $assignment)">Review</x-button>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    @if ($completed->isNotEmpty())
        <x-card title="Completed" :padding="false">
            <ul class="divide-y divide-ink-100">
                @foreach ($completed as $assignment)
                    <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-xs font-semibold text-ink-500">{{ $assignment->abstract->blindId() }}</p>
                            <p class="font-medium text-ink-900">{{ $assignment->abstract->title }}</p>
                        </div>
                        <span class="text-sm font-semibold text-ink-700">{{ $assignment->totalScore() }}/20</span>
                        <x-status :tone="$assignment->recommendation->tone()">{{ $assignment->recommendation->label() }}</x-status>
                        <x-button variant="ghost" size="sm" :href="route('reviews.edit', $assignment)">View</x-button>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-layouts.portal>
