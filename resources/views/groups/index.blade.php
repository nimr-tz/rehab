<x-layouts.portal title="Group registration">
    <x-slot:header>
        <x-page-header eyebrow="Registration" title="Group registration"
            description="Register several colleagues and pay once for everyone: one invoice, one reference, the sum of each person's fee.">
            @if ($edition?->registration_open)
                <x-button :href="route('groups.create')" icon="user-plus">Register a group</x-button>
            @endif
        </x-page-header>
    </x-slot:header>

    <x-card :padding="false">
        @if ($groups->isEmpty())
            <x-empty icon="users" title="No groups yet">
                @if ($edition?->registration_open)
                    For a hospital, university or organisation sending several people. Each person gets their own badge and account; you pay for all of them at once.
                    <x-slot:action><x-button :href="route('groups.create')" icon="user-plus">Register a group</x-button></x-slot:action>
                @else
                    Registration is not open yet.
                @endif
            </x-empty>
        @else
            <ul class="divide-y divide-ink-100">
                @foreach ($groups as $group)
                    <li>
                        <a href="{{ route('groups.show', $group) }}" class="flex flex-wrap items-center gap-4 px-6 py-4 hover:bg-ink-50/60">
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-ink-900">{{ $group->name }}</span>
                                <span class="block text-xs text-ink-500"><span class="font-mono">{{ $group->reference }}</span> · {{ $group->members_count }} {{ \Illuminate\Support\Str::plural('person', $group->members_count) }} · {{ $group->formattedAmount() }}</span>
                            </span>
                            <x-status :tone="$group->status->tone()">{{ $group->status->label() }}</x-status>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-layouts.portal>
