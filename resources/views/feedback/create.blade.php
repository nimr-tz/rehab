<x-layouts.portal title="Feedback">
    <x-slot:header>
        <x-page-header :eyebrow="$summit->title()" title="Tell us how it went"
            description="A few minutes, and it shapes the next summit. Your answers are anonymous: the organisers see them without your name." />
    </x-slot:header>

    @if ($answered)
        <x-card><x-empty icon="check-circle" title="Thank you for your feedback">Your answers have been recorded.</x-empty></x-card>
    @elseif (! $canRespond)
        <x-card>
            <x-empty icon="clipboard" title="The survey is not open to you">
                {{ $open ? 'It is for everyone recorded at the summit: checked in at the desk or scanned at a session.' : 'It opens on the summit\'s last day, for everyone who attended.' }}
            </x-empty>
        </x-card>
    @else
        <form method="POST" action="{{ route('feedback.store') }}" class="space-y-6">
            @csrf

            <x-card title="How would you rate…" description="1 is poor, 5 is excellent.">
                <div class="space-y-5">
                    @foreach (config('feedback.ratings') as $key => $label)
                        <fieldset class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <legend class="text-sm font-semibold text-ink-900 sm:float-left">{{ $label }}</legend>
                            <div class="flex gap-2">
                                @foreach (range(1, 5) as $n)
                                    <label class="grid h-11 w-11 cursor-pointer place-items-center rounded-xl border border-ink-200 text-sm font-bold text-ink-700 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-700 has-[:checked]:text-white">
                                        <input type="radio" name="ratings[{{ $key }}]" value="{{ $n }}" @checked((int) old("ratings.{$key}") === $n) class="sr-only" required>
                                        {{ $n }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        @error("ratings.{$key}") <p class="-mt-3 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                    @endforeach
                </div>
            </x-card>

            <x-card title="How likely are you to recommend the summit to a colleague?" description="0 is not at all, 10 is extremely likely.">
                <div class="flex flex-wrap gap-2">
                    @foreach (range(0, 10) as $n)
                        <label class="grid h-11 w-11 cursor-pointer place-items-center rounded-xl border border-ink-200 text-sm font-bold text-ink-700 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-700 has-[:checked]:text-white">
                            <input type="radio" name="recommend" value="{{ $n }}" @checked(old('recommend') !== null && (int) old('recommend') === $n) class="sr-only" required>
                            {{ $n }}
                        </label>
                    @endforeach
                </div>
                @error('recommend') <p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p> @enderror

                <fieldset class="mt-6">
                    <legend class="label">Do you plan to come to the next summit?</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach (config('feedback.next_year') as $value => $label)
                            <label class="cursor-pointer rounded-full border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-800">
                                <input type="radio" name="next_year" value="{{ $value }}" @checked(old('next_year') === $value) class="sr-only" required>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('next_year') <p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                </fieldset>
            </x-card>

            <x-card title="In your own words (optional)">
                <div class="space-y-5">
                    @foreach (config('feedback.comments') as $key => $question)
                        <x-form.textarea :name="'comments['.$key.']'" :id="'comments-'.$key" :label="$question" rows="3" :value="old('comments.'.$key)" />
                    @endforeach
                </div>
            </x-card>

            <x-button size="lg" icon="check">Send my feedback</x-button>
        </form>
    @endif
</x-layouts.portal>
