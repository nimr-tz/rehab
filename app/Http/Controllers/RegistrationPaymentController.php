<?php

namespace App\Http\Controllers;

use App\Models\GroupRegistration;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Payments\PaymentException;
use App\Payments\PaymentOptions;
use App\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RegistrationPaymentController extends Controller
{
    public function __construct(
        protected PaymentService $payments,
        protected PaymentOptions $options,
    ) {}

    /**
     * Payment page: fee, payment reference, where to pay and the form to
     * submit the transaction details.
     */
    public function show()
    {
        $user = Auth::user();
        $groupAsLeader = $this->pendingLeadGroup($user);
        $payable = $groupAsLeader ?? $user;

        $amount = $payable->paymentAmount();
        $currency = $payable->paymentCurrency();
        $reference = $amount > 0 && ! $payable->isPaymentSettled() ? $payable->ensurePaymentReference() : $payable->payment_reference;

        return view('user.payment', [
            'user' => $user,
            'groupAsLeader' => $groupAsLeader,
            'payable' => $payable,
            'amount' => $amount,
            'currency' => $currency,
            'reference' => $reference,
            'feeLabel' => $groupAsLeader ? $groupAsLeader->paymentDescription() : ($user->getRegistrationFee()['label'] ?? null),
            'isSettled' => $groupAsLeader ? $groupAsLeader->isPaymentSettled() : $user->isPaid(),
            'awaitingReview' => $payable->transactionAwaitingReview(),
            'transactions' => $payable->paymentTransactions()->get(),
            'methods' => $this->options->methodsFor($currency),
            'bankAccount' => $this->options->bankAccount($currency),
            'mobileMoneyProviders' => $this->options->mobileMoneyProviders($currency),
            'studentPending' => $user->student_status === 'yes' && $user->student_verification_status !== 'verified',
        ]);
    }

    /**
     * Payer submits the details of a bank transfer or mobile money payment.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $payable = $this->pendingLeadGroup($user) ?? $user;

        if ($payable instanceof User && $user->student_status === 'yes' && $user->student_verification_status !== 'verified') {
            return back()->with('error', 'Student verification is required before you can pay the student fee.');
        }

        if ($payable->paymentAmount() <= 0) {
            return back()->with('error', 'Registration fees have not been published yet. Please check back soon.');
        }

        $currency = $payable->paymentCurrency();

        $validated = $request->validate([
            'method' => ['required', Rule::in(array_keys($this->options->methodsFor($currency)))],
            'provider' => ['nullable', 'required_if:method,'.PaymentTransaction::METHOD_MOBILE_MONEY, Rule::in(array_keys($this->options->mobileMoneyProviders($currency)))],
            'external_reference' => ['required', 'string', 'max:100'],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'payer_phone' => ['nullable', 'string', 'max:30'],
            'paid_on' => ['nullable', 'date', 'before_or_equal:today'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        try {
            $this->payments->submit($payable, $validated, $request->file('proof'), $user);
        } catch (PaymentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('payment.show')
            ->with('success', 'Thank you. Your payment details have been sent to the finance team for verification. You will receive an email once it is confirmed.');
    }

    /**
     * Let payers re-open the proof they uploaded.
     */
    public function proof(PaymentTransaction $transaction)
    {
        $user = Auth::user();
        $payable = $transaction->payable;

        $owns = ($payable instanceof User && $payable->is($user))
            || ($payable instanceof GroupRegistration && (int) $payable->leader_user_id === (int) $user->id);

        abort_unless($owns, 403);

        return $this->payments->downloadProof($transaction);
    }

    private function pendingLeadGroup(User $user): ?GroupRegistration
    {
        return GroupRegistration::where('leader_user_id', $user->id)
            ->whereIn('payment_status', ['pending', 'submitted', 'rejected', 'verified', 'waived'])
            ->with('members')
            ->latest('id')
            ->first();
    }
}
