@php
    use App\Enums\PaymentStatus;
    use App\Enums\RegistrationStatus;
    $members = $group->members;
@endphp

<x-layouts.portal :title="$group->name">
    <x-slot:header>
        <x-page-header eyebrow="Group registration" :title="$group->name" :back="route('groups.index')">
            <x-status :tone="$group->status->tone()">{{ $group->status->label() }}</x-status>
        </x-page-header>
    </x-slot:header>

    @error('member') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

    <div class="grid gap-6 lg:grid-cols-[1fr_1.3fr]">
        <div class="space-y-6">
            <x-card title="The group">
                <dl class="space-y-4 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Reference</dt><dd class="font-mono font-semibold text-ink-900">{{ $group->reference }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">People</dt><dd class="font-medium text-ink-900">{{ $members->count() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Total</dt><dd class="font-bold text-ink-900">{{ $group->formattedAmount() }}</dd></div>
                    @if ($group->confirmed_at)
                        <div class="flex justify-between gap-4"><dt class="text-ink-500">Confirmed</dt><dd class="font-medium text-ink-900">{{ $group->confirmed_at->format('j M Y') }}</dd></div>
                    @endif
                </dl>
            </x-card>

            <x-card title="People" :padding="false" :description="$group->canChangeMembers() ? 'You can add or remove people until you pay.' : 'The group is fixed now that a payment has been sent.'">
                @if ($members->isEmpty())
                    <p class="px-6 py-4 text-sm text-ink-500">Add at least one person before paying.</p>
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($members as $member)
                            <li class="flex items-center gap-3 px-6 py-3">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold text-ink-900">{{ $member->user->name }}{{ $member->user_id === auth()->id() ? ' (you)' : '' }}</span>
                                    <span class="block truncate text-xs text-ink-500">{{ $member->category->name }} · {{ $member->formattedAmount() }} · <span class="font-mono">{{ $member->reference }}</span></span>
                                </span>
                                @if ($group->canChangeMembers())
                                    <form method="POST" action="{{ route('groups.members.destroy', [$group, $member]) }}" x-data @submit="if (! confirm(@js('Take '.$member->user->name.' out of the group?'))) $event.preventDefault()">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-semibold text-red-700 hover:underline">Remove</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($group->canChangeMembers())
                    <div class="border-t border-ink-100 px-6 py-4" x-data="{ open: {{ $errors->any() && old('first_name') ? 'true' : 'false' }} }">
                        <button type="button" x-show="! open" @click="open = true" class="text-sm font-semibold text-brand-700 hover:underline">+ Add a person</button>
                        <form x-show="open" x-cloak method="POST" action="{{ route('groups.members.store', $group) }}" class="space-y-4">
                            @csrf
                            @include('groups._member-fields', ['prefix' => ''])
                            <x-button variant="secondary" icon="user-plus">Add to the group</x-button>
                        </form>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            @if ($group->isConfirmed())
                <x-card>
                    <x-empty icon="check-circle" title="Everyone is confirmed">
                        The payment was verified on {{ $group->confirmed_at->format('j F Y') }}. Each person has been emailed and downloads their own badge from the portal.
                    </x-empty>
                </x-card>
            @elseif ($pendingMpesa)
                <x-card>
                    <div x-data="mpesaPay({ storeUrl: @js(route('groups.mpesa.store', $group)), statusUrl: @js(route('groups.mpesa.status', $group)), initialState: 'pending' })">
                        <x-empty icon="clock" title="Waiting for M-Pesa">
                            We sent a request for {{ $pendingMpesa->formattedAmount() }} to {{ $pendingMpesa->payer_phone }}. If you entered your PIN, M-Pesa will confirm it shortly. This page updates by itself, and you will not be charged twice.
                            <x-slot:action>
                                <p x-show="state === 'confirmed' || state === 'failed'" x-cloak x-text="message" class="mb-3 text-sm font-semibold" :class="state === 'confirmed' ? 'text-emerald-700' : 'text-red-700'"></p>
                                <form method="POST" action="{{ route('groups.mpesa.check', $group) }}" x-show="state !== 'confirmed'">
                                    @csrf
                                    <x-button variant="secondary" icon="arrow-trend">Check the payment</x-button>
                                </form>
                            </x-slot:action>
                        </x-empty>
                    </div>
                </x-card>
            @elseif ($group->status === RegistrationStatus::PaymentSubmitted)
                <x-card>
                    <x-empty icon="clock" title="We are verifying your payment">
                        A finance officer is checking the payment. This usually takes one working day. Everyone in the group is confirmed together, and emailed.
                    </x-empty>
                </x-card>
            @elseif ($group->canSubmitPayment())
                @include('registration._payment', [
                    'payable' => $group,
                    'payRoutes' => [
                        'manual' => route('groups.payments.store', $group),
                        'mpesa' => route('groups.mpesa.store', $group),
                        'mpesa_status' => route('groups.mpesa.status', $group),
                        'back' => route('groups.show', $group),
                    ],
                ])
            @endif

            @if ($group->payments->isNotEmpty())
                <x-card title="Payment history" :padding="false">
                    <ul class="divide-y divide-ink-100">
                        @foreach ($group->payments as $payment)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-3 text-sm">
                                <span>
                                    <span class="font-semibold text-ink-900">{{ $payment->channel() }} · {{ $payment->formattedAmount() }}</span>
                                    <span class="block text-xs text-ink-500">{{ $payment->created_at->format('j M Y') }}@if ($payment->rejection_reason) · {{ $payment->rejection_reason }}@endif</span>
                                </span>
                                <x-status :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-status>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.portal>
