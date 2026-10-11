@php
    use App\Enums\StudentProofStatus;
    $tabs = [StudentProofStatus::Pending->value => 'To check', StudentProofStatus::Approved->value => 'Approved', StudentProofStatus::Rejected->value => 'Not accepted'];
@endphp

<x-layouts.portal title="Student proofs">
    <x-slot:header>
        <x-page-header eyebrow="Finance" title="Student proofs"
            description="Students upload proof of enrolment when they register. They can pay the student rate only once you approve it." />
    </x-slot:header>

    <x-card :padding="false">
        <div class="flex flex-wrap gap-2 border-b border-ink-100 p-4">
            @foreach ($tabs as $value => $label)
                <a href="{{ route('finance.students.index', ['status' => $value]) }}"
                   @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-brand-700 text-white' => $status->value === $value, 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50' => $status->value !== $value])>
                    {{ $label }} <span class="ml-1 opacity-70">{{ $counts[$value] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        @if ($registrations->isEmpty())
            <x-empty icon="check-circle" :title="$status === StudentProofStatus::Pending ? 'Nothing to check' : 'None here'">
                {{ $status === StudentProofStatus::Pending ? 'Every student proof has been checked.' : 'Try another tab.' }}
            </x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <tr><th class="px-5 py-3">Student</th><th class="px-5 py-3">Registration</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">{{ $status === StudentProofStatus::Pending ? 'Uploaded' : 'Checked' }}</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($registrations as $registration)
                            <tr class="hover:bg-ink-50/60">
                                <td class="px-5 py-3.5"><p class="font-medium text-ink-900">{{ $registration->user->name }}</p><p class="text-xs text-ink-500">{{ $registration->user->institution }}</p></td>
                                <td class="px-5 py-3.5 font-mono text-ink-700">{{ $registration->reference }}</td>
                                <td class="px-5 py-3.5 text-ink-700">{{ $registration->category->name }} · {{ $registration->formattedAmount() }}</td>
                                <td class="px-5 py-3.5 text-ink-600">
                                    @if ($status === StudentProofStatus::Pending)
                                        {{ $registration->updated_at->format('j M Y') }}
                                    @else
                                        {{ $registration->student_reviewed_at?->format('j M Y') }}{{ $registration->studentReviewer ? ' · '.$registration->studentReviewer->name : '' }}
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <x-button :variant="$status === StudentProofStatus::Pending ? 'primary' : 'ghost'" size="sm" :href="route('finance.students.show', $registration)">
                                        {{ $status === StudentProofStatus::Pending ? 'Check' : 'Open' }}
                                    </x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-ink-100 px-5 py-3">{{ $registrations->links() }}</div>
        @endif
    </x-card>
</x-layouts.portal>
