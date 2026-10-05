<x-layouts.portal title="Print queue">
    <x-slot:header>
        <x-page-header eyebrow="Registration desk" title="Print queue" :back="route('desk.index')"
            :description="$unprinted->count().' confirmed '.\Illuminate\Support\Str::plural('badge', $unprinted->count()).' not yet printed'" />
    </x-slot:header>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <x-card :padding="false">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 pb-4 pt-6">
                <div>
                    <h2 class="text-lg font-bold text-ink-900">Waiting to print</h2>
                    <p class="text-sm text-ink-500">Badges print one per A6 page, in batches of {{ $batchSize }}. A printed batch is marked as printed.</p>
                </div>
                @if ($unprinted->isNotEmpty())
                    <form method="POST" action="{{ route('desk.print-batch') }}">
                        @csrf
                        <x-button icon="printer">Print next {{ min($batchSize, $unprinted->count()) }}</x-button>
                    </form>
                @endif
            </div>
            @if ($unprinted->isEmpty())
                <x-empty icon="check-circle" title="Everything is printed" class="!py-10">New badges appear here as payments are verified.</x-empty>
            @else
                <ul class="divide-y divide-ink-100 border-t border-ink-100">
                    @foreach ($unprinted->take(25) as $registration)
                        <li class="flex items-center gap-4 px-6 py-3">
                            <span class="w-6 text-xs font-bold text-ink-400">{{ $loop->iteration }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-ink-900">{{ $registration->displayName() }}</span>
                                <span class="block truncate text-xs text-ink-500">{{ $registration->category->name }} · {{ $registration->user->institution }}</span>
                            </span>
                            <span class="font-mono text-xs font-bold text-brand-700">{{ $registration->reference }}</span>
                            <x-button variant="ghost" size="sm" :href="route('desk.badge', $registration)" icon="printer">Print</x-button>
                        </li>
                    @endforeach
                </ul>
                @if ($unprinted->count() > 25)
                    <p class="border-t border-ink-100 px-6 py-3 text-sm text-ink-500">and {{ $unprinted->count() - 25 }} more in the queue.</p>
                @endif
            @endif
        </x-card>

        <x-card title="Recently printed" :padding="false">
            <ul class="divide-y divide-ink-100">
                @forelse ($printed as $registration)
                    <li class="flex items-center justify-between gap-3 px-6 py-3">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-ink-900">{{ $registration->displayName() }}</span>
                            <span class="block text-xs text-ink-500">{{ $registration->badge_printed_at->diffForHumans() }}</span>
                        </span>
                        <x-button variant="ghost" size="sm" :href="route('desk.badge', $registration)">Reprint</x-button>
                    </li>
                @empty
                    <li class="px-6 py-6 text-sm text-ink-500">No badges printed yet.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-layouts.portal>
