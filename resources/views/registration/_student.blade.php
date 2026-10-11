{{-- A student rate waits for finance to approve the proof of enrolment before anything can be paid. --}}
@php use App\Enums\StudentProofStatus; @endphp

@if ($registration->student_status === StudentProofStatus::Pending)
    <x-card>
        <x-empty icon="clock" title="We are checking your student proof">
            Finance checks proofs of enrolment, usually within 2 working days, and we email you when it is done.
            You can then pay the student rate of {{ $registration->formattedDue() }}.
        </x-empty>
    </x-card>
@else
    <x-card title="Your student proof was not accepted">
        <p class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{{ $registration->student_rejection_reason }}</p>

        <form method="POST" action="{{ route('registration.student-proof') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf
            @include('registration._student-proof-input', ['required' => true])
            <x-button icon="upload">Send the new document</x-button>
        </form>

        @if ($categories->reject->is_student->isNotEmpty())
            <div class="mt-6 border-t border-ink-100 pt-5">
                <p class="mb-3 text-sm font-semibold text-ink-900">Or change to another category</p>
                @include('registration._category', ['current' => $registration->category, 'categories' => $categories->reject->is_student])
            </div>
        @endif
    </x-card>
@endif
