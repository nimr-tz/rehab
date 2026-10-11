@php
    // Rows to show again after a validation error, or one empty row to start.
    $rows = max(1, count(old('members', [])));
@endphp

<x-layouts.portal title="Register a group">
    <x-slot:header>
        <x-page-header eyebrow="Group registration" title="Register a group" :back="route('groups.index')"
            description="Each person gets their own registration, badge and account (we email them). You pay once for everyone." />
    </x-slot:header>

    @error('members') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

    <form method="POST" action="{{ route('groups.store') }}" class="space-y-6"
          x-data="{ next: {{ $rows }}, add() { const html = this.$refs.row.innerHTML.replaceAll('__INDEX__', this.next++); this.$refs.rows.insertAdjacentHTML('beforeend', html); } }">
        @csrf

        <x-card title="The group">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Group name" placeholder="e.g. KCMC Physiotherapy Department" hint="Shown to finance and on the invoice." required />
                <div>
                    @if ($leaderRegistered)
                        <p class="label">You</p>
                        <p class="text-sm text-ink-600">You are already registered yourself, so the group is for other people.</p>
                    @else
                        <label for="leader_category" class="label">Are you attending too?</label>
                        <select id="leader_category" name="leader_category" class="field">
                            <option value="">No, I am only paying for others</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('leader_category') === $category->id)>Yes, as {{ $category->name }} · {{ $category->formattedFee() }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
            <p class="mt-4 text-xs text-ink-500">Everyone in a group pays in the same currency. Students register by themselves, with their own proof of enrolment.</p>
        </x-card>

        <x-card title="The people" description="Their details appear on their badges. Anyone without an account gets one, with an email to set a password.">
            <div x-ref="rows" class="space-y-5">
                @for ($i = 0; $i < $rows; $i++)
                    <div class="rounded-2xl border border-ink-100 p-4" x-data>
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-bold text-ink-900">Person</p>
                            <button type="button" @click="$el.closest('.rounded-2xl').remove()" class="text-xs font-semibold text-red-700 hover:underline">Remove</button>
                        </div>
                        @include('groups._member-fields', ['prefix' => 'members['.$i.']'])
                    </div>
                @endfor
            </div>
            <template x-ref="row">
                <div class="rounded-2xl border border-ink-100 p-4" x-data>
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-bold text-ink-900">Person</p>
                        <button type="button" @click="$el.closest('.rounded-2xl').remove()" class="text-xs font-semibold text-red-700 hover:underline">Remove</button>
                    </div>
                    @include('groups._member-fields', ['prefix' => 'members[__INDEX__]', 'errors' => new \Illuminate\Support\ViewErrorBag])
                </div>
            </template>
            <x-button type="button" variant="secondary" class="mt-5" icon="plus" @click="add()">Add another person</x-button>
        </x-card>

        <div class="flex flex-col gap-4 rounded-card border border-ink-100 bg-white p-6 shadow-soft sm:flex-row sm:items-center sm:justify-between">
            <label class="flex items-start gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="confirm" value="1" required class="mt-0.5 h-4 w-4 accent-brand-700">
                <span>I will pay the fee for everyone in this group, in one payment.</span>
            </label>
            <x-button size="lg" icon="check">Register the group</x-button>
        </div>
        @error('confirm') <p class="text-xs font-medium text-red-700">{{ $message }}</p> @enderror
    </form>
</x-layouts.portal>
