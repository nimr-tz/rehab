@php
    $categoryRows = old('categories', $categories->map(fn ($c) => [
        'id' => $c->id, 'name' => $c->name, 'currency' => $c->currency,
        'amount' => $c->amount !== null ? (float) $c->amount : '', 'is_student' => $c->is_student,
    ])->values()->all());
    $topicRows = old('topics', $topics->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'code' => $t->code])->values()->all());
    $date = fn ($field) => old($field, $edition->{$field}?->toDateString());
@endphp

<x-layouts.portal title="Summit settings">
    <x-slot:header>
        <x-page-header eyebrow="Administration" title="Summit settings"
            description="Everything the public pages, registration and abstract submission read. Leave a field empty and it shows as “To be announced”." />
    </x-slot:header>

    @if ($errors->any())
        <x-alert tone="danger">Please check the highlighted fields.</x-alert>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card title="The summit">
            <div class="grid gap-5 sm:grid-cols-4">
                <x-form.input name="year" type="number" label="Year" :value="$edition->year" required />
                <x-form.input name="ordinal" label="Edition" :value="$edition->ordinal" placeholder="5th" required />
                <div class="sm:col-span-2"><x-form.input name="name" label="Name" :value="$edition->name" required /></div>
                <div class="sm:col-span-2"><x-form.input name="short_name" label="Short name" :value="$edition->short_name" required /></div>
                <div class="sm:col-span-2"><x-form.input name="theme" label="Theme" :value="$edition->theme" /></div>
            </div>
        </x-card>

        <x-card title="Dates and venue">
            <div class="grid gap-5 sm:grid-cols-3">
                <x-form.input name="start_date" type="date" label="First day" :value="$date('start_date')" />
                <x-form.input name="end_date" type="date" label="Last day" :value="$date('end_date')" />
                <div></div>
                <x-form.input name="venue" label="Venue" :value="$edition->venue" />
                <x-form.input name="city" label="City" :value="$edition->city" />
                <x-form.input name="country" label="Country" :value="$edition->country" />
            </div>
        </x-card>

        <x-card title="Registration and abstracts">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="space-y-4">
                    <x-form.checkbox name="registration_open" label="Registration is open" :checked="$edition->registration_open" hint="Participants can register and pay." />
                    <x-form.checkbox name="abstracts_open" label="Abstract submission is open" :checked="$edition->abstracts_open" hint="Closes automatically after the deadline." />
                    <x-form.input name="registration_target" type="number" min="1" label="Registration target (optional)" :value="$edition->registration_target" hint="Drives the pace line on the executive summary." />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="abstract_deadline" type="date" label="Abstract deadline" :value="$date('abstract_deadline')" />
                    <x-form.input name="review_deadline" type="date" label="Review deadline" :value="$date('review_deadline')" />
                    <x-form.input name="session_role_deadline" type="date" label="Chair applications close" :value="$date('session_role_deadline')" />
                    <x-form.input name="presentation_deadline" type="date" label="Presentation upload" :value="$date('presentation_deadline')" />
                </div>
            </div>
        </x-card>

        <x-card title="Registration fees" description="Leave the amount empty until it is confirmed; the category stays hidden from participants.">
            <div x-data="{ rows: @js($categoryRows) }" class="space-y-3">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="grid gap-3 rounded-2xl border border-ink-100 bg-canvas p-3 sm:grid-cols-[1fr_7rem_10rem_auto_auto] sm:items-center">
                        <input type="hidden" :name="`categories[${i}][id]`" :value="row.id ?? ''">
                        <input class="field" :name="`categories[${i}][name]`" x-model="row.name" placeholder="Category name" required>
                        <select class="field" :name="`categories[${i}][currency]`" x-model="row.currency"><option>TZS</option><option>USD</option></select>
                        <input class="field" type="number" min="0" step="1" :name="`categories[${i}][amount]`" x-model="row.amount" placeholder="Amount">
                        <label class="flex items-center gap-2 text-sm text-ink-700">
                            <input type="hidden" :name="`categories[${i}][is_student]`" value="0">
                            <input type="checkbox" value="1" :name="`categories[${i}][is_student]`" x-model="row.is_student" class="h-4 w-4 accent-brand-700"> Student
                        </label>
                        <button type="button" @click="rows.splice(i, 1)" class="grid h-9 w-9 place-items-center rounded-lg text-ink-400 hover:bg-red-50 hover:text-red-700" aria-label="Remove category"><x-icon name="trash" class="h-4 w-4" /></button>
                    </div>
                </template>
                <x-button type="button" variant="secondary" size="sm" icon="plus" x-on:click="rows.push({ name: '', currency: 'TZS', amount: '', is_student: false })">Add category</x-button>
            </div>
        </x-card>

        <x-card title="Topics" description="Abstracts are submitted under these. The code forms the conference code, e.g. OR-HBR-01.">
            <div x-data="{ rows: @js($topicRows) }" class="space-y-3">
                @error('topics') <p class="text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                <template x-for="(row, i) in rows" :key="i">
                    <div class="grid gap-3 rounded-2xl border border-ink-100 bg-canvas p-3 sm:grid-cols-[2.5rem_1fr_7rem_auto] sm:items-center">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-white text-sm font-bold text-ink-600" x-text="i + 1"></span>
                        <input type="hidden" :name="`topics[${i}][id]`" :value="row.id ?? ''">
                        <input class="field" :name="`topics[${i}][name]`" x-model="row.name" placeholder="Topic name" required>
                        <input class="field uppercase" :name="`topics[${i}][code]`" x-model="row.code" maxlength="6" placeholder="CODE" required>
                        <button type="button" @click="rows.splice(i, 1)" class="grid h-9 w-9 place-items-center rounded-lg text-ink-400 hover:bg-red-50 hover:text-red-700" aria-label="Remove topic"><x-icon name="trash" class="h-4 w-4" /></button>
                    </div>
                </template>
                <x-button type="button" variant="secondary" size="sm" icon="plus" x-on:click="rows.push({ name: '', code: '' })">Add topic</x-button>
            </div>
        </x-card>

        <div class="sticky bottom-4 flex justify-end">
            <x-button size="lg" icon="check" class="shadow-lift">Save settings</x-button>
        </div>
    </form>
</x-layouts.portal>
