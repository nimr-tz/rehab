<?php

namespace App\Payments;

use App\Models\GroupRegistration;
use App\Models\PaymentTransaction;
use App\Models\SponsorPayment;
use App\Models\User;
use App\Payments\Contracts\Payable;
use App\Services\EmailNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Records bank transfer and mobile money payments and moves payables through
 * pending → submitted → verified (or back to pending when rejected).
 */
class PaymentService
{
    public function __construct(
        protected PaymentOptions $options,
        protected EmailNotificationService $emails,
    ) {}

    /**
     * A payer submits the details of a payment they have made.
     *
     * @param  array{method:string, provider?:?string, external_reference:string, amount?:?float, payer_name?:?string, payer_phone?:?string, paid_on?:?string}  $data
     */
    public function submit(Payable $payable, array $data, ?UploadedFile $proof, ?User $submittedBy): PaymentTransaction
    {
        if ($payable->isPaymentSettled()) {
            throw new PaymentException('This registration is already paid.');
        }

        if ($payable->transactionAwaitingReview()) {
            throw new PaymentException('A payment is already awaiting review by the finance team.');
        }

        $transaction = $this->createTransaction($payable, $data, $proof, $submittedBy, PaymentTransaction::STATUS_SUBMITTED);

        $this->markSubmitted($payable, $transaction);
        $this->notifySubmitted($payable);

        return $transaction;
    }

    /**
     * Finance records a payment it has already confirmed (e.g. a sponsor's
     * bank transfer seen on the statement) and verifies it in one step.
     */
    public function recordVerified(Payable $payable, array $data, ?UploadedFile $proof, User $officer, ?string $notes = null): PaymentTransaction
    {
        if ($payable->isPaymentSettled()) {
            throw new PaymentException('This payment is already settled.');
        }

        $transaction = $this->createTransaction($payable, $data, $proof, $officer, PaymentTransaction::STATUS_SUBMITTED);

        return $this->verify($transaction, $officer, $notes);
    }

    public function verify(PaymentTransaction $transaction, User $officer, ?string $notes = null): PaymentTransaction
    {
        if ($transaction->status === PaymentTransaction::STATUS_VERIFIED) {
            return $transaction;
        }

        DB::transaction(function () use ($transaction, $officer, $notes) {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_VERIFIED,
                'reviewed_at' => now(),
                'reviewed_by' => $officer->id,
                'review_notes' => $notes,
            ]);

            $this->markVerified($transaction->payable, $transaction, $officer, $notes);
        });

        return $transaction->fresh();
    }

    public function reject(PaymentTransaction $transaction, User $officer, string $reason): PaymentTransaction
    {
        if ($transaction->status !== PaymentTransaction::STATUS_SUBMITTED) {
            throw new PaymentException('Only payments awaiting review can be rejected.');
        }

        $transaction->update([
            'status' => PaymentTransaction::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $officer->id,
            'review_notes' => $reason,
        ]);

        $payable = $transaction->payable;
        // Back to pending so the payer can submit corrected details.
        $payable->forceFill(['payment_status' => 'pending'])->save();

        match (true) {
            $payable instanceof User => $this->emails->sendPaymentRejected($payable, $reason),
            $payable instanceof GroupRegistration => $this->emails->sendGroupPaymentRejected($payable, $reason),
            default => null,
        };

        return $transaction->fresh();
    }

    public function proofDisk(): string
    {
        return config('payments.proof_disk', 'local');
    }

    /**
     * Mark group members (and any matching user accounts) as registered once
     * the group's payment has cleared.
     *
     * @return array{members:int, synced_users:int, missing_users:int}
     */
    public function activateGroupMembers(GroupRegistration $group): array
    {
        $group->loadMissing(['members', 'leader']);

        $syncedUsers = 0;
        $missingUsers = 0;

        foreach ($group->members as $member) {
            if (! $member->qr_token) {
                $member->generateQrToken();
            }

            $email = strtolower(trim((string) $member->email));
            $user = $email !== '' ? User::whereRaw('LOWER(email) = ?', [$email])->first() : null;

            if (! $user) {
                $missingUsers++;

                continue;
            }

            $updates = [
                'registration_category' => $member->registration_category,
                'payment_status' => 'verified',
                'payment_verified_at' => $group->payment_verified_at ?: now(),
                'payment_notes' => 'Verified via group registration: '.($group->group_name ?: 'Group #'.$group->id),
                'qr_code_token' => $member->qr_token,
            ];

            foreach (['phone' => 'phone', 'institution' => 'affiliation', 'country' => 'country'] as $from => $to) {
                if ($member->{$from}) {
                    $updates[$to] = $member->{$from};
                }
            }

            if (in_array($member->registration_category, ['student_local', 'student_international'], true)) {
                $updates += [
                    'student_status' => 'yes',
                    'student_document' => $member->student_id_path,
                    'student_verification_status' => 'verified',
                    'student_verified_at' => now(),
                    'student_verification_notes' => 'Verified through group registration Student ID.',
                ];
            }

            $user->update($updates);
            $syncedUsers++;

            try {
                $this->emails->sendPaymentVerified($user);
            } catch (\Throwable $e) {
                Log::warning("Failed to send group member payment email for User {$user->id}: ".$e->getMessage());
            }
        }

        return [
            'members' => $group->members->count(),
            'synced_users' => $syncedUsers,
            'missing_users' => $missingUsers,
        ];
    }

    private function createTransaction(Payable $payable, array $data, ?UploadedFile $proof, ?User $actor, string $status): PaymentTransaction
    {
        $currency = $payable->paymentCurrency();
        $method = $data['method'] ?? null;

        if (! array_key_exists($method, $this->options->methodsFor($currency))) {
            throw new PaymentException('That payment method is not available for '.$currency.' payments.');
        }

        $provider = null;
        if ($method === PaymentTransaction::METHOD_MOBILE_MONEY) {
            $provider = $data['provider'] ?? null;
            if (! array_key_exists((string) $provider, $this->options->mobileMoneyProviders($currency))) {
                throw new PaymentException('Please choose the mobile money operator you paid with.');
            }
        }

        $reference = trim((string) ($data['external_reference'] ?? ''));
        if ($reference === '') {
            throw new PaymentException('Please enter the transaction reference from your bank slip or mobile money message.');
        }

        if (! $proof && $this->options->requiresProof($method)) {
            throw new PaymentException('Please upload proof of payment (bank slip or transfer confirmation).');
        }

        $payable->ensurePaymentReference();

        $transaction = $payable->paymentTransactions()->make([
            'method' => $method,
            'provider' => $provider,
            'amount' => $data['amount'] ?? $payable->paymentAmount(),
            'currency' => $currency,
            'payer_name' => $data['payer_name'] ?? null,
            'payer_phone' => $data['payer_phone'] ?? null,
            'external_reference' => $reference,
            'paid_on' => $data['paid_on'] ?? null,
            'status' => $status,
            'submitted_at' => now(),
            'submitted_by' => $actor?->id,
        ]);

        if ($proof) {
            $transaction->proof_path = $proof->store('payment-proofs/'.now()->format('Y/m'), $this->proofDisk());
        }

        $transaction->save();

        return $transaction;
    }

    private function markSubmitted(Payable $payable, PaymentTransaction $transaction): void
    {
        $updates = ['payment_status' => 'submitted'];

        if ($payable instanceof User) {
            $updates['payment_method'] = $transaction->method;
        }
        if ($payable instanceof GroupRegistration) {
            $updates['payment_submitted_at'] = now();
        }

        $payable->forceFill($updates)->save();
    }

    private function markVerified(Payable $payable, PaymentTransaction $transaction, User $officer, ?string $notes): void
    {
        if ($payable instanceof User) {
            $payable->forceFill([
                'payment_status' => 'verified',
                'payment_method' => $transaction->method,
                'payment_verified_at' => now(),
                'payment_verified_by' => $officer->id,
                'payment_notes' => $notes,
            ])->save();
            $payable->generateQrToken();
            $this->emails->sendPaymentVerified($payable, $notes);

            return;
        }

        if ($payable instanceof GroupRegistration) {
            $payable->forceFill([
                'payment_status' => 'verified',
                'payment_verified_at' => now(),
                'verified_by' => $officer->id,
                'admin_notes' => $notes,
            ])->save();
            $this->activateGroupMembers($payable->fresh(['members', 'leader']));
            $this->emails->sendGroupPaymentVerified($payable, $notes);

            return;
        }

        if ($payable instanceof SponsorPayment) {
            $payable->forceFill([
                'payment_status' => 'verified',
                'payment_verified_at' => now(),
                'payment_verified_by' => $officer->id,
                'paid_amount' => $transaction->amount,
                'paid_currency' => $transaction->currency,
                'paid_at' => $transaction->paid_on ?? now(),
                'payment_notes' => $notes,
            ])->save();
        }
    }

    private function notifySubmitted(Payable $payable): void
    {
        try {
            if ($payable instanceof User) {
                $this->emails->sendPaymentSubmissionConfirmation($payable);
                $this->emails->notifyFinanceOfPaymentSubmission($payable);
            } elseif ($payable instanceof GroupRegistration) {
                $this->emails->notifyFinanceOfGroupPaymentSubmission($payable);
            }
        } catch (\Throwable $e) {
            Log::warning('Payment submission notification failed: '.$e->getMessage());
        }
    }

    /**
     * Stream a proof-of-payment file (callers must authorise access first).
     */
    public function downloadProof(PaymentTransaction $transaction)
    {
        $disk = Storage::disk($this->proofDisk());

        abort_unless($transaction->proof_path && $disk->exists($transaction->proof_path), 404);

        return $disk->download($transaction->proof_path, 'payment-proof-'.$transaction->id.'.'.pathinfo($transaction->proof_path, PATHINFO_EXTENSION));
    }
}
