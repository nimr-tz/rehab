{{-- Move to another category before anything is paid. A student category needs proof of enrolment. --}}
<form method="POST" action="{{ route('registration.category') }}" enctype="multipart/form-data" class="space-y-4"
      x-data="{ student: @js((bool) $categories->firstWhere('id', (int) old('category'))?->is_student) }">
    @csrf
    @method('PUT')
    <div class="space-y-2">
        @foreach ($categories as $category)
            @continue($category->is($current))
            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-ink-200 p-3 text-sm has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                <span class="flex items-center gap-3">
                    <input type="radio" name="category" value="{{ $category->id }}" @checked(old('category') == $category->id) @change="student = @js((bool) $category->is_student)" required class="h-4 w-4 accent-brand-700">
                    <span class="font-semibold text-ink-900">{{ $category->name }}</span>
                </span>
                <span class="font-bold text-ink-900">{{ $category->formattedFee() }}</span>
            </label>
        @endforeach
    </div>
    @error('category') <p class="text-xs font-medium text-red-700">{{ $message }}</p> @enderror
    @if ($registration->student_status !== \App\Enums\StudentProofStatus::Approved && $categories->contains('is_student', true))
        <div x-show="student" x-cloak>@include('registration._student-proof-input')</div>
    @endif
    <x-button variant="secondary" size="sm">Change category</x-button>
</form>
