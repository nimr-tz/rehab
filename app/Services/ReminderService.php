<?php

namespace App\Services;

use App\Enums\AbstractStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\StudentProofStatus;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\Registration;
use App\Models\ReviewAssignment;
use App\Notifications\PaymentReminder;
use App\Notifications\ReviewReminder;
use App\Notifications\RevisionReminder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Reminders the portal sends on its own. Each kind goes out once per review,
 * abstract or registration; `sent_reminders` remembers what was sent.
 */
class ReminderService
{
    /** Send every reminder that is due for the current edition. Returns how many went out. */
    public function sendDue(): int
    {
        $edition = Edition::current();

        if (! $edition) {
            return 0;
        }

        return $this->reviews($edition) + $this->revisions($edition) + $this->payments($edition);
    }

    private function reviews(Edition $edition): int
    {
        $soon = today()->addDays(config('background.remind_days_before'));

        return ReviewAssignment::query()
            ->whereNull('completed_at')
            ->whereNotNull('due_on')
            ->whereDate('due_on', '<=', $soon)
            ->whereHas('abstract', fn ($q) => $q->where('edition_id', $edition->id))
            ->with('abstract.topic', 'reviewer')
            ->get()
            ->filter->isOpen()
            ->sum(function (ReviewAssignment $assignment) {
                $overdue = $assignment->due_on->lt(today());

                return $this->once($overdue ? 'review_overdue' : 'review_due', $assignment,
                    fn () => $assignment->reviewer->notify(new ReviewReminder($assignment, $overdue)));
            });
    }

    private function revisions(Edition $edition): int
    {
        return AbstractSubmission::query()
            ->where('edition_id', $edition->id)
            ->where('status', AbstractStatus::RevisionRequested)
            ->whereDate('revision_due_on', '>=', today())
            ->whereDate('revision_due_on', '<=', today()->addDays(config('background.remind_days_before')))
            ->with('submitter')
            ->get()
            ->sum(fn (AbstractSubmission $abstract) => $this->once('revision_due', $abstract,
                fn () => $abstract->submitter->notify(new RevisionReminder($abstract))));
    }

    /** Fees still unpaid a week after registering, and again two weeks before the summit. */
    private function payments(Edition $edition): int
    {
        $unpaid = fn () => Registration::query()
            ->where('edition_id', $edition->id)
            ->where('status', RegistrationStatus::PendingPayment)
            ->whereDoesntHave('payments', fn ($q) => $q->whereIn('status', [PaymentStatus::Submitted, PaymentStatus::Pending]))
            // A student whose proof is not yet approved cannot pay, so is not chased.
            ->where(fn ($q) => $q->whereNull('student_status')->orWhere('student_status', StudentProofStatus::Approved))
            ->with('user');

        $sent = $unpaid()
            ->where('created_at', '<=', now()->subDays(config('background.unpaid_after_days')))
            ->get()
            ->sum(fn (Registration $registration) => $this->once('payment_unpaid', $registration,
                fn () => $registration->user->notify(new PaymentReminder($registration))));

        $start = $edition->start_date;
        if ($start && today()->lt($start) && today()->gte($start->copy()->subDays(config('background.unpaid_before_summit_days')))) {
            $sent += $unpaid()
                ->get()
                ->sum(fn (Registration $registration) => $this->once('payment_before_summit', $registration,
                    fn () => $registration->user->notify(new PaymentReminder($registration, beforeSummit: true))));
        }

        return $sent;
    }

    /** Send a reminder unless this kind already went out for this record. Returns 1 when sent. */
    private function once(string $kind, Model $remindable, callable $send): int
    {
        try {
            DB::table('sent_reminders')->insert([
                'kind' => $kind,
                'remindable_type' => $remindable->getMorphClass(),
                'remindable_id' => $remindable->getKey(),
                'sent_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return 0;
        }

        $send();

        return 1;
    }
}
