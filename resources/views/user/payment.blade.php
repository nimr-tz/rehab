@extends('layouts.app')

@section('title', 'Registration Payment')

@section('content')
@php
    $isGroup = (bool) $groupAsLeader;
    $formatAmount = fn ($value, $cur) => $cur === 'USD' ? '$'.number_format((float) $value, 2) : $cur.' '.number_format((float) $value, 0);
    $statusStyles = [
        'submitted' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        'verified' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
        'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
        'pending' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        'failed' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
    ];
    $flashStyles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'error' => 'border-rose-200 bg-rose-50 text-rose-800',
        'info' => 'border-sky-200 bg-sky-50 text-sky-800',
    ];
    $lastRejected = $transactions->firstWhere('status', 'rejected');
    $canSubmit = ! $isSettled && ! $awaitingReview && $amount > 0 && ! empty($methods) && ! ($studentPending && ! $isGroup);
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    <div>
        <a href="{{ route('dashboard') }}" class="text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-slate-600">&larr; Dashboard</a>
        <h1 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $isGroup ? 'Group Registration Payment' : 'Registration Payment' }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pay by bank transfer or mobile money, then submit the transaction details below. The finance team verifies every payment.</p>
    </div>

    @foreach ($flashStyles as $key => $classes)
        @if (session($key))
            <div class="rounded-2xl border px-5 py-4 text-sm font-semibold {{ $classes }}">{{ session($key) }}</div>
        @endif
    @endforeach
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Summary --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Amount due</p>
            <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                @if ($payable->payment_status === 'waived')
                    Waived
                @elseif ($amount > 0)
                    {{ $formatAmount($amount, $currency) }}
                @else
                    Not yet published
                @endif
            </p>
            @if ($feeLabel)
                <p class="mt-1 text-xs text-slate-500">{{ $feeLabel }}</p>
            @endif
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Payment reference</p>
            <p class="mt-2 font-mono text-2xl font-black text-indigo-700 dark:text-indigo-300">{{ $reference ?: '—' }}</p>
            <p class="mt-1 text-xs text-slate-500">Quote this in the bank narration or mobile money reference.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Status</p>
            <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                @if ($isSettled)
                    <span class="text-emerald-600">{{ $payable->payment_status === 'waived' ? 'Fee waived' : 'Paid' }}</span>
                @elseif ($awaitingReview)
                    <span class="text-amber-600">Under review</span>
                @else
                    Not paid
                @endif
            </p>
        </div>
    </div>

    @if ($isSettled)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-emerald-900">
            <p class="font-black">Your registration is confirmed.</p>
            <p class="mt-1 text-sm">Your conference badge will be available at the registration desk. No further payment is needed.</p>
        </div>
    @elseif ($awaitingReview)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
            <p class="font-black">Payment received — awaiting verification</p>
            <p class="mt-1 text-sm">You submitted {{ $awaitingReview->method_label }} reference <span class="font-mono font-bold">{{ $awaitingReview->external_reference }}</span> on {{ $awaitingReview->submitted_at?->format('M j, Y H:i') }}. You will receive an email once the finance team confirms it.</p>
        </div>
    @elseif ($amount <= 0)
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
            <p class="font-black">Registration fees have not been published yet.</p>
            <p class="mt-1 text-sm">Please check back soon. You will be able to pay here as soon as fees are announced.</p>
        </div>
    @elseif (empty($methods))
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
            <p class="font-black">Payment details for {{ $currency }} payments are not available yet.</p>
            <p class="mt-1 text-sm">Please contact the organisers at {{ config('conference.contact_email') }}.</p>
        </div>
    @elseif ($studentPending && ! $isGroup)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
            <p class="font-black">Student verification pending</p>
            <p class="mt-1 text-sm">Your student ID must be verified before you pay the student fee. You will be notified once it is reviewed.</p>
        </div>
    @endif

    @if ($lastRejected && ! $isSettled && ! $awaitingReview)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-rose-900">
            <p class="font-black">Your last payment submission could not be verified</p>
            <p class="mt-1 text-sm">{{ $lastRejected->review_notes }}</p>
            <p class="mt-1 text-sm">Please check the details and submit again.</p>
        </div>
    @endif

    @if ($canSubmit)
        {{-- Where to pay --}}
        <div class="grid gap-4 md:grid-cols-2">
            @if ($bankAccount)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Bank transfer ({{ $currency }})</p>
                    <dl class="mt-3 space-y-1.5 text-sm">
                        @foreach (['bank_name' => 'Bank', 'account_name' => 'Account name', 'account_number' => 'Account number', 'branch' => 'Branch', 'swift_code' => 'SWIFT'] as $field => $label)
                            @if (! empty($bankAccount[$field]))
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-bold text-slate-900 dark:text-white {{ $field === 'account_number' ? 'font-mono' : '' }}">{{ $bankAccount[$field] }}</dd></div>
                            @endif
                        @endforeach
                    </dl>
                    <p class="mt-3 text-xs text-slate-500">Use <span class="font-mono font-bold">{{ $reference }}</span> as the transfer narration. Keep the bank slip — you will upload it below.</p>
                </div>
            @endif
            @if (! empty($mobileMoneyProviders))
                <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Mobile money ({{ $currency }})</p>
                    <dl class="mt-3 space-y-1.5 text-sm">
                        @foreach ($mobileMoneyProviders as $provider)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">{{ $provider['label'] }}</dt>
                                <dd class="text-right"><span class="font-mono font-bold text-slate-900 dark:text-white">{{ $provider['pay_number'] }}</span>@if (! empty($provider['account_name']))<br><span class="text-xs text-slate-500">{{ $provider['account_name'] }}</span>@endif</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-3 text-xs text-slate-500">Enter <span class="font-mono font-bold">{{ $reference }}</span> as the account/reference number, then copy the transaction ID from the confirmation SMS.</p>
                </div>
            @endif
        </div>

        {{-- Submit payment details --}}
        <form method="POST" action="{{ route('payment.store') }}" enctype="multipart/form-data"
              x-data="{ method: '{{ old('method', array_key_first($methods)) }}' }"
              class="rounded-2xl border border-slate-200 bg-white p-6 space-y-5 dark:border-slate-800 dark:bg-slate-900">
            @csrf
            <h2 class="text-lg font-black text-slate-900 dark:text-white">I have paid — submit my payment details</h2>

            <div>
                <p class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Payment method</p>
                <div class="flex flex-wrap gap-3">
                    @foreach ($methods as $value => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="method" value="{{ $value }}" x-model="method" class="peer sr-only">
                            <span class="inline-block rounded-xl border-2 border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 dark:border-slate-700 dark:text-slate-300">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            @if (! empty($mobileMoneyProviders))
                <div x-show="method === 'mobile_money'" x-cloak>
                    <label for="provider" class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Operator</label>
                    <select id="provider" name="provider" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                        <option value="">Select the operator you paid with</option>
                        @foreach ($mobileMoneyProviders as $key => $provider)
                            <option value="{{ $key }}" @selected(old('provider') === $key)>{{ $provider['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="external_reference" class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                        <span x-show="method === 'bank_transfer'">Bank slip / transfer reference</span>
                        <span x-show="method !== 'bank_transfer'" x-cloak>Transaction ID (from the SMS)</span>
                    </label>
                    <input id="external_reference" name="external_reference" value="{{ old('external_reference') }}" required maxlength="100"
                           class="w-full rounded-xl border-slate-300 font-mono dark:border-slate-700 dark:bg-slate-800">
                </div>
                <div>
                    <label for="paid_on" class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Date paid</label>
                    <input id="paid_on" type="date" name="paid_on" value="{{ old('paid_on') }}" max="{{ now()->toDateString() }}"
                           class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                </div>
                <div>
                    <label for="payer_name" class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Paid by (name on account)</label>
                    <input id="payer_name" name="payer_name" value="{{ old('payer_name', $user->full_name) }}" maxlength="255"
                           class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                </div>
                <div>
                    <label for="payer_phone" class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Phone number used</label>
                    <input id="payer_phone" name="payer_phone" value="{{ old('payer_phone', $user->phone) }}" maxlength="30"
                           class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                </div>
            </div>

            <div>
                <label for="proof" class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                    Proof of payment
                    <span x-show="method === 'bank_transfer'" class="text-rose-500">(required)</span>
                    <span x-show="method !== 'bank_transfer'" x-cloak class="normal-case font-semibold text-slate-400">(optional screenshot)</span>
                </label>
                <input id="proof" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm">
                <p class="mt-1 text-xs text-slate-400">PDF, JPG or PNG, up to 5 MB.</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-black text-white hover:bg-indigo-700">Submit for verification</button>
            </div>
        </form>
    @endif

    {{-- History --}}
    @if ($transactions->isNotEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <p class="px-6 pt-5 text-[11px] font-black uppercase tracking-widest text-slate-400">Payment history</p>
            <div class="overflow-x-auto">
                <table class="mt-3 w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wider text-slate-400 dark:border-slate-800">
                            <th class="px-6 py-2">Submitted</th>
                            <th class="px-6 py-2">Method</th>
                            <th class="px-6 py-2">Reference</th>
                            <th class="px-6 py-2">Amount</th>
                            <th class="px-6 py-2">Status</th>
                            <th class="px-6 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr class="border-b border-slate-50 dark:border-slate-800/50">
                                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ $transaction->submitted_at?->format('M j, Y H:i') }}</td>
                                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ $transaction->method_label }}</td>
                                <td class="px-6 py-3 font-mono text-slate-900 dark:text-white">{{ $transaction->external_reference }}</td>
                                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ $formatAmount($transaction->amount, $transaction->currency) }}</td>
                                <td class="px-6 py-3"><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-widest {{ $statusStyles[$transaction->status] ?? $statusStyles['pending'] }}">{{ $transaction->status }}</span></td>
                                <td class="px-6 py-3 text-right">
                                    @if ($transaction->proof_path)
                                        <a href="{{ route('payment.proof', $transaction) }}" class="text-xs font-bold text-indigo-600 hover:underline">Proof</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
