@php
    use App\Enums\StudentProofStatus;
    $user = $registration->user;
@endphp

<x-layouts.portal :title="'Student proof '.$registration->reference">
    <x-slot:header>
        <x-page-header eyebrow="Finance · Student proofs" :title="$user->name" :back="route('finance.students.index')">
            <x-status :tone="$registration->student_status->tone()">{{ $registration->student_status->label() }}</x-status>
        </x-page-header>
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
        <x-card title="Proof of enrolment" :padding="false">
            <x-slot:actions>
                @if ($registration->student_proof_path)
                    <x-button variant="ghost" size="sm" :href="route('finance.students.proof', $registration)" icon="download" target="_blank">Open in new tab</x-button>
                @endif
            </x-slot:actions>
            @if (! $registration->student_proof_path)
                <x-empty icon="user" title="Seen at the registration desk">
                    {{ $registration->studentReviewer?->name ?? 'A desk officer' }} saw their student ID in person.
                </x-empty>
            @elseif ($proofIsImage)
                <div class="bg-ink-50 p-4"><img src="{{ route('finance.students.proof', $registration) }}" alt="Proof of enrolment" class="mx-auto max-h-[640px] rounded-xl shadow-soft"></div>
            @else
                <iframe src="{{ route('finance.students.proof', $registration) }}" title="Proof of enrolment" class="h-[640px] w-full rounded-b-card bg-ink-50"></iframe>
            @endif
        </x-card>

        <div class="space-y-6">
            <x-card title="Student">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Registration</dt><dd class="font-mono font-semibold text-ink-900">{{ $registration->reference }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Category</dt><dd class="text-right font-medium text-ink-900">{{ $registration->category->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Student rate</dt><dd class="font-bold text-ink-900">{{ $registration->formattedAmount() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Institution</dt><dd class="text-right text-ink-900">{{ $user->institution ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Profession</dt><dd class="text-right text-ink-900">{{ $user->profession ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Email</dt><dd class="break-all text-right text-ink-900">{{ $user->email }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Decision">
                @if ($registration->student_status === StudentProofStatus::Pending)
                    <p class="mb-4 text-sm text-ink-600">Check that the name matches and the enrolment is current. Once approved, the student can pay the student rate.</p>
                    <form method="POST" action="{{ route('finance.students.approve', $registration) }}">
                        @csrf
                        <x-button variant="success" class="w-full" icon="check">Approve the student rate</x-button>
                    </form>
                    <form method="POST" action="{{ route('finance.students.reject', $registration) }}" class="mt-5 space-y-3 border-t border-ink-100 pt-5" x-data="{ open: {{ $errors->has('student_rejection_reason') ? 'true' : 'false' }} }">
                        @csrf
                        <button type="button" x-show="! open" @click="open = true" class="w-full text-sm font-semibold text-red-700 hover:underline">Do not accept…</button>
                        <div x-show="open" x-cloak class="space-y-3">
                            <x-form.textarea name="student_rejection_reason" label="Reason (sent to the student)" rows="3" placeholder="e.g. The student ID expired in 2025. Please upload a current one." />
                            <x-button variant="danger" class="w-full" icon="x-circle">Do not accept</x-button>
                        </div>
                    </form>
                @else
                    <p class="text-sm text-ink-700">
                        {{ $registration->student_status->label() }}
                        @if ($registration->studentReviewer) by <span class="font-semibold">{{ $registration->studentReviewer->name }}</span>@endif
                        @if ($registration->student_reviewed_at) on {{ $registration->student_reviewed_at->format('j M Y, H:i') }}@endif.
                    </p>
                    @if ($registration->student_rejection_reason)
                        <p class="mt-2 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-800">{{ $registration->student_rejection_reason }}</p>
                    @endif
                @endif
            </x-card>
        </div>
    </div>
</x-layouts.portal>
