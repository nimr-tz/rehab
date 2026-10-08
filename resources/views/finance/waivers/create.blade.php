@php
    $fee = (float) $registration->amount;
    $user = $registration->user;
@endphp

<x-layouts.portal :title="'Waive fee · '.$user->name">
    <x-slot:header>
        <x-page-header eyebrow="Finance · fee waiver" :title="$user->name" :back="route('finance.waivers.index')"
            :description="$registration->category->name.' · fee '.$registration->formattedAmount()" />
    </x-slot:header>

    <div class="grid items-start gap-6 lg:grid-cols-[1fr_340px]">
        <form method="POST" action="{{ route('finance.waivers.store', $registration) }}" class="space-y-6"
              x-data="{ amount: @js((float) old('amount', $fee)), fee: @js($fee), reason: @js(old('reason', '')) }">
            @csrf

            <x-card title="How much to waive" description="The whole fee confirms the registration at once. Part of it leaves the rest for the participant to pay.">
                <div class="flex flex-wrap gap-2">
                    @foreach ([100, 75, 50, 25] as $percent)
                        <button type="button" @click="amount = Math.round(fee * {{ $percent }} / 100 * 100) / 100"
                                class="rounded-full px-4 py-2 text-sm font-semibold ring-1 transition"
                                :class="amount === Math.round(fee * {{ $percent }} / 100 * 100) / 100 ? 'bg-brand-700 text-white ring-brand-700' : 'bg-white text-ink-700 ring-ink-200 hover:bg-ink-50'">
                            {{ $percent === 100 ? 'Full fee' : $percent.'%' }}
                        </button>
                    @endforeach
                </div>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="amount" class="label">Amount waived ({{ $registration->currency }})</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $fee }}" x-model.number="amount" required
                               @class(['field', 'border-red-400' => $errors->has('amount')])>
                        @error('amount') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-2xl bg-canvas px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">The participant then pays</p>
                        <p class="mt-1 text-2xl font-extrabold tabular-nums text-brand-700"
                           x-text="'{{ $registration->currency }} ' + Math.max(0, fee - (amount || 0)).toLocaleString('en-US', { maximumFractionDigits: 2 })">{{ $registration->formattedAmount() }}</p>
                        <p class="text-xs text-ink-500" x-text="amount >= fee ? 'Nothing: the registration is confirmed now.' : 'By bank transfer or mobile money, as usual.'"></p>
                    </div>
                </div>
            </x-card>

            <x-card title="Why">
                <div class="space-y-5">
                    <div>
                        <label for="reason" class="label">Reason</label>
                        <select id="reason" name="reason" x-model="reason" required @class(['field', 'border-red-400' => $errors->has('reason')])>
                            <option value="" disabled>Choose a reason</option>
                            @foreach ($reasons as $reason)
                                <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                            @endforeach
                        </select>
                        @error('reason') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="note" class="label">Note <span class="font-normal text-ink-400" x-text="reason === 'other' ? '(required)' : '(optional)'">(optional)</span></label>
                        <textarea id="note" name="note" rows="3" maxlength="500" :required="reason === 'other'"
                                  placeholder="e.g. Keynote speaker on day 2; sponsored by Humanity & Inclusion"
                                  @class(['w-full rounded-control border bg-white px-4 py-3 text-[15px] text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15', 'border-red-400' => $errors->has('note'), 'border-ink-200' => ! $errors->has('note')])>{{ old('note') }}</textarea>
                        @error('note') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-card>

            <div class="flex flex-col-reverse gap-3 rounded-card border border-ink-100 bg-white p-5 shadow-soft sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-ink-500">The participant is emailed. You can withdraw the waiver later, until they pay the rest or check in.</p>
                <x-button icon="check">Waive fee</x-button>
            </div>
        </form>

        <x-card title="Registration">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Reference</dt><dd class="font-mono font-semibold text-ink-900">{{ $registration->reference }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Email</dt><dd class="truncate text-right text-ink-900">{{ $user->email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Institution</dt><dd class="text-right text-ink-900">{{ $user->institution ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Category</dt><dd class="text-right text-ink-900">{{ $registration->category->name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Fee</dt><dd class="font-bold text-ink-900">{{ $registration->formattedAmount() }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Status</dt><dd><x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Registered</dt><dd class="text-ink-900">{{ $registration->created_at->format('j M Y') }}</dd></div>
                @if ($registration->payments->isNotEmpty())
                    <div class="border-t border-ink-100 pt-3 text-xs text-ink-500">{{ $registration->payments->count() }} earlier {{ \Illuminate\Support\Str::plural('payment', $registration->payments->count()) }}, last {{ strtolower($registration->payments->first()->status->label()) }}.</div>
                @endif
            </dl>
        </x-card>
    </div>
</x-layouts.portal>
