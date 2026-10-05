<x-layouts.portal title="Check-in">
    <x-page-header eyebrow="Registration desk" title="Check-in"
        description="Scan a badge or search by name, email or reference. Only confirmed participants can be checked in." />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-stat label="Confirmed participants" :value="$confirmed" icon="users" />
        <x-stat label="Checked in" :value="$checkedIn" :hint="$confirmed ? round($checkedIn / max($confirmed, 1) * 100).'% of confirmed' : null" icon="check-circle" tint="bg-emerald-50 text-emerald-700" />
    </div>

    @error('check_in') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

    <x-card>
        <form method="GET" class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
                <input name="q" value="{{ $search }}" autofocus placeholder="Name, email, RH27-000123 or scanned QR code" class="field h-12 pl-12 text-base">
            </div>
            <x-button size="lg" icon="search">Find</x-button>
        </form>
    </x-card>

    @if ($search !== '')
        <x-card :title="$results->count().' '.\Illuminate\Support\Str::plural('result', $results->count())" :padding="false">
            @forelse ($results as $registration)
                <div class="flex flex-col gap-3 border-b border-ink-100 px-5 py-4 last:border-0 sm:flex-row sm:items-center sm:px-6">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-100 font-bold text-brand-800">{{ $registration->user->initials() }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-ink-900">{{ $registration->user->name }}</p>
                        <p class="text-sm text-ink-500">{{ $registration->reference }} · {{ $registration->category->name }} · {{ $registration->user->institution }}</p>
                    </div>
                    @if ($registration->checked_in_at)
                        <x-status tone="success">Checked in {{ $registration->checked_in_at->format('D H:i') }}</x-status>
                    @else
                        <x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status>
                    @endif
                    <div class="flex gap-2">
                        @if ($registration->isConfirmed())
                            <x-button variant="secondary" size="sm" :href="route('desk.badge', $registration)" icon="download">Badge</x-button>
                            @unless ($registration->checked_in_at)
                                <form method="POST" action="{{ route('desk.check-in', $registration) }}">
                                    @csrf
                                    <x-button variant="success" size="sm" icon="check">Check in</x-button>
                                </form>
                            @endunless
                        @endif
                    </div>
                </div>
            @empty
                <x-empty icon="search" title="No one found">Check the spelling, or search by email or reference.</x-empty>
            @endforelse
        </x-card>
    @endif
</x-layouts.portal>
