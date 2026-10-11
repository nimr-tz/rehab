<x-layouts.portal :title="$announcement->exists ? 'Edit announcement' : 'New announcement'">
    <x-slot:header>
        <x-page-header eyebrow="Announcements" :title="$announcement->exists ? 'Edit announcement' : 'New announcement'" :back="$announcement->exists ? route('admin.announcements.show', $announcement) : route('admin.announcements.index')" />
    </x-slot:header>

    <form method="POST" action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" class="grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        @csrf
        @if ($announcement->exists) @method('PUT') @endif

        <x-card title="Message" description="Each email starts with “Hello” and the person's first name, and ends with a button to the portal.">
            <div class="space-y-5">
                <x-form.input name="subject" label="Subject" :value="$announcement->subject" maxlength="150" required />
                <x-form.textarea name="body" label="Message" rows="14" :value="$announcement->body" required
                    hint="Plain text. Leave a blank line between paragraphs." />
            </div>
        </x-card>

        <div class="space-y-6">
            <x-card title="Who gets it" description="Counted now; fixed when you send.">
                <div class="space-y-2">
                    @foreach ($audiences as ['audience' => $audience, 'count' => $count])
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-ink-200 p-3 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                            <input type="radio" name="audience" value="{{ $audience->value }}" @checked(old('audience', $announcement->audience?->value) === $audience->value) class="mt-1 h-4 w-4 accent-brand-700">
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="font-semibold text-ink-900">{{ $audience->label() }}</span>
                                    <span class="text-sm font-bold text-brand-700">{{ number_format($count) }}</span>
                                </span>
                                <span class="block text-xs text-ink-500">{{ $audience->hint() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('audience') <p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
            </x-card>

            <x-button size="lg" icon="check" class="w-full">Save the draft</x-button>
        </div>
    </form>
</x-layouts.portal>
