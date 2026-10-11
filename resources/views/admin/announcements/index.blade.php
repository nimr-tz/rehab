<x-layouts.portal title="Announcements">
    <x-slot:header>
        <x-page-header eyebrow="Administration" title="Announcements" description="Email a group of people: everyone registered, the unpaid, presenters, reviewers and more. People with an account also see it under the bell.">
            <x-button :href="route('admin.announcements.create')" icon="plus">New announcement</x-button>
        </x-page-header>
    </x-slot:header>

    <x-card :padding="false">
        @if ($announcements->isEmpty())
            <x-empty icon="mail" title="No announcements yet">
                Write one to tell people that registration is open, remind them of a deadline, or share the venue details.
                <x-slot:action><x-button :href="route('admin.announcements.create')" icon="plus">New announcement</x-button></x-slot:action>
            </x-empty>
        @else
            <ul class="divide-y divide-ink-100">
                @foreach ($announcements as $announcement)
                    <li>
                        <a href="{{ route('admin.announcements.show', $announcement) }}" class="flex flex-wrap items-center gap-4 px-6 py-4 hover:bg-ink-50/60">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-ink-900">{{ $announcement->subject }}</span>
                                <span class="block text-xs text-ink-500">{{ $announcement->audience->label() }} · {{ $announcement->author?->name }} · {{ ($announcement->queued_at ?? $announcement->created_at)->format('j M Y, H:i') }}</span>
                            </span>
                            @if ($announcement->isDraft())
                                <x-status tone="neutral">Draft</x-status>
                            @elseif ($announcement->isSending())
                                <x-status tone="warning">Sending · {{ $announcement->sent_count }} of {{ $announcement->recipients_count }}</x-status>
                            @else
                                <x-status :tone="$announcement->failed_count ? 'warning' : 'success'">Sent to {{ $announcement->sent_count }}{{ $announcement->failed_count ? ' · '.$announcement->failed_count.' failed' : '' }}</x-status>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-layouts.portal>
