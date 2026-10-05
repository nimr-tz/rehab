@php
    $maxCategory = max(1, $byCategory->max() ?? 1);
    $maxTopic = max(1, $byTopic->max('abstracts_count') ?? 1);
    $abstractTotal = $abstracts->except('draft')->sum();
@endphp

<x-layouts.portal title="Overview">
    <x-page-header eyebrow="Administration" :title="$summit->title()"
        :description="($summit->dateRange() ?? 'Dates to be announced').' · '.($summit->venueLine() ?? 'Venue to be announced')">
        <x-button variant="secondary" :href="route('admin.settings.edit')" icon="cog">Settings</x-button>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Registrations" :value="$registrationTotal" :hint="($registrations['confirmed'] ?? 0).' confirmed'" icon="ticket" :href="route('admin.participants.index')" />
        <x-stat label="Revenue verified" :value="'TZS '.number_format((float) ($revenue['TZS'] ?? 0))" :hint="'+ USD '.number_format((float) ($revenue['USD'] ?? 0))" icon="banknotes" tint="bg-emerald-50 text-emerald-700" :href="route('finance.payments.index', ['status' => 'verified'])" />
        <x-stat label="Abstracts submitted" :value="$abstractTotal" :hint="($abstracts['accepted'] ?? 0).' accepted'" icon="document" tint="bg-coral-100 text-coral-800" :href="route('scientific.abstracts.index')" />
        <x-stat label="Countries" :value="$countries" hint="represented" icon="globe" tint="bg-sun-100 text-sun-800" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Registrations by status">
            <div class="space-y-3">
                @foreach ($statuses as $status)
                    @php $n = $registrations[$status->value] ?? 0; @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-44 shrink-0"><x-status :tone="$status->tone()">{{ $status->label() }}</x-status></span>
                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full bg-brand-600" style="width: {{ $registrationTotal ? $n / $registrationTotal * 100 : 0 }}%"></div></div>
                        <span class="w-8 text-right text-sm font-semibold text-ink-900">{{ $n }}</span>
                    </div>
                @endforeach
            </div>
            @if ($pendingPayments)
                <x-alert tone="warning" class="mt-5">
                    <span class="font-semibold">{{ $pendingPayments }} {{ \Illuminate\Support\Str::plural('payment', $pendingPayments) }}</span> waiting for verification.
                    <a href="{{ route('finance.payments.index') }}" class="font-semibold underline">Review now</a>
                </x-alert>
            @endif
        </x-card>

        <x-card title="Registrations by category">
            <div class="space-y-3">
                @forelse ($byCategory as $name => $n)
                    <div>
                        <div class="flex justify-between text-sm"><span class="text-ink-700">{{ $name }}</span><span class="font-semibold text-ink-900">{{ $n }}</span></div>
                        <div class="mt-1 h-2.5 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full bg-ember-500" style="width: {{ $n / $maxCategory * 100 }}%"></div></div>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No registrations yet.</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="Abstracts by topic" :description="$reviewsDone.' of '.$reviewsTotal.' reviews completed'">
            <div class="space-y-3">
                @foreach ($byTopic as $topic)
                    <div>
                        <div class="flex justify-between gap-3 text-sm"><span class="truncate text-ink-700">{{ $topic->name }}</span><span class="font-semibold text-ink-900">{{ $topic->abstracts_count }}</span></div>
                        <div class="mt-1 h-2.5 overflow-hidden rounded-full bg-ink-100">
                            <div class="h-full rounded-full {{ ['bg-ember-500', 'bg-coral-400', 'bg-olive-700', 'bg-sun-400'][$topic->sort % 4] }}" style="width: {{ $topic->abstracts_count / $maxTopic * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Latest registrations" :padding="false">
            <x-slot:actions><x-button variant="ghost" size="sm" :href="route('admin.participants.index')">All participants</x-button></x-slot:actions>
            <ul class="divide-y divide-ink-100">
                @forelse ($recent as $registration)
                    <li>
                        <a href="{{ route('admin.participants.show', $registration) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-ink-50 sm:px-6">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-800">{{ $registration->user->initials() }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-ink-900">{{ $registration->user->name }}</span>
                                <span class="block text-xs text-ink-500">{{ $registration->category->name }} · {{ $registration->created_at->diffForHumans() }}</span>
                            </span>
                            <x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status>
                        </a>
                    </li>
                @empty
                    <li class="px-6 py-5 text-sm text-ink-500">No registrations yet.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-layouts.portal>
