<?php

namespace App\Http\Controllers\Finance;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\MpesaPaymentService;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', PaymentStatus::Submitted->value);
        $search = trim((string) $request->query('q'));
        $method = PaymentMethod::tryFrom((string) $request->query('method'));

        $payments = Payment::query()
            ->with('registration.user', 'registration.category', 'group.leader')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($method, fn ($q) => $q->where('method', $method))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('transaction_reference', 'like', "%{$search}%")
                ->orWhere('payer_name', 'like', "%{$search}%")
                ->orWhereHas('registration', fn ($q) => $q->where('reference', 'like', "%{$search}%"))
                ->orWhereHas('group', fn ($q) => $q->where('reference', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))))
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
            'method' => $method,
            'counts' => $totals->groupBy(fn ($row) => $row->status->value)->map->sum('n'),
            'verifiedTotals' => $totals->filter(fn ($row) => $row->status === PaymentStatus::Verified)->pluck('total', 'currency'),
        ]);
    }

    /** CSV for reconciliation against the bank and mobile money statements. */
    public function export(Request $request): StreamedResponse
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(['all', 'submitted', 'verified', 'rejected'])]])['status'] ?? 'all';

        $payments = Payment::with('registration.user', 'registration.category', 'group.leader', 'reviewer')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->oldest()
            ->get();

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Registration', 'Participant', 'Email', 'Category', 'Channel', 'Transaction reference', 'Paid on', 'Currency', 'Amount', 'Status', 'Reviewed by', 'Reviewed at', 'Rejection reason', 'Submitted at']);
            foreach ($payments as $p) {
                $payable = $p->payable();
                fputcsv($out, [
                    $payable->reference, $payable->payer()->name, $payable->payer()->email, $payable->payableLabel(),
                    $p->channel(), $p->transaction_reference, $p->paid_on->toDateString(), $p->currency, $p->amount, $p->status->label(),
                    $p->reviewer?->name, $p->reviewed_at?->toDateTimeString(), $p->rejection_reason, $p->created_at->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, 'payments-'.$status.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function show(Payment $payment): View
    {
        $payment->load('registration.user', 'registration.category', 'registration.payments', 'group.leader', 'group.members.user', 'group.members.category', 'group.payments', 'reviewer');

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

        return $disk->response($payment->proof_path, 'proof-'.$payment->payable()->reference.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION));
    }

    public function verify(Request $request, Payment $payment, RegistrationService $service): RedirectResponse
    {
        $service->verify($payment, $request->user());

        return $this->next($payment, 'Payment verified. '.$this->confirmedName($payment).' now confirmed.');
    }

    public function reject(Request $request, Payment $payment, RegistrationService $service): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'min:5', 'max:255']]);

        $service->reject($payment, $request->user(), $data['rejection_reason']);

        return $this->next($payment, 'Payment rejected. The participant has been asked to resubmit.');
    }

    /** Ask M-Pesa again about a payment still pending. */
    public function mpesaCheck(Payment $payment, MpesaPaymentService $mpesa): RedirectResponse
    {
        abort_unless($payment->gateway === 'mpesa', 404);

        $payment = $mpesa->reconcile($payment);

        return back()->with('status', match ($payment->status) {
            PaymentStatus::Verified => 'M-Pesa confirmed the payment. '.$this->confirmedName($payment).' now confirmed.',
            PaymentStatus::Failed => 'M-Pesa has no record of this payment. The participant can pay again.',
            default => 'M-Pesa has not answered yet. Try again later, or settle it from the M-Pesa statement.',
        });
    }

    /** Finance found the payment on the M-Pesa statement. */
    public function mpesaReceived(Request $request, Payment $payment, MpesaPaymentService $mpesa): RedirectResponse
    {
        $data = $request->validate([
            'mpesa_transaction' => ['required', 'string', 'regex:/^[A-Za-z0-9]{6,20}$/'],
        ], ['mpesa_transaction.regex' => 'Enter the transaction ID exactly as it appears on the statement, for example QX7TD52KHB.']);

        try {
            $mpesa->markReceived($payment, $request->user(), $data['mpesa_transaction']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['mpesa_transaction' => $e->getMessage()]);
        }

        return back()->with('status', 'Payment recorded. '.$this->confirmedName($payment).' now confirmed.');
    }

    /** Finance found no such payment on the M-Pesa statement. */
    public function mpesaNotReceived(Request $request, Payment $payment, MpesaPaymentService $mpesa): RedirectResponse
    {
        $data = $request->validate(['mpesa_reason' => ['required', 'string', 'min:5', 'max:255']]);

        try {
            $mpesa->markNotReceived($payment, $request->user(), $data['mpesa_reason']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['mpesa_reason' => $e->getMessage()]);
        }

        return back()->with('status', 'Marked as not received. The participant can pay again.');
    }

    /** "Neema Mushi is", or "Everyone in KCMC Physiotherapy is". */
    private function confirmedName(Payment $payment): string
    {
        return $payment->registration ? $payment->registration->user->name.' is' : 'Everyone in '.$payment->group->name.' is';
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
