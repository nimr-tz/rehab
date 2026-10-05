<x-layouts.portal title="Reviewers">
    <x-slot:header>
        <x-page-header eyebrow="Scientific committee" title="Reviewers"
            description="Workload across the review panel. Admins add reviewers under Users & roles.">
            @role('admin')
                <x-button variant="secondary" :href="route('admin.users.index', ['role' => 'reviewer'])" icon="shield">Manage reviewers</x-button>
            @endrole
        </x-page-header>
    </x-slot:header>

    <x-card :padding="false">
        @if ($reviewers->isEmpty())
            <x-empty icon="users" title="No reviewers yet">Give the reviewer role to committee members under Users & roles.</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr><th class="px-5 py-3">Reviewer</th><th class="px-5 py-3">Institution</th><th class="px-5 py-3">Assigned</th><th class="px-5 py-3">Completed</th><th class="px-5 py-3 w-56">Progress</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($reviewers as $reviewer)
                            @php $pct = $reviewer->assigned_count ? round($reviewer->completed_count / $reviewer->assigned_count * 100) : 0; @endphp
                            <tr>
                                <td class="px-5 py-3.5"><p class="font-medium text-ink-900">{{ $reviewer->name }}</p><p class="text-xs text-ink-500">{{ $reviewer->email }}</p></td>
                                <td class="px-5 py-3.5 text-ink-700">{{ $reviewer->institution ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-ink-900">{{ $reviewer->assigned_count }}</td>
                                <td class="px-5 py-3.5 text-ink-900">{{ $reviewer->completed_count }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full bg-brand-600" style="width: {{ $pct }}%"></div></div>
                                        <span class="w-10 text-right text-xs font-semibold text-ink-600">{{ $pct }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layouts.portal>
