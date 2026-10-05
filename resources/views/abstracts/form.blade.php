@php
    use App\Enums\PresentationType;
    use App\Models\AbstractSubmission;

    $authors = old('authors', $authors);
    $presenter = (int) old('presenter', $presenter);
    $sections = [
        'background' => ['Background', 'Why does this work matter? What problem does it address?'],
        'methods' => ['Methods', 'Design, setting, participants and how you collected and analysed data.'],
        'results' => ['Results', 'Your main findings, with numbers where you have them.'],
        'conclusions' => ['Conclusions', 'What it means for rehabilitation practice or policy.'],
    ];
@endphp

<x-layouts.portal :title="$abstract ? 'Edit abstract' : 'New abstract'">
    <x-slot:header>
        <x-page-header :eyebrow="$summit->title()" :title="$abstract ? 'Edit abstract' : 'Submit an abstract'"
            :back="$abstract ? route('abstracts.show', $abstract) : route('abstracts.index')"
            :description="'Up to '.AbstractSubmission::WORD_LIMIT.' words across the four sections. Do not put author names in the text: review is double-blind.'" />
    </x-slot:header>

    @if ($errors->any())
        <x-alert tone="danger">Please check the highlighted fields.</x-alert>
    @endif

    <form method="POST" action="{{ $abstract ? route('abstracts.update', $abstract) : route('abstracts.store') }}" class="space-y-6"
          x-data="{
              authors: @js(array_values($authors)),
              presenter: {{ $presenter }},
              text: { background: @js(old('background', $abstract?->background ?? '')), methods: @js(old('methods', $abstract?->methods ?? '')), results: @js(old('results', $abstract?->results ?? '')), conclusions: @js(old('conclusions', $abstract?->conclusions ?? '')) },
              get words() { return Object.values(this.text).join(' ').trim().split(/\s+/).filter(Boolean).length },
              add() { this.authors.push({ name: '', email: '', affiliation: '' }) },
              remove(i) { this.authors.splice(i, 1); if (this.presenter >= this.authors.length) this.presenter = 0 },
          }">
        @csrf
        @if ($abstract) @method('PUT') @endif

        <x-card title="About the abstract">
            <div class="space-y-5">
                <x-form.input name="title" label="Title" :value="$abstract?->title" maxlength="200" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.select name="topic_id" label="Topic" placeholder="Select a topic" required
                        :options="$edition->topics->pluck('name', 'id')->all()" :value="$abstract?->topic_id" />
                    <x-form.select name="preferred_type" label="Preferred presentation" required
                        :options="collect(PresentationType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()"
                        :value="$abstract?->preferred_type?->value ?? 'either'" />
                </div>
                <x-form.input name="keywords" label="Keywords (optional)" :value="$abstract?->keywords" placeholder="e.g. stroke, community rehabilitation, Tanzania" />
            </div>
        </x-card>

        <x-card title="Abstract">
            <x-slot:actions>
                <span class="rounded-full px-3 py-1 text-sm font-semibold"
                      :class="words > {{ AbstractSubmission::WORD_LIMIT }} ? 'bg-red-50 text-red-700' : 'bg-brand-50 text-brand-800'">
                    <span x-text="words"></span> / {{ AbstractSubmission::WORD_LIMIT }} words
                </span>
            </x-slot:actions>
            <div class="space-y-5">
                @foreach ($sections as $field => [$label, $hint])
                    <x-form.textarea :name="$field" :label="$label" :hint="$hint" rows="4" x-model="text.{{ $field }}" />
                @endforeach
            </div>
        </x-card>

        <x-card title="Authors" description="List every author in order. Choose who will present.">
            <div class="space-y-3">
                <template x-for="(author, i) in authors" :key="i">
                    <div class="grid gap-3 rounded-2xl border border-ink-100 bg-canvas p-4 sm:grid-cols-[auto_1fr_1fr_1fr_auto] sm:items-end">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-white text-sm font-bold text-ink-600 sm:mb-2" x-text="i + 1"></span>
                        <div><label class="label">Name</label><input class="field" :name="`authors[${i}][name]`" x-model="author.name" required></div>
                        <div><label class="label">Affiliation</label><input class="field" :name="`authors[${i}][affiliation]`" x-model="author.affiliation" required></div>
                        <div><label class="label">Email (optional)</label><input class="field" type="email" :name="`authors[${i}][email]`" x-model="author.email"></div>
                        <div class="flex items-center gap-3 sm:mb-2.5">
                            <label class="flex items-center gap-1.5 text-xs font-semibold text-ink-600">
                                <input type="radio" name="presenter" :value="i" x-model.number="presenter" class="h-4 w-4 accent-brand-700"> Presenter
                            </label>
                            <button type="button" @click="remove(i)" x-show="authors.length > 1" class="grid h-8 w-8 place-items-center rounded-lg text-ink-400 hover:bg-red-50 hover:text-red-700" aria-label="Remove author">
                                <x-icon name="trash" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            @error('authors') <p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
            @foreach ($errors->get('authors.*') as $messages)
                <p class="mt-2 text-xs font-medium text-red-700">{{ $messages[0] }}</p>
                @break
            @endforeach
            <x-button type="button" variant="secondary" size="sm" icon="plus" class="mt-4" x-on:click="add()">Add author</x-button>
        </x-card>

        <div class="flex flex-col-reverse gap-3 rounded-card border border-ink-100 bg-white p-5 shadow-soft sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-ink-500">Drafts can be incomplete. Submitting sends the abstract to the scientific committee.</p>
            <div class="flex gap-2">
                @if (! $abstract || $abstract->status->value === 'draft')
                    <x-button variant="secondary" name="action" value="draft" formnovalidate>Save draft</x-button>
                @endif
                <x-button name="action" value="submit" icon="check">{{ $abstract && $abstract->status->value === 'submitted' ? 'Save changes' : 'Submit abstract' }}</x-button>
            </div>
        </div>
    </form>
</x-layouts.portal>
