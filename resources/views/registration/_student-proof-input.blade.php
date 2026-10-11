{{-- Proof of enrolment for a student rate. --}}
<div>
    <label for="student_proof" class="label">Proof of enrolment</label>
    <input id="student_proof" type="file" name="student_proof" accept=".pdf,.jpg,.jpeg,.png" @if ($required ?? false) required @endif
           class="block w-full rounded-control border border-dashed border-ink-300 bg-canvas px-4 py-4 text-sm text-ink-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-brand-400">
    <p class="mt-1.5 text-xs text-ink-500">Your current student ID, or a letter from your institution. PDF, JPG or PNG, up to 5 MB. Finance checks it before you pay.</p>
    @error('student_proof') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
</div>
