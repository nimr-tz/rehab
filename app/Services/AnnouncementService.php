<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\Audience;
use App\Enums\RegistrationStatus;
use App\Models\AbstractSubmission;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Edition;
use App\Models\Registration;
use App\Models\SessionRoleApplication;
use App\Models\User;
use App\Notifications\AnnouncementMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Announcements by email. Sending fixes the recipient list; the emails then go
 * out in batches from the background work (production has no queue worker),
 * so a large audience never holds up a page.
 */
class AnnouncementService
{
    /**
     * The people an audience reaches now, one per email address.
     *
     * @return Collection<string, array{user_id: ?int, email: string, name: string}> keyed by email
     */
    public function recipients(Edition $edition, Audience $audience): Collection
    {
        $fromUsers = fn ($users) => collect($users)->map(fn (User $u) => ['user_id' => $u->id, 'email' => Str::lower($u->email), 'name' => $u->first_name]);
        $registered = fn (?callable $scope = null) => User::whereIn('id', Registration::where('edition_id', $edition->id)->when($scope, $scope)->select('user_id'))->get();

        $people = match ($audience) {
            Audience::Registered => $fromUsers($registered()),
            Audience::Confirmed => $fromUsers($registered(fn ($q) => $q->where('status', RegistrationStatus::Confirmed))),
            Audience::Unpaid => $fromUsers($registered(fn ($q) => $q->whereIn('status', [RegistrationStatus::PendingPayment, RegistrationStatus::PaymentSubmitted]))),
            Audience::Authors => $fromUsers(User::whereIn('id', AbstractSubmission::where('edition_id', $edition->id)->whereNotNull('submitted_at')->select('user_id'))->get()),
            Audience::Presenters => app(PresenterService::class)->rows($edition)->filter(fn ($row) => $row['email'])
                ->map(fn ($row) => ['user_id' => $row['user']?->id, 'email' => $row['email'], 'name' => $row['user']?->first_name ?? $row['name']]),
            Audience::Reviewers => $fromUsers(User::role('reviewer')->get()),
            Audience::SessionRoles => $fromUsers(User::whereIn('id', SessionRoleApplication::where('edition_id', $edition->id)->where('status', ApplicationStatus::Accepted)->select('user_id'))->get()),
            Audience::Accounts => $fromUsers(User::whereNotNull('email_verified_at')->get()),
        };

        return $people->keyBy('email');
    }

    /** Fix the recipients and start sending. */
    public function send(Announcement $announcement): int
    {
        return DB::transaction(function () use ($announcement) {
            $announcement = Announcement::lockForUpdate()->findOrFail($announcement->id);

            if (! $announcement->isDraft()) {
                throw new InvalidArgumentException('This announcement has already been sent.');
            }

            $recipients = $this->recipients($announcement->edition, $announcement->audience);
            if ($recipients->isEmpty()) {
                throw new InvalidArgumentException('Nobody is in this audience yet.');
            }

            foreach ($recipients->chunk(500) as $chunk) {
                AnnouncementRecipient::insert($chunk->map(fn ($r) => $r + ['announcement_id' => $announcement->id])->values()->all());
            }

            $announcement->update(['queued_at' => now()]);

            return $recipients->count();
        });
    }

    /** Email a test copy to the author, before sending. */
    public function test(Announcement $announcement, User $to): void
    {
        Notification::route('mail', $to->email)->notify(new AnnouncementMessage($announcement, $to->first_name, test: true));
    }

    /** Send the next batch of every announcement being sent. Returns how many emails went out. */
    public function sendBatch(int $size = 50): int
    {
        $sent = 0;

        foreach (Announcement::whereNotNull('queued_at')->whereNull('finished_at')->oldest('queued_at')->get() as $announcement) {
            $batch = $announcement->recipients()->whereNull('sent_at')->whereNull('failed_at')->with('user')->limit($size - $sent)->get();

            foreach ($batch as $recipient) {
                try {
                    $message = new AnnouncementMessage($announcement, $recipient->name);
                    $recipient->user ? $recipient->user->notify($message) : Notification::route('mail', $recipient->email)->notify($message);
                    $recipient->update(['sent_at' => now()]);
                    $sent++;
                } catch (Throwable $e) {
                    report($e);
                    $recipient->update(['failed_at' => now(), 'error' => Str::limit($e->getMessage(), 250)]);
                }
            }

            if (! $announcement->recipients()->whereNull('sent_at')->whereNull('failed_at')->exists()) {
                $announcement->update(['finished_at' => now()]);
            }

            if ($sent >= $size) {
                break;
            }
        }

        return $sent;
    }

    /** Try the failed emails of an announcement again. */
    public function retryFailed(Announcement $announcement): int
    {
        $count = $announcement->recipients()->whereNotNull('failed_at')->update(['failed_at' => null, 'error' => null]);

        if ($count > 0) {
            $announcement->update(['finished_at' => null]);
        }

        return $count;
    }
}
