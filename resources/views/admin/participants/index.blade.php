<x-layouts.portal title="Participants">
    <x-slot:header>
        <x-page-header eyebrow="Administration" title="Participants" :description="$registrations->total().' registrations for the '.$summit->title()" />
    </x-slot:header>

    <x-card :padding="false">
        <form method="GET" class="flex flex-col gap-3 border-b border-ink-100 p-4 lg:flex-row">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
                <input name="q" value="{{ $filters['search'] }}" placeholder="Name, email, institution or reference" class="field h-11 pl-12">
            </div>
            <select name="status" class="field h-11 lg:w-56" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select name="category" class="field h-11 lg:w-64" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category'] == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <x-button variant="secondary" icon="search">Search</x-button>
        </form>

        @if ($registrations->isEmpty())
            <x-empty icon="users" title="No participants match">Try another search or filter.</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr><th class="px-5 py-3">Participant</th><th class="px-5 py-3">Institution</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Registered</th><th class="px-5 py-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($registrations as $registration)
                            <tr class="hover:bg-ink-50/60">
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('admin.participants.show', $registration) }}" class="font-medium text-ink-900 hover:text-brand-700">{{ $registration->user->name }}</a>
                                    <p class="text-xs text-ink-500">{{ $registration->user->email }} · {{ $registration->user->countryName() }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-ink-700">{{ $registration->user->institution ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-ink-700">{{ $registration->category->name }}</td>
                                <td class="px-5 py-3.5 font-mono text-ink-700">{{ $registration->reference }}</td>
                                <td class="px-5 py-3.5 text-ink-600">{{ $registration->created_at->format('j M Y') }}</td>
                                <td class="px-5 py-3.5"><x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-ink-100 px-5 py-3">{{ $registrations->links() }}</div>
        @endif
    </x-card>
</x-layouts.portal>
