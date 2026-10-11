<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\AbstractStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\StudentProofStatus;
use App\Notifications\RegistrationConfirmed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Registration extends Model implements Payable
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'amount' => 'decimal:2',
            'waived_amount' => 'decimal:2',
            'needs_invitation_letter' => 'boolean',
            'confirmed_at' => 'datetime',
            'badge_printed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'student_status' => StudentProofStatus::class,
            'student_reviewed_at' => 'datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RegistrationCategory::class, 'registration_category_id');
    }

    /** The group that registered and pays for this person, if any. */
    public function group(): BelongsTo
    {
        return $this->belongsTo(GroupRegistration::class, 'group_registration_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(FeedbackResponse::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isConfirmed(): bool
    {
        return $this->status === RegistrationStatus::Confirmed;
    }

    /**
     * A new payment can be submitted unless one is pending, the fee is settled,
     * a student proof is still to be approved, or a group pays for this person.
     */
    public function canSubmitPayment(): bool
    {
        return $this->status === RegistrationStatus::PendingPayment
            && ! $this->group_registration_id
            && ! $this->awaitsStudentApproval()
            && ! $this->payments()->whereIn('status', [PaymentStatus::Submitted, PaymentStatus::Pending])->exists();
    }

    /** A student rate whose proof of enrolment has not been approved: nothing can be paid yet. */
    public function awaitsStudentApproval(): bool
    {
        return in_array($this->student_status, [StudentProofStatus::Pending, StudentProofStatus::Rejected], true);
    }

    /**
     * The category can change while nothing has been paid or sent for
     * verification, and no waiver applies.
     */
    public function canChangeCategory(): bool
    {
        return $this->status === RegistrationStatus::PendingPayment
            && ! $this->group_registration_id
            && ! $this->isWaived()
            && ! $this->payments()->whereIn('status', [PaymentStatus::Submitted, PaymentStatus::Pending, PaymentStatus::Verified])->exists();
    }

    public function studentReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_reviewed_by');
    }

    /** An M-Pesa payment still waiting for the participant's PIN or M-Pesa's answer. */
    public function pendingGatewayPayment(): ?Payment
    {
        return $this->payments()->where('status', PaymentStatus::Pending)->latest('id')->first();
    }

    public function waivers(): HasMany
    {
        return $this->hasMany(FeeWaiver::class)->latest('id');
    }

    public function activeWaiver(): HasOne
    {
        return $this->hasOne(FeeWaiver::class)->whereNull('revoked_at')->latestOfMany();
    }

    /** The fee of the category, before any waiver. */
    public function formattedAmount(): string
    {
        return $this->currency.' '.number_format((float) $this->amount);
    }

    /** What the participant still has to pay: the fee less any waiver. */
    public function amountDue(): float
    {
        return max(0, round((float) $this->amount - (float) $this->waived_amount, 2));
    }

    public function formattedDue(): string
    {
        return $this->currency.' '.number_format($this->amountDue());
    }

    public function isWaived(): bool
    {
        return (float) $this->waived_amount > 0;
    }

    public function isFullyWaived(): bool
    {
        return $this->isWaived() && $this->amountDue() <= 0;
    }

    public function payer(): User
    {
        return $this->user;
    }

    public function payableLabel(): string
    {
        return $this->category->name;
    }

    public function paymentUrl(): string
    {
        return route('registration.show');
    }

    public function markPaymentSubmitted(): void
    {
        $this->update(['status' => RegistrationStatus::PaymentSubmitted]);
    }

    public function markPaid(): void
    {
        $this->update(['status' => RegistrationStatus::Confirmed, 'confirmed_at' => now()]);
    }

    public function markUnpaid(): void
    {
        $this->update(['status' => RegistrationStatus::PendingPayment]);
    }

    public function notifyPaid(): void
    {
        $this->user->notify(new RegistrationConfirmed($this->fresh()));
    }

    /** Attended the summit: confirmed, and checked in at the desk or scanned at a session. */
    public function attended(): bool
    {
        return $this->isConfirmed() && ($this->checked_in_at || $this->attendances()->exists());
    }

    /** The certificate of attendance can be downloaded once the summit is over. */
    public function hasAttendanceCertificate(): bool
    {
        $end = $this->edition->end_date;

        return $end && today()->gt($end) && $this->attended();
    }

    /** "AT27-000123" */
    public function certificateNumber(): string
    {
        return 'AT'.$this->edition->shortYear().'-'.Str::padLeft((string) $this->id, 6, '0');
    }

    /** Printed on the certificate, so anyone can check it is genuine. */
    public function certificateCode(): string
    {
        return substr(hash_hmac('sha256', 'attendance-certificate:'.$this->id, (string) config('app.key')), 0, 12);
    }

    public function displayName(): string
    {
        return $this->badge_name ?: $this->user->name;
    }

    /**
     * Change the name printed on the badge. A badge already printed goes back
     * into the desk's print queue, unless its owner has collected it.
     */
    public function renameBadge(?string $name): void
    {
        $name = filled($name) ? trim($name) : null;

        if ($name === $this->badge_name) {
            return;
        }

        $this->update(['badge_name' => $name] + ($this->checked_in_at ? [] : ['badge_printed_at' => null, 'badge_batch' => null]));
    }

    /** The ribbon on the badge: presenters of an accepted abstract, students, everyone else. */
    public function badgeRole(): string
    {
        $presenting = $this->user->abstracts()
            ->where('edition_id', $this->edition_id)
            ->where('status', AbstractStatus::Accepted)
            ->exists();

        return match (true) {
            $presenting => 'Presenter',
            (bool) $this->category?->is_student => 'Student',
            default => 'Participant',
        };
    }
}
