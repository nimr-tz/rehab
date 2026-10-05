<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\RegistrationCategory;
use App\Models\User;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentRejected;
use App\Notifications\RegistrationConfirmed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Registration and payment. Payments are high-risk: every state change goes
 * through here, inside a transaction, with a row lock on the payment.
 */
class RegistrationService
{
    /**
     * @param  array{badge_name?: ?string, needs_invitation_letter?: bool, passport_number?: ?string, dietary_needs?: ?string, accessibility_needs?: ?string}  $details
     */
    public function register(User $user, Edition $edition, RegistrationCategory $category, array $details = []): Registration
    {
        if ($category->edition_id !== $edition->id || ! $category->hasFee()) {
            throw new InvalidArgumentException('This category is not available.');
        }

        return DB::transaction(function () use ($user, $edition, $category, $details) {
            $registration = Registration::create([
                'edition_id' => $edition->id,
                'user_id' => $user->id,
                'registration_category_id' => $category->id,
                'reference' => 'pending-'.Str::random(12),
                'currency' => $category->currency,
                'amount' => $category->amount,
                'status' => RegistrationStatus::PendingPayment,
                'badge_name' => $details['badge_name'] ?? null,
                'needs_invitation_letter' => (bool) ($details['needs_invitation_letter'] ?? false),
                'passport_number' => $details['passport_number'] ?? null,
                'dietary_needs' => $details['dietary_needs'] ?? null,
                'accessibility_needs' => $details['accessibility_needs'] ?? null,
                'qr_token' => Str::random(32),
            ]);

            // RH27-000123: derived from the id so it is unique and never reused.
            $registration->update([
                'reference' => sprintf('%s%s-%06d', config('payments.reference_prefix'), $edition->shortYear(), $registration->id),
            ]);

            return $registration;
        });
    }

    /**
     * @param  array{method: string, provider?: ?string, transaction_reference: string, payer_name: string, payer_phone?: ?string, paid_on: string}  $data
     */
    public function submitPayment(Registration $registration, array $data, ?UploadedFile $proof): Payment
    {
        $payment = DB::transaction(function () use ($registration, $data, $proof) {
            $registration = Registration::lockForUpdate()->findOrFail($registration->id);

            if (! $registration->canSubmitPayment()) {
                throw new InvalidArgumentException('A payment cannot be submitted for this registration right now.');
            }

            $payment = $registration->payments()->create([
                'method' => PaymentMethod::from($data['method']),
                'provider' => $data['method'] === PaymentMethod::MobileMoney->value ? $data['provider'] : null,
                'currency' => $registration->currency,
                'amount' => $registration->amount,
                'transaction_reference' => trim($data['transaction_reference']),
                'payer_name' => trim($data['payer_name']),
                'payer_phone' => $data['payer_phone'] ?? null,
                'paid_on' => $data['paid_on'],
                'proof_path' => $proof?->store('payment-proofs', config('payments.proof_disk')),
                'status' => PaymentStatus::Submitted,
            ]);

            $registration->update(['status' => RegistrationStatus::PaymentSubmitted]);

            return $payment;
        });

        $registration->user->notify(new PaymentReceived($payment));

        return $payment;
    }

    public function verify(Payment $payment, User $officer): void
    {
        DB::transaction(function () use ($payment, $officer) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $this->assertPending($payment);

            $payment->update([
                'status' => PaymentStatus::Verified,
                'reviewed_by' => $officer->id,
                'reviewed_at' => now(),
            ]);

            $payment->registration->update([
                'status' => RegistrationStatus::Confirmed,
                'confirmed_at' => now(),
            ]);
        });

        $payment->registration->user->notify(new RegistrationConfirmed($payment->registration->fresh()));
    }

    public function reject(Payment $payment, User $officer, string $reason): void
    {
        DB::transaction(function () use ($payment, $officer, $reason) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $this->assertPending($payment);

            $payment->update([
                'status' => PaymentStatus::Rejected,
                'reviewed_by' => $officer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            // The participant can submit a corrected payment.
            $payment->registration->update(['status' => RegistrationStatus::PendingPayment]);
        });

        $payment->registration->user->notify(new PaymentRejected($payment->fresh()));
    }

    private function assertPending(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::Submitted) {
            throw new InvalidArgumentException('This payment has already been reviewed.');
        }
    }
}
