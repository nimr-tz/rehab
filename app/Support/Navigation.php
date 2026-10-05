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
     * @return list<array{label: ?string, items: list<array{label: string, route: string, icon: string, active: string, count?: int}>}>
     */
    public static function for(User $user): array
    {
        $is = fn (Role ...$roles) => $user->hasAnyRole(array_map(fn (Role $role) => $role->value, $roles));
        $sections = [];

        if ($is(Role::Participant)) {
            $sections[] = ['label' => null, 'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'active' => 'dashboard'],
                ['label' => 'Registration & payment', 'route' => 'registration.show', 'icon' => 'ticket', 'active' => 'registration.*'],
                ['label' => 'My abstracts', 'route' => 'abstracts.index', 'icon' => 'document', 'active' => 'abstracts.*'],
                ['label' => 'Programme', 'route' => 'programme', 'icon' => 'calendar', 'active' => 'programme'],
                ['label' => 'Profile', 'route' => 'profile.edit', 'icon' => 'user', 'active' => 'profile.*'],
            ]];
        } else {
            $sections[] = ['label' => null, 'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'active' => 'dashboard'],
                ['label' => 'Programme', 'route' => 'programme', 'icon' => 'calendar', 'active' => 'programme'],
                ['label' => 'Profile', 'route' => 'profile.edit', 'icon' => 'user', 'active' => 'profile.*'],
            ]];
        }

        if ($is(Role::Reviewer)) {
            $sections[] = ['label' => 'Reviewing', 'items' => [
                ['label' => 'My reviews', 'route' => 'reviews.index', 'icon' => 'star', 'active' => 'reviews.*',
                    'count' => ReviewAssignment::where('reviewer_id', $user->id)->whereNull('completed_at')->count()],
            ]];
        }

        if ($is(Role::ScientificAdmin, Role::Admin)) {
            $sections[] = ['label' => 'Scientific committee', 'items' => [
                ['label' => 'Abstracts', 'route' => 'scientific.abstracts.index', 'icon' => 'clipboard', 'active' => 'scientific.abstracts.*',
                    'count' => AbstractSubmission::whereIn('status', [AbstractStatus::Submitted, AbstractStatus::UnderReview])->count()],
                ['label' => 'Reviewers', 'route' => 'scientific.reviewers', 'icon' => 'users', 'active' => 'scientific.reviewers'],
            ]];
        }

        if ($is(Role::FinanceOfficer, Role::Admin)) {
            $sections[] = ['label' => 'Finance', 'items' => [
                ['label' => 'Payments', 'route' => 'finance.payments.index', 'icon' => 'banknotes', 'active' => 'finance.*',
                    'count' => Payment::where('status', PaymentStatus::Submitted)->count()],
            ]];
        }

        if ($is(Role::RegistrationOfficer, Role::Admin)) {
            $sections[] = ['label' => 'Registration desk', 'items' => [
                ['label' => 'Check-in', 'route' => 'desk.index', 'icon' => 'qr', 'active' => 'desk.*'],
            ]];
        }

        if ($is(Role::Admin)) {
            $sections[] = ['label' => 'Administration', 'items' => [
                ['label' => 'Overview', 'route' => 'admin.overview', 'icon' => 'chart', 'active' => 'admin.overview'],
                ['label' => 'Participants', 'route' => 'admin.participants.index', 'icon' => 'users', 'active' => 'admin.participants.*'],
                ['label' => 'Users & roles', 'route' => 'admin.users.index', 'icon' => 'shield', 'active' => 'admin.users.*'],
                ['label' => 'Summit settings', 'route' => 'admin.settings.edit', 'icon' => 'cog', 'active' => 'admin.settings.*'],
            ]];
        }

        return $sections;
    }
}
