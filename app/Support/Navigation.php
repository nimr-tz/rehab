<?php

namespace App\Support;

use App\Enums\AbstractStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\AwardEntry;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\PhotoRemovalRequest;
use App\Models\ReviewAssignment;
use App\Models\User;

/**
 * The portal sidebar for the workspace the person is in (see Workspace): one
 * role at a time. Counts show work waiting for that person.
 */
class Navigation
{
    /**
     * @return list<array{label: ?string, items: list<array<string, mixed>>}>
     */
    public static function for(User $user): array
    {
        $item = fn (string $label, string $route, string $active, array $extra = []) => ['label' => $label, 'route' => $route, 'active' => $active] + $extra;
        $workspace = Workspace::current($user);

        // Admins, the registration desk, photographers and judges land on the executive summary, the registry, their albums and their scoring instead.
        $overview = in_array($workspace, [Role::Participant, Role::Reviewer, Role::ScientificAdmin, Role::FinanceOfficer], true)
            ? [$item('Overview', 'dashboard', 'dashboard')] : [];

        $items = match ($workspace) {
            Role::Reviewer => [
                $item('Assigned abstracts', 'reviews.index', 'reviews.*', ['count' => ReviewAssignment::where('reviewer_id', $user->id)->whereNull('completed_at')->count()]),
            ],
            Role::ScientificAdmin => [
                $item('Abstracts', 'scientific.abstracts.index', 'scientific.abstracts.*', [
                    'not_query' => ['status', 'ready'],
                    'count' => AbstractSubmission::where('status', AbstractStatus::Submitted)->count(),
                ]),
                $item('Decision queue', 'scientific.abstracts.index', 'scientific.abstracts.index', [
                    'params' => ['status' => 'ready'], 'query' => ['status', 'ready'],
                    'count' => AbstractSubmission::where('status', AbstractStatus::UnderReview)
                        ->whereDoesntHave('reviews', fn ($q) => $q->whereNull('completed_at'))->count(),
                ]),
                $item('Reviewers', 'scientific.reviewers', 'scientific.reviewers'),
                $item('Awards', 'committee.awards.index', 'committee.awards.*'),
            ],
            Role::Judge => [
                $item('Finalists to score', 'judging.index', 'judging.*', ['count' => self::unscoredFinalists($user)]),
            ],
            Role::FinanceOfficer => [
                $item('Verification queue', 'finance.payments.index', 'finance.*', ['count' => Payment::where('status', PaymentStatus::Submitted)->count()]),
            ],
            Role::RegistrationOfficer => [
                $item('Registry & check-in', 'desk.index', 'desk.index'),
                $item('Print queue', 'desk.queue', 'desk.queue'),
            ],
            Role::Photographer => [
                $item('Albums & uploads', 'media.albums.index', 'media.albums.*'),
                $item('Public gallery', 'gallery.index', 'gallery.*'),
            ],
            Role::Executive => [
                $item('Executive summary', 'admin.overview', 'admin.overview'),
            ],
            Role::Admin => [
                $item('Executive summary', 'admin.overview', 'admin.overview'),
                $item('Participants', 'admin.participants.index', 'admin.participants.*'),
                $item('Users & roles', 'admin.users.index', 'admin.users.*'),
                $item('Photo removal requests', 'media.removal-requests.index', 'media.removal-requests.*', ['count' => PhotoRemovalRequest::pending()->count()]),
                $item('Summit settings', 'admin.settings.edit', 'admin.settings.*'),
            ],
            default => $user->hasRole(Role::Participant->value) ? [
                $item('Registration & payment', 'registration.show', 'registration.show'),
                $item('Badge & check-in', 'registration.badge.show', 'registration.badge.show'),
                $item('My abstracts', 'abstracts.index', 'abstracts.*', ['count' => $user->abstracts()->whereIn('status', [AbstractStatus::Draft, AbstractStatus::UnderReview])->count()]),
                $item('Programme', 'programme', 'programme'),
                $item('Awards', 'awards.mine', 'awards.mine'),
            ] : [
                $item('Programme', 'programme', 'programme'),
                $item('Awards', 'awards.mine', 'awards.mine'),
            ],
        };

        return [
            ['label' => Workspace::label($workspace), 'items' => [...$overview, ...$items]],
            ['label' => 'Account', 'items' => [$item('Profile', 'profile.edit', 'profile.*')]],
        ];
    }

    /** Finalists in this judge's open awards that they have not scored and did not write. */
    private static function unscoredFinalists(User $judge): int
    {
        return AwardEntry::query()
            ->whereHas('category', fn ($q) => $q->whereNull('announced_at')
                ->where('edition_id', Edition::current()?->id)
                ->whereHas('judges', fn ($q) => $q->whereKey($judge->id)))
            ->whereDoesntHave('scores', fn ($q) => $q->where('judge_id', $judge->id))
            ->with('abstract.authors')
            ->get()
            ->reject(fn (AwardEntry $entry) => $entry->conflictsWith($judge))
            ->count();
    }

    /** Placeholder for the search box in the top bar. */
    public static function searchHint(User $user): string
    {
        return match (Workspace::current($user)) {
            Role::Admin => 'Search people, abstracts, payments…',
            Role::Executive => 'Search sessions…',
            Role::ScientificAdmin => 'Search abstracts, reviewers, codes…',
            Role::FinanceOfficer => 'Search name, reference, transaction…',
            Role::RegistrationOfficer => 'Search attendees…',
            Role::Reviewer => 'Search assigned abstracts…',
            Role::Photographer, Role::Judge => 'Search sessions…',
            default => 'Search sessions and your abstracts…',
        };
    }
}
