<?php

namespace App\Support;

use App\Enums\AbstractStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Payment;
use App\Models\ReviewAssignment;
use App\Models\User;

/**
 * The portal sidebar: one section per role the person holds. Admins see every
 * staff section. Counts show work waiting for that person.
 */
class Navigation
{
    /**
     * @return list<array{label: ?string, items: list<array<string, mixed>>}>
     */
    public static function for(User $user): array
    {
        $is = fn (Role ...$roles) => $user->hasAnyRole(array_map(fn (Role $role) => $role->value, $roles));
        $item = fn (string $label, string $route, string $active, array $extra = []) => ['label' => $label, 'route' => $route, 'active' => $active] + $extra;
        $sections = [];

        // Admins and the registration desk land on the executive summary and the registry instead.
        $ownDashboard = ! $is(Role::Admin) && ! ($is(Role::RegistrationOfficer) && ! $is(Role::ScientificAdmin, Role::FinanceOfficer, Role::Reviewer, Role::Participant));
        $overview = $ownDashboard ? [$item('Overview', 'dashboard', 'dashboard')] : [];

        if ($is(Role::Participant)) {
            $sections[] = ['label' => null, 'items' => [
                ...$overview,
                $item('Registration & payment', 'registration.show', 'registration.show'),
                $item('Badge & check-in', 'registration.badge.show', 'registration.badge.show'),
                $item('My abstracts', 'abstracts.index', 'abstracts.*', ['count' => $user->abstracts()->whereIn('status', [AbstractStatus::Draft, AbstractStatus::UnderReview])->count()]),
                $item('Programme', 'programme', 'programme'),
            ]];
        } elseif ($overview) {
            $sections[] = ['label' => null, 'items' => $overview];
        }

        if ($is(Role::Reviewer)) {
            $sections[] = ['label' => 'Reviewing', 'items' => [
                $item('Assigned abstracts', 'reviews.index', 'reviews.*', ['count' => ReviewAssignment::where('reviewer_id', $user->id)->whereNull('completed_at')->count()]),
            ]];
        }

        if ($is(Role::ScientificAdmin, Role::Admin)) {
            $ready = AbstractSubmission::where('status', AbstractStatus::UnderReview)
                ->whereDoesntHave('reviews', fn ($q) => $q->whereNull('completed_at'))->count();
            $sections[] = ['label' => 'Scientific committee', 'items' => [
                $item('Abstracts', 'scientific.abstracts.index', 'scientific.abstracts.*', [
                    'not_query' => ['status', 'ready'],
                    'count' => AbstractSubmission::where('status', AbstractStatus::Submitted)->count(),
                ]),
                $item('Decision queue', 'scientific.abstracts.index', 'scientific.abstracts.index', ['params' => ['status' => 'ready'], 'query' => ['status', 'ready'], 'count' => $ready]),
                $item('Reviewers', 'scientific.reviewers', 'scientific.reviewers'),
            ]];
        }

        if ($is(Role::FinanceOfficer, Role::Admin)) {
            $sections[] = ['label' => 'Finance', 'items' => [
                $item('Verification queue', 'finance.payments.index', 'finance.*', ['count' => Payment::where('status', PaymentStatus::Submitted)->count()]),
            ]];
        }

        if ($is(Role::RegistrationOfficer, Role::Admin)) {
            $sections[] = ['label' => 'Registration desk', 'items' => [
                $item('Registry & check-in', 'desk.index', 'desk.index'),
                $item('Print queue', 'desk.queue', 'desk.queue'),
            ]];
        }

        if ($is(Role::Admin)) {
            $sections[] = ['label' => 'Administration', 'items' => [
                $item('Executive summary', 'admin.overview', 'admin.overview'),
                $item('Participants', 'admin.participants.index', 'admin.participants.*'),
                $item('Users & roles', 'admin.users.index', 'admin.users.*'),
                $item('Summit settings', 'admin.settings.edit', 'admin.settings.*'),
            ]];
        }

        $sections[] = ['label' => 'Account', 'items' => [
            $item('Profile', 'profile.edit', 'profile.*'),
        ]];

        return $sections;
    }

    /** Placeholder for the search box in the top bar. */
    public static function searchHint(User $user): string
    {
        return match (true) {
            $user->hasRole(Role::Admin->value) => 'Search people, abstracts, payments…',
            $user->hasRole(Role::ScientificAdmin->value) => 'Search abstracts, reviewers, codes…',
            $user->hasRole(Role::FinanceOfficer->value) => 'Search name, reference, transaction…',
            $user->hasRole(Role::RegistrationOfficer->value) => 'Search attendees…',
            $user->hasRole(Role::Reviewer->value) => 'Search assigned abstracts…',
            default => 'Search sessions and your abstracts…',
        };
    }
}
