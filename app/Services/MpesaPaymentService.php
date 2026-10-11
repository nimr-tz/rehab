<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\RegistrationConfirmed;
use App\Services\Mpesa\MpesaClient;
use App\Services\Mpesa\MpesaResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Registration fees paid with an M-Pesa prompt on the participant's phone.
 *
 * The payment is saved as pending before M-Pesa is called, so it is never lost:
 * if the call is cut off or its result is unclear, it stays pending and is
 * settled later by asking M-Pesa for its status. A confirmed payment verifies
 * itself and confirms the registration, with no finance officer involved.
 */
class MpesaPaymentService
{
    // Results that do not tell us whether the money moved: ask M-Pesa later.
    private const UNCLEAR = ['INS-1', 'INS-9'];

    // How long M-Pesa may take to know about a payment before "not found" means it never happened.
    private const NOT_FOUND_AFTER_MINUTES = 5;

    // How long a payment stays pending before finance may settle it from the M-Pesa statement.
    public const SETTLE_BY_HAND_AFTER_MINUTES = 10;

    public function __construct(private MpesaClient $mpesa) {}

    public static function availableFor(?Registration $registration): bool
    {
        return config('mpesa.enabled')
            && filled(config('mpesa.api_key'))
            && $registration?->currency === config('mpesa.currency')
            && $registration->status === RegistrationStatus::PendingPayment
            && ! $registration->awaitsStudentApproval()
            && $registration->amountDue() > 0
            && self::offeredTo($registration);
    }

    /**
     * Whether this participant may pay by M-Pesa at all. MPESA_ONLY_FOR limits it
     * to named accounts. The sandbox moves no real money, so in production it is
     * only ever offered to those named testers.
     */
    private static function offeredTo(Registration $registration): bool
    {
        $only = config('mpesa.only_for');

        if (app()->isProduction() && config('mpesa.environment') === 'sandbox' && empty($only)) {
            return false;
        }

        return empty($only) || in_array(strtolower($registration->user->email), $only, true);
    }

    /** Valid M-Pesa numbers: Tanzanian mobiles, plus the sandbox's test numbers. */
    public static function acceptsNumber(string $msisdn): bool
    {
        $msisdn = MpesaClient::normalizeMsisdn($msisdn);

        return preg_match('/^255[67]\d{8}$/', $msisdn)
            || (config('mpesa.environment') === 'sandbox' && preg_match('/^0{11}[1-9]$/', $msisdn));
    }

    /** Send the prompt to the participant's phone and record the outcome. $by is the desk officer who sent it, if any. */
    public function start(Registration $registration, string $msisdn, ?User $by = null): Payment
    {
        if (! self::availableFor($registration)) {
            throw new InvalidArgumentException('M-Pesa payments are not available for this registration.');
        }

        $payment = DB::transaction(function () use ($registration, $msisdn, $by) {
            $registration = Registration::lockForUpdate()->findOrFail($registration->id);

            if (! $registration->canSubmitPayment()) {
                throw new InvalidArgumentException('A payment cannot be made for this registration right now.');
            }

            $reference = Str::uuid()->getHex()->toString();

            return $registration->payments()->create([
                'method' => PaymentMethod::MobileMoney,
                'provider' => 'mpesa',
                'gateway' => 'mpesa',
                'gateway_reference' => $reference,
                'currency' => $registration->currency,
                'amount' => $registration->amountDue(),
                // Replaced by M-Pesa's transaction ID once it is paid.
                'transaction_reference' => $reference,
                'payer_name' => $registration->user->name,
                'payer_phone' => MpesaClient::normalizeMsisdn($msisdn),
                'paid_on' => today(),
                'status' => PaymentStatus::Pending,
                'initiated_by' => $by?->id,
            ]);
        });

        try {
            $result = $this->mpesa->c2bPayment(
                $payment->payer_phone,
                number_format((float) $payment->amount, 0, '', ''),
                // M-Pesa allows letters and digits only: RH27-000123 becomes RH27000123.
                preg_replace('/[^A-Za-z0-9]/', '', $registration->reference),
                $payment->gateway_reference,
                'Summit registration '.preg_replace('/[^A-Za-z0-9]/', '', $registration->reference),
            );
        } catch (ConnectionException) {
            // The prompt may or may not have gone out. Ask M-Pesa later.
            $payment->update(['gateway_message' => 'No answer from M-Pesa.', 'gateway_checked_at' => now()]);

            return $payment->fresh();
        } catch (RuntimeException $e) {
            // No session, so no prompt was sent.
            $this->fail($payment, null, $e->getMessage());

            return $payment->fresh();
        }

        $this->settle($payment, $result);

        return $payment->fresh();
    }

    /** Ask M-Pesa what became of a pending payment. */
    public function reconcile(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            return $payment;
        }

        try {
            $result = $this->mpesa->queryTransactionStatus($payment->gateway_reference, $payment->gateway_reference);
        } catch (ConnectionException|RuntimeException) {
            return $payment;
        }

        if ($result->successful() && $result->get('output_ResponseTransactionStatus') === 'Completed') {
            $this->confirm($payment, $result->get('output_OriginalTransactionID'), $result);
        } elseif ($result->code === 'INS-23' && $payment->created_at->lte(now()->subMinutes(self::NOT_FOUND_AFTER_MINUTES))) {
            $this->fail($payment, $result->code, 'M-Pesa has no record of this payment.');
        } else {
            $payment->update(['gateway_checked_at' => now()]);
        }

        return $payment->fresh();
    }

    /** Whether finance may settle a pending payment by hand: M-Pesa has had its chance to answer. */
    public static function canSettleByHand(Payment $payment): bool
    {
        return $payment->status === PaymentStatus::Pending
            && $payment->gateway === 'mpesa'
            && $payment->created_at->lte(now()->subMinutes(self::SETTLE_BY_HAND_AFTER_MINUTES));
    }

    /**
     * Finance found the payment on the M-Pesa statement: it is verified with
     * the statement's transaction ID, and the registration is confirmed.
     */
    public function markReceived(Payment $payment, User $officer, string $transactionId): void
    {
        $transactionId = strtoupper(trim($transactionId));

        $confirmed = DB::transaction(function () use ($payment, $officer, $transactionId) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $this->assertSettleable($payment);

            if (Payment::where('transaction_reference', $transactionId)->where('status', PaymentStatus::Verified)->exists()) {
                throw new InvalidArgumentException('M-Pesa transaction '.$transactionId.' has already been counted for another payment.');
            }

            $payment->update([
                'status' => PaymentStatus::Verified,
                'transaction_reference' => $transactionId,
                'gateway_message' => 'Found on the M-Pesa statement by finance.',
                'reviewed_by' => $officer->id,
                'reviewed_at' => now(),
            ]);

            $payment->registration->update([
                'status' => RegistrationStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            return $payment;
        });

        $confirmed->registration->user->notify(new RegistrationConfirmed($confirmed->registration->fresh()));
    }

    /** Finance found no such payment on the M-Pesa statement: it failed, and the participant can pay again. */
    public function markNotReceived(Payment $payment, User $officer, string $reason): void
    {
        DB::transaction(function () use ($payment, $officer, $reason) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $this->assertSettleable($payment);

            $payment->update([
                'status' => PaymentStatus::Failed,
                'gateway_message' => trim($reason),
                'reviewed_by' => $officer->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    /** Ask M-Pesa again about every payment left pending, oldest first. Run in the background. */
    public function reconcileStale(int $limit = 20): int
    {
        if (! config('mpesa.enabled') || blank(config('mpesa.api_key'))) {
            return 0;
        }

        return Payment::where('status', PaymentStatus::Pending)
            ->where('gateway', 'mpesa')
            ->where('created_at', '<=', now()->subMinutes(2))
            ->where(fn ($q) => $q->whereNull('gateway_checked_at')->orWhere('gateway_checked_at', '<=', now()->subMinutes(2)))
            ->oldest('id')
            ->limit($limit)
            ->get()
            ->each(fn (Payment $payment) => $this->reconcile($payment))
            ->count();
    }

    private function assertSettleable(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::Pending || $payment->gateway !== 'mpesa') {
            throw new InvalidArgumentException('This payment has already been settled.');
        }
        if (! self::canSettleByHand($payment)) {
            throw new InvalidArgumentException('M-Pesa may still answer. Settle it by hand after '.self::SETTLE_BY_HAND_AFTER_MINUTES.' minutes.');
        }
    }

    /**
     * Where a payment stands, for the pages that follow it live.
     *
     * @return array{state: string, message: string, transaction: ?string}
     */
    public static function state(Payment $payment): array
    {
        return [
            'state' => match ($payment->status) {
                PaymentStatus::Verified => 'confirmed',
                PaymentStatus::Pending => 'pending',
                default => 'failed',
            },
            'message' => $payment->status === PaymentStatus::Verified
                ? 'Payment received. The registration is confirmed.'
                : self::message($payment),
            'transaction' => $payment->status === PaymentStatus::Verified ? $payment->transaction_reference : null,
        ];
    }

    /** Ask M-Pesa about a pending payment, at most once every few seconds however often the page asks. */
    public function refresh(Payment $payment): Payment
    {
        if ($payment->status === PaymentStatus::Pending
            && (! $payment->gateway_checked_at || $payment->gateway_checked_at->lte(now()->subSeconds(5)))) {
            return $this->reconcile($payment);
        }

        return $payment;
    }

    /** What to tell the participant about a failed or pending payment. */
    public static function message(Payment $payment): string
    {
        if ($payment->status === PaymentStatus::Pending) {
            return 'We are waiting for M-Pesa to confirm your payment. If you entered your PIN, it will show here shortly.';
        }

        return match (true) {
            $payment->gateway_code === 'INS-6' => 'The payment was not completed. It may have been cancelled or the PIN was wrong. Please try again.',
            $payment->gateway_code === 'INS-2006' => 'Your M-Pesa balance is too low for this payment. Top up and try again, or pay another way.',
            $payment->gateway_code === 'INS-2051' => 'That number is not registered for M-Pesa. Check the number and try again.',
            in_array($payment->gateway_code, ['INS-990', 'INS-991', 'INS-992', 'INS-993', 'INS-994', 'INS-995'], true) => 'This payment is above an M-Pesa limit. Please pay by bank transfer instead.',
            $payment->gateway_code === 'INS-996' => 'M-Pesa payments are not available at this time of day. Please try again later.',
            default => 'M-Pesa could not take this payment. Please try again, or pay another way.',
        };
    }

    private function settle(Payment $payment, MpesaResult $result): void
    {
        match (true) {
            $result->successful() => $this->confirm($payment, $result->get('output_TransactionID'), $result),
            $result->code === null || in_array($result->code, self::UNCLEAR, true) || $result->status >= 500 => $payment->update([
                'gateway_code' => $result->code,
                'gateway_message' => $result->description,
                'gateway_checked_at' => now(),
            ]),
            default => $this->fail($payment, $result->code, $result->description),
        };
    }

    private function confirm(Payment $payment, ?string $transactionId, MpesaResult $result): void
    {
        $confirmed = DB::transaction(function () use ($payment, $transactionId, $result) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::Pending) {
                return false;
            }

            $payment->update([
                'status' => PaymentStatus::Verified,
                'transaction_reference' => $transactionId ?: $payment->transaction_reference,
                'gateway_code' => $result->code,
                'gateway_message' => $result->description,
                'gateway_checked_at' => now(),
                'reviewed_at' => now(),
            ]);

            $payment->registration->update([
                'status' => RegistrationStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            return true;
        });

        if ($confirmed) {
            $payment->registration->user->notify(new RegistrationConfirmed($payment->registration->fresh()));
        }
    }

    private function fail(Payment $payment, ?string $code, ?string $message): void
    {
        DB::transaction(function () use ($payment, $code, $message) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::Pending) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Failed,
                'gateway_code' => $code,
                'gateway_message' => $message,
                'gateway_checked_at' => now(),
            ]);
        });
    }
}
