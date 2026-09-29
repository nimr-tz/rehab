{{--
    Payment transactions for a payable, with proof links and duplicate-reference warnings.
    Expects: $transactions (Collection of PaymentTransaction)
--}}
@php
    $statusStyles = [
        'submitted' => 'bg-amber-100 text-amber-700',
        'verified' => 'bg-emerald-100 text-emerald-700',
        'rejected' => 'bg-rose-100 text-rose-700',
        'pending' => 'bg-slate-100 text-slate-600',
        'failed' => 'bg-rose-100 text-rose-700',
    ];
@endphp
<div class="bg-white dark:bg-[#151C2C] rounded-2xl p-8 border border-slate-100 dark:border-slate-800 shadow-sm">
    <div class="flex items-center justify-between mb-6">
        <h3 class="font-bold text-slate-900 dark:text-white text-lg tracking-tight">Payment Submissions</h3>
        <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-bold text-slate-500 uppercase">{{ $transactions->count() }} total</span>
    </div>

    @forelse ($transactions as $transaction)
        @php $duplicates = $transaction->duplicateReferences()->with('payable')->get(); @endphp
        <div class="mb-4 last:mb-0 rounded-xl border border-slate-100 dark:border-slate-800 p-5 {{ $transaction->isAwaitingReview() ? 'bg-amber-50/40 dark:bg-amber-900/10' : 'bg-slate-50 dark:bg-slate-900' }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $transaction->method_label }}</p>
                    <p class="mt-1 font-mono text-lg font-black text-slate-900 dark:text-white">{{ $transaction->external_reference ?: '—' }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $transaction->currency }} {{ number_format((float) $transaction->amount, $transaction->currency === 'USD' ? 2 : 0) }}
                        @if ($transaction->paid_on) · paid {{ $transaction->paid_on->format('M j, Y') }} @endif
                        · submitted {{ $transaction->submitted_at?->format('M j, Y H:i') }}
                        @if ($transaction->submitter) by {{ $transaction->submitter->full_name }} @endif
                    </p>
                    @if ($transaction->payer_name || $transaction->payer_phone)
                        <p class="mt-1 text-xs text-slate-500">Payer: {{ $transaction->payer_name }} {{ $transaction->payer_phone }}</p>
                    @endif
                </div>
                <div class="text-right space-y-2">
                    <span class="inline-block rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-widest {{ $statusStyles[$transaction->status] ?? $statusStyles['pending'] }}">{{ $transaction->status }}</span>
                    @if ($transaction->proof_path)
                        <div><a href="{{ route('finance.transactions.proof', $transaction) }}" target="_blank" class="text-xs font-bold text-indigo-600 hover:underline">View proof</a></div>
                    @elseif ($transaction->isAwaitingReview())
                        <div class="text-xs font-bold text-slate-400">No proof attached</div>
                    @endif
                </div>
            </div>

            @if ($duplicates->isNotEmpty())
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800">
                    <p class="font-black uppercase tracking-wide">Reference already used</p>
                    @foreach ($duplicates as $duplicate)
                        <p class="mt-1">{{ $duplicate->payable?->paymentDescription() ?? 'Unknown payer' }} — {{ $duplicate->status }} ({{ $duplicate->submitted_at?->format('M j, Y') }})</p>
                    @endforeach
                </div>
            @endif

            @if ($transaction->reviewed_at)
                <p class="mt-3 text-xs text-slate-500">
                    Reviewed {{ $transaction->reviewed_at->format('M j, Y H:i') }}
                    @if ($transaction->reviewer) by {{ $transaction->reviewer->full_name }} @endif
                    @if ($transaction->review_notes) — {{ $transaction->review_notes }} @endif
                </p>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">No payment has been submitted yet.</p>
    @endforelse
</div>
