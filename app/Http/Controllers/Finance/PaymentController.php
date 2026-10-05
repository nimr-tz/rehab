<?php

namespace App\Http\Controllers\Finance;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', PaymentStatus::Submitted->value);
        $search = trim((string) $request->query('q'));

        $payments = Payment::query()
            ->with('registration.user', 'registration.category')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('transaction_reference', 'like', "%{$search}%")
                ->orWhere('payer_name', 'like', "%{$search}%")
                ->orWhereHas('registration', fn ($q) => $q->where('reference', 'like', "%{$search}%"))))
            ->orderByRaw("case when status = 'submitted' then 0 else 1 end")
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $totals = Payment::selectRaw('status, currency, count(*) as n, sum(amount) as total')
            ->groupBy('status', 'currency')->get();

        return view('finance.payments.index', [
            'payments' => $payments,
            'status' => $status,
            'search' => $search,
            'counts' => $totals->groupBy(fn ($row) => $row->status->value)->map->sum('n'),
            'verifiedTotals' => $totals->filter(fn ($row) => $row->status === PaymentStatus::Verified)->pluck('total', 'currency'),
        ]);
    }

    public function show(Payment $payment): View
    {
        $payment->load('registration.user', 'registration.category', 'registration.payments', 'reviewer');

        return view('finance.payments.show', [
            'payment' => $payment,
            'proofIsImage' => $payment->proof_path && in_array(strtolower(pathinfo($payment->proof_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true),
        ]);
    }

    /** Proofs live on a private disk; only finance staff can open them. */
    public function proof(Payment $payment): StreamedResponse
    {
        $disk = Storage::disk(config('payments.proof_disk'));
        abort_unless($payment->proof_path && $disk->exists($payment->proof_path), 404);

        return $disk->response($payment->proof_path, 'proof-'.$payment->registration->reference.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION));
    }

    public function verify(Request $request, Payment $payment, RegistrationService $service): RedirectResponse
    {
        $service->verify($payment, $request->user());

        return $this->next($payment, 'Payment verified. '.$payment->registration->user->name.' is now confirmed.');
    }

    public function reject(Request $request, Payment $payment, RegistrationService $service): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'min:5', 'max:255']]);

        $service->reject($payment, $request->user(), $data['rejection_reason']);

        return $this->next($payment, 'Payment rejected. The participant has been asked to resubmit.');
    }

    /** After a decision, go straight to the next payment waiting, if any. */
    private function next(Payment $payment, string $message): RedirectResponse
    {
        $next = Payment::where('status', PaymentStatus::Submitted)->where('id', '!=', $payment->id)->oldest()->first();

        return $next
            ? redirect()->route('finance.payments.show', $next)->with('status', $message)
            : redirect()->route('finance.payments.index')->with('status', $message);
    }
}
