<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\SessionRole;
use App\Models\Edition;
use App\Models\ProgrammeSession;
use App\Models\SessionRoleApplication;
use App\Models\User;
use App\Notifications\SessionRoleDecided;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Session chairs and rapporteurs. Participants apply; the committee accepts an
 * application by giving it a session (which then names that person as its
 * chair or rapporteur) or declines it. Rapporteurs report on their session.
 */
class SessionRoleService
{
    /** @param  list<int>  $topicIds */
    public function apply(User $user, Edition $edition, SessionRole $role, array $topicIds, string $experience): SessionRoleApplication
    {
        if (! $edition->acceptsSessionRoleApplications()) {
            throw new InvalidArgumentException('Applications to chair or report a session are closed.');
        }
        if (SessionRoleApplication::where(['edition_id' => $edition->id, 'user_id' => $user->id, 'role' => $role])->exists()) {
            throw new InvalidArgumentException('You have already applied to be a '.strtolower($role->label()).'.');
        }

        return SessionRoleApplication::create([
            'edition_id' => $edition->id,
            'user_id' => $user->id,
            'role' => $role,
            'topic_ids' => array_values(array_unique(array_map('intval', $topicIds))),
            'experience' => trim($experience),
            'status' => ApplicationStatus::Pending,
        ]);
    }

    /** The applicant changes their mind, while the committee has not decided. */
    public function withdraw(SessionRoleApplication $application): void
    {
        if ($application->status !== ApplicationStatus::Pending) {
            throw new InvalidArgumentException('The committee has already decided on this application.');
        }

        $application->delete();
    }

    public function accept(SessionRoleApplication $application, ProgrammeSession $session, User $by, ?string $note = null): void
    {
        DB::transaction(function () use ($application, $session, $by, $note) {
            $application = SessionRoleApplication::lockForUpdate()->findOrFail($application->id);
            $session = ProgrammeSession::lockForUpdate()->findOrFail($session->id);

            if ($application->status !== ApplicationStatus::Pending) {
                throw new InvalidArgumentException('This application has already been decided.');
            }
            if ($session->edition_id !== $application->edition_id || ! $session->isScannable()) {
                throw new InvalidArgumentException('Choose a session of this summit (not a break).');
            }

            [$userColumn, $nameColumn] = $this->columns($application->role);
            if ($session->{$userColumn} && $session->{$userColumn} !== $application->user_id) {
                throw new InvalidArgumentException($session->title.' already has a '.strtolower($application->role->label()).'. Release them first.');
            }

            $session->update([$userColumn => $application->user_id, $nameColumn => $application->user->name]);
            $application->update([
                'status' => ApplicationStatus::Accepted,
                'programme_session_id' => $session->id,
                'decided_by' => $by->id,
                'decided_at' => now(),
                'decision_note' => filled($note) ? trim($note) : null,
            ]);
        });

        $application->refresh()->user->notify(new SessionRoleDecided($application));
    }

    public function decline(SessionRoleApplication $application, User $by, ?string $note = null): void
    {
        if ($application->status !== ApplicationStatus::Pending) {
            throw new InvalidArgumentException('This application has already been decided.');
        }

        $application->update([
            'status' => ApplicationStatus::Declined,
            'decided_by' => $by->id,
            'decided_at' => now(),
            'decision_note' => filled($note) ? trim($note) : null,
        ]);

        $application->user->notify(new SessionRoleDecided($application));
    }

    /** Take an accepted person off their session, so the application can be decided again. */
    public function release(SessionRoleApplication $application): void
    {
        DB::transaction(function () use ($application) {
            if ($application->status !== ApplicationStatus::Accepted) {
                throw new InvalidArgumentException('Only an accepted application can be released.');
            }

            [$userColumn, $nameColumn] = $this->columns($application->role);
            $session = $application->session;

            if ($session && $session->{$userColumn} === $application->user_id) {
                $session->update([$userColumn => null, $nameColumn => null]);
            }

            $application->update(['status' => ApplicationStatus::Pending, 'programme_session_id' => null, 'decided_by' => null, 'decided_at' => null, 'decision_note' => null]);
        });
    }

    /** The rapporteur's summary of the session, once it has started. They can revise it. */
    public function report(ProgrammeSession $session, User $rapporteur, string $report): void
    {
        if ($session->rapporteur_user_id !== $rapporteur->id) {
            throw new InvalidArgumentException('Only the rapporteur of this session can report on it.');
        }
        if (! $session->acceptsReport()) {
            throw new InvalidArgumentException('The report can be written once the session has started.');
        }

        $session->update(['rapporteur_report' => trim($report), 'rapporteur_report_at' => now()]);
    }

    /** @return array{0: string, 1: string} */
    private function columns(SessionRole $role): array
    {
        return $role === SessionRole::Chair ? ['chair_user_id', 'chair'] : ['rapporteur_user_id', 'rapporteur'];
    }
}
