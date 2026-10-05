@php
    use App\Models\ReviewAssignment;

    $abstract = $assignment->abstract;
    $locked = in_array($abstract->status->value, ['accepted', 'rejected', 'withdrawn'], true);
@endphp

<x-layouts.portal :title="'Review '.$abstract->blindId()">
    <x-slot:header>
        <x-page-header :eyebrow="'Review '.$abstract->blindId()" :title="$abstract->title" :back="route('reviews.index')">
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $abstract->topic->chipClasses() }}">{{ $abstract->topic->name }}</span>
        </x-page-header>
    </x-slot:header>

    <x-alert>
        <span class="font-semibold">Double-blind review.</span> The authors' names and affiliations are hidden from you, and yours from them.
        Comments for the author are shared anonymously after the decision.
    </x-alert>

    <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
        <x-card>
            <div class="space-y-6 text-[15px] leading-relaxed text-ink-700">
                @foreach (['background' => 'Background', 'methods' => 'Methods', 'results' => 'Results', 'conclusions' => 'Conclusions'] as $field => $label)
                    <section>
                        <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-700">{{ $label }}</h2>
                        <p class="mt-1.5 whitespace-pre-line">{{ $abstract->{$field} }}</p>
                    </section>
                @endforeach
                <p class="text-sm text-ink-500">{{ $abstract->wordCount() }} words · preferred: {{ strtolower($abstract->preferred_type->label()) }}@if ($abstract->keywords) · {{ $abstract->keywords }}@endif</p>
            </div>
        </x-card>

        <form method="POST" action="{{ route('reviews.update', $assignment) }}" class="space-y-5 self-start rounded-card border border-ink-100 bg-white p-6 shadow-soft lg:sticky lg:top-6">
            @csrf
            @method('PUT')
            <h2 class="font-semibold text-ink-900">Your scores <span class="font-normal text-ink-500">(1 = poor, 5 = excellent)</span></h2>

            @foreach (ReviewAssignment::CRITERIA as $field => $label)
                <fieldset>
                    <legend class="mb-1.5 text-sm font-medium text-ink-800">{{ $label }}</legend>
                    <div class="grid grid-cols-5 gap-1.5">
                        @foreach (range(1, 5) as $score)
                            <label class="cursor-pointer">
                                <input type="radio" name="{{ $field }}" value="{{ $score }}" class="peer sr-only" @checked(old($field, $assignment->{$field}) == $score) @disabled($locked) required>
                                <span class="grid h-10 place-items-center rounded-xl border border-ink-200 text-sm font-semibold text-ink-600 transition peer-checked:border-brand-700 peer-checked:bg-brand-700 peer-checked:text-white peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/20 hover:border-brand-300">{{ $score }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error($field) <p class="mt-1 text-xs font-medium text-red-700">Choose a score.</p> @enderror
                </fieldset>
            @endforeach

            <x-form.select name="recommendation" label="Recommendation" placeholder="Select" required :disabled="$locked"
                :options="collect($recommendations)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()"
                :value="$assignment->recommendation?->value" />

            <x-form.textarea name="comments_for_author" label="Comments for the author" rows="5" required :disabled="$locked"
                :value="$assignment->comments_for_author" hint="Shared anonymously. Be specific and constructive." />

            <x-form.textarea name="comments_for_committee" label="Confidential note to the committee (optional)" rows="3" :disabled="$locked"
                :value="$assignment->comments_for_committee" />

            @if ($locked)
                <p class="text-sm text-ink-500">A decision has been made on this abstract, so the review is closed.</p>
            @else
                <x-button class="w-full" icon="check">{{ $assignment->isComplete() ? 'Update review' : 'Submit review' }}</x-button>
            @endif
        </form>
    </div>
</x-layouts.portal>
