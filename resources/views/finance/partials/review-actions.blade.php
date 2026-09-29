{{--
    Verify / reject the submission awaiting review.
    Expects: $verifyRoute, $rejectRoute (URLs), $awaiting (?PaymentTransaction), $payerName
--}}
@if ($awaiting)
    <form action="{{ $verifyRoute }}" method="POST" class="space-y-2" onsubmit="return confirm('Confirm that this payment has been received for {{ addslashes($payerName) }}?')">
        @csrf
        <textarea name="notes" rows="2" class="w-full rounded-xl border-slate-200 dark:border-slate-700 text-sm bg-slate-50 dark:bg-slate-800" placeholder="Verification note (optional), e.g. matched on bank statement"></textarea>
        <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition-all shadow-lg shadow-emerald-500/20">
            Verify Payment
        </button>
    </form>
    <form action="{{ $rejectRoute }}" method="POST" class="space-y-2 pt-3" onsubmit="return confirm('Reject this payment submission?')">
        @csrf
        <textarea name="notes" rows="2" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 text-sm bg-slate-50 dark:bg-slate-800" placeholder="Reason shown to the payer (required)"></textarea>
        <button type="submit" class="w-full py-3 bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-800 text-rose-600 hover:bg-rose-50 font-bold text-sm rounded-xl transition-all">
            Reject Submission
        </button>
    </form>
@else
    <p class="text-sm text-slate-500">No payment is awaiting review.</p>
@endif
