@php
    $editing = $category->exists;
    $kind = old('kind', $category->kind?->value ?? 'presentation');
@endphp

<x-layouts.portal :title="$editing ? 'Edit award' : 'New award'">
    <x-slot:header>
        <x-page-header eyebrow="Awards" :title="$editing ? 'Edit '.$category->name : 'New award'"
            :back="$editing ? route('committee.awards.show', $category) : route('committee.awards.index')"
            description="Presentation awards are judged from shortlisted abstracts. Honours go to people, nominated by participants or chosen by the committee." />
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <x-card>
            <form method="POST" action="{{ $editing ? route('committee.awards.update', $category) : route('committee.awards.store') }}"
                  x-data="{ kind: @js($kind) }" class="space-y-5">
                @csrf
                @if ($editing) @method('PUT') @endif

                <x-form.input name="name" label="Award name" :value="$category->name" required maxlength="120"
                    hint="For example: Best Poster, Student Research Award." />

                <x-form.select name="kind" label="Kind of award" x-model="kind" :value="$kind" :options="[
                    'presentation' => 'Presentation award: judges score shortlisted abstracts',
                    'honour' => 'Honour: for a person or organisation',
                ]" />

                <div x-show="kind === 'presentation'" class="space-y-5">
                    <x-form.select name="presentation_type" label="Which presentations" :value="$category->presentation_type?->value"
                        :options="['oral' => 'Oral presentations only', 'poster' => 'Posters only']" placeholder="Oral presentations and posters" />
                    <x-form.checkbox name="students_only" label="Students only" :checked="$category->students_only"
                        hint="Only abstracts submitted by someone registered in a student category." />
                </div>

                <div x-show="kind === 'honour'" x-cloak>
                    <x-form.input name="nominations_close_on" type="date" label="Nominations close (optional)" :value="$category->nominations_close_on?->toDateString()"
                        hint="Set a date to let participants nominate people. Leave it empty if the committee chooses the recipient itself." />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.select name="places" label="Number of places" :value="$category->places ?? 1"
                        :options="[1 => 'One winner', 2 => 'First and second', 3 => 'First, second and third']" />
                    <x-form.input name="prize" label="Prize (optional)" :value="$category->prize" maxlength="160" hint="For example: Trophy and certificate." />
                </div>

                <x-form.textarea name="description" label="Description" :value="$category->description" rows="3" maxlength="1000"
                    hint="Shown on the public awards page. Say what the award recognises." />

                <div class="flex flex-wrap gap-3 pt-2">
                    <x-button icon="check">{{ $editing ? 'Save award' : 'Create award' }}</x-button>
                    <x-button variant="secondary" :href="$editing ? route('committee.awards.show', $category) : route('committee.awards.index')">Cancel</x-button>
                </div>
            </form>
        </x-card>

        <div class="space-y-6">
            <x-card title="Judging criteria">
                <p class="text-sm text-ink-600">Judges score finalists of presentation awards on:</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach (\App\Support\AwardRubric::criteria() as $criterion)
                        <li class="flex gap-2.5"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" /><span><span class="font-semibold text-ink-900">{{ $criterion['label'] }}</span> <span class="text-ink-500">· {{ $criterion['hint'] }}</span></span></li>
                    @endforeach
                </ul>
            </x-card>

            @if ($editing)
                <x-card title="Delete award">
                    @error('category') <x-alert tone="danger" class="mb-4">{{ $message }}</x-alert> @enderror
                    <p class="text-sm text-ink-600">Deleting the award also deletes its finalists, nominations and judges' scores.</p>
                    <form method="POST" action="{{ route('committee.awards.destroy', $category) }}" class="mt-4"
                          x-data x-on:submit="if (! confirm('Delete this award with its finalists and scores? This cannot be undone.')) $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <x-button variant="danger" size="sm" icon="trash">Delete award</x-button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.portal>
