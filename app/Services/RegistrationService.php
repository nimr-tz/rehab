<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Enums\WaiverReason;
use App\Models\Edition;
use App\Models\FeeWaiver;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\RegistrationCategory;
use App\Models\User;
use App\Notifications\FeeWaived;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentRejected;
use App\Notifications\RegisteredAtDesk;
use App\Notifications\RegistrationConfirmed;
use App\Notifications\WaiverWithdrawn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Role as RoleModel;

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
     * The desk registers someone at the venue. A new account is created for an
     * unknown email (and emailed a link to set its password); a known email gets
     * its existing account. Either way the registration awaits payment.
     *
     * @param  array{title?: ?string, first_name: string, last_name: string, email: string, phone: string, country: string, institution: string, profession: string, dietary_needs?: ?string, accessibility_needs?: ?string}  $data
     */
    public function registerAtDesk(array $data, Edition $edition, RegistrationCategory $category): Registration
    {
        $email = Str::lower(trim($data['email']));

        [$registration, $created] = DB::transaction(function () use ($data, $email, $edition, $category) {
            $user = User::where('email', $email)->lockForUpdate()->first();
            $created = ! $user;

            if ($user && ($existing = $user->registrationFor($edition))) {
                throw new InvalidArgumentException($user->name.' is already registered, as '.$existing->reference.'.');
            }

            $user ??= User::create([
                'title' => $data['title'] ?? null,
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $email,
                'phone' => trim($data['phone']),
                'country' => $data['country'],
                // Replaced when they set their own from the welcome email.
                'password' => Str::random(40),
            ]);
            $user->update(['institution' => trim($data['institution']), 'profession' => trim($data['profession'])]);
            $user->assignRole(RoleModel::findOrCreate(Role::Participant->value, 'web'));

            return [$this->register($user, $edition, $category, $data), $created];
        });

        if ($created) {
            $registration->user->notify(new RegisteredAtDesk($registration, Password::broker()->createToken($registration->user)));
        }

        return $registration;
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
                // What is due: the fee, less any waiver.
                'amount' => $registration->amountDue(),
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

    /**
     * Waive the whole fee, which confirms the registration at once, or part of
     * it, which leaves the rest to pay. Only while the fee is unpaid and no
     * payment is waiting for verification, and one waiver at a time.
     */
    public function waive(Registration $registration, User $officer, float $amount, WaiverReason $reason, ?string $note = null): FeeWaiver
    {
        $waiver = DB::transaction(function () use ($registration, $officer, $amount, $reason, $note) {
            $registration = Registration::lockForUpdate()->findOrFail($registration->id);
            $amount = round($amount, 2);

            if ($registration->status !== RegistrationStatus::PendingPayment) {
                throw new InvalidArgumentException($registration->status === RegistrationStatus::PaymentSubmitted
                    ? 'A payment is waiting for verification. Verify or reject it first.'
                    : 'Only a registration that is still awaiting payment can have its fee waived.');
            }
            if ($registration->pendingGatewayPayment()) {
                throw new InvalidArgumentException('An M-Pesa payment for this registration is in progress. Wait for it to finish first.');
            }
            if ($registration->isWaived()) {
                throw new InvalidArgumentException('This registration already has a waiver. Withdraw it first to change it.');
            }
            if ($amount <= 0 || $amount > (float) $registration->amount) {
                throw new InvalidArgumentException('The waiver must be more than nothing and no more than the fee of '.$registration->formattedAmount().'.');
            }
            if ($reason === WaiverReason::Other && blank($note)) {
                throw new InvalidArgumentException('Explain the waiver in the note.');
            }

            $waiver = $registration->waivers()->create([
                'currency' => $registration->currency,
                'amount' => $amount,
                'reason' => $reason,
                'note' => filled($note) ? trim($note) : null,
                'granted_by' => $officer->id,
            ]);

            $registration->update(['waived_amount' => $amount]);

            // Nothing left to pay: the registration is confirmed.
            if ($registration->amountDue() <= 0) {
                $registration->update(['status' => RegistrationStatus::Confirmed, 'confirmed_at' => now()]);
            }

            return $waiver->setRelation('registration', $registration);
        });

        $waiver->registration->user->notify(new FeeWaived($waiver));

        return $waiver;
    }

    /**
     * Withdraw a waiver: the full fee is due again. Not once the participant
     * has checked in or paid the rest, and not while a payment is being verified.
     */
    public function withdrawWaiver(FeeWaiver $waiver, User $officer, string $reason): void
    {
        DB::transaction(function () use ($waiver, $officer, $reason) {
            $registration = Registration::lockForUpdate()->findOrFail($waiver->registration_id);
            $waiver = FeeWaiver::lockForUpdate()->findOrFail($waiver->id);

            if (! $waiver->isActive()) {
                throw new InvalidArgumentException('This waiver has already been withdrawn.');
            }
            if ($registration->checked_in_at) {
                throw new InvalidArgumentException('The participant has checked in, so the waiver can no longer be withdrawn.');
            }
            if ($registration->status === RegistrationStatus::PaymentSubmitted) {
                throw new InvalidArgumentException('A payment is waiting for verification. Verify or reject it first.');
            }
            if ($registration->payments()->where('status', PaymentStatus::Verified)->exists()) {
                throw new InvalidArgumentException('The participant has paid the rest of the fee, so the waiver can no longer be withdrawn.');
            }

            $waiver->update(['revoked_at' => now(), 'revoked_by' => $officer->id, 'revoke_reason' => trim($reason)]);
            $registration->update([
                'waived_amount' => 0,
                'status' => RegistrationStatus::PendingPayment,
                'confirmed_at' => null,
            ]);
        });

        $waiver->refresh()->load('registration.user');
        $waiver->registration->user->notify(new WaiverWithdrawn($waiver));
    }

    private function assertPending(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::Submitted) {
            throw new InvalidArgumentException('This payment has already been reviewed.');
        }
    }
}
