<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Edition;
use App\Models\GroupRegistration;
use App\Models\Registration;
use App\Models\RegistrationCategory;
use App\Models\User;
use App\Notifications\RegisteredByGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Group registration. A leader registers several people and pays once for
 * all of them: the sum of each member's category fee, in one currency. Each
 * member gets an ordinary registration (and an account if they have none),
 * paid for by the group. Members can change until a payment is sent.
 *
 * Student rates are not available in a group: they need each student's own
 * proof of enrolment.
 */
class GroupRegistrationService
{
    public const MAX_MEMBERS = 50;

    public function __construct(private RegistrationService $registrations) {}

    /**
     * @param  list<array{title?: ?string, first_name: string, last_name: string, email: string, phone: string, country: string, institution: string, profession: string, category: int, dietary_needs?: ?string, accessibility_needs?: ?string}>  $members
     * @param  ?int  $leaderCategory  the leader's own category, when they attend too
     */
    public function create(User $leader, Edition $edition, string $name, array $members, ?int $leaderCategory = null): GroupRegistration
    {
        if (! $edition->registration_open) {
            throw new InvalidArgumentException('Registration is not open.');
        }
        if (count($members) + ($leaderCategory ? 1 : 0) === 0) {
            throw new InvalidArgumentException('Add at least one person to the group.');
        }

        $categories = collect($members)->pluck('category')->push($leaderCategory)->filter()
            ->map(fn ($id) => $this->category($edition, (int) $id));
        $currency = $categories->first()->currency;
        if ($categories->contains(fn (RegistrationCategory $c) => $c->currency !== $currency)) {
            throw new InvalidArgumentException('Everyone in a group pays in the same currency. Choose categories that are all in '.$currency.', or make two groups.');
        }

        [$group, $added] = DB::transaction(function () use ($leader, $edition, $name, $members, $leaderCategory, $currency) {
            $group = GroupRegistration::create([
                'edition_id' => $edition->id,
                'leader_id' => $leader->id,
                'name' => trim($name),
                'reference' => 'pending-'.Str::random(12),
                'currency' => $currency,
                'status' => RegistrationStatus::PendingPayment,
            ]);
            // RH27-G0004: derived from the id, like individual references.
            $group->update(['reference' => sprintf('%s%s-G%04d', config('payments.reference_prefix'), $edition->shortYear(), $group->id)]);

            if ($leaderCategory) {
                if ($existing = $leader->registrationFor($edition)) {
                    throw new InvalidArgumentException('You are already registered, as '.$existing->reference.'.');
                }
                $this->join($group, $leader, $this->category($edition, $leaderCategory), []);
            }

            $added = collect($members)->map(fn (array $member) => $this->addLocked($group, $member));

            return [$group, $added];
        });

        $added->each(fn (array $added) => $this->welcome(...$added));

        return $group;
    }

    /** @param  array{title?: ?string, first_name: string, last_name: string, email: string, phone: string, country: string, institution: string, profession: string, category: int, dietary_needs?: ?string, accessibility_needs?: ?string}  $member */
    public function addMember(GroupRegistration $group, array $member): Registration
    {
        [$registration, $created] = DB::transaction(function () use ($group, $member) {
            $group = GroupRegistration::lockForUpdate()->findOrFail($group->id);

            if (! $group->canChangeMembers()) {
                throw new InvalidArgumentException('The group can no longer change: a payment has been sent.');
            }
            if ($group->members()->count() >= self::MAX_MEMBERS) {
                throw new InvalidArgumentException('A group can have up to '.self::MAX_MEMBERS.' people. Start another group for the rest.');
            }

            return $this->addLocked($group, $member);
        });

        $this->welcome($registration, $created);

        return $registration;
    }

    /** Take someone out of the group. Their registration is cancelled, so they can register again by themselves. */
    public function removeMember(GroupRegistration $group, Registration $member): void
    {
        DB::transaction(function () use ($group, $member) {
            $group = GroupRegistration::lockForUpdate()->findOrFail($group->id);

            if ($member->group_registration_id !== $group->id) {
                throw new InvalidArgumentException('This person is not in the group.');
            }
            if (! $group->canChangeMembers()) {
                throw new InvalidArgumentException('The group can no longer change: a payment has been sent.');
            }

            $member->delete();
        });
    }

    /** @return array{0: Registration, 1: bool} the member's registration, and whether their account is new */
    private function addLocked(GroupRegistration $group, array $member): array
    {
        $category = $this->category($group->edition, (int) $member['category']);

        if ($category->currency !== $group->currency) {
            throw new InvalidArgumentException('Everyone in this group pays in '.$group->currency.'. Choose a '.$group->currency.' category.');
        }

        [$user, $created] = $this->registrations->participantFor($member, $group->edition);

        return [$this->join($group, $user, $category, $member), $created];
    }

    private function join(GroupRegistration $group, User $user, RegistrationCategory $category, array $details): Registration
    {
        $registration = $this->registrations->register($user, $group->edition, $category, $details);
        $registration->update(['group_registration_id' => $group->id]);

        return $registration->setRelation('group', $group);
    }

    /** Group members cannot take a student rate: it needs each student's own proof. */
    private function category(Edition $edition, int $id): RegistrationCategory
    {
        $category = $edition->categories()->find($id);

        if (! $category || ! $category->hasFee()) {
            throw new InvalidArgumentException('That category is not available.');
        }
        if ($category->is_student) {
            throw new InvalidArgumentException('Students register by themselves, with their own proof of enrolment. Choose another category, or ask them to register individually.');
        }

        return $category;
    }

    private function welcome(Registration $registration, bool $created): void
    {
        if ($registration->user_id === $registration->group->leader_id) {
            return;
        }

        $registration->user->notify(new RegisteredByGroup($registration, $created ? Password::broker()->createToken($registration->user) : null));
    }
}
