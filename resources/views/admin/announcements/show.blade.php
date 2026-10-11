<x-layouts.portal :title="$announcement->subject">
    <x-slot:header>
        <x-page-header eyebrow="Announcements" :title="$announcement->subject" :back="route('admin.announcements.index')">
            @if ($announcement->isDraft())
                <x-status tone="neutral">Draft</x-status>
                <x-button variant="secondary" size="sm" :href="route('admin.announcements.edit', $announcement)" icon="pencil">Edit</x-button>
            @elseif ($announcement->isSending())
                <x-status tone="warning">Sending</x-status>
            @else
                <x-status tone="success">Sent {{ $announcement->finished_at->format('j M Y, H:i') }}</x-status>
            @endif
        </x-page-header>
    </x-slot:header>

    @if ($announcement->isSending())
        {{-- Each visit lets the next batch go out; refreshing shows progress. --}}
        <div x-data x-init="setTimeout(() => window.location.reload(), 30000)"></div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <x-card title="The email">
            <p class="text-sm text-ink-500">To: {{ $announcement->audience->label() }}</p>
            <div class="mt-4 space-y-3 rounded-2xl bg-canvas p-5 text-[15px] leading-relaxed text-ink-800">
                <p>Hello <span class="text-ink-500">[first name]</span>,</p>
                @foreach ($announcement->paragraphs() as $paragraph)
                    <p class="whitespace-pre-line">{{ $paragraph }}</p>
                @endforeach
                <p><span class="inline-block rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Open the portal</span></p>
            </div>
        </x-card>

        <div class="space-y-6">
            @if ($announcement->isDraft())
                <x-card title="Send">
                    <p class="text-sm text-ink-700">This goes to <span class="font-bold text-ink-900">{{ number_format($reach) }} {{ \Illuminate\Support\Str::plural('person', $reach) }}</span>: {{ strtolower($announcement->audience->label()) }}.</p>
                    <form method="POST" action="{{ route('admin.announcements.test', $announcement) }}" class="mt-4">
                        @csrf
                        <x-button variant="secondary" class="w-full" icon="mail">Send me a test</x-button>
                    </form>
                    <form method="POST" action="{{ route('admin.announcements.send', $announcement) }}" class="mt-5 space-y-3 border-t border-ink-100 pt-5">
                        @csrf
                        <x-form.checkbox name="confirm" label="Send it now" hint="Once sent, it cannot be changed or recalled." />
                        @error('confirm') <p class="text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                        <x-button class="w-full" icon="arrow-right" :disabled="$reach === 0">Send to {{ number_format($reach) }}</x-button>
                    </form>
                    <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" class="mt-4 text-center" x-data @submit="if (! confirm('Delete this draft?')) $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <button class="text-sm font-semibold text-red-700 hover:underline">Delete the draft</button>
                    </form>
                </x-card>
            @else
                <x-card title="Delivery">
                    <p class="text-3xl font-extrabold text-ink-900">{{ number_format($announcement->sent_count) }} <span class="text-base font-semibold text-ink-500">of {{ number_format($announcement->recipients_count) }} sent</span></p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-ink-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $announcement->recipients_count ? round($announcement->sent_count / $announcement->recipients_count * 100) : 0 }}%"></div>
                    </div>
                    @if ($announcement->isSending())
                        <p class="mt-3 text-sm text-ink-600">Emails go out in batches of 50 every few minutes. This page refreshes itself.</p>
                    @endif
                    <p class="mt-3 text-xs text-ink-500">Sent by {{ $announcement->author?->name }}, {{ $announcement->queued_at->format('j M Y, H:i') }}.</p>

                    @if ($announcement->failed_count)
                        <div class="mt-5 border-t border-ink-100 pt-4">
                            <p class="text-sm font-semibold text-red-700">{{ $announcement->failed_count }} could not be sent</p>
                            <ul class="mt-2 space-y-1 text-xs text-ink-600">
                                @foreach ($failures as $failure)
                                    <li><span class="font-medium text-ink-900">{{ $failure->email }}</span> · {{ $failure->error }}</li>
                                @endforeach
                            </ul>
                            <form method="POST" action="{{ route('admin.announcements.retry', $announcement) }}" class="mt-3">
                                @csrf
                                <x-button variant="secondary" size="sm">Try them again</x-button>
                            </form>
                        </div>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.portal>
