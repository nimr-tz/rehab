<?php

namespace App\Services;

use App\Enums\AbstractStatus;
use App\Enums\RegistrationStatus;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\PresenterRegistrationReminder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Who presents each accepted abstract, and whether they have registered and
 * paid. A presenter is matched to an account by the email given for them,
 * or is the submitting author when no other presenter email was given.
 */
class PresenterService
{
    public const NOT_REGISTERED = 'not_registered';

    public const UNPAID = 'unpaid';

    public const VERIFYING = 'verifying';

    public const CONFIRMED = 'confirmed';

    public const STATES = [
        self::NOT_REGISTERED => ['Not registered', 'danger'],
        self::UNPAID => ['Registered, not paid', 'warning'],
        self::VERIFYING => ['Payment being verified', 'info'],
        self::CONFIRMED => ['Confirmed', 'success'],
    ];

    /**
     * @return Collection<int, array{abstract: AbstractSubmission, name: string, email: ?string, user: ?User, registration: ?Registration, state: string}>
     */
    public function rows(Edition $edition): Collection
    {
        $abstracts = AbstractSubmission::where('edition_id', $edition->id)
            ->where('status', AbstractStatus::Accepted)
            ->with('authors', 'submitter', 'topic', 'sessions')
            ->orderBy('code')
            ->get();

        $emails = $abstracts->map(fn (AbstractSubmission $a) => $this->email($a))->filter()->unique();
        $users = User::whereIn('email', $emails)->get()->keyBy(fn (User $u) => Str::lower($u->email));
        $registrations = Registration::where('edition_id', $edition->id)->whereIn('user_id', $users->pluck('id'))->get()->keyBy('user_id');

        return $abstracts->map(function (AbstractSubmission $abstract) use ($users, $registrations) {
            $email = $this->email($abstract);
            $user = $email ? $users->get($email) : null;
            $registration = $user ? $registrations->get($user->id) : null;

            return [
                'abstract' => $abstract,
                'name' => $abstract->presenter()?->name ?? $abstract->submitter->name,
                'email' => $email,
                'user' => $user,
                'registration' => $registration,
                'state' => match (true) {
                    ! $registration => self::NOT_REGISTERED,
                    $registration->status === RegistrationStatus::Confirmed => self::CONFIRMED,
                    $registration->status === RegistrationStatus::PaymentSubmitted => self::VERIFYING,
                    default => self::UNPAID,
                },
            ];
        });
    }

    /** Remind the presenter to register and pay. Returns false when there is nobody to email. */
    public function remind(array $row): bool
    {
        if (! $row['email'] || $row['state'] === self::CONFIRMED) {
            return false;
        }

        $notification = new PresenterRegistrationReminder($row['abstract'], $row['name'], $row['state']);
        $row['user'] ? $row['user']->notify($notification) : Notification::route('mail', $row['email'])->notify($notification);

        $row['abstract']->update(['presenter_reminded_at' => now()]);

        return true;
    }

    /** The presenter's email: the author marked as presenter, or the submitter. */
    private function email(AbstractSubmission $abstract): ?string
    {
        $email = $abstract->presenter()?->email ?: $abstract->submitter->email;

        return $email ? Str::lower($email) : null;
    }
}
